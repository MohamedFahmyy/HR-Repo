<?php

declare(strict_types=1);

return new class {
    public function up(PDO $pdo): void
    {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `leave_types` (
                `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `code` VARCHAR(30) NOT NULL UNIQUE,
                `name_ar` VARCHAR(100) NOT NULL,
                `name_en` VARCHAR(100) NOT NULL,
                `default_days_per_year` DECIMAL(5, 2) NOT NULL DEFAULT 21.00,
                `is_paid` TINYINT(1) NOT NULL DEFAULT 1,
                `is_carry_forward` TINYINT(1) NOT NULL DEFAULT 0,
                `max_carry_forward_days` DECIMAL(5, 2) NOT NULL DEFAULT 0.00,
                `requires_attachment` TINYINT(1) NOT NULL DEFAULT 0,
                `color` VARCHAR(20) NOT NULL DEFAULT '#198754',
                `is_active` TINYINT(1) NOT NULL DEFAULT 1,
                `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

            CREATE TABLE IF NOT EXISTS `leave_balances` (
                `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `employee_id` BIGINT UNSIGNED NOT NULL,
                `leave_type_id` INT UNSIGNED NOT NULL,
                `year` INT UNSIGNED NOT NULL,
                `opening_balance` DECIMAL(5, 2) NOT NULL DEFAULT 0.00,
                `accrued_days` DECIMAL(5, 2) NOT NULL DEFAULT 0.00,
                `used_days` DECIMAL(5, 2) NOT NULL DEFAULT 0.00,
                `current_balance` DECIMAL(5, 2) NOT NULL DEFAULT 0.00,
                `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY `uk_emp_leave_year` (`employee_id`, `leave_type_id`, `year`),
                CONSTRAINT `fk_leave_bal_emp` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE,
                CONSTRAINT `fk_leave_bal_type` FOREIGN KEY (`leave_type_id`) REFERENCES `leave_types` (`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

            CREATE TABLE IF NOT EXISTS `leave_balance_transactions` (
                `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `employee_id` BIGINT UNSIGNED NOT NULL,
                `leave_type_id` INT UNSIGNED NOT NULL,
                `transaction_type` ENUM('opening', 'accrual', 'deduction', 'reversal', 'adjustment') NOT NULL,
                `amount` DECIMAL(5, 2) NOT NULL,
                `balance_after` DECIMAL(5, 2) NOT NULL,
                `reference_type` VARCHAR(50) NULL,
                `reference_id` BIGINT UNSIGNED NULL,
                `notes` VARCHAR(255) NULL,
                `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                INDEX `idx_trans_emp_type` (`employee_id`, `leave_type_id`),
                CONSTRAINT `fk_leave_trans_emp` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE,
                CONSTRAINT `fk_leave_trans_type` FOREIGN KEY (`leave_type_id`) REFERENCES `leave_types` (`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

            CREATE TABLE IF NOT EXISTS `leave_requests` (
                `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `employee_id` BIGINT UNSIGNED NOT NULL,
                `leave_type_id` INT UNSIGNED NOT NULL,
                `start_date` DATE NOT NULL,
                `end_date` DATE NOT NULL,
                `total_days` DECIMAL(5, 2) NOT NULL,
                `is_half_day` TINYINT(1) NOT NULL DEFAULT 0,
                `reason` TEXT NOT NULL,
                `attachment_path` VARCHAR(255) NULL,
                `status` ENUM('pending', 'approved', 'rejected', 'cancelled') NOT NULL DEFAULT 'pending',
                `approved_by` BIGINT UNSIGNED NULL,
                `approved_at` DATETIME NULL,
                `rejection_reason` TEXT NULL,
                `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX `idx_leave_req_status` (`status`),
                INDEX `idx_leave_req_dates` (`employee_id`, `start_date`, `end_date`),
                CONSTRAINT `fk_leave_req_emp` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE,
                CONSTRAINT `fk_leave_req_type` FOREIGN KEY (`leave_type_id`) REFERENCES `leave_types` (`id`) ON DELETE CASCADE,
                CONSTRAINT `fk_leave_req_approver` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

            CREATE TABLE IF NOT EXISTS `overtime_requests` (
                `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `employee_id` BIGINT UNSIGNED NOT NULL,
                `date` DATE NOT NULL,
                `start_time` TIME NOT NULL,
                `end_time` TIME NOT NULL,
                `hours` DECIMAL(5, 2) NOT NULL,
                `overtime_type` ENUM('regular', 'rest_day', 'holiday') NOT NULL DEFAULT 'regular',
                `multiplier` DECIMAL(4, 2) NOT NULL DEFAULT 1.50,
                `reason` TEXT NOT NULL,
                `status` ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
                `approved_by` BIGINT UNSIGNED NULL,
                `approved_at` DATETIME NULL,
                `rejection_reason` TEXT NULL,
                `is_paid` TINYINT(1) NOT NULL DEFAULT 0,
                `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX `idx_ot_status` (`status`),
                INDEX `idx_ot_emp_date` (`employee_id`, `date`),
                CONSTRAINT `fk_ot_emp` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE,
                CONSTRAINT `fk_ot_approver` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");
    }
};
