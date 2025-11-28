<?php
include_once __DIR__ .  '/../vendor/autoload.php';

$page = $_GET['page'] ?? 'index';

switch ($page) {
    case 'index':
        echo mainController();
        break;
    case 'posts':
        echo postsController();
        break;
    case 'post':
        echo  postsController();
        break;
    case 'create':
        echo postsController();
        break;
    case 'calc':
        echo calcController();
        break;
    case 'edit':
        echo postsController();
        break;
    case 'delete':
        echo postsController();
        break;
    case 'likes':
        echo postsController();
        break;
    case 'author':
        echo usersController();
        break;
    case 'admin':
        echo adminController();
        break;
    case 'editAdmin':
        echo adminController();
        break;
    case 'deleteAdmin':
        echo adminController();
        break;
    case 'theme':
        echo usersController();
        break;
    default:
        echo mainController();
}








