<?php
declare(strict_types=1);

namespace App\Core;

class Response
{
    public function json(array $data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode($data);
    }

    public function html(string $html, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: text/html; charset=utf-8');
        echo $html;
    }

    public function redirect(string $location, int $status = 302): void
    {
        http_response_code($status);
        header('Location: ' . $location);
    }
}