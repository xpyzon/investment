<?php
declare(strict_types=1);

use App\Core\Database;
use App\Core\Env;

require __DIR__ . '/../vendor/autoload.php';
Env::load(__DIR__ . '/..');

$pdo = Database::pdo();
$pdo->exec('CREATE TABLE IF NOT EXISTS migrations (id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(191) NOT NULL, ran_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP)');

$ran = [];
foreach ($pdo->query('SELECT name FROM migrations') as $row) {
    $ran[$row['name']] = true;
}

$dir = __DIR__ . '/../migrations';
$files = glob($dir . '/*.sql');
sort($files);

foreach ($files as $file) {
    $name = basename($file);
    if (isset($ran[$name])) {
        echo "Skip $name\n";
        continue;
    }
    $sql = file_get_contents($file);
    echo "Running $name...\n";
    $pdo->beginTransaction();
    try {
        $pdo->exec($sql);
        $stmt = $pdo->prepare('INSERT INTO migrations (name) VALUES (?)');
        $stmt->execute([$name]);
        $pdo->commit();
        echo "OK $name\n";
    } catch (Throwable $e) {
        $pdo->rollBack();
        fwrite(STDERR, "Failed $name: " . $e->getMessage() . "\n");
        exit(1);
    }
}

echo "Migrations complete.\n";