<?php
/**
 * CLI Script: Calculate Attendance
 * 
 * Usage:
 *   php bin/calculate-attendance.php [--date=2026-10-15] [--employee-id=1] [--recalculate]
 * 
 * Scheduled daily at midnight or after sync runs.
 * Evaluates raw biometric punches, shifts, grace periods, lateness, overtime, and absences.
 */

declare(strict_types=1);

require_once __DIR__ . '/../bootstrap/app.php';

use App\Core\Application;
use App\Models\Employee;
use App\Services\AttendanceCalculationService;

if (php_sapi_name() !== 'cli') {
    die("Error: This script can only be executed via the CLI.\n");
}

$lockFile = sys_get_temp_dir() . '/enterprise_attendance_calc.lock';
$lockFp = fopen($lockFile, 'c+');

if (!flock($lockFp, LOCK_EX | LOCK_NB)) {
    echo "[!] Another attendance calculation process is already running. Exiting.\n";
    exit(0);
}

echo "====================================================\n";
echo "   Enterprise HRIS - Attendance Calculation CLI    \n";
echo "   Time: " . date('Y-m-d H:i:s') . "\n";
echo "====================================================\n";

$options = getopt('', ['date::', 'employee-id::', 'recalculate::']);
$targetDate = $options['date'] ?? date('Y-m-d');
$targetEmployeeId = isset($options['employee-id']) ? (int)$options['employee-id'] : null;

$calculationService = new AttendanceCalculationService();
$employeeModel = new Employee();

if ($targetEmployeeId) {
    $employees = [$employeeModel->find($targetEmployeeId)];
    if (empty($employees[0])) {
        echo "[-] Error: Employee with ID {$targetEmployeeId} not found.\n";
        flock($lockFp, LOCK_UN);
        fclose($lockFp);
        exit(1);
    }
} else {
    $employees = $employeeModel->where(['employment_status' => 'active']);
}

echo "[*] Processing attendance for Date: {$targetDate}\n";
echo "[*] Active Employees to evaluate: " . count($employees) . "\n\n";

$processed = 0;
$present = 0;
$absent = 0;
$late = 0;

foreach ($employees as $emp) {
    try {
        $record = $calculationService->calculateDailyAttendance((int)$emp['id'], $targetDate);
        $processed++;
        $status = $record['status'] ?? 'unknown';

        if ($status === 'present') $present++;
        elseif ($status === 'absent') $absent++;
        elseif ($status === 'late') $late++;

        $fullName = ($emp['first_name_ar'] ?? '') . ' ' . ($emp['last_name_ar'] ?? '');
        echo sprintf(
            "  -> Employee [%s] %-20s | Status: %-8s | In: %-5s | Out: %-5s | Late: %d m | OT: %.1f h\n",
            $emp['employee_code'],
            mb_substr($fullName, 0, 15, 'UTF-8'),
            $status,
            !empty($record['check_in']) ? date('H:i', strtotime($record['check_in'])) : '--:--',
            !empty($record['check_out']) ? date('H:i', strtotime($record['check_out'])) : '--:--',
            (int)($record['late_minutes'] ?? 0),
            (float)($record['overtime_hours'] ?? 0)
        );
    } catch (\Throwable $e) {
        echo "  [-] Error processing employee {$emp['employee_code']}: " . $e->getMessage() . "\n";
    }
}

echo "\n----------------------------------------------------\n";
echo "[+] Attendance calculation finished.\n";
echo "    Total Evaluated: {$processed}\n";
echo "    Present:         {$present}\n";
echo "    Late:            {$late}\n";
echo "    Absent:          {$absent}\n";
echo "====================================================\n";

flock($lockFp, LOCK_UN);
fclose($lockFp);
@unlink($lockFile);
exit(0);
