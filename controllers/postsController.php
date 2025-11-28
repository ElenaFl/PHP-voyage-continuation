<?php

session_start(); // Запуск сессии — необходимо для flash‑сообщений
function postsController(): string
{
    $page = $_GET['page']; // Получаем параметр page из URL (например, ?page=posts)

    switch($page) {
        case 'posts':
            // Отображение списка всех постов

            $postsTitle = getStr(
                page:'postsTitle',
                field:'postsTitle',
                default: 'Посты'
            ); // Получаем заголовок страницы из настроек (с fallback на «Посты»)

            $data = getData('posts') ?? []; // Загружаем все посты из хранилища (пустой массив, если нет данных)

            $categories = getArray('categories', 'categories'); // Загружаем список категорий

            // Формируем HTML-контент через шаблон posts.phtml
            $contentPosts = renderTemplate('posts', [
                'postsTitle' => $postsTitle,
                'posts' => $data,
                'categories' => $categories
            ]);

            $menu = menuController(); // Получаем HTML-код меню навигации

            // Собираем итоговую страницу (шаблон main.phtml с меню и контентом)
            return renderTemplate('main', [
                'menu' => $menu,
                'content' => $contentPosts
            ]);

        case 'post':
            // Отображение конкретного поста по ID

            $id = $_GET['id'] ?? null; // Получаем ID поста из URL

            if ($id === null) {
                http_response_code(400); // 400 Bad Request — ID не указан
                die('id не указан');
            }

            $content = getPost('posts', $id); // Загружаем пост по ID

            $categories = getArray('categories', 'categories'); // Загружаем категории

            // Формируем страницу поста через шаблон post.phtml
            $content = renderTemplate('post', [
                'data' => $content,
                'categories' => $categories
            ]);

            $menu = menuController();

            return renderTemplate('main', [
                'menu' => $menu,
                'content' => $content
            ]);

        case 'create':
            
            $formData = [ // Инициализируем данные формы
                'title'      => '',
                'text'       => '',
                'image'      => '',
                'errors'     => [],
                'message'    => '',
                'message_type' => ''
            ];
            
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $inputData = [
                    'title'       => trim(htmlspecialchars($_POST['title'])) ?? '',
                    'category_id' => (int)($_POST['category_id'] ?? 0),
                    'text'        => trim(htmlspecialchars($_POST['text'])) ?? ''
                ];
                // Загрузка изображения
                $result = handleImageUpload($inputData, $formData);
                $inputData = $result['inputData'];
                $formData = $result['formData'];

                $validationErrors = validatePostData($inputData);

                if (empty($validationErrors)) { // Если ошибок нет
                    if (savePost('posts', $inputData)) { // Сохраняем пост
                        $formData['message']      = 'Пост успешно создан';
                        $formData['message_type'] = 'success';
                    } else {
                        $formData['message']      = 'Ошибка при сохранении';
                        $formData['message_type'] = 'error';
                    }
                } else { // Если есть ошибки валидации
                    $formData['errors']     = $validationErrors;
                    $formData = array_merge($formData, $inputData); // Сохраняем введённые данные
                    $formData['message']    = 'Исправьте ошибки';
                    $formData['message_type'] = 'error';
                }
            }

            $postCreateTitle = getStr( // Получаем заголовок страницы
                page: 'postCreateTitle',
                field: 'postCreateTitle',
                default: 'Создание поста'
            );

            $categories = getArray('categories', 'categories'); // Загружаем категории

            // Формируем форму через шаблон postCreate.phtml
            $content = renderTemplate('postCreate', [
                'postCreateTitle' => $postCreateTitle,
                ...$formData,
                'categories' => $categories
            ]);

            $menu = menuController();

            return renderTemplate('main', [
                'menu' => $menu,
                'content' => $content
            ]);
        
        case 'edit':
            // Форма редактирования поста
            $id = $_GET['id'] ?? null; // Получаем id поста из URL
            $data = getData('posts')[$id] ?? null; // Загружаем данные поста

            if ($_SERVER['REQUEST_METHOD'] === 'POST') { // Если форма отправлена
                $inputData = [ // Собираем данные из POST
                    'id' => $id,
                    'title' => trim($_POST['title'] ?? ''),
                    'category_id' => (int)($_POST['category_id'] ?? 0),
                    'text' => trim($_POST['text'] ?? ''),
                ];

                $result = handleImageUpload($inputData, $data, $data);
                $inputData = $result['inputData'];
                $data = $result['formData'];

                $validationErrors = validatePostData($inputData);

                if (empty($validationErrors)) {
                    if (editPost('posts', $inputData, $id)) {
                        $_SESSION['flash_message'] = "Пост отредактирован успешно";
                        $_SESSION['flash_message_type'] = "success";
                        header('Location: ?page=post&id=' . $id);
                        exit;
                    } else {
                        $data['message'] = 'Ошибка при редактировании';
                        $data['message_type'] = 'error';
                    }
                } else { // Если есть ошибки валидации
                    $data['errors'] = array_merge($data['errors'] ?? [], $validationErrors);
                    $data['message'] = 'Исправьте ошибки';
                    $data['message_type'] = 'error';
                    $data['title'] = $inputData['title'];
                    $data['category_id'] = $inputData['category_id'];
                    $data['text'] = $inputData['text'];
                }
            }

            $categories = getArray('categories', 'categories'); // Загружаем категории

            // Формируем форму редактирования через шаблон edit.phtml
            $content = renderTemplate('edit', [
                ...$data,
                'categories' => $categories
            ]);

            $menu = menuController();

            return renderTemplate('main', [
                'menu' => $menu,
                'content' => $content
            ]);


        case 'delete':

            $id = $_GET['id'] ?? null;


            if (deletePost('posts', $id)) {
                $_SESSION['flash_message'] = "Пост удален успешно";
                $_SESSION['flash_message_type'] = "success";
                header('Location: ?page=posts');
                exit;
            } else {
                http_response_code(500);
                return 'Ошибка при удалении поста';
            }

        case 'likes':
            $id = $_GET['id'] ?? null;
    
            if ($id === null) {
                http_response_code(400);
                echo json_encode(['error' => 'ID поста не указан']);
                exit;
            }
    
            header('Content-Type: application/json');
            addLike($id);
            break;

        default:
            // Обработка несуществующих страниц
            http_response_code(400); // 400 "Bad Request"
            return "Нет такой страницы";
    }
}






