<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use App\Core\Model;

class Position extends Model
{
    protected string $table = 'positions';
    protected array $fillable = [
        'department_id',
        'title_ar',
        'title_en',
        'code',
        'is_active'
    ];

    public function allWithDepartment(): array
    {
        return Database::fetchAll(
            "SELECT p.*, d.name_ar as department_name, COUNT(e.id) as employee_count
             FROM `positions` p
             LEFT JOIN `departments` d ON d.id = p.department_id
             LEFT JOIN `employees` e ON e.position_id = p.id AND e.deleted_at IS NULL
             GROUP BY p.id
             ORDER BY p.id ASC"
        );
    }
}
