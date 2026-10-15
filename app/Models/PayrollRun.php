<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use App\Core\Model;

class PayrollRun extends Model
{
    protected string $table = 'payroll_runs';
    protected array $fillable = [
        'period_id',
        'run_number',
        'title',
        'total_gross',
        'total_deductions',
        'total_net',
        'employee_count',
        'status',
        'approved_by',
        'approved_at',
        'locked_by',
        'locked_at',
        'notes'
    ];

    public function allWithPeriod(): array
    {
        return Database::fetchAll(
            "SELECT r.*, p.name_ar as period_name, p.start_date, p.end_date, p.payment_date,
                    u.full_name_ar as approver_name,
                    l.full_name_ar as locker_name
             FROM `payroll_runs` r
             JOIN `payroll_periods` p ON p.id = r.period_id
             LEFT JOIN `users` u ON u.id = r.approved_by
             LEFT JOIN `users` l ON l.id = r.locked_by
             ORDER BY r.id DESC"
        );
    }
}
