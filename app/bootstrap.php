<?php
declare(strict_types=1);

if (!defined('APP_PATH')) {
    define('APP_PATH', __DIR__);
}

if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__));
}

spl_autoload_register(static function (string $class): void {
    $prefix = 'App\\';
    if (strpos($class, $prefix) !== 0) {
        return;
    }

    $relative = substr($class, strlen($prefix));
    if ($relative === false) {
        return;
    }

    $relativePath = str_replace('\\', DIRECTORY_SEPARATOR, $relative) . '.php';
    $file = APP_PATH . DIRECTORY_SEPARATOR . $relativePath;

    if (is_file($file)) {
        require_once $file;
    }
});
