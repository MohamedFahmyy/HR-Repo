<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use App\Core\Model;

class PerformanceReview extends Model
{
    protected string $table = 'performance_reviews';
    protected array $fillable = [
        'employee_id',
        'reviewer_id',
        'review_period',
        'rating',
        'kpis_feedback',
        'strengths',
        'areas_for_improvement',
        'status'
    ];

    public function allWithDetails(): array
    {
        return Database::fetchAll(
            "SELECT pr.*, 
                    e.employee_code, e.first_name_ar, e.last_name_ar, e.first_name_en, e.last_name_en,
                    d.name_ar as department_name, p.title_ar as position_title,
                    u.full_name_ar as reviewer_name
             FROM `performance_reviews` pr
             JOIN `employees` e ON e.id = pr.employee_id
             LEFT JOIN `departments` d ON d.id = e.department_id
             LEFT JOIN `positions` p ON p.id = e.position_id
             JOIN `users` u ON u.id = pr.reviewer_id
             ORDER BY pr.id DESC"
        );
    }
}
