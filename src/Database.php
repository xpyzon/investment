<?php

declare(strict_types=1);

class Database
{
    private static ?PDO $pdo = null;

    public static function getConnection(): PDO
    {
        if (self::$pdo instanceof PDO) {
            return self::$pdo;
        }

        $basePath = __DIR__ . '/..';
        $storageDir = realpath($basePath) . '/storage';
        if (!is_dir($storageDir)) {
            mkdir($storageDir, 0775, true);
        }
        $dbPath = $storageDir . '/app.sqlite';

        $dsn = 'sqlite:' . $dbPath;
        $pdo = new PDO($dsn);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        // Enable foreign keys
        $pdo->exec('PRAGMA foreign_keys = ON');

        self::$pdo = $pdo;
        return self::$pdo;
    }
}