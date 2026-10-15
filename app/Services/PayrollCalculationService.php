<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Models\Employee;
use App\Models\PayrollItem;
use App\Models\PayrollPeriod;
use App\Models\PayrollRun;
use App\Models\Payslip;
use RuntimeException;

class PayrollCalculationService
{
    private PayrollPeriod $periodModel;
    private PayrollRun $runModel;
    private PayrollItem $itemModel;
    private Payslip $payslipModel;
    private OvertimeCalculationService $otService;

    public function __construct(
        ?PayrollPeriod $periodModel = null,
        ?PayrollRun $runModel = null,
        ?PayrollItem $itemModel = null,
        ?Payslip $payslipModel = null,
        ?OvertimeCalculationService $otService = null
    ) {
        $this->periodModel = $periodModel ?? new PayrollPeriod();
        $this->runModel = $runModel ?? new PayrollRun();
        $this->itemModel = $itemModel ?? new PayrollItem();
        $this->payslipModel = $payslipModel ?? new Payslip();
        $this->otService = $otService ?? new OvertimeCalculationService();
    }

    public function calculatePayroll(int $periodId, ?int $runId = null): array
    {
        return $this->calculatePayrollRun($periodId, '', $runId);
    }

    /**
     * Calculate or recalculate an entire payroll run for a period.
     */
    public function calculatePayrollRun(int $periodId, string $title = '', ?int $runId = null): array
    {
        $period = $this->periodModel->findOrFail($periodId);

        // Check if run exists and is locked
        if ($runId !== null) {
            $existingRun = $this->runModel->findOrFail($runId);
            if ($existingRun['status'] === 'locked') {
                throw new RuntimeException("لا يمكن إعادة احتساب مسير رواتب مقفل نهائياً (#{$runId}).");
            }
        }

        return Database::transaction(function () use ($period, $title, $runId) {
            $periodId = (int)$period['id'];
            $startDate = $period['start_date'];
            $endDate = $period['end_date'];

            if ($runId === null) {
                $runNumber = 'RUN-' . date('Ym') . '-' . sprintf('%03d', rand(100, 999));
                $runId = (int)$this->runModel->create([
                    'period_id' => $periodId,
                    'run_number' => $runNumber,
                    'title' => $title ?: "مسير رواتب {$period['name_ar']}",
                    'status' => 'draft',
                ]);
            } else {
                // Clear existing draft items
                Database::execute("DELETE FROM `payroll_items` WHERE `payroll_run_id` = :run_id", ['run_id' => $runId]);
            }

            // Fetch all active employees
            $employees = Database::fetchAll(
                "SELECT e.* FROM `employees` e WHERE e.deleted_at IS NULL AND e.employment_status = 'active' ORDER BY e.id ASC"
            );

            $totalGross = 0.00;
            $totalDeductions = 0.00;
            $totalNet = 0.00;
            $count = 0;

            foreach ($employees as $emp) {
                $itemData = $this->calculateEmployeePayroll($emp, $startDate, $endDate, $runId);
                $itemId = $this->itemModel->create($itemData);

                // Generate / update payslip
                $payslipNumber = 'PS-' . $period['code'] . '-' . $emp['employee_code'];
                $token = bin2hex(random_bytes(24));

                Database::execute(
                    "INSERT INTO `payslips` (`payroll_item_id`, `employee_id`, `period_id`, `payslip_number`, `issue_date`, `access_token`)
                     VALUES (:item_id, :emp_id, :period_id, :ps_num, :issue_date, :token)
                     ON DUPLICATE KEY UPDATE `access_token` = VALUES(`access_token`)",
                    [
                        'item_id' => $itemId,
                        'emp_id' => $emp['id'],
                        'period_id' => $periodId,
                        'ps_num' => $payslipNumber,
                        'issue_date' => $period['payment_date'],
                        'token' => $token,
                    ]
                );

                $totalGross += $itemData['gross_salary'];
                $totalDeductions += $itemData['total_deductions'];
                $totalNet += $itemData['net_salary'];
                $count++;
            }

            // Update Run Totals
            $this->runModel->update($runId, [
                'total_gross' => round($totalGross, 2),
                'total_deductions' => round($totalDeductions, 2),
                'total_net' => round($totalNet, 2),
                'employee_count' => $count,
                'status' => 'calculated',
            ]);

            return [
                'run_id' => $runId,
                'employee_count' => $count,
                'total_gross' => round($totalGross, 2),
                'total_deductions' => round($totalDeductions, 2),
                'total_net' => round($totalNet, 2),
            ];
        });
    }

