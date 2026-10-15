<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Services\AttendanceCalculationService;
use App\Models\Employee;
use App\Core\Database;

class AttendanceCalculationTest extends TestCase
{
    private AttendanceCalculationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new AttendanceCalculationService();
    }

    public function testGracePeriodLatenessCalculation(): void
    {
        // Target: Employee EMP-0001 (assigned standard shift 08:00 - 17:00, 15m grace)
        $employeeModel = new Employee();
        $emp = $employeeModel->firstWhere('employee_code', 'EMP-0001');
        $this->assertNotNull($emp, 'Test employee EMP-0001 must exist in seeded database.');

        $empId = (int)$emp['id'];
        $testDate = '2026-10-15';

        // 1. Employee punches in at 08:05 (within 15m grace period)
        Database::execute("DELETE FROM `biometric_raw_events` WHERE device_user_id = '1001' AND punch_time LIKE '{$testDate}%'");
        Database::execute(
            "INSERT INTO `biometric_raw_events` (`device_id`, `device_user_id`, `punch_time`, `punch_state`, `verify_type`, `processed`)
             VALUES (1, '1001', '{$testDate} 08:05:00', 0, 1, 0),
                    (1, '1001', '{$testDate} 17:05:00', 1, 1, 0)"
        );

        $result = $this->service->calculateDailyAttendance($empId, $testDate);
        $this->assertSame('present', $result['status'], 'Check-in within 15 min grace should be marked present.');
        $this->assertSame(5, $result['late_minutes']);
        $this->assertGreaterThanOrEqual(8.0, (float)$result['work_hours']);

        // 2. Employee punches in at 08:35 (exceeds 15m grace period)
        Database::execute("DELETE FROM `biometric_raw_events` WHERE device_user_id = '1001' AND punch_time LIKE '{$testDate}%'");
        Database::execute(
            "INSERT INTO `biometric_raw_events` (`device_id`, `device_user_id`, `punch_time`, `punch_state`, `verify_type`, `processed`)
             VALUES (1, '1001', '{$testDate} 08:35:00', 0, 1, 0),
                    (1, '1001', '{$testDate} 17:00:00', 1, 1, 0)"
        );

        $resultLate = $this->service->calculateDailyAttendance($empId, $testDate);
        $this->assertSame('late', $resultLate['status'], 'Check-in past grace period must be marked late.');
        $this->assertSame(35, $resultLate['late_minutes']);
    }

    public function testMissingPunchHandlingProducesExplainableResult(): void
    {
        $employeeModel = new Employee();
        $emp = $employeeModel->firstWhere('employee_code', 'EMP-0001');
        $empId = (int)$emp['id'];
        $testDate = '2026-10-16';

        // Single check-in punch only (missing checkout)
        Database::execute("DELETE FROM `biometric_raw_events` WHERE device_user_id = '1001' AND punch_time LIKE '{$testDate}%'");
        Database::execute(
            "INSERT INTO `biometric_raw_events` (`device_id`, `device_user_id`, `punch_time`, `punch_state`, `verify_type`, `processed`)
             VALUES (1, '1001', '{$testDate} 08:00:00', 0, 1, 0)"
        );

        $result = $this->service->calculateDailyAttendance($empId, $testDate);
        $this->assertNotNull($result['check_in']);
        $this->assertNull($result['check_out']);
        $this->assertStringContainsString('انصراف مفقود', $result['notes']);
    }

    public function testAbsenceDetectedWhenNoPunchesRecorded(): void
    {
        $employeeModel = new Employee();
        $emp = $employeeModel->firstWhere('employee_code', 'EMP-0005');
        $empId = (int)$emp['id'];
        $testDate = '2026-10-14'; // A normal Wednesday without punches

        Database::execute("DELETE FROM `biometric_raw_events` WHERE device_user_id = '1005' AND punch_time LIKE '{$testDate}%'");

        $result = $this->service->calculateDailyAttendance($empId, $testDate);
        $this->assertSame('absent', $result['status']);
        $this->assertSame(0.0, (float)$result['work_hours']);
    }
}
