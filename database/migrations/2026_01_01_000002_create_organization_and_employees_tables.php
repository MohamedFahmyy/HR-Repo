<?php

declare(strict_types=1);

return new class {
    public function up(PDO $pdo): void
    {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `branches` (
                `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `code` VARCHAR(30) NOT NULL UNIQUE,
                `name_ar` VARCHAR(150) NOT NULL,
                `name_en` VARCHAR(150) NOT NULL,
                `timezone` VARCHAR(50) NOT NULL DEFAULT 'Asia/Riyadh',
                `address` TEXT NULL,
                `phone` VARCHAR(30) NULL,
                `is_active` TINYINT(1) NOT NULL DEFAULT 1,
                `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

            CREATE TABLE IF NOT EXISTS `departments` (
                `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `branch_id` INT UNSIGNED NULL,
                `parent_id` INT UNSIGNED NULL,
                `code` VARCHAR(30) NOT NULL UNIQUE,
                `name_ar` VARCHAR(150) NOT NULL,
                `name_en` VARCHAR(150) NOT NULL,
                `manager_id` BIGINT UNSIGNED NULL,
                `is_active` TINYINT(1) NOT NULL DEFAULT 1,
                `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                CONSTRAINT `fk_departments_branch` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL,
                CONSTRAINT `fk_departments_parent` FOREIGN KEY (`parent_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

            CREATE TABLE IF NOT EXISTS `positions` (
                `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `department_id` INT UNSIGNED NULL,
                `title_ar` VARCHAR(150) NOT NULL,
                `title_en` VARCHAR(150) NOT NULL,
                `code` VARCHAR(30) NOT NULL UNIQUE,
                `is_active` TINYINT(1) NOT NULL DEFAULT 1,
                `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                CONSTRAINT `fk_positions_dept` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

            CREATE TABLE IF NOT EXISTS `employees` (
                `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `user_id` BIGINT UNSIGNED NULL UNIQUE,
                `employee_code` VARCHAR(30) NOT NULL UNIQUE,
                `first_name_ar` VARCHAR(100) NOT NULL,
                `last_name_ar` VARCHAR(100) NOT NULL,
                `first_name_en` VARCHAR(100) NOT NULL,
                `last_name_en` VARCHAR(100) NOT NULL,
                `national_id` VARCHAR(50) NOT NULL,
                `passport_number` VARCHAR(50) NULL,
                `gender` ENUM('male', 'female') NOT NULL DEFAULT 'male',
                `date_of_birth` DATE NULL,
                `nationality` VARCHAR(100) NULL DEFAULT 'Saudi',
                `marital_status` ENUM('single', 'married', 'divorced', 'widowed') NOT NULL DEFAULT 'single',
                `email` VARCHAR(150) NOT NULL UNIQUE,
                `phone` VARCHAR(30) NOT NULL,
                `photo` VARCHAR(255) NULL,
                `branch_id` INT UNSIGNED NULL,
                `department_id` INT UNSIGNED NULL,
                `position_id` INT UNSIGNED NULL,
                `manager_id` BIGINT UNSIGNED NULL,
                `employment_type` ENUM('full_time', 'part_time', 'contract', 'probation', 'intern') NOT NULL DEFAULT 'full_time',
                `employment_status` ENUM('active', 'on_leave', 'suspended', 'terminated', 'resigned') NOT NULL DEFAULT 'active',
                `hire_date` DATE NOT NULL,
                `probation_end_date` DATE NULL,
                `work_location` VARCHAR(100) NULL,
                `basic_salary` DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
                `housing_allowance` DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
                `transport_allowance` DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
                `other_allowances` DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
                `bank_name` VARCHAR(100) NULL,
                `iban` VARCHAR(50) NULL,
                `notes` TEXT NULL,
                `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                `deleted_at` DATETIME NULL,
                INDEX `idx_emp_code` (`employee_code`),
                INDEX `idx_emp_status` (`employment_status`),
                INDEX `idx_emp_dept` (`department_id`),
                CONSTRAINT `fk_employees_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
                CONSTRAINT `fk_employees_branch` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL,
                CONSTRAINT `fk_employees_dept` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL,
                CONSTRAINT `fk_employees_pos` FOREIGN KEY (`position_id`) REFERENCES `positions` (`id`) ON DELETE SET NULL,
                CONSTRAINT `fk_employees_mgr` FOREIGN KEY (`manager_id`) REFERENCES `employees` (`id`) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

            CREATE TABLE IF NOT EXISTS `employment_contracts` (
                `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `employee_id` BIGINT UNSIGNED NOT NULL,
                `contract_number` VARCHAR(60) NOT NULL UNIQUE,
                `contract_type` ENUM('definite', 'indefinite', 'seasonal', 'temporary') NOT NULL DEFAULT 'definite',
                `start_date` DATE NOT NULL,
                `end_date` DATE NULL,
                `status` ENUM('active', 'expired', 'renewed', 'terminated') NOT NULL DEFAULT 'active',
                `basic_salary` DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
                `housing_allowance` DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
                `transport_allowance` DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
                `terms` TEXT NULL,
                `attachment_path` VARCHAR(255) NULL,
                `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX `idx_contracts_emp` (`employee_id`),
                INDEX `idx_contracts_dates` (`start_date`, `end_date`),
                CONSTRAINT `fk_contracts_emp` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

            CREATE TABLE IF NOT EXISTS `employee_documents` (
                `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `employee_id` BIGINT UNSIGNED NOT NULL,
                `document_type` VARCHAR(60) NOT NULL,
                `document_title` VARCHAR(150) NOT NULL,
                `file_path` VARCHAR(255) NOT NULL,
                `file_size` INT UNSIGNED NOT NULL DEFAULT 0,
                `mime_type` VARCHAR(100) NULL,
                `issue_date` DATE NULL,
                `expiry_date` DATE NULL,
                `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX `idx_documents_emp` (`employee_id`),
                INDEX `idx_documents_expiry` (`expiry_date`),
                CONSTRAINT `fk_documents_emp` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");
    }
};
