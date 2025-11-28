<?php

//Получение данных в виде массива из json-файла
function getData(string $page): array
{
    $filePath = __DIR__ . "/../json/$page.json";

    // Если файла нет — возвращаем пустой массив
    if (!file_exists($filePath)) {
        return [];
    }

    // Считываем данные как json-строку
    $json = file_get_contents($filePath);

    // Если файл пуст или содержит только пробелы
    if (trim($json) === '') {
        return [];
    }

    // Декодирование данных в ассоциативный массив
    $data = json_decode($json, true);

    // Если декодирование не удалось или данные не массив — возвращаем пустой массив
    if (!is_array($data)) {
        return [];
    }

    return $data;
}


//Получение данных в виде строки из json-файла
function getStr(string $page, string $field, string $default): string
{
    // Получаем данные из json-файла в виде ассоциативного массива
    $data = getData( $page);

    // Если поле существует и является строкой — возвращаем его значение
    if (isset($data[$field]) && is_string($data[$field])) {
        return $data[$field];
    }

    // В остальных случаях (нет поля, null, не строка) — возвращаем значение по умолчанию
    return $default;
}

// Получение поста по id
function getPost(string $page, string $id): ?array
{
    $data = getData($page);

    return $data[$id] ?? null;
}

// Получение данных в виде строки из поля поста по id поста и наименованию поля
function getField(string $page, string $id, string $field, string $default): string
{
    $post = getPost($page, $id);

    // Если пост не найден — сразу возвращаем дефолтное значение
    if (!$post) {
        return $default;
    }
    
    // Проверяем наличие поля и его тип
    if ($post && isset($post[$field]) && is_string($post[$field])) {
        return $post[$field];
    }
    
    return $default;
}

// Получение данных в виде строки из поля объекта из json-файла, которое само является объектом
function getArray(string $page, string $field): array
{
    // Получаем данные из json-файла в виде ассоциативного массива
    $data = getData($page);

    // Проверяем, существует ли поле и является ли оно массивом
    if (isset($data[$field]) && is_array($data[$field])) {
        return $data[$field];
    }

    // Во всех остальных случаях (поле отсутствует, не массив, null, ...) — возвращаем пустой массив
    return [];
}

// Проверка данных
function validatePostData(array $data): array
{
    $errors = [];

    // Валидация заголовка
    $title = trim($data['title'] ?? '');
    if ($title === '') {
        $errors['title'] = 'Заголовок обязателен';
    } else {
        $len = mb_strlen($title, 'UTF-8');
        if ($len < 3) {
            $errors['title'] = 'Заголовок слишком короткий (минимум 3 символа)';
        } elseif ($len > 100) {
            $errors['title'] = 'Заголовок слишком длинный (максимум 100 символов)';
        }

        // Проверка повтора символов
        if (preg_match('/(.)\\1{5,}/u', $title)) {
            $errors['title'] = 'Избегайте повтора символов в заголовке';
        }
    }

    // Валидация категории
    if (empty($data['category_id']) || $data['category_id'] <= 0) {
        $errors['category_id'] = 'Выберите маршрут';
    }

    // Валидация текста
    $text = trim($data['text'] ?? '');
    if ($text === '') {
        $errors['text'] = 'Содержание обязательно';
    } else {
        $len = mb_strlen($text, 'UTF-8');
        if ($len < 10) {
            $errors['text'] = 'Текст слишком короткий (минимум 10 символов)';
        } elseif ($len > 5000) {
            $errors['text'] = 'Текст слишком длинный (максимум 5000 символов)';
        }

        // Проверка повтора символов
        if (preg_match('/(.)\\1{10,}/u', $text)) {
            $errors['text'] = 'Избегайте повтора символов в тексте';
        }
    }

    // Проверка запрещённых слов (только если нет критических ошибок)
    if (empty($errors['title']) && empty($errors['text'])) {
        $bannedWords = [
            'мат', 'оскорбление', 'спам', 'viagra', 'casino', 'loan',
            'free money', 'click here', 'xxx', 'porn', 'грабь', 'убивай',
            'взлом', 'ключ', 'аккаунт', 'бесплатно', 'выигрыш', 'приз'
        ];

        $combinedText = strtolower(
            ($title !== '' ? $title . ' ' : '') .
            ($text !== '' ? $text : '')
        );

        foreach ($bannedWords as $word) {
            if (stripos($combinedText, $word) !== false) {
                if (stripos(strtolower($title), $word) !== false) {
                    $errors['title'] = 'В заголовке обнаружено запрещённое слово: «' . htmlspecialchars($word) . '»';
                }
                if (stripos(strtolower($text), $word) !== false) {
                    $errors['text'] = 'В тексте обнаружено запрещённое слово: «' . htmlspecialchars($word) . '»';
                }
                break;
            }
        }
    }

    return $errors;
}

