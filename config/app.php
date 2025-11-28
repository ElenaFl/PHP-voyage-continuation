<?php
define('ROOT_PATH', dirname(__DIR__));
define('VIEWS_PATH', dirname(__DIR__) . '/views');

$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'];
$script_path = dirname($_SERVER['SCRIPT_NAME']);
define('BASE_URL', $protocol . '://' . $host . rtrim($script_path));



