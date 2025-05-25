<?php

require_once dirname(__DIR__) . '/includes/bootstrap.php';

// Используем пространства имен для доступа к классам
use App\Lib\Database;
use App\Lib\FlashMessageService;
use App\Lib\Auth;

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$database_handler = new Database();
$flashMessageService = new FlashMessageService();
$auth = new Auth($database_handler, $flashMessageService);


$auth->logout();

$redirect_url = '/index.php?page=home';
header("Location: " . $redirect_url);
exit();
?>