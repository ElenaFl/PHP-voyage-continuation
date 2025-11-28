<?php
session_start();
function usersController(): string
{
    $page = $_GET['page'] ?? '';
    
    switch ($page) {
        case 'author':
            
            $data = [
            'name' => '',
            'password' => '',
            'role' => '',
            'error' => '',
            'error_type' => ''
            ];

            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                if(isset($_POST['name'])) {
                    $_SESSION['name'] = htmlspecialchars(trim($_POST['name']));
                }
                if(isset($_POST['password'])) {
                    $_SESSION['password'] = htmlspecialchars(trim($_POST['password']));
                }
                
                $name = $_SESSION['name'];
                $password = $_SESSION['password'];

                $role = checkUsers($name, $password);

                $_SESSION['role'] = $role;


                if ($role === 'Администратор') {
                    $data['error'] = 'Авторизация прошла успешно';
                    $data['error_type'] = 'success';
                    $data['role'] = $role;
                    header('Location: ?page=admin');
                    exit;
                } 
                    elseif ($role === 'Гость' || $role === 'Такой пользователь не зарегистрирован') {
                    header('Location: ?page=posts');
                    exit;
                }
            }

            $title = getStr('authorTitle', 'authorTitle', 'Авторизация');
            
            $authorTitle = renderTemplate('authorTitle', [
                'authorTitle' => $title
            ]);

            $authForm = renderTemplate('author', $data);

            $content = $authorTitle . $authForm;

            $menu = menuController();
            //  var_dump($data);

            return renderTemplate('main', [
                'menu' => $menu,
                'content' => $content
            ]);

        case 'theme':

            $page = $_GET['page'] ?? '';

            if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['radio'])) {

                $theme = $_POST['radio'];

                if ($theme === 'backgroundColorLigth' || $theme === 'backgroundColorDark') {
                    $_SESSION['theme'] = $theme;
                }
            }

            $menu = menuController();

            $dataTitle = getData('themeTitle');

            $themeTitle = renderTemplate('themeTitle', $dataTitle);

            $themeForm = renderPartial('theme');

            $content = $themeTitle . $themeForm;

            return renderTemplate('main', [
                'menu' => $menu,
                'content' => $content
            ]);

        default:

            http_response_code(400);

            return "Нет такой страницы";

    }
}
