<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use App\Core\Model;

class Department extends Model
{
    protected string $table = 'departments';
    protected array $fillable = [
        'branch_id',
        'parent_id',
        'code',
        'name_ar',
        'name_en',
        'manager_id',
        'is_active'
    ];

    public function allWithDetails(): array
    {
        return Database::fetchAll(
            "SELECT d.*, 
                    b.name_ar as branch_name, 
                    CONCAT(m.first_name_ar, ' ', m.last_name_ar) as manager_name,
                    COUNT(e.id) as employee_count
             FROM `departments` d
             LEFT JOIN `branches` b ON b.id = d.branch_id
             LEFT JOIN `employees` m ON m.id = d.manager_id
             LEFT JOIN `employees` e ON e.department_id = d.id AND e.deleted_at IS NULL
             GROUP BY d.id
             ORDER BY d.id ASC"
        );
    }
}
