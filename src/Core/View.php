<?php
declare(strict_types=1);

namespace App\Core;

class View
{
    public static function render(string $template, array $data = []): string
    {
        $base = __DIR__ . '/../../views/';
        $file = $base . $template . '.php';
        if (!is_file($file)) {
            return '<h1>View not found</h1>';
        }
        extract($data, EXTR_OVERWRITE);
        ob_start();
        include $file;
        return (string)ob_get_clean();
    }
}