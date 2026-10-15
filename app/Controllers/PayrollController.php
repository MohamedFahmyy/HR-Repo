<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Models\AuditLog;
use App\Models\PayrollItem;
use App\Models\PayrollPeriod;
use App\Models\PayrollRun;
use App\Models\Payslip;
use App\Services\PayrollCalculationService;

class PayrollController extends Controller
{
    private PayrollPeriod $periodModel;
    private PayrollRun $runModel;
    private PayrollItem $itemModel;
    private Payslip $payslipModel;
    private PayrollCalculationService $payrollService;

    public function __construct()
    {
        $this->periodModel = new PayrollPeriod();
        $this->runModel = new PayrollRun();
        $this->itemModel = new PayrollItem();
        $this->payslipModel = new Payslip();
        $this->payrollService = new PayrollCalculationService();
    }

    public function index(Request $request): Response
    {
        $this->authorize('payroll.view');

        $periods = $this->periodModel->all('start_date', 'DESC');
        $runs = $this->runModel->allWithPeriod();

        return $this->view('payroll/index', [
            'title' => 'إدارة الرواتب ومسيرات الصرف',
            'periods' => $periods,
            'runs' => $runs,
        ]);
    }

    public function storePeriod(Request $request): Response
    {
        $this->authorize('payroll.calculate');

        $data = $this->validate($request, [
            'code' => 'required|unique:payroll_periods,code',
            'name_ar' => 'required',
            'name_en' => 'required',
            'start_date' => 'required|date',
            'end_date' => 'required|date',
            'payment_date' => 'required|date',
        ]);

        $id = $this->periodModel->create($data);
        AuditLog::log('create_payroll_period', 'PayrollPeriod', $id, null, $data);
        $this->flash('success', "تم إنشاء فترة الرواتب {$data['name_ar']} بنجاح.");

        return $this->redirect(url('payroll'));
    }

    public function calculate(Request $request): Response
    {
        $this->authorize('payroll.calculate');

        $periodId = (int)$request->post('period_id');
        $runId = $request->post('run_id') ? (int)$request->post('run_id') : null;
        $title = $request->post('title', '');

        try {
            $result = $this->payrollService->calculatePayrollRun($periodId, $title, $runId);
            AuditLog::log('calculate_payroll', 'PayrollRun', $result['run_id'], null, $result);
            $this->flash('success', "تم احتساب مسير الرواتب بنجاح لعدد ({$result['employee_count']}) موظف. إجمالي الصافي: " . number_format($result['total_net'], 2) . " " . config('payroll.currency_symbol'));
            return $this->redirect(url("payroll/runs/{$result['run_id']}"));
        } catch (\Throwable $e) {
            $this->flash('error', $e->getMessage());
            return $this->redirect(url('payroll'));
        }
    }

    public function showRun(Request $request, string|int $id): Response
    {
        $this->authorize('payroll.view');

        $run = $this->runModel->findOrFail((int)$id);
        $period = $this->periodModel->findOrFail((int)$run['period_id']);
        $items = $this->itemModel->getItemsForRun((int)$id);

        return $this->view('payroll/show_run', [
            'title' => "تفاصيل مسير الرواتب: {$run['run_number']}",
            'run' => $run,
            'period' => $period,
            'items' => $items,
        ]);
    }

    public function approveRun(Request $request, string|int $id): Response
    {
        $this->authorize('payroll.approve');

        $userId = Auth::getInstance()->id();
        $this->payrollService->approvePayrollRun((int)$id, $userId);

        AuditLog::log('approve_payroll', 'PayrollRun', $id);
        $this->flash('success', 'تم اعتماد مسير الرواتب بنجاح وإتاحته للقفل والصرف.');

        return $this->redirect(url("payroll/runs/{$id}"));
    }

    public function lockRun(Request $request, string|int $id): Response
    {
        $this->authorize('payroll.lock');

        $userId = Auth::getInstance()->id();
        $this->payrollService->lockPayrollRun((int)$id, $userId);

        AuditLog::log('lock_payroll', 'PayrollRun', $id);
        $this->flash('success', 'تم قفل مسير الرواتب نهائياً لحماية سلامة البيانات ومنع أي تعديل لاحق.');

        return $this->redirect(url("payroll/runs/{$id}"));
    }

    public function payslip(Request $request, string|int $itemId): Response
    {
        $item = $this->itemModel->getDetail((int)$itemId);
        if (!$item) {
            $this->flash('error', 'بيانات قسيمة الراتب غير موجودة.');
            return $this->redirect(url('payroll'));
        }

        // Check authorization: Admin/HR or employee owner
        $currentEmpId = Auth::getInstance()->employeeId();
        if (!Auth::getInstance()->can('payroll.view') && $currentEmpId !== (int)$item['employee_id']) {
            return $this->view('errors/403', ['permission' => 'payroll.view'], 'layouts/error')->setStatusCode(403);
        }

        return $this->view('payroll/payslip', [
            'title' => "قسيمة الراتب: {$item['payslip_number']} - {$item['first_name_ar']} {$item['last_name_ar']}",
            'item' => $item,
        ], 'layouts/print');
    }

    public function exportCsv(Request $request, string|int $id): Response
    {
        $this->authorize('payroll.export');

        $run = $this->runModel->findOrFail((int)$id);
        $period = $this->periodModel->findOrFail((int)$run['period_id']);
        $items = $this->itemModel->getItemsForRun((int)$id);

        $filename = "payroll_{$run['run_number']}_wps.csv";
        $output = fopen('php://memory', 'w');

        // Add UTF-8 BOM for Excel Arabic support
        fputs($output, "\xEF\xBB\xBF");

        // Header Row
        fputcsv($output, [
            'Employee Code',
            'Employee Name (AR)',
            'Employee Name (EN)',
            'Bank Name',
            'IBAN',
            'Basic Salary',
            'Housing Allowance',
            'Transport Allowance',
            'Other Allowances',
            'Overtime Hours',
            'Overtime Pay',
            'Gross Salary',
            'Absence Deduction',
            'Lateness Deduction',
            'Unpaid Leave Deduction',
            'Total Deductions',
            'Net Salary',
        ]);

        foreach ($items as $item) {
            fputcsv($output, [
                $item['employee_code'],
                $item['first_name_ar'] . ' ' . $item['last_name_ar'],
                $item['first_name_en'] . ' ' . $item['last_name_en'],
                $item['bank_name'] ?? 'N/A',
                $item['iban'] ?? 'N/A',
                number_format((float)$item['basic_salary'], 2, '.', ''),
                number_format((float)$item['housing_allowance'], 2, '.', ''),
                number_format((float)$item['transport_allowance'], 2, '.', ''),
                number_format((float)$item['other_allowances'], 2, '.', ''),
                $item['overtime_hours'],
                number_format((float)$item['overtime_pay'], 2, '.', ''),
                number_format((float)$item['gross_salary'], 2, '.', ''),
                number_format((float)$item['absence_deduction'], 2, '.', ''),
                number_format((float)$item['late_deduction'], 2, '.', ''),
                number_format((float)$item['unpaid_leave_deduction'], 2, '.', ''),
                number_format((float)$item['total_deductions'], 2, '.', ''),
                number_format((float)$item['net_salary'], 2, '.', ''),
            ]);
        }

        rewind($output);
        $csvContent = stream_get_contents($output);
        fclose($output);

        AuditLog::log('export_payroll', 'PayrollRun', $id);

        return new Response($csvContent, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }
}
