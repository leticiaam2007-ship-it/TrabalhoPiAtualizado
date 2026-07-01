<?php
declare(strict_types=1);

namespace App\Core;

abstract class Controller
{
    protected function render(string $template, array $data = []): void
    {
        View::render($template, $data);
    }

    protected function redirect(string $url): void
    {
        header('Location: ' . $url);
        exit;
    }
}
