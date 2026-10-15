<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use App\Core\Model;

class BiometricRawEvent extends Model
{
    protected string $table = 'biometric_raw_events';
    protected array $fillable = [
        'device_id',
        'device_user_id',
        'punch_time',
        'punch_state',
        'verify_type',
        'work_code',
        'raw_payload',
        'processed',
        'processed_at'
    ];

    public function insertIdempotent(array $punch): bool
    {
        $sql = "INSERT INTO `biometric_raw_events` 
                (`device_id`, `device_user_id`, `punch_time`, `punch_state`, `verify_type`, `work_code`, `raw_payload`, `processed`)
                VALUES (:device_id, :device_user_id, :punch_time, :punch_state, :verify_type, :work_code, :raw_payload, 0)
                ON DUPLICATE KEY UPDATE `work_code` = VALUES(`work_code`)";

        $params = [
            'device_id' => $punch['device_id'],
            'device_user_id' => (string)$punch['device_user_id'],
            'punch_time' => $punch['punch_time'],
            'punch_state' => (int)($punch['punch_state'] ?? 0),
            'verify_type' => (int)($punch['verify_type'] ?? 1),
            'work_code' => (int)($punch['work_code'] ?? 0),
            'raw_payload' => $punch['raw_payload'] ?? null,
        ];

        return Database::execute($sql, $params);
    }

    public function getUnprocessedEvents(int $limit = 500): array
    {
        $sql = "SELECT r.*, m.employee_id
                FROM `biometric_raw_events` r
                LEFT JOIN `biometric_employee_mappings` m ON m.device_id = r.device_id AND m.device_user_id = r.device_user_id
                WHERE r.processed = 0
                ORDER BY r.punch_time ASC
                LIMIT {$limit}";

        return Database::fetchAll($sql);
    }
}
