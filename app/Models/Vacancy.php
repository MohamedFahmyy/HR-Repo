<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use App\Core\Model;

class Vacancy extends Model
{
    protected string $table = 'vacancies';
    protected array $fillable = [
        'department_id',
        'position_id',
        'title_ar',
        'title_en',
        'openings_count',
        'employment_type',
        'experience_years',
        'description',
        'status'
    ];

    public function allWithDetails(): array
    {
        return Database::fetchAll(
            "SELECT v.*, d.name_ar as department_name, p.title_ar as position_title,
                    COUNT(c.id) as candidates_count
             FROM `vacancies` v
             LEFT JOIN `departments` d ON d.id = v.department_id
             LEFT JOIN `positions` p ON p.id = v.position_id
             LEFT JOIN `candidates` c ON c.vacancy_id = v.id
             GROUP BY v.id
             ORDER BY v.id DESC"
        );
    }
}
