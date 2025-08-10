<?php
declare(strict_types=1);

namespace App\Core;

class Response
{
    public function json(array $data, int $status = 200): void
    {
        http_response_code($status);
        echo json_encode($data);
    }
}