<?php
declare(strict_types=1);

namespace App\Core;

use RuntimeException;

final class View
{
    public static function render(string $template, array $data = []): void
    {
        $viewFile = APP_PATH . DIRECTORY_SEPARATOR . 'Views' . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $template) . '.php';
        if (!is_file($viewFile)) {
            throw new RuntimeException('View nao encontrada: ' . $template);
        }

        extract($data, EXTR_SKIP);
        require $viewFile;
    }
}
