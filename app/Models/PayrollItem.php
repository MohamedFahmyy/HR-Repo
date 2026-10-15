<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use App\Core\Model;

class PayrollItem extends Model
{
    protected string $table = 'payroll_items';
    protected array $fillable = [
        'payroll_run_id',
        'employee_id',
        'basic_salary',
        'housing_allowance',
        'transport_allowance',
        'other_allowances',
        'overtime_hours',
        'overtime_pay',
        'bonus_amount',
        'absence_days',
        'absence_deduction',
        'late_minutes',
        'late_deduction',
        'unpaid_leave_deduction',
        'advance_deduction',
        'other_deductions',
        'gross_salary',
        'total_deductions',
        'net_salary',
        'payment_status',
        'notes'
    ];

    public function getItemsForRun(int|string $runId): array
    {
        $sql = "SELECT i.*, 
                       e.employee_code, e.first_name_ar, e.last_name_ar, e.first_name_en, e.last_name_en, e.bank_name, e.iban,
                       d.name_ar as department_name, p.title_ar as position_title,
                       ps.payslip_number, ps.access_token
                FROM `payroll_items` i
                JOIN `employees` e ON e.id = i.employee_id
                LEFT JOIN `departments` d ON d.id = e.department_id
                LEFT JOIN `positions` p ON p.id = e.position_id
                LEFT JOIN `payslips` ps ON ps.payroll_item_id = i.id
                WHERE i.payroll_run_id = :run_id
                ORDER BY e.employee_code ASC";

        return Database::fetchAll($sql, ['run_id' => $runId]);
    }

    public function getDetail(int|string $itemId): ?array
    {
        $sql = "SELECT i.*, 
                       r.run_number, r.status as run_status, r.period_id,
                       prd.name_ar as period_name_ar, prd.name_en as period_name_en, prd.start_date, prd.end_date, prd.payment_date,
                       e.employee_code, e.first_name_ar, e.last_name_ar, e.first_name_en, e.last_name_en, e.national_id, e.bank_name, e.iban,
                       d.name_ar as department_name, p.title_ar as position_title, b.name_ar as branch_name,
                       ps.payslip_number, ps.access_token, ps.issue_date
                FROM `payroll_items` i
                JOIN `payroll_runs` r ON r.id = i.payroll_run_id
                JOIN `payroll_periods` prd ON prd.id = r.period_id
                JOIN `employees` e ON e.id = i.employee_id
                LEFT JOIN `departments` d ON d.id = e.department_id
                LEFT JOIN `positions` p ON p.id = e.position_id
                LEFT JOIN `branches` b ON b.id = e.branch_id
                LEFT JOIN `payslips` ps ON ps.payroll_item_id = i.id
                WHERE i.id = :id
                LIMIT 1";

        return Database::fetchOne($sql, ['id' => $itemId]);
    }
}
