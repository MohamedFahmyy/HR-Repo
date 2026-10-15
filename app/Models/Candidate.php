<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use App\Core\Model;

class Candidate extends Model
{
    protected string $table = 'candidates';
    protected array $fillable = [
        'vacancy_id',
        'first_name',
        'last_name',
        'email',
        'phone',
        'cv_file_path',
        'status',
        'interview_date',
        'notes',
        'converted_to_employee_id'
    ];

    public function allWithVacancy(): array
    {
        return Database::fetchAll(
            "SELECT c.*, v.title_ar as vacancy_title
             FROM `candidates` c
             LEFT JOIN `vacancies` v ON v.id = c.vacancy_id
             ORDER BY c.id DESC"
        );
    }
}
