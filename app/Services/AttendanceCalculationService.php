<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Models\AttendanceRecord;
use App\Models\BiometricRawEvent;
use App\Models\Employee;
use App\Models\Holiday;
use App\Models\Shift;
use DateTime;
use DateTimeImmutable;

class AttendanceCalculationService
{
    private Shift $shiftModel;
    private Holiday $holidayModel;
    private AttendanceRecord $attendanceRecordModel;

    public function __construct(
        ?Shift $shiftModel = null,
        ?Holiday $holidayModel = null,
        ?AttendanceRecord $attendanceRecordModel = null
    ) {
        $this->shiftModel = $shiftModel ?? new Shift();
        $this->holidayModel = $holidayModel ?? new Holiday();
        $this->attendanceRecordModel = $attendanceRecordModel ?? new AttendanceRecord();
    }

    /**
     * Process all unprocessed raw events into attendance records.
     */
    public function processUnprocessedRawEvents(): int
    {
        $rawModel = new BiometricRawEvent();
        $unprocessed = $rawModel->getUnprocessedEvents(1000);

        if (empty($unprocessed)) {
            return 0;
        }

        $datesToRecalculate = [];
        $processedEventIds = [];

        foreach ($unprocessed as $event) {
            $processedEventIds[] = $event['id'];

            if (!empty($event['employee_id'])) {
                $punchDate = substr($event['punch_time'], 0, 10);
                $datesToRecalculate[$event['employee_id']][$punchDate] = true;
            }
        }

        // Recalculate attendance for affected employees and dates
        $calculatedCount = 0;
        foreach ($datesToRecalculate as $empId => $dates) {
            foreach (array_keys($dates) as $date) {
                $this->calculateDailyAttendance((int)$empId, $date);
                $calculatedCount++;
            }
        }

        // Mark raw events as processed
        if (!empty($processedEventIds)) {
            $placeholders = implode(',', array_fill(0, count($processedEventIds), '?'));
            Database::execute(
                "UPDATE `biometric_raw_events` SET `processed` = 1, `processed_at` = NOW() WHERE `id` IN ({$placeholders})",
                $processedEventIds
            );
        }

        return $calculatedCount;
    }

