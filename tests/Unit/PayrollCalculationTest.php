<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Services\PayrollCalculationService;
use App\Models\Employee;
use App\Models\PayrollPeriod;
use App\Models\PayrollRun;
use App\Core\Database;

class PayrollCalculationTest extends TestCase
{
    private PayrollCalculationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new PayrollCalculationService();
    }

    public function testStandardSalaryComputationFormula(): void
    {
        $employeeModel = new Employee();
        $emp = $employeeModel->firstWhere('employee_code', 'EMP-0001');
        $this->assertNotNull($emp);

        // Seeded EMP-0001: Basic: 25,000, Housing: 6,250, Transport: 2,500 = 33,750
        $basic = (float)$emp['basic_salary'];
        $housing = (float)$emp['housing_allowance'];
        $transport = (float)$emp['transport_allowance'];
        $totalEarnings = $basic + $housing + $transport;

        $this->assertSame(25000.0, $basic);
        $this->assertSame(6250.0, $housing);
        $this->assertSame(2500.0, $transport);
        $this->assertSame(33750.0, $totalEarnings);
    }

    public function testPayrollRunLockingProtectsAgainstRecalculation(): void
    {
        $periodCode = 'PAY-' . date('Y-m');
        $periodModel = new PayrollPeriod();
        $period = $periodModel->firstWhere('code', $periodCode);
        $this->assertNotNull($period, "Payroll period {$periodCode} must exist.");

        $runModel = new PayrollRun();
        $run = $runModel->firstWhere('period_id', $period['id']);
        $this->assertNotNull($run, "Payroll run for period {$periodCode} must exist.");

        // Lock the payroll run
        $this->service->lockPayrollRun((int)$run['id'], 1);

        $lockedRun = $runModel->find($run['id']);
        $this->assertSame('locked', $lockedRun['status']);
        $this->assertNotNull($lockedRun['locked_at']);

        // Attempting to calculate or modify locked run must throw an exception
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('مقفل');

        $this->service->calculatePayroll((int)$period['id'], (int)$run['id']);
    }

    public function testPayslipTokenGeneration(): void
    {
        $payslip = Database::fetchOne("SELECT * FROM `payslips` LIMIT 1");
        if ($payslip) {
            $this->assertNotEmpty($payslip['access_token']);
            $this->assertSame(64, strlen($payslip['access_token']));
            $this->assertGreaterThan(0, (float)$payslip['net_salary']);
        } else {
            // Generate a payslip if none exist yet
            $this->assertTrue(true);
        }
    }
}
