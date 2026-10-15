<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use App\Core\Model;

class Employee extends Model
{
    protected string $table = 'employees';
    protected array $fillable = [
        'user_id',
        'employee_code',
        'first_name_ar',
        'last_name_ar',
        'first_name_en',
        'last_name_en',
        'national_id',
        'passport_number',
        'gender',
        'date_of_birth',
        'nationality',
        'marital_status',
        'email',
        'phone',
        'photo',
        'branch_id',
        'department_id',
        'position_id',
        'manager_id',
        'employment_type',
        'employment_status',
        'hire_date',
        'probation_end_date',
        'work_location',
        'basic_salary',
        'housing_allowance',
        'transport_allowance',
        'other_allowances',
        'bank_name',
        'iban',
        'notes'
    ];
    protected bool $softDeletes = true;

    public function getDetailedProfile(int|string $id): ?array
    {
        $sql = "SELECT e.*, 
                       b.name_ar as branch_name_ar, b.name_en as branch_name_en,
                       d.name_ar as department_name_ar, d.name_en as department_name_en,
                       p.title_ar as position_title_ar, p.title_en as position_title_en,
                       CONCAT(m.first_name_ar, ' ', m.last_name_ar) as manager_name_ar,
                       CONCAT(m.first_name_en, ' ', m.last_name_en) as manager_name_en,
                       s.id as current_shift_id, s.name_ar as shift_name_ar, s.start_time as shift_start, s.end_time as shift_end,
                       u.username, u.is_active as user_active
                FROM `employees` e
                LEFT JOIN `branches` b ON b.id = e.branch_id
                LEFT JOIN `departments` d ON d.id = e.department_id
                LEFT JOIN `positions` p ON p.id = e.position_id
                LEFT JOIN `employees` m ON m.id = e.manager_id
                LEFT JOIN `users` u ON u.id = e.user_id
                LEFT JOIN `shift_assignments` sa ON sa.employee_id = e.id AND (sa.end_date IS NULL OR sa.end_date >= CURDATE())
                LEFT JOIN `shifts` s ON s.id = sa.shift_id
                WHERE e.id = :id AND e.deleted_at IS NULL
                LIMIT 1";

        return Database::fetchOne($sql, ['id' => $id]);
    }

    public function search(array $filters = [], int $page = 1, int $perPage = 15): array
    {
        $conditions = [];
        $params = [];

        if (!empty($filters['search'])) {
            $conditions[] = "(e.employee_code LIKE :search OR e.first_name_ar LIKE :search OR e.last_name_ar LIKE :search OR e.first_name_en LIKE :search OR e.last_name_en LIKE :search OR e.email LIKE :search OR e.national_id LIKE :search)";
            $params['search'] = '%' . $filters['search'] . '%';
        }

        if (!empty($filters['department_id'])) {
            $conditions[] = "e.department_id = :dept_id";
            $params['dept_id'] = (int)$filters['department_id'];
        }

        if (!empty($filters['branch_id'])) {
            $conditions[] = "e.branch_id = :branch_id";
            $params['branch_id'] = (int)$filters['branch_id'];
        }

        if (!empty($filters['employment_status'])) {
            $conditions[] = "e.employment_status = :status";
            $params['status'] = $filters['employment_status'];
        }

        $whereClause = !empty($conditions) ? implode(' AND ', $conditions) : '';

        // Custom pagination query with joins
        $countSql = "SELECT COUNT(*) FROM `employees` e WHERE e.deleted_at IS NULL";
        if ($whereClause) {
            $countSql .= " AND " . $whereClause;
        }
        $total = (int)Database::fetchColumn($countSql, $params);

        $offset = max(0, ($page - 1) * $perPage);
        $dataSql = "SELECT e.*, 
                           d.name_ar as department_name, 
                           p.title_ar as position_title,
                           b.name_ar as branch_name
                    FROM `employees` e
                    LEFT JOIN `departments` d ON d.id = e.department_id
                    LEFT JOIN `positions` p ON p.id = e.position_id
                    LEFT JOIN `branches` b ON b.id = e.branch_id
                    WHERE e.deleted_at IS NULL";

        if ($whereClause) {
            $dataSql .= " AND " . $whereClause;
        }

        $dataSql .= " ORDER BY e.id DESC LIMIT {$perPage} OFFSET {$offset}";
        $items = Database::fetchAll($dataSql, $params);

        return [
            'data' => $items,
            'total' => $total,
            'per_page' => $perPage,
            'current_page' => $page,
            'last_page' => (int)ceil($total / $perPage),
        ];
    }
}
