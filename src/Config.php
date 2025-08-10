<?php

declare(strict_types=1);

class Config
{
    private const FILE = __DIR__ . '/../storage/settings.json';

    public static function getAll(): array
    {
        $dir = dirname(self::FILE);
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
        if (!file_exists(self::FILE)) {
            file_put_contents(self::FILE, json_encode(new stdClass()));
        }
        $raw = file_get_contents(self::FILE) ?: '{}';
        $data = json_decode($raw, true);
        return is_array($data) ? $data : [];
    }

    public static function get(string $key, $default = null)
    {
        $all = self::getAll();
        return $all[$key] ?? $default;
    }

    public static function setMany(array $kv): array
    {
        $all = self::getAll();
        foreach ($kv as $k => $v) {
            $all[$k] = $v;
        }
        file_put_contents(self::FILE, json_encode($all, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        return $all;
    }
}