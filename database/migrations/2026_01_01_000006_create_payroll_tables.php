<?php

declare(strict_types=1);

return new class {
    public function up(PDO $pdo): void
    {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `salary_components` (
                `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `code` VARCHAR(30) NOT NULL UNIQUE,
                `name_ar` VARCHAR(100) NOT NULL,
                `name_en` VARCHAR(100) NOT NULL,
                `type` ENUM('earning', 'deduction') NOT NULL,
                `is_fixed` TINYINT(1) NOT NULL DEFAULT 1,
                `default_amount` DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
                `is_active` TINYINT(1) NOT NULL DEFAULT 1,
                `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

            CREATE TABLE IF NOT EXISTS `employee_salary_components` (
                `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `employee_id` BIGINT UNSIGNED NOT NULL,
                `component_id` INT UNSIGNED NOT NULL,
                `amount` DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
                `effective_date` DATE NOT NULL,
                `end_date` DATE NULL,
                `is_active` TINYINT(1) NOT NULL DEFAULT 1,
                `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX `idx_emp_salary_comp` (`employee_id`, `is_active`),
                CONSTRAINT `fk_sal_comp_emp` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE,
                CONSTRAINT `fk_sal_comp_item` FOREIGN KEY (`component_id`) REFERENCES `salary_components` (`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

            CREATE TABLE IF NOT EXISTS `payroll_periods` (
                `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `code` VARCHAR(30) NOT NULL UNIQUE,
                `name_ar` VARCHAR(100) NOT NULL,
                `name_en` VARCHAR(100) NOT NULL,
                `start_date` DATE NOT NULL,
                `end_date` DATE NOT NULL,
                `payment_date` DATE NOT NULL,
                `status` ENUM('open', 'processing', 'approved', 'locked') NOT NULL DEFAULT 'open',
                `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX `idx_payroll_period_dates` (`start_date`, `end_date`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

            CREATE TABLE IF NOT EXISTS `payroll_runs` (
                `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `period_id` INT UNSIGNED NOT NULL,
                `run_number` VARCHAR(50) NOT NULL UNIQUE,
                `title` VARCHAR(150) NOT NULL,
                `total_gross` DECIMAL(14, 2) NOT NULL DEFAULT 0.00,
                `total_deductions` DECIMAL(14, 2) NOT NULL DEFAULT 0.00,
                `total_net` DECIMAL(14, 2) NOT NULL DEFAULT 0.00,
                `employee_count` INT UNSIGNED NOT NULL DEFAULT 0,
                `status` ENUM('draft', 'calculated', 'approved', 'locked') NOT NULL DEFAULT 'draft',
                `approved_by` BIGINT UNSIGNED NULL,
                `approved_at` DATETIME NULL,
                `locked_by` BIGINT UNSIGNED NULL,
                `locked_at` DATETIME NULL,
                `notes` TEXT NULL,
                `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX `idx_payroll_run_status` (`status`),
                CONSTRAINT `fk_payroll_run_period` FOREIGN KEY (`period_id`) REFERENCES `payroll_periods` (`id`) ON DELETE RESTRICT,
                CONSTRAINT `fk_payroll_run_approver` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
                CONSTRAINT `fk_payroll_run_locker` FOREIGN KEY (`locked_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

            CREATE TABLE IF NOT EXISTS `payroll_items` (
                `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `payroll_run_id` BIGINT UNSIGNED NOT NULL,
                `employee_id` BIGINT UNSIGNED NOT NULL,
                `basic_salary` DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
                `housing_allowance` DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
                `transport_allowance` DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
                `other_allowances` DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
                `overtime_hours` DECIMAL(5, 2) NOT NULL DEFAULT 0.00,
                `overtime_pay` DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
                `bonus_amount` DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
                `absence_days` DECIMAL(5, 2) NOT NULL DEFAULT 0.00,
                `absence_deduction` DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
                `late_minutes` INT UNSIGNED NOT NULL DEFAULT 0,
                `late_deduction` DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
                `unpaid_leave_deduction` DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
                `advance_deduction` DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
                `other_deductions` DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
                `gross_salary` DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
                `total_deductions` DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
                `net_salary` DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
                `payment_status` ENUM('unpaid', 'processed', 'paid', 'hold') NOT NULL DEFAULT 'unpaid',
                `notes` TEXT NULL,
                `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY `uk_payroll_item_emp` (`payroll_run_id`, `employee_id`),
                INDEX `idx_payroll_item_emp` (`employee_id`),
                CONSTRAINT `fk_payroll_item_run` FOREIGN KEY (`payroll_run_id`) REFERENCES `payroll_runs` (`id`) ON DELETE CASCADE,
                CONSTRAINT `fk_payroll_item_emp` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

            CREATE TABLE IF NOT EXISTS `payslips` (
                `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `payroll_item_id` BIGINT UNSIGNED NOT NULL UNIQUE,
                `employee_id` BIGINT UNSIGNED NOT NULL,
                `period_id` INT UNSIGNED NOT NULL,
                `payslip_number` VARCHAR(60) NOT NULL UNIQUE,
                `issue_date` DATE NOT NULL,
                `access_token` VARCHAR(64) NOT NULL UNIQUE,
                `view_count` INT UNSIGNED NOT NULL DEFAULT 0,
                `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                INDEX `idx_payslip_emp` (`employee_id`),
                CONSTRAINT `fk_payslip_item` FOREIGN KEY (`payroll_item_id`) REFERENCES `payroll_items` (`id`) ON DELETE CASCADE,
                CONSTRAINT `fk_payslip_emp` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE,
                CONSTRAINT `fk_payslip_period` FOREIGN KEY (`period_id`) REFERENCES `payroll_periods` (`id`) ON DELETE RESTRICT
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");
    }
};
