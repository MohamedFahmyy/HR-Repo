<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use App\Core\Model;

class AttendanceAdjustment extends Model
{
    protected string $table = 'attendance_adjustments';
    protected array $fillable = [
        'record_id',
        'employee_id',
        'date',
        'adjustment_type',
        'original_check_in',
        'original_check_out',
        'requested_check_in',
        'requested_check_out',
        'reason',
        'status',
        'requested_by',
        'reviewed_by',
        'reviewed_at',
        'review_remarks'
    ];

    public function allWithDetails(string $status = 'all'): array
    {
        $sql = "SELECT adj.*, 
                       e.employee_code, e.first_name_ar, e.last_name_ar, e.first_name_en, e.last_name_en,
                       d.name_ar as department_name,
                       u.full_name_ar as requester_name,
                       rev.full_name_ar as reviewer_name
                FROM `attendance_adjustments` adj
                JOIN `employees` e ON e.id = adj.employee_id
                LEFT JOIN `departments` d ON d.id = e.department_id
                JOIN `users` u ON u.id = adj.requested_by
                LEFT JOIN `users` rev ON rev.id = adj.reviewed_by";

        $params = [];
        if ($status !== 'all') {
            $sql .= " WHERE adj.status = :status";
            $params['status'] = $status;
        }

        $sql .= " ORDER BY adj.id DESC";

        return Database::fetchAll($sql, $params);
    }
}
