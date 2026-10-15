<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use App\Core\Model;

class OvertimeRequest extends Model
{
    protected string $table = 'overtime_requests';
    protected array $fillable = [
        'employee_id',
        'date',
        'start_time',
        'end_time',
        'hours',
        'overtime_type',
        'multiplier',
        'reason',
        'status',
        'approved_by',
        'approved_at',
        'rejection_reason',
        'is_paid'
    ];

    public function allWithDetails(array $filters = []): array
    {
        $conditions = [];
        $params = [];

        if (!empty($filters['employee_id'])) {
            $conditions[] = "ot.employee_id = :emp_id";
            $params['emp_id'] = (int)$filters['employee_id'];
        }

        if (!empty($filters['status'])) {
            $conditions[] = "ot.status = :status";
            $params['status'] = $filters['status'];
        }

        $whereClause = !empty($conditions) ? "WHERE " . implode(' AND ', $conditions) : '';

        $sql = "SELECT ot.*, 
                       e.employee_code, e.first_name_ar, e.last_name_ar, e.first_name_en, e.last_name_en,
                       d.name_ar as department_name,
                       u.full_name_ar as approver_name
                FROM `overtime_requests` ot
                JOIN `employees` e ON e.id = ot.employee_id
                LEFT JOIN `departments` d ON d.id = e.department_id
                LEFT JOIN `users` u ON u.id = ot.approved_by
                {$whereClause}
                ORDER BY ot.id DESC";

        return Database::fetchAll($sql, $params);
    }
}
