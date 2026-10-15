<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Models\AuditLog;
use App\Models\OvertimeRequest;
use DateTime;

class OvertimeController extends Controller
{
    private OvertimeRequest $otModel;

    public function __construct()
    {
        $this->otModel = new OvertimeRequest();
    }

    public function index(Request $request): Response
    {
        $this->authorize('overtime.view');

        $filters = [
            'status' => $request->get('status', ''),
            'employee_id' => $request->get('employee_id', ''),
        ];

        $requests = $this->otModel->allWithDetails($filters);
        $employees = Database::fetchAll("SELECT id, employee_code, CONCAT(first_name_ar, ' ', last_name_ar) as name FROM `employees` WHERE deleted_at IS NULL");

        return $this->view('overtime/index', [
            'title' => 'إدارة ساعات العمل الإضافي',
            'requests' => $requests,
            'employees' => $employees,
            'filters' => $filters,
        ]);
    }

    public function store(Request $request): Response
    {
        $this->authorize('overtime.view');

        $data = $this->validate($request, [
            'employee_id' => 'required|integer',
            'date' => 'required|date',
            'start_time' => 'required',
            'end_time' => 'required',
            'reason' => 'required',
        ]);

        $startDt = new DateTime($data['date'] . ' ' . $data['start_time']);
        $endDt = new DateTime($data['date'] . ' ' . $data['end_time']);
        if ($endDt <= $startDt) {
            $endDt->modify('+1 day'); // Overnight overtime
        }

        $hours = round(($endDt->getTimestamp() - $startDt->getTimestamp()) / 3600, 2);
        $type = $request->post('overtime_type', 'regular');
        $multiplier = match ($type) {
            'rest_day' => (float)config('attendance.overtime.rest_day_multiplier', 2.0),
            'holiday' => (float)config('attendance.overtime.holiday_multiplier', 2.5),
            default => (float)config('attendance.overtime.regular_multiplier', 1.5),
        };

        $id = $this->otModel->create([
            'employee_id' => (int)$data['employee_id'],
            'date' => $data['date'],
            'start_time' => $data['start_time'],
            'end_time' => $data['end_time'],
            'hours' => $hours,
            'overtime_type' => $type,
            'multiplier' => $multiplier,
            'reason' => trim($data['reason']),
            'status' => 'pending',
        ]);

        AuditLog::log('request_overtime', 'OvertimeRequest', $id, null, $data);
        $this->flash('success', "تم تقديم طلب العمل الإضافي بنجاح ({$hours} ساعة). بانتظار الاعتماد.");

        return $this->redirect(url('overtime'));
    }

    public function review(Request $request, string|int $id): Response
    {
        $this->authorize('overtime.approve');

        $action = $request->post('action'); // approve or reject
        $remarks = $request->post('rejection_reason', '');
        $userId = Auth::getInstance()->id();

        $ot = $this->otModel->findOrFail((int)$id);
        if ($ot['status'] !== 'pending') {
            $this->flash('error', 'تمت مراجعة هذا الطلب مسبقاً.');
            return $this->redirect(url('overtime'));
        }

        if ($action === 'approve') {
            $this->otModel->update((int)$id, [
                'status' => 'approved',
                'approved_by' => $userId,
                'approved_at' => date('Y-m-d H:i:s'),
            ]);

            AuditLog::log('approve_overtime', 'OvertimeRequest', $id, $ot);
            $this->flash('success', 'تم اعتماد طلب العمل الإضافي بنجاح.');
        } else {
            $this->otModel->update((int)$id, [
                'status' => 'rejected',
                'rejection_reason' => $remarks,
            ]);

            AuditLog::log('reject_overtime', 'OvertimeRequest', $id, $ot);
            $this->flash('info', 'تم رفض طلب العمل الإضافي.');
        }

        return $this->redirect(url('overtime'));
    }
}
