<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;

class OvertimeCalculationService
{
    public function calculateOvertimeForPeriod(int $employeeId, string $startDate, string $endDate, float $hourlyRate): array
    {
        // 1. Get approved overtime requests in the period
        $requests = Database::fetchAll(
            "SELECT * FROM `overtime_requests` 
             WHERE employee_id = :emp_id 
               AND status = 'approved' 
               AND date BETWEEN :start AND :end",
            ['emp_id' => $employeeId, 'start' => $startDate, 'end' => $endDate]
        );

        $totalHours = 0.00;
        $totalPay = 0.00;

        foreach ($requests as $ot) {
            $hours = (float)$ot['hours'];
            $multiplier = (float)$ot['multiplier'];
            $totalHours += $hours;
            $totalPay += round($hours * $hourlyRate * $multiplier, 2);
        }

        // 2. Also check attendance_records with calculated overtime not already covered by requests
        $records = Database::fetchAll(
            "SELECT SUM(overtime_hours) as auto_ot 
             FROM `attendance_records` 
             WHERE employee_id = :emp_id 
               AND date BETWEEN :start AND :end 
               AND overtime_hours > 0",
            ['emp_id' => $employeeId, 'start' => $startDate, 'end' => $endDate]
        );

        $autoOtHours = (float)($records[0]['auto_ot'] ?? 0.00);
        // If no formal requests were filed but auto OT exists, incorporate with default multiplier (1.5x)
        if ($totalHours === 0.00 && $autoOtHours > 0) {
            $totalHours = $autoOtHours;
            $multiplier = (float)config('attendance.overtime.regular_multiplier', 1.50);
            $totalPay = round($autoOtHours * $hourlyRate * $multiplier, 2);
        }

        return [
            'hours' => round($totalHours, 2),
            'pay' => round($totalPay, 2),
        ];
    }
}
