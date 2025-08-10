<?php
declare(strict_types=1);

namespace App\Core;

class Request
{
    public array $params;

    public function __construct(array $params = [])
    {
        $this->params = $params;
    }

    public function input(): array
    {
        $raw = file_get_contents('php://input') ?: '';
        $data = json_decode($raw, true);
        return is_array($data) ? $data : [];
    }

    public function header(string $name): ?string
    {
        $key = 'HTTP_' . strtoupper(str_replace('-', '_', $name));
        return $_SERVER[$key] ?? null;
    }
}