//Добавление нового поста в массив $posts и перезапись содерержимого posts.json
function savePost(string $page, array $post): bool
{
    try {
        /* Получаем текущие данные из файла posts.json
         Если файла нет или он пуст, getData() вернёт пустой массив*/
        $posts = getData($page);

        /* Гарантируем, что $posts — ассоциативный массив
        Это необходимо, даже если getData() вернул не-массив (например, null)*/

        if (!is_array($posts)) {
            $posts = [];
        }

        // Добавляем метку времени создания поста
        // $post['created_at'] = date('Y-m-d H:i:s');

         $post = [
        'title' => $post['title'],
        'category_id' => $post['category_id'],
        'text' => $post['text'],
        'image' => $post['image'] ?? null, // Сохраняем имя файла
        'created_at' => date('Y-m-d H:i:s')
    ];

        // Генерируем уникальный ID для нового поста
        if (empty($posts)) {
            // Если постов ещё нет, начинаем с id = '1'
            $post['id'] = '1';
        } else {
            // Находим максимальный существующий id среди ключей массива
            $ids = array_keys($posts);
            $maxId = max(array_map('intval', $ids));
            // Новый id= maxId + 1 (преобразуем в строку для согласованности)
            $post['id'] = (string)($maxId + 1);
        }

        /* Добавляем новый пост в массив с ключом, равным его id
        Это обеспечивает уникальность и быстрый доступ по id*/
        $posts[$post['id']] = $post;

        /* Преобразуем массив в json-строку
        Флаги:
        - JSON_UNESCAPED_UNICODE: сохраняем кириллицу и другие символы без экранирования
        - JSON_PRETTY_PRINT: форматируем JSON с отступами для читаемости*/

        $json = json_encode($posts, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

        // Проверяем, удалось ли кодирование
        if ($json === false) {
            error_log('Ошибка JSON: ' . json_last_error_msg());
            return false;
        }

        /* Сохраняем JSON-строку в файл с эксклюзивной блокировкой
        LOCK_EX предотвращает конфликты при параллельных записях*/

        if (file_put_contents(__DIR__ . "/../json/posts.json", $json, LOCK_EX) === false) {
            error_log('Не удалось записать файл: ' . __DIR__ . "/../json/posts.json",);
            return false;
        }

        // Если все шаги выполнены успешно — возвращаем true
        return true;

    } catch (Exception $e) {
        // Логируем любую неожиданную ошибку
        error_log('Ошибка сохранения поста: ' . $e->getMessage());
        return false;
    }
}

// Сохранение данных из массива в json-файл
function save(array $array): bool
{
     // Преобразуем массив в форматированный JSON
        $json = json_encode($array, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        if ($json === false) {
            error_log('Ошибка JSON: ' . json_last_error_msg());
            return false;
        }

        /* Сохраняем данные json-формате в файл с эксклюзивной блокировкой
        LOCK_EX предотвращает ситуацию, когда два PHP‑скрипта одновременно пытаются записать данные в один и тот же файл.*/
        if (file_put_contents(__DIR__ . "/../json/posts.json", $json, LOCK_EX) === false) {
            error_log('Не удалось записать в файл: ' . __DIR__ . "/../json/posts.json",);
            return false;
        }
    return true;
}

function handleImageUpload(array $inputData, array $formData, ?array $existingData = null): array {
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = 'uploads/posts/';
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
        $maxSize = 2 * 1024 * 1024; // 2 МБ

        $file = $_FILES['image'];
        $fileName = basename($file['name']);
        $fileType = $file['type'];
        $fileSize = $file['size'];
        $fileTmpPath = $file['tmp_name'];

        if (!in_array($fileType, $allowedTypes)) {
            $formData['errors']['image'] = 'Допустимы только JPG, PNG или GIF';
        } elseif ($fileSize > $maxSize) {
            $formData['errors']['image'] = 'Файл слишком большой (макс. 2 МБ)';
        } else {
            $newFileName = uniqid() . '_' . $fileName;
            $destinationPath = $uploadDir . $newFileName;

            if (move_uploaded_file($fileTmpPath, $destinationPath)) {
                $inputData['image'] = $newFileName;
            } else {
                $formData['errors']['image'] = 'Ошибка при загрузке файла';
            }
        }
    } 
    // Если файл не загружен, но в посте уже было изображение — оставляем старое
    elseif ($existingData && isset($existingData['image'])) {
        $inputData['image'] = $existingData['image'];
    }

    return [
        'inputData' => $inputData,
        'formData'  => $formData
    ];
}

function uploadPostImage(array $inputData, array $postData): array {
    // Если файла нет или была ошибка загрузки — оставляем старое изображение (если есть)
    if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
        if (isset($postData['image'])) {
            $inputData['image'] = $postData['image'];
        }
        return $inputData;
    }

    $uploadDir = 'uploads/posts/';
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
    $maxSize = 2 * 1024 * 1024; // 2 МБ

    $file = $_FILES['image'];
    $fileName = basename($file['name']);
    $fileType = $file['type'];
    $fileSize = $file['size'];
    $fileTmpPath = $file['tmp_name'];

    // Проверка типа файла
    if (!in_array($fileType, $allowedTypes)) {
        $postData['errors']['image'] = 'Допустимы только JPG, PNG или GIF';
        return $inputData;
    }

    // Проверка размера
    if ($fileSize > $maxSize) {
        $postData['errors']['image'] = 'Файл слишком большой (макс. 2 МБ)';
        return $inputData;
    }

    // Генерируем уникальное имя
    $newFileName = uniqid() . '_' . $fileName;
    $destinationPath = $uploadDir . $newFileName;

    // Сохраняем файл
    if (move_uploaded_file($fileTmpPath, $destinationPath)) {
        $inputData['image'] = $newFileName;
    } else {
        $postData['errors']['image'] = 'Ошибка при загрузке файла';
    }

    return $inputData;
}


