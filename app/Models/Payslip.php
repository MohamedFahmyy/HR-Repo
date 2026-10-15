<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use App\Core\Model;

class Payslip extends Model
{
    protected string $table = 'payslips';
    protected array $fillable = [
        'payroll_item_id',
        'employee_id',
        'period_id',
        'payslip_number',
        'issue_date',
        'access_token',
        'view_count'
    ];

    public function findByToken(string $token): ?array
    {
        return $this->firstWhere('access_token', $token);
    }

    public function getPayslipsForEmployee(int|string $employeeId): array
    {
        $sql = "SELECT ps.*, p.name_ar as period_name_ar, p.name_en as period_name_en, p.start_date, p.end_date, p.payment_date,
                       i.net_salary, i.gross_salary, i.total_deductions, i.payment_status,
                       r.status as run_status
                FROM `payslips` ps
                JOIN `payroll_items` i ON i.id = ps.payroll_item_id
                JOIN `payroll_runs` r ON r.id = i.payroll_run_id
                JOIN `payroll_periods` p ON p.id = ps.period_id
                WHERE ps.employee_id = :emp_id AND r.status IN ('approved', 'locked')
                ORDER BY p.start_date DESC";

        return Database::fetchAll($sql, ['emp_id' => $employeeId]);
    }
}
