<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use App\Core\Model;

class LeaveRequest extends Model
{
    protected string $table = 'leave_requests';
    protected array $fillable = [
        'employee_id',
        'leave_type_id',
        'start_date',
        'end_date',
        'total_days',
        'is_half_day',
        'reason',
        'attachment_path',
        'status',
        'approved_by',
        'approved_at',
        'rejection_reason'
    ];

    public function allWithDetails(array $filters = []): array
    {
        $conditions = [];
        $params = [];

        if (!empty($filters['employee_id'])) {
            $conditions[] = "r.employee_id = :emp_id";
            $params['emp_id'] = (int)$filters['employee_id'];
        }

        if (!empty($filters['status'])) {
            $conditions[] = "r.status = :status";
            $params['status'] = $filters['status'];
        }

        if (!empty($filters['department_id'])) {
            $conditions[] = "e.department_id = :dept_id";
            $params['dept_id'] = (int)$filters['department_id'];
        }

        $whereClause = !empty($conditions) ? "WHERE " . implode(' AND ', $conditions) : '';

        $sql = "SELECT r.*, 
                       e.employee_code, e.first_name_ar, e.last_name_ar, e.first_name_en, e.last_name_en,
                       d.name_ar as department_name,
                       t.name_ar as leave_type_name_ar, t.name_en as leave_type_name_en, t.color, t.is_paid,
                       u.full_name_ar as approver_name
                FROM `leave_requests` r
                JOIN `employees` e ON e.id = r.employee_id
                LEFT JOIN `departments` d ON d.id = e.department_id
                JOIN `leave_types` t ON t.id = r.leave_type_id
                LEFT JOIN `users` u ON u.id = r.approved_by
                {$whereClause}
                ORDER BY r.id DESC";

        return Database::fetchAll($sql, $params);
    }
}
