<?php

declare(strict_types=1);

return new class {
    public function up(PDO $pdo): void
    {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `vacancies` (
                `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `department_id` INT UNSIGNED NULL,
                `position_id` INT UNSIGNED NULL,
                `title_ar` VARCHAR(150) NOT NULL,
                `title_en` VARCHAR(150) NOT NULL,
                `openings_count` INT UNSIGNED NOT NULL DEFAULT 1,
                `employment_type` ENUM('full_time', 'part_time', 'contract', 'probation') NOT NULL DEFAULT 'full_time',
                `experience_years` INT UNSIGNED NOT NULL DEFAULT 1,
                `description` TEXT NULL,
                `status` ENUM('draft', 'published', 'closed') NOT NULL DEFAULT 'published',
                `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                CONSTRAINT `fk_vacancies_dept` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL,
                CONSTRAINT `fk_vacancies_pos` FOREIGN KEY (`position_id`) REFERENCES `positions` (`id`) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

            CREATE TABLE IF NOT EXISTS `candidates` (
                `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `vacancy_id` INT UNSIGNED NULL,
                `first_name` VARCHAR(100) NOT NULL,
                `last_name` VARCHAR(100) NOT NULL,
                `email` VARCHAR(150) NOT NULL,
                `phone` VARCHAR(30) NOT NULL,
                `cv_file_path` VARCHAR(255) NULL,
                `status` ENUM('applied', 'shortlisted', 'interviewed', 'offered', 'hired', 'rejected') NOT NULL DEFAULT 'applied',
                `interview_date` DATETIME NULL,
                `notes` TEXT NULL,
                `converted_to_employee_id` BIGINT UNSIGNED NULL,
                `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX `idx_cand_status` (`status`),
                CONSTRAINT `fk_candidates_vacancy` FOREIGN KEY (`vacancy_id`) REFERENCES `vacancies` (`id`) ON DELETE SET NULL,
                CONSTRAINT `fk_candidates_converted_emp` FOREIGN KEY (`converted_to_employee_id`) REFERENCES `employees` (`id`) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

            CREATE TABLE IF NOT EXISTS `onboarding_tasks` (
                `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `employee_id` BIGINT UNSIGNED NOT NULL,
                `task_title` VARCHAR(150) NOT NULL,
                `task_category` VARCHAR(60) NOT NULL DEFAULT 'General',
                `is_completed` TINYINT(1) NOT NULL DEFAULT 0,
                `completed_at` DATETIME NULL,
                `assigned_to` BIGINT UNSIGNED NULL,
                `due_date` DATE NULL,
                `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX `idx_onboard_emp` (`employee_id`),
                CONSTRAINT `fk_onboard_emp` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE,
                CONSTRAINT `fk_onboard_assignee` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

            CREATE TABLE IF NOT EXISTS `training_records` (
                `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `employee_id` BIGINT UNSIGNED NOT NULL,
                `course_name` VARCHAR(150) NOT NULL,
                `institution` VARCHAR(150) NOT NULL,
                `start_date` DATE NOT NULL,
                `end_date` DATE NOT NULL,
                `cost` DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
                `status` ENUM('planned', 'in_progress', 'completed', 'cancelled') NOT NULL DEFAULT 'completed',
                `certificate_path` VARCHAR(255) NULL,
                `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                INDEX `idx_training_emp` (`employee_id`),
                CONSTRAINT `fk_training_emp` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

            CREATE TABLE IF NOT EXISTS `performance_reviews` (
                `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `employee_id` BIGINT UNSIGNED NOT NULL,
                `reviewer_id` BIGINT UNSIGNED NOT NULL,
                `review_period` VARCHAR(60) NOT NULL, -- e.g. 2026-Annual or Q3-2026
                `rating` DECIMAL(3, 2) NOT NULL DEFAULT 3.00, -- 1.00 to 5.00
                `kpis_feedback` TEXT NULL,
                `strengths` TEXT NULL,
                `areas_for_improvement` TEXT NULL,
                `status` ENUM('draft', 'submitted', 'acknowledged') NOT NULL DEFAULT 'submitted',
                `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX `idx_review_emp` (`employee_id`),
                CONSTRAINT `fk_review_emp` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE,
                CONSTRAINT `fk_review_reviewer` FOREIGN KEY (`reviewer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

            CREATE TABLE IF NOT EXISTS `notifications` (
                `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `user_id` BIGINT UNSIGNED NOT NULL,
                `title` VARCHAR(150) NOT NULL,
                `message` TEXT NOT NULL,
                `link` VARCHAR(255) NULL,
                `is_read` TINYINT(1) NOT NULL DEFAULT 0,
                `read_at` DATETIME NULL,
                `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                INDEX `idx_notif_user` (`user_id`, `is_read`),
                CONSTRAINT `fk_notif_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");
    }
};
