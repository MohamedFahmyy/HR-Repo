<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Models\AttendanceAdjustment;
use App\Models\AttendanceRecord;
use App\Models\AuditLog;
use App\Models\Department;
use App\Models\Employee;
use App\Services\AttendanceCalculationService;

class AttendanceController extends Controller
{
    private AttendanceRecord $recordModel;
    private AttendanceAdjustment $adjModel;
    private AttendanceCalculationService $calcService;

    public function __construct()
    {
        $this->recordModel = new AttendanceRecord();
        $this->adjModel = new AttendanceAdjustment();
        $this->calcService = new AttendanceCalculationService();
    }

    public function index(Request $request): Response
    {
        $this->authorize('attendance.view');

        $date = $request->get('date', date('Y-m-d'));
        $filters = [
            'department_id' => $request->get('department_id', ''),
            'status' => $request->get('status', ''),
        ];

        $records = $this->recordModel->getDailyAttendance($date, $filters);
        $departments = (new Department())->all('name_ar', 'ASC');
        $employees = Database::fetchAll("SELECT id, employee_code, CONCAT(first_name_ar, ' ', last_name_ar) as name FROM `employees` WHERE deleted_at IS NULL ORDER BY employee_code ASC");

        // Daily stats summary
        $stats = [
            'total' => count($records),
            'present' => 0,
            'late' => 0,
            'absent' => 0,
            'on_leave' => 0,
            'total_late_minutes' => 0,
            'total_ot_hours' => 0.0,
        ];

        foreach ($records as $r) {
            if ($r['status'] === 'present' || $r['status'] === 'early_leave') $stats['present']++;
            elseif ($r['status'] === 'late') $stats['late']++;
            elseif ($r['status'] === 'absent') $stats['absent']++;
            elseif ($r['status'] === 'on_leave') $stats['on_leave']++;

            $stats['total_late_minutes'] += (int)$r['late_minutes'];
            $stats['total_ot_hours'] += (float)$r['overtime_hours'];
        }

        return $this->view('attendance/index', [
            'title' => 'سجلات الحضور والانصراف اليومية',
            'date' => $date,
            'records' => $records,
            'departments' => $departments,
            'employees' => $employees,
            'filters' => $filters,
            'stats' => $stats,
        ]);
    }

    public function calculate(Request $request): Response
    {
        $this->authorize('attendance.calculate');

        $startDate = $request->post('start_date', date('Y-m-01'));
        $endDate = $request->post('end_date', date('Y-m-d'));

        $count = $this->calcService->recalculatePeriod($startDate, $endDate);
        AuditLog::log('calculate_attendance', 'AttendanceRecord', null, null, ['start' => $startDate, 'end' => $endDate, 'records' => $count]);

        $this->flash('success', "تم احتساب وتحديث سجلات الحضور بنجاح للفترة ({$startDate} إلى {$endDate}). إجمالي السجلات: {$count}.");

        return $this->redirect(url("attendance?date={$endDate}"));
    }

    public function storeManual(Request $request): Response
    {
        $this->authorize('attendance.adjust');

        $data = $this->validate($request, [
            'employee_id' => 'required|integer',
            'date' => 'required|date',
            'check_in' => 'required',
        ]);

        $empId = (int)$data['employee_id'];
        $date = $data['date'];
        $checkIn = $date . ' ' . $data['check_in'];
        $checkOut = $request->post('check_out') ? ($date . ' ' . $request->post('check_out')) : null;

        // Insert or update attendance record
        Database::execute(
            "INSERT INTO `attendance_records` 
             (`employee_id`, `date`, `shift_id`, `check_in`, `check_out`, `status`, `work_hours`, `notes`, `is_manually_adjusted`)
             VALUES (:emp_id, :date, 1, :check_in, :check_out, 'present', 8.00, 'تسجيل يدوي مباشر', 1)
             ON DUPLICATE KEY UPDATE 
                `check_in` = VALUES(`check_in`), 
                `check_out` = VALUES(`check_out`), 
                `status` = 'present', 
                `is_manually_adjusted` = 1, 
                `notes` = 'تعديل يدوي مباشر'",
            [
                'emp_id' => $empId,
                'date' => $date,
                'check_in' => $checkIn,
                'check_out' => $checkOut,
            ]
        );

        AuditLog::log('manual_attendance', 'AttendanceRecord', $empId, null, $data);
        $this->flash('success', 'تم حفظ تسجيل الحضور اليدوي واحتساب السجل.');

        return $this->redirect(url("attendance?date={$date}"));
    }

    public function adjustments(Request $request): Response
    {
        $this->authorize('attendance.view');

        $status = $request->get('status', 'all');
        $adjustments = $this->adjModel->allWithDetails($status);

        return $this->view('attendance/adjustments', [
            'title' => 'طلبات تصحيح الحضور والانصراف',
            'adjustments' => $adjustments,
            'currentStatus' => $status,
        ]);
    }

    public function reviewAdjustment(Request $request, string|int $id): Response
    {
        $this->authorize('attendance.approve');

        $action = $request->post('action'); // approve or reject
        $remarks = $request->post('review_remarks', '');
        $userId = Auth::getInstance()->id();

        $adj = $this->adjModel->findOrFail((int)$id);
        if ($adj['status'] !== 'pending') {
            $this->flash('error', 'تمت مراجعة هذا الطلب مسبقاً.');
            return $this->redirect(url('attendance/adjustments'));
        }

        if ($action === 'approve') {
            Database::transaction(function () use ($adj, $id, $userId, $remarks) {
                // Update adjustment status
                $this->adjModel->update((int)$id, [
                    'status' => 'approved',
                    'reviewed_by' => $userId,
                    'reviewed_at' => date('Y-m-d H:i:s'),
                    'review_remarks' => $remarks,
                ]);

                // Update the attendance record
                Database::execute(
                    "INSERT INTO `attendance_records` 
                     (`employee_id`, `date`, `shift_id`, `check_in`, `check_out`, `status`, `work_hours`, `notes`, `is_manually_adjusted`)
                     VALUES (:emp_id, :date, 1, :check_in, :check_out, 'present', 8.00, 'تصحيح معتمد', 1)
                     ON DUPLICATE KEY UPDATE 
                        `check_in` = COALESCE(:set_check_in, `check_in`), 
                        `check_out` = COALESCE(:set_check_out, `check_out`), 
                        `status` = 'present', 
                        `is_manually_adjusted` = 1,
                        `notes` = 'تم اعتماد طلب تصحيح الحضور'",
                    [
                        'emp_id' => $adj['employee_id'],
                        'date' => $adj['date'],
                        'check_in' => $adj['requested_check_in'],
                        'check_out' => $adj['requested_check_out'],
                        'set_check_in' => $adj['requested_check_in'],
                        'set_check_out' => $adj['requested_check_out'],
                    ]
                );
            });

            AuditLog::log('approve_adjustment', 'AttendanceAdjustment', $id, $adj);
            $this->flash('success', 'تم اعتماد طلب تصحيح الحضور وتحديث السجل بنجاح.');
        } else {
            $this->adjModel->update((int)$id, [
                'status' => 'rejected',
                'reviewed_by' => $userId,
                'reviewed_at' => date('Y-m-d H:i:s'),
                'review_remarks' => $remarks,
            ]);

            AuditLog::log('reject_adjustment', 'AttendanceAdjustment', $id, $adj);
            $this->flash('info', 'تم رفض طلب تصحيح الحضور.');
        }

        return $this->redirect(url('attendance/adjustments'));
    }
}
