<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use App\Core\Model;

class AttendanceRecord extends Model
{
    protected string $table = 'attendance_records';
    protected array $fillable = [
        'employee_id',
        'date',
        'shift_id',
        'check_in',
        'check_out',
        'status',
        'work_hours',
        'late_minutes',
        'early_departure_minutes',
        'overtime_hours',
        'notes',
        'is_manually_adjusted'
    ];

    public function getDailyAttendance(string $date, array $filters = []): array
    {
        $conditions = ["a.date = :date"];
        $params = ['date' => $date];

        if (!empty($filters['department_id'])) {
            $conditions[] = "e.department_id = :dept_id";
            $params['dept_id'] = (int)$filters['department_id'];
        }

        if (!empty($filters['status'])) {
            $conditions[] = "a.status = :status";
            $params['status'] = $filters['status'];
        }

        $whereClause = implode(' AND ', $conditions);

        $sql = "SELECT a.*, 
                       e.employee_code, e.first_name_ar, e.last_name_ar, e.first_name_en, e.last_name_en, e.photo,
                       d.name_ar as department_name, 
                       s.name_ar as shift_name, s.start_time as shift_start, s.end_time as shift_end
                FROM `attendance_records` a
                JOIN `employees` e ON e.id = a.employee_id
                LEFT JOIN `departments` d ON d.id = e.department_id
                LEFT JOIN `shifts` s ON s.id = a.shift_id
                WHERE {$whereClause}
                ORDER BY a.check_in ASC, e.employee_code ASC";

        return Database::fetchAll($sql, $params);
    }

    public function getMonthlySummaryForEmployee(int|string $employeeId, string $startDate, string $endDate): array
    {
        $sql = "SELECT a.*, s.name_ar as shift_name
                FROM `attendance_records` a
                LEFT JOIN `shifts` s ON s.id = a.shift_id
                WHERE a.employee_id = :emp_id AND a.date BETWEEN :start AND :end
                ORDER BY a.date ASC";

        return Database::fetchAll($sql, [
            'emp_id' => $employeeId,
            'start' => $startDate,
            'end' => $endDate,
        ]);
    }
}