function editPost(string $page, array $post, string $id): bool
{
    try {
        // Получаем текущие данные из json-файла
        $posts = getData($page);

        if (!is_array($posts)) {
            error_log("Данные не являются массивом");
            return false;
        }

        // Проверяем наличие поста с указанным id
        if (!isset($posts[$id])) {
            error_log("Пост с ID {$id} не найден");
            return false;
        }

        /* Сохраняем исходную дату создания поста, если она есть
        В противном случае устанавливаем текущую дату*/

        if (isset($posts[$id]['created_at'])) {
            $post['created_at'] = $posts[$id]['created_at'];
        } else {
            $post['created_at'] = date('Y-m-d H:i:s');
        }

        // Добавляем метку времени последнего обновления
        $post['update_at'] = date('Y-m-d H:i:s');

        // Обновляем данные поста в массиве
        $posts[$id] = $post;

        save($posts);
        return true;

    } catch (Exception $e) {
        error_log('Ошибка редактирования поста: ' . $e->getMessage());
        return false;
    }
}


function deletePost(string $page, string $id): bool
{
    // Получаем массив постов
    $posts = getData($page);

    //Из массива удаляем пост по id
    unset($posts[$id]);

    // Возвращаем результат выполнения вункции сохранения массива в файл-json
    return save($posts);
}

function addLike(string $id): void
{
    // Инициализируем массив лайков, если его нет
    if (!isset($_SESSION['post_likes'])) {
        $_SESSION['post_likes'] = [];
    }

    // Увеличиваем лайки для конкретного поста
    $_SESSION['post_likes'][$id] = ($_SESSION['post_likes'][$id] ?? 0) + 1;

    // Возвращаем только лайки текущего поста
    header('Content-Type: application/json');
    echo json_encode(['likes' => $_SESSION['post_likes'][$id]]);
    exit;
}

function checkUsers(string $n, string $p): string
{
    $users = getData('users');

    foreach ($users as $user) {
        if (isset($user['name'], $user['password']) && $user['name'] === $n && $user['password'] === $p) {
            return $user['role'];
        }
    }

    return 'Такой пользователь не зарегистрирован';
}

//Калькулятор
function addition(float $arg1, float $arg2): float
{
    return $arg1 + $arg2;
}

function subtraction (float $arg1, float $arg2): float
{
    return $arg1 - $arg2;
}

function multiply(float $arg1, float $arg2): float
{
    return $arg1 * $arg2;
}

function division(float $arg1, float $arg2): float|string
{
    return $arg2 === 0.0 ? 'На ноль делить нельзя!' : $arg1 / $arg2;
}


function calculate(float $arg1, float $arg2, string $operator): float|string
{
    return match ($operator) {
        '+' => addition($arg1, $arg2),
        '-' => subtraction($arg1, $arg2),
        '*' => multiply($arg1, $arg2),
        '/' => division($arg1, $arg2),
        default => 'Ошибка!'
    };
}




