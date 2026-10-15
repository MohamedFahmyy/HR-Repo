<?php

declare(strict_types=1);

return new class {
    public function up(PDO $pdo): void
    {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `biometric_devices` (
                `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `name` VARCHAR(100) NOT NULL,
                `branch_id` INT UNSIGNED NULL,
                `ip_address` VARCHAR(45) NOT NULL,
                `port` INT UNSIGNED NOT NULL DEFAULT 4370,
                `protocol` ENUM('direct_tcp', 'adms_push', 'mock_adapter') NOT NULL DEFAULT 'direct_tcp',
                `comm_key` VARCHAR(50) NOT NULL DEFAULT '0',
                `serial_number` VARCHAR(100) NULL,
                `model_name` VARCHAR(100) NULL,
                `firmware_version` VARCHAR(100) NULL,
                `location_description` VARCHAR(255) NULL,
                `is_active` TINYINT(1) NOT NULL DEFAULT 1,
                `last_sync_status` ENUM('idle', 'success', 'failed', 'syncing') NOT NULL DEFAULT 'idle',
                `last_sync_at` DATETIME NULL,
                `last_punch_at` DATETIME NULL,
                `last_error_message` TEXT NULL,
                `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX `idx_devices_status` (`is_active`, `last_sync_status`),
                CONSTRAINT `fk_devices_branch` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

            CREATE TABLE IF NOT EXISTS `biometric_employee_mappings` (
                `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `device_id` INT UNSIGNED NOT NULL,
                `employee_id` BIGINT UNSIGNED NOT NULL,
                `device_user_id` VARCHAR(50) NOT NULL,
                `card_number` VARCHAR(50) NULL,
                `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY `uk_device_user` (`device_id`, `device_user_id`),
                UNIQUE KEY `uk_device_employee` (`device_id`, `employee_id`),
                INDEX `idx_mapping_device_user` (`device_user_id`),
                CONSTRAINT `fk_mapping_device` FOREIGN KEY (`device_id`) REFERENCES `biometric_devices` (`id`) ON DELETE CASCADE,
                CONSTRAINT `fk_mapping_emp` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

            CREATE TABLE IF NOT EXISTS `biometric_sync_logs` (
                `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `device_id` INT UNSIGNED NOT NULL,
                `sync_type` ENUM('manual', 'cli_cron', 'adms_push', 'mock_sim') NOT NULL,
                `status` ENUM('success', 'failed') NOT NULL,
                `records_fetched` INT UNSIGNED NOT NULL DEFAULT 0,
                `records_inserted` INT UNSIGNED NOT NULL DEFAULT 0,
                `error_message` TEXT NULL,
                `duration_seconds` DECIMAL(6, 2) NOT NULL DEFAULT 0.00,
                `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                INDEX `idx_sync_logs_device` (`device_id`, `created_at`),
                CONSTRAINT `fk_sync_logs_device` FOREIGN KEY (`device_id`) REFERENCES `biometric_devices` (`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

            CREATE TABLE IF NOT EXISTS `biometric_raw_events` (
                `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `device_id` INT UNSIGNED NOT NULL,
                `device_user_id` VARCHAR(50) NOT NULL,
                `punch_time` DATETIME NOT NULL,
                `punch_state` TINYINT NOT NULL DEFAULT 0, -- 0=In, 1=Out, 2=Break-Out, 3=Break-In, 4=OT-In, 5=OT-Out
                `verify_type` TINYINT NOT NULL DEFAULT 1, -- 1=Finger, 2=Password, 3=Card, 4=Face
                `work_code` INT NOT NULL DEFAULT 0,
                `raw_payload` TEXT NULL,
                `processed` TINYINT(1) NOT NULL DEFAULT 0,
                `processed_at` DATETIME NULL,
                `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY `uk_raw_punch` (`device_id`, `device_user_id`, `punch_time`),
                INDEX `idx_raw_time` (`punch_time`),
                INDEX `idx_raw_processed` (`processed`),
                CONSTRAINT `fk_raw_events_device` FOREIGN KEY (`device_id`) REFERENCES `biometric_devices` (`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

            CREATE TABLE IF NOT EXISTS `attendance_records` (
                `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `employee_id` BIGINT UNSIGNED NOT NULL,
                `date` DATE NOT NULL,
                `shift_id` INT UNSIGNED NULL,
                `check_in` DATETIME NULL,
                `check_out` DATETIME NULL,
                `status` ENUM('present', 'late', 'early_leave', 'absent', 'half_day', 'on_leave', 'holiday', 'rest_day') NOT NULL DEFAULT 'absent',
                `work_hours` DECIMAL(5, 2) NOT NULL DEFAULT 0.00,
                `late_minutes` INT UNSIGNED NOT NULL DEFAULT 0,
                `early_departure_minutes` INT UNSIGNED NOT NULL DEFAULT 0,
                `overtime_hours` DECIMAL(5, 2) NOT NULL DEFAULT 0.00,
                `notes` VARCHAR(255) NULL,
                `is_manually_adjusted` TINYINT(1) NOT NULL DEFAULT 0,
                `calculated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY `uk_emp_date` (`employee_id`, `date`),
                INDEX `idx_att_date` (`date`),
                INDEX `idx_att_status` (`status`),
                CONSTRAINT `fk_att_emp` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE,
                CONSTRAINT `fk_att_shift` FOREIGN KEY (`shift_id`) REFERENCES `shifts` (`id`) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

            CREATE TABLE IF NOT EXISTS `attendance_adjustments` (
                `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `record_id` BIGINT UNSIGNED NULL,
                `employee_id` BIGINT UNSIGNED NOT NULL,
                `date` DATE NOT NULL,
                `adjustment_type` ENUM('missing_punch', 'excused_late', 'early_leave', 'manual_attendance', 'dispute') NOT NULL,
                `original_check_in` DATETIME NULL,
                `original_check_out` DATETIME NULL,
                `requested_check_in` DATETIME NULL,
                `requested_check_out` DATETIME NULL,
                `reason` TEXT NOT NULL,
                `status` ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
                `requested_by` BIGINT UNSIGNED NOT NULL,
                `reviewed_by` BIGINT UNSIGNED NULL,
                `reviewed_at` DATETIME NULL,
                `review_remarks` TEXT NULL,
                `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX `idx_adj_status` (`status`),
                INDEX `idx_adj_emp` (`employee_id`, `date`),
                CONSTRAINT `fk_adj_record` FOREIGN KEY (`record_id`) REFERENCES `attendance_records` (`id`) ON DELETE SET NULL,
                CONSTRAINT `fk_adj_emp` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE,
                CONSTRAINT `fk_adj_requester` FOREIGN KEY (`requested_by`) REFERENCES `users` (`id`) ON DELETE CASCADE,
                CONSTRAINT `fk_adj_reviewer` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");
    }
};
