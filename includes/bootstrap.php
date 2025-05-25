<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}


if (!defined('DS')) {
    define('DS', DIRECTORY_SEPARATOR);
}
if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', dirname(__DIR__));
}

if (file_exists(ROOT_PATH . DS . 'vendor' . DS . 'autoload.php')) {
    require_once ROOT_PATH . DS . 'vendor' . DS . 'autoload.php';
} else {
    error_log("Composer autoload.php not found. Please run 'composer install'.");
}

if (file_exists(ROOT_PATH . DS . 'includes' . DS . 'config' . DS . 'app_config.php')) {
    require_once ROOT_PATH . DS . 'includes' . DS . 'config' . DS . 'app_config.php';
} else {
    error_log("Application configuration (app_config.php) not found.");
}

spl_autoload_register(function ($className) {
    $baseNamespace = 'App\\';
    $baseDir = ROOT_PATH . DS . 'includes' . DS;

    if (strpos($className, $baseNamespace) === 0) {
        $relativeClassName = substr($className, strlen($baseNamespace));
        $classPath = str_replace('\\', DS, $relativeClassName);
        $pathParts = explode(DS, $classPath);
        $fileName = array_pop($pathParts);
        $lowercaseDirectoryPath = implode(DS, array_map('strtolower', $pathParts));

        $filePath = $baseDir
                    . ($lowercaseDirectoryPath ? $lowercaseDirectoryPath . DS : '')
                    . $fileName . '.php';

        if (file_exists($filePath)) {
            require_once $filePath;
            return;
        }
    }
});
?>