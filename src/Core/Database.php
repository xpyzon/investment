<?php
declare(strict_types=1);

namespace App\Core;

use PDO;
use PDOException;

class Database
{
    private static ?PDO $pdo = null;

    public static function pdo(): PDO
    {
        if (self::$pdo) {
            return self::$pdo;
        }

        $driver = Env::get('DB_DRIVER', 'mysql');

        try {
            if ($driver === 'sqlite') {
                $dsn = 'sqlite:' . Env::get('DB_DATABASE', __DIR__ . '/../../database.sqlite');
                self::$pdo = new PDO($dsn);
            } else {
                $host = Env::get('DB_HOST', '127.0.0.1');
                $port = Env::get('DB_PORT', '3306');
                $db = Env::get('DB_DATABASE', 'investment');
                $user = Env::get('DB_USERNAME', 'root');
                $pass = Env::get('DB_PASSWORD', '');
                $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $host, $port, $db);
                self::$pdo = new PDO($dsn, $user, $pass, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                ]);
            }
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['error' => 'DB connection failed', 'message' => $e->getMessage()]);
            exit;
        }

        return self::$pdo;
    }
}