<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use App\Core\Model;

class BiometricDevice extends Model
{
    protected string $table = 'biometric_devices';
    protected array $fillable = [
        'name',
        'branch_id',
        'ip_address',
        'port',
        'protocol',
        'comm_key',
        'serial_number',
        'model_name',
        'firmware_version',
        'location_description',
        'is_active',
        'last_sync_status',
        'last_sync_at',
        'last_punch_at',
        'last_error_message'
    ];

    public function allWithStats(): array
    {
        return Database::fetchAll(
            "SELECT d.*, 
                    b.name_ar as branch_name,
                    COUNT(DISTINCT m.employee_id) as mapped_users_count,
                    COUNT(r.id) as total_punches_count
             FROM `biometric_devices` d
             LEFT JOIN `branches` b ON b.id = d.branch_id
             LEFT JOIN `biometric_employee_mappings` m ON m.device_id = d.id
             LEFT JOIN `biometric_raw_events` r ON r.device_id = d.id
             GROUP BY d.id
             ORDER BY d.id ASC"
        );
    }
}
