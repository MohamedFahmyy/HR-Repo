<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use App\Core\Model;

class Shift extends Model
{
    protected string $table = 'shifts';
    protected array $fillable = [
        'code',
        'name_ar',
        'name_en',
        'start_time',
        'end_time',
        'is_overnight',
        'working_hours',
        'break_duration_minutes',
        'is_ramadan_shift',
        'color'
    ];

    public function getActiveShiftForEmployee(int|string $employeeId, ?string $date = null): ?array
    {
        $date = $date ?? date('Y-m-d');
        $sql = "SELECT s.*, sa.policy_id, p.grace_period_minutes, p.early_departure_grace_minutes, p.overtime_regular_rate
                FROM `shift_assignments` sa
                JOIN `shifts` s ON s.id = sa.shift_id
                LEFT JOIN `attendance_policies` p ON p.id = sa.policy_id
                WHERE sa.employee_id = :emp_id 
                  AND sa.start_date <= :d1 
                  AND (sa.end_date IS NULL OR sa.end_date >= :d2)
                ORDER BY sa.id DESC
                LIMIT 1";

        $shift = Database::fetchOne($sql, ['emp_id' => $employeeId, 'd1' => $date, 'd2' => $date]);
        if (!$shift) {
            // Fallback to default shift (ID 1)
            $shift = Database::fetchOne("SELECT s.*, 15 as grace_period_minutes, 15 as early_departure_grace_minutes, 1.50 as overtime_regular_rate FROM `shifts` s WHERE s.id = 1 LIMIT 1");
        }
        return $shift;
    }
}