    /**
     * Calculate daily attendance for a specific employee and date.
     */
    public function calculateDailyAttendance(int $employeeId, string $date): array
    {
        $shift = $this->shiftModel->getActiveShiftForEmployee($employeeId, $date);
        $isOvernight = (bool)($shift['is_overnight'] ?? false);

        // Check if employee is on approved leave
        $leave = Database::fetchOne(
            "SELECT lr.*, lt.name_ar as leave_name, lt.is_paid 
             FROM `leave_requests` lr
             JOIN `leave_types` lt ON lt.id = lr.leave_type_id
             WHERE lr.employee_id = :emp_id 
               AND lr.status = 'approved'
               AND lr.start_date <= :d1 AND lr.end_date >= :d2
             LIMIT 1",
            ['emp_id' => $employeeId, 'd1' => $date, 'd2' => $date]
        );

        // Check if date is a public holiday
        $isHoliday = $this->holidayModel->isHoliday($date);

        // Check if date is a weekly rest day (Friday = 5, Saturday = 6)
        $dayOfWeek = (int)date('w', strtotime($date));
        $isRestDay = in_array($dayOfWeek, [5, 6], true);

        // Query raw biometric punches for this employee on this date
        // If overnight shift, look for punches up to next day noon
        $punches = $this->fetchPunchesForDate($employeeId, $date, $isOvernight);

        $checkIn = null;
        $checkOut = null;
        $workHours = 0.00;
        $lateMinutes = 0;
        $earlyDepartureMinutes = 0;
        $overtimeHours = 0.00;
        $status = 'absent';
        $notes = '';

        if (!empty($punches)) {
            $checkIn = $punches[0]['punch_time'];
            $checkOut = count($punches) > 1 ? end($punches)['punch_time'] : null;

            // Calculate hours worked
            if ($checkIn && $checkOut) {
                $inDt = new DateTime($checkIn);
                $outDt = new DateTime($checkOut);
                $diffSeconds = max(0, $outDt->getTimestamp() - $inDt->getTimestamp());
                $workHours = round($diffSeconds / 3600, 2);

                // Deduct break if worked over 5 hours
                $breakMins = (int)($shift['break_duration_minutes'] ?? 60);
                if ($workHours > 5.0 && $breakMins > 0) {
                    $workHours = max(0.00, round($workHours - ($breakMins / 60), 2));
                }
            } else {
                // Single punch (missing punch)
                $workHours = 4.00; // default partial credit pending adjustment
                $notes = 'بصمة واحدة فقط (انصراف مفقود)';
            }

            // Lateness check
            $scheduledStart = $date . ' ' . $shift['start_time'];
            $graceMinutes = (int)($shift['grace_period_minutes'] ?? 15);
            $shiftStartDt = new DateTime($scheduledStart);
            $inDt = new DateTime($checkIn);

            if ($inDt > $shiftStartDt) {
                $actualLate = (int)round(($inDt->getTimestamp() - $shiftStartDt->getTimestamp()) / 60);
                if ($actualLate > $graceMinutes) {
                    $lateMinutes = $actualLate;
                    $status = 'late';
                } else {
                    $lateMinutes = $actualLate;
                    $status = 'present';
                }
            } else {
                $status = 'present';
            }

            // Early departure check
            if ($checkOut) {
                $scheduledEnd = $date . ' ' . $shift['end_time'];
                if ($isOvernight) {
                    $scheduledEnd = date('Y-m-d', strtotime($date . ' +1 day')) . ' ' . $shift['end_time'];
                }
                $shiftEndDt = new DateTime($scheduledEnd);
                $outDt = new DateTime($checkOut);
                $earlyGrace = (int)($shift['early_departure_grace_minutes'] ?? 15);

                if ($outDt < $shiftEndDt) {
                    $actualEarly = (int)round(($shiftEndDt->getTimestamp() - $outDt->getTimestamp()) / 60);
                    if ($actualEarly > $earlyGrace) {
                        $earlyDepartureMinutes = $actualEarly;
                        if ($status === 'present') {
                            $status = 'early_leave';
                        }
                    }
                } elseif ($outDt > $shiftEndDt) {
                    // Check for overtime
                    $extraMinutes = (int)round(($outDt->getTimestamp() - $shiftEndDt->getTimestamp()) / 60);
                    $otMinMins = (int)config('attendance.overtime.minimum_minutes', 30);
                    if ($extraMinutes >= $otMinMins) {
                        $overtimeHours = round($extraMinutes / 60, 2);
                    }
                }
            }

            // Half-day check
            $halfDayThreshold = (float)config('attendance.half_day_threshold_hours', 4.0);
            if ($workHours < $halfDayThreshold && $status !== 'present') {
                $status = 'half_day';
            }

        } else {
            // No punches found
            if ($leave) {
                $status = 'on_leave';
                $notes = 'إجازة معتمدة: ' . $leave['leave_name'];
            } elseif ($isHoliday) {
                $status = 'holiday';
                $notes = 'عطلة رسمية للمنشأة';
            } elseif ($isRestDay) {
                $status = 'rest_day';
                $notes = 'يوم راحة أسبوعية';
            } else {
                $status = 'absent';
                $notes = 'غياب غير مبرر بدون بصمة';
            }
        }

        $recordData = [
            'employee_id' => $employeeId,
            'date' => $date,
            'shift_id' => $shift['id'] ?? 1,
            'check_in' => $checkIn,
            'check_out' => $checkOut,
            'status' => $status,
            'work_hours' => $workHours,
            'late_minutes' => $lateMinutes,
            'early_departure_minutes' => $earlyDepartureMinutes,
            'overtime_hours' => $overtimeHours,
            'notes' => $notes,
            'is_manually_adjusted' => 0,
        ];

        // Upsert into attendance_records
        $sql = "INSERT INTO `attendance_records` 
                (`employee_id`, `date`, `shift_id`, `check_in`, `check_out`, `status`, `work_hours`, `late_minutes`, `early_departure_minutes`, `overtime_hours`, `notes`, `is_manually_adjusted`, `calculated_at`)
                VALUES (:employee_id, :date, :shift_id, :check_in, :check_out, :status, :work_hours, :late_minutes, :early_departure_minutes, :overtime_hours, :notes, :is_manually_adjusted, NOW())
                ON DUPLICATE KEY UPDATE
                    `shift_id` = VALUES(`shift_id`),
                    `check_in` = COALESCE(VALUES(`check_in`), `check_in`),
                    `check_out` = COALESCE(VALUES(`check_out`), `check_out`),
                    `status` = VALUES(`status`),
                    `work_hours` = VALUES(`work_hours`),
                    `late_minutes` = VALUES(`late_minutes`),
                    `early_departure_minutes` = VALUES(`early_departure_minutes`),
                    `overtime_hours` = VALUES(`overtime_hours`),
                    `notes` = VALUES(`notes`),
                    `calculated_at` = NOW()";

        Database::execute($sql, $recordData);

        return $recordData;
    }

    private function fetchPunchesForDate(int $employeeId, string $date, bool $isOvernight): array
    {
        $startWindow = $date . ' 05:00:00';
        $endWindow = $date . ' 23:59:59';

        if ($isOvernight) {
            $endWindow = date('Y-m-d', strtotime($date . ' +1 day')) . ' 12:00:00';
        }

        $sql = "SELECT r.* 
                FROM `biometric_raw_events` r
                JOIN `biometric_employee_mappings` m ON m.device_id = r.device_id AND m.device_user_id = r.device_user_id
                WHERE m.employee_id = :emp_id 
                  AND r.punch_time BETWEEN :start_win AND :end_win
                ORDER BY r.punch_time ASC";

        return Database::fetchAll($sql, [
            'emp_id' => $employeeId,
            'start_win' => $startWindow,
            'end_win' => $endWindow
        ]);
    }

    /**
     * Recalculate attendance for an entire month/period.
     */
    public function recalculatePeriod(string $startDate, string $endDate): int
    {
        $employees = Database::fetchAll("SELECT id FROM `employees` WHERE deleted_at IS NULL AND employment_status = 'active'");
        $count = 0;

        $current = new DateTime($startDate);
        $end = new DateTime($endDate);

        while ($current <= $end) {
            $dateStr = $current->format('Y-m-d');
            foreach ($employees as $emp) {
                $this->calculateDailyAttendance((int)$emp['id'], $dateStr);
                $count++;
            }
            $current->modify('+1 day');
        }

        return $count;
    }
}