    /**
     * Compute single employee payroll item for a period.
     */
    public function calculateEmployeePayroll(array $employee, string $startDate, string $endDate, int $runId): array
    {
        $empId = (int)$employee['id'];
        $basicSalary = (float)$employee['basic_salary'];
        $housing = (float)$employee['housing_allowance'];
        $transport = (float)$employee['transport_allowance'];
        $otherAllowances = (float)$employee['other_allowances'];

        $daysPerMonth = (int)config('payroll.default_days_per_month', 30);
        $hoursPerDay = (int)config('payroll.hours_per_day', 8);

        // Daily rate and hourly rate for deductions / OT
        $dailyRate = round($basicSalary / $daysPerMonth, 2);
        $hourlyRate = round(($basicSalary + $housing) / ($daysPerMonth * $hoursPerDay), 2);

        // 1. Attendance Metrics (Absences and Lateness)
        $attStats = Database::fetchOne(
            "SELECT 
                COUNT(CASE WHEN status = 'absent' THEN 1 END) as absent_days,
                SUM(late_minutes) as total_late_minutes
             FROM `attendance_records`
             WHERE employee_id = :emp_id AND date BETWEEN :start AND :end",
            ['emp_id' => $empId, 'start' => $startDate, 'end' => $endDate]
        );

        $absentDays = (float)($attStats['absent_days'] ?? 0);
        $totalLateMinutes = (int)($attStats['total_late_minutes'] ?? 0);

        // Absence Deduction
        $absenceDeduction = round($absentDays * $dailyRate, 2);

        // Lateness Deduction based on tiers
        $lateDeduction = $this->calculateLatenessDeduction($totalLateMinutes, $dailyRate);

        // 2. Unpaid Leave Days
        $unpaidDays = (float)Database::fetchColumn(
            "SELECT COALESCE(SUM(lr.total_days), 0)
             FROM `leave_requests` lr
             JOIN `leave_types` lt ON lt.id = lr.leave_type_id
             WHERE lr.employee_id = :emp_id 
               AND lt.is_paid = 0
               AND lr.status = 'approved'
               AND lr.start_date <= :end AND lr.end_date >= :start",
            ['emp_id' => $empId, 'start' => $startDate, 'end' => $endDate]
        );

        $unpaidDeduction = round($unpaidDays * $dailyRate, 2);

        // 3. Overtime Pay
        $otData = $this->otService->calculateOvertimeForPeriod($empId, $startDate, $endDate, $hourlyRate);
        $overtimeHours = (float)$otData['hours'];
        $overtimePay = (float)$otData['pay'];

        // Totals
        $grossSalary = round($basicSalary + $housing + $transport + $otherAllowances + $overtimePay, 2);
        $totalDeductions = round($absenceDeduction + $lateDeduction + $unpaidDeduction, 2);
        $netSalary = max(0.00, round($grossSalary - $totalDeductions, 2));

        return [
            'payroll_run_id' => $runId,
            'employee_id' => $empId,
            'basic_salary' => $basicSalary,
            'housing_allowance' => $housing,
            'transport_allowance' => $transport,
            'other_allowances' => $otherAllowances,
            'overtime_hours' => $overtimeHours,
            'overtime_pay' => $overtimePay,
            'bonus_amount' => 0.00,
            'absence_days' => $absentDays,
            'absence_deduction' => $absenceDeduction,
            'late_minutes' => $totalLateMinutes,
            'late_deduction' => $lateDeduction,
            'unpaid_leave_deduction' => $unpaidDeduction,
            'advance_deduction' => 0.00,
            'other_deductions' => 0.00,
            'gross_salary' => $grossSalary,
            'total_deductions' => $totalDeductions,
            'net_salary' => $netSalary,
            'payment_status' => 'unpaid',
            'notes' => null,
        ];
    }

    private function calculateLatenessDeduction(int $lateMinutes, float $dailyRate): float
    {
        if ($lateMinutes <= 15) {
            return 0.00;
        }

        // Tiers: 16-30 mins = 0.25 day, 31-60 mins = 0.5 day, >60 mins = 1 day per hour
        if ($lateMinutes <= 30) {
            return round($dailyRate * 0.25, 2);
        }
        if ($lateMinutes <= 60) {
            return round($dailyRate * 0.50, 2);
        }

        $daysDeduct = ceil($lateMinutes / 60) * 0.50;
        return round($dailyRate * $daysDeduct, 2);
    }

    /**
     * Approve payroll run.
     */
    public function approvePayrollRun(int $runId, int $userId): bool
    {
        $run = $this->runModel->findOrFail($runId);
        if ($run['status'] === 'locked') {
            throw new RuntimeException("المسير مقفل بالفعل.");
        }

        return $this->runModel->update($runId, [
            'status' => 'approved',
            'approved_by' => $userId,
            'approved_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Lock payroll run to make it immutable.
     */
    public function lockPayrollRun(int $runId, int $userId): bool
    {
        return $this->runModel->update($runId, [
            'status' => 'locked',
            'locked_by' => $userId,
            'locked_at' => date('Y-m-d H:i:s'),
        ]);
    }
}
