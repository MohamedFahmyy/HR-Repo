<?php

declare(strict_types=1);

require_once __DIR__ . '/../bootstrap/app.php';
require_once __DIR__ . '/../database/seeders/DatabaseSeeder.php';

use App\Core\Database;
use Database\Seeders\DatabaseSeeder;

echo "====================================================\n";
echo " Enterprise HR & Payroll System - Database Seeder   \n";
echo "====================================================\n\n";

$pdo = Database::getConnection();

try {
    $seeder = new DatabaseSeeder();
    $seeder->run($pdo);
    echo "\nDatabase seeding completed successfully!\n";
    echo "Super Admin Credentials:\n";
    echo "  Email:    admin@enterprise.local\n";
    echo "  Password: Admin@123456\n\n";
    echo "Employee Test Account:\n";
    echo "  Email:    fahad.dev@enterprise.local\n";
    echo "  Password: Emp@123456\n";
} catch (\Throwable $e) {
    echo "\nSeeder error: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}
