<?php

declare(strict_types=1);

require_once __DIR__ . '/../bootstrap/app.php';

use App\Core\Database;

echo "====================================================\n";
echo " Enterprise HR & Payroll System - Database Migration \n";
echo "====================================================\n\n";

$pdo = Database::getConnection();

// Ensure migrations tracking table exists
$pdo->exec("
    CREATE TABLE IF NOT EXISTS `migrations` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `migration` VARCHAR(255) NOT NULL UNIQUE,
        `batch` INT NOT NULL,
        `executed_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");

// Determine current batch
$currentBatch = (int)Database::fetchColumn("SELECT COALESCE(MAX(`batch`), 0) FROM `migrations`") + 1;

$executed = Database::fetchAll("SELECT `migration` FROM `migrations`");
$executedList = array_column($executed, 'migration');

$migrationFiles = glob(__DIR__ . '/../database/migrations/*.php');
sort($migrationFiles);

$ranCount = 0;

foreach ($migrationFiles as $filePath) {
    $fileName = basename($filePath, '.php');

    if (in_array($fileName, $executedList, true)) {
        continue;
    }

    echo "Running migration: {$fileName}... ";
    
    $migration = require $filePath;
    if (is_object($migration) && method_exists($migration, 'up')) {
        $migration->up($pdo);
    } elseif (is_callable($migration)) {
        $migration($pdo);
    } else {
        echo "[SKIPPED - Invalid migration format]\n";
        continue;
    }

    $stmt = $pdo->prepare("INSERT INTO `migrations` (`migration`, `batch`) VALUES (:migration, :batch)");
    $stmt->execute(['migration' => $fileName, 'batch' => $currentBatch]);

    echo "[DONE]\n";
    $ranCount++;
}

if ($ranCount === 0) {
    echo "Nothing to migrate. All migrations are up to date.\n";
} else {
    echo "\nSuccessfully executed {$ranCount} migration(s) in batch {$currentBatch}.\n";
}
