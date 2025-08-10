<?php
declare(strict_types=1);

namespace App\Core;

use App\Core\Database;
use PDO;

class Auth
{
    public static function requireAdmin(?string $providedKey): void
    {
        $expected = Env::get('ADMIN_API_KEY');
        if (!$expected || !$providedKey || !hash_equals($expected, $providedKey)) {
            http_response_code(401);
            echo json_encode(['error' => 'Unauthorized (admin)']);
            exit;
        }
    }

    public static function requireUser(?string $userIdHeader): int
    {
        if (!$userIdHeader) {
            http_response_code(401);
            echo json_encode(['error' => 'Unauthorized (user)']);
            exit;
        }
        $userId = (int)$userIdHeader;

        $pdo = Database::pdo();
        $stmt = $pdo->prepare('SELECT id FROM users WHERE id = ? AND is_active = 1');
        $stmt->execute([$userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            http_response_code(401);
            echo json_encode(['error' => 'User not found or inactive']);
            exit;
        }
        return $userId;
    }
}