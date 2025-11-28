<?php
session_start();
function adminController(): string
{
    $page = $_GET['page']; // Получаем параметр page из URL (например, ?page=posts)

    switch($page) {
        case 'admin':
            // Список постов
            $data = getData('posts') ?? [];

            $categories = getArray('categories', 'categories');

            $contentPosts = renderTemplate('postsAdmin', [
                'posts' => $data,
                'categories' => $categories
            ]);

            $menu = menuAdminController();

            return renderTemplate('main', [
                'menu' => $menu,
                'content' => $contentPosts
            ]);

        case 'editAdmin':
            
            $id = $_GET['id'] ?? null; // Получаем id поста из URL

            $data = getData('posts')[$id] ?? null; // Загружаем данные поста

            $validationErrors = [];
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {

                $inputData = [
                    'id' => $id,
                    'title' => trim($_POST['title'] ?? ''),
                    'category_id' => (int)($_POST['category_id'] ?? 0),
                    'text' => trim($_POST['text'] ?? ''),
                ];

            $inputData = uploadPostImage($inputData, $data);

            $validationErrors = validatePostData($inputData);
                
            if (empty($validationErrors)) {
                if (editPost('posts', $inputData, $id)) {
                    $_SESSION['flash_message'] = "Пост отредактирован успешно";
                    $_SESSION['flash_message_type'] = "success";
                    header('Location: ?page=admin');
                    exit;
                    } else {
                        $data['message'] = 'Ошибка при сохранении';
                        $data['message_type'] = 'error';
                    }
                } else {
                    $data['errors'] = array_merge($data['errors'] ?? [], $validationErrors);
                    $data['message'] = 'Исправьте ошибки';
                    $data['message_type'] = 'error';
                }
            }
            $categories = getArray('categories', 'categories');

            $content = renderTemplate('editAdmin', [
                'data' => $data,
                'categories' => $categories,
                'validationErrors' => $validationErrors ?? []
            ]);

            $menu = menuAdminController();

            return renderTemplate('main', [
                'menu' => $menu,
                'content' => $content
            ]);

        case 'deleteAdmin' :
            
            $id = $_GET['id'] ?? null;

            if (deletePost('posts', $id)) {
                $_SESSION['flash_message'] = "Пост удален успешно";
                $_SESSION['flash_message_type'] = "success";
                header('Location: ?page=admin');
                exit;
            } else {
                http_response_code(500);
                return 'Ошибка при удалении поста';
            }

            default:

            http_response_code(400);

            return "Нет такой страницы";
    }

}