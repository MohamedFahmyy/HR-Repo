<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use App\Core\Model;

class LeaveBalance extends Model
{
    protected string $table = 'leave_balances';
    protected array $fillable = [
        'employee_id',
        'leave_type_id',
        'year',
        'opening_balance',
        'accrued_days',
        'used_days',
        'current_balance'
    ];

    public function getBalancesForEmployee(int|string $employeeId, ?int $year = null): array
    {
        $year = $year ?? (int)date('Y');
        $sql = "SELECT b.*, t.name_ar as leave_type_name_ar, t.name_en as leave_type_name_en, t.color, t.is_paid
                FROM `leave_balances` b
                JOIN `leave_types` t ON t.id = b.leave_type_id
                WHERE b.employee_id = :emp_id AND b.year = :year
                ORDER BY t.id ASC";

        return Database::fetchAll($sql, ['emp_id' => $employeeId, 'year' => $year]);
    }
}
