<?php

declare(strict_types=1);

return new class {
    public function up(PDO $pdo): void
    {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `attendance_policies` (
                `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `name_ar` VARCHAR(100) NOT NULL,
                `name_en` VARCHAR(100) NOT NULL,
                `grace_period_minutes` INT UNSIGNED NOT NULL DEFAULT 15,
                `early_departure_grace_minutes` INT UNSIGNED NOT NULL DEFAULT 15,
                `half_day_threshold_hours` DECIMAL(4, 2) NOT NULL DEFAULT 4.00,
                `full_day_hours` DECIMAL(4, 2) NOT NULL DEFAULT 8.00,
                `overtime_minimum_minutes` INT UNSIGNED NOT NULL DEFAULT 30,
                `overtime_regular_rate` DECIMAL(4, 2) NOT NULL DEFAULT 1.50,
                `overtime_rest_day_rate` DECIMAL(4, 2) NOT NULL DEFAULT 2.00,
                `overtime_holiday_rate` DECIMAL(4, 2) NOT NULL DEFAULT 2.50,
                `is_default` TINYINT(1) NOT NULL DEFAULT 0,
                `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

            CREATE TABLE IF NOT EXISTS `shifts` (
                `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `code` VARCHAR(30) NOT NULL UNIQUE,
                `name_ar` VARCHAR(100) NOT NULL,
                `name_en` VARCHAR(100) NOT NULL,
                `start_time` TIME NOT NULL,
                `end_time` TIME NOT NULL,
                `is_overnight` TINYINT(1) NOT NULL DEFAULT 0,
                `working_hours` DECIMAL(4, 2) NOT NULL DEFAULT 8.00,
                `break_duration_minutes` INT UNSIGNED NOT NULL DEFAULT 60,
                `is_ramadan_shift` TINYINT(1) NOT NULL DEFAULT 0,
                `color` VARCHAR(20) NOT NULL DEFAULT '#0d6efd',
                `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

            CREATE TABLE IF NOT EXISTS `shift_assignments` (
                `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `employee_id` BIGINT UNSIGNED NOT NULL,
                `shift_id` INT UNSIGNED NOT NULL,
                `start_date` DATE NOT NULL,
                `end_date` DATE NULL,
                `policy_id` INT UNSIGNED NULL,
                `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX `idx_shift_assign_emp` (`employee_id`, `start_date`, `end_date`),
                CONSTRAINT `fk_shift_assign_emp` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE,
                CONSTRAINT `fk_shift_assign_shift` FOREIGN KEY (`shift_id`) REFERENCES `shifts` (`id`) ON DELETE CASCADE,
                CONSTRAINT `fk_shift_assign_policy` FOREIGN KEY (`policy_id`) REFERENCES `attendance_policies` (`id`) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

            CREATE TABLE IF NOT EXISTS `work_calendars` (
                `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `name_ar` VARCHAR(100) NOT NULL,
                `name_en` VARCHAR(100) NOT NULL,
                `year` INT UNSIGNED NOT NULL,
                `weekly_rest_days` VARCHAR(50) NOT NULL DEFAULT '5,6', -- Friday, Saturday (0=Sun, 1=Mon, ..., 5=Fri, 6=Sat)
                `is_default` TINYINT(1) NOT NULL DEFAULT 1,
                `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

            CREATE TABLE IF NOT EXISTS `holidays` (
                `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `calendar_id` INT UNSIGNED NULL,
                `name_ar` VARCHAR(150) NOT NULL,
                `name_en` VARCHAR(150) NOT NULL,
                `start_date` DATE NOT NULL,
                `end_date` DATE NOT NULL,
                `is_recurring` TINYINT(1) NOT NULL DEFAULT 0,
                `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX `idx_holidays_dates` (`start_date`, `end_date`),
                CONSTRAINT `fk_holidays_calendar` FOREIGN KEY (`calendar_id`) REFERENCES `work_calendars` (`id`) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");
    }
};
