<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Services\LeaveBalanceService;
use App\Models\Employee;
use App\Models\LeaveType;

class LeaveLedgerTest extends TestCase
{
    private LeaveBalanceService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new LeaveBalanceService();
    }

    public function testLeaveDeductionAndReversalIdempotency(): void
    {
        $employeeModel = new Employee();
        $emp = $employeeModel->firstWhere('employee_code', 'EMP-0001');
        $this->assertNotNull($emp);
        $empId = (int)$emp['id'];

        $leaveTypeModel = new LeaveType();
        $annualLeave = $leaveTypeModel->firstWhere('code', 'LV-ANN');
        $this->assertNotNull($annualLeave, 'Leave type LV-ANN should exist in seeded database.');
        $leaveTypeId = (int)$annualLeave['id'];

        $currentYear = (int)date('Y');

        // Get initial remaining balance
        $initialBalance = $this->service->getRemainingBalance($empId, $leaveTypeId, $currentYear);
        $this->assertGreaterThan(0.0, $initialBalance);

        // Deduct 2 days
        $this->service->deductBalance(
            employeeId: $empId,
            leaveTypeId: $leaveTypeId,
            year: $currentYear,
            days: 2.0,
            referenceType: 'test_request',
            referenceId: 99999,
            notes: 'Unit test deduction'
        );

        $afterDeduction = $this->service->getRemainingBalance($empId, $leaveTypeId, $currentYear);
        $this->assertEquals($initialBalance - 2.0, $afterDeduction);

        // Reverse the 2 days
        $this->service->revertBalance(
            employeeId: $empId,
            leaveTypeId: $leaveTypeId,
            year: $currentYear,
            days: 2.0,
            referenceType: 'test_request',
            referenceId: 99999,
            notes: 'Unit test reversal'
        );

        $afterReversal = $this->service->getRemainingBalance($empId, $leaveTypeId, $currentYear);
        $this->assertEquals($initialBalance, $afterReversal);
    }

    public function testInsufficientLeaveBalanceThrowsException(): void
    {
        $employeeModel = new Employee();
        $emp = $employeeModel->firstWhere('employee_code', 'EMP-0001');
        $empId = (int)$emp['id'];

        $leaveTypeModel = new LeaveType();
        $annualLeave = $leaveTypeModel->firstWhere('code', 'LV-ANN');
        $this->assertNotNull($annualLeave);
        $leaveTypeId = (int)$annualLeave['id'];

        $currentYear = (int)date('Y');
        $currentRemaining = $this->service->getRemainingBalance($empId, $leaveTypeId, $currentYear);

        // Requesting more days than balance should throw RuntimeException
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('غير كافٍ');
        $this->service->deductBalance(
            employeeId: $empId,
            leaveTypeId: $leaveTypeId,
            year: $currentYear,
            days: $currentRemaining + 50.0,
            referenceType: 'test_overdraw',
            referenceId: 88888,
            notes: 'Overdraw test'
        );
    }
}
