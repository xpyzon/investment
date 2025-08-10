<?php
declare(strict_types=1);

namespace App\Core;

use Dotenv\Dotenv;

class Env
{
    public static function load(string $basePath): void
    {
        if (file_exists($basePath . '/.env')) {
            $dotenv = Dotenv::createImmutable($basePath);
            $dotenv->safeLoad();
        } else {
            // .env missing is acceptable for some environments
        }
    }

    public static function get(string $key, ?string $default = null): ?string
    {
        $value = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key) ?: null;
        return $value !== null ? (string)$value : $default;
    }
}