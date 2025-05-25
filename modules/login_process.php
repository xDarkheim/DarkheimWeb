<?php

require_once dirname(__DIR__) . '/includes/bootstrap.php';

use App\Lib\Database;
use App\Lib\Auth;
use App\Lib\FlashMessageService;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $database_handler = new Database();
    $flashMessageService = new FlashMessageService();
    $auth = new Auth($database_handler, $flashMessageService);

    $identifier = $_POST['username_or_email'] ?? '';
    $password = $_POST['password'] ?? '';

    if (empty($identifier) || empty($password)) {
        $flashMessageService->addError('Username and password cannot be empty.');
        header("Location: /index.php?page=login");
        exit();
    }

    $loginResult = $auth->login($identifier, $password);

    if ($loginResult && isset($loginResult['success']) && $loginResult['success']) {
        session_regenerate_id(true);

        $_SESSION['user_id'] = $loginResult['user_id'];
        $_SESSION['username'] = $loginResult['username'];
        $_SESSION['user_role'] = $loginResult['role'];

        $flashMessageService->addSuccess('Login successful. Welcome back, ' . htmlspecialchars($loginResult['username']) . '!');

        header('Location: /index.php?page=account_dashboard');
        exit;
    } else {
        // Улучшенная обработка ошибок
        if (isset($loginResult['errors']) && !empty($loginResult['errors'])) {
            foreach ($loginResult['errors'] as $error) {
                if (strpos($error, '<a href=') !== false) {
                    $flashMessageService->addError($error, true); 
                } else {
                    $flashMessageService->addError($error); 
                }
            }
        } else {
            $flashMessageService->addError('Login failed. Please check your credentials.');
        }
        
        $_SESSION['form_data_login_username'] = $identifier;

        header('Location: /index.php?page=login');
        exit();
    }
} else {
    header("Location: /index.php?page=login");
    exit();
}

?>