<?php
declare(strict_types=1);

use App\Core\Database;
use App\Core\Env;

require __DIR__ . '/../vendor/autoload.php';
Env::load(__DIR__ . '/..');

$pdo = Database::pdo();

$adminPass = password_hash('admin123', PASSWORD_BCRYPT);
$invPass = password_hash('investor123', PASSWORD_BCRYPT);

$pdo->prepare('INSERT IGNORE INTO users (id, name, email, password_hash, role, is_active) VALUES (1, "Admin", "admin@example.com", ?, "admin", 1)')->execute([$adminPass]);
$pdo->prepare('INSERT IGNORE INTO users (id, name, email, password_hash, role, is_active) VALUES (2, "Investor One", "investor1@example.com", ?, "investor", 1)')->execute([$invPass]);

echo "Seed complete. Admin id=1, Investor id=2.\n";