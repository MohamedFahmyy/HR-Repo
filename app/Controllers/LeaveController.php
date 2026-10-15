<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Models\AuditLog;
use App\Models\LeaveBalance;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Services\AttendanceCalculationService;
use App\Services\LeaveBalanceService;
use DateTime;

class LeaveController extends Controller
{
    private LeaveRequest $leaveRequestModel;
    private LeaveType $leaveTypeModel;
    private LeaveBalanceService $balanceService;

    public function __construct()
    {
        $this->leaveRequestModel = new LeaveRequest();
        $this->leaveTypeModel = new LeaveType();
        $this->balanceService = new LeaveBalanceService();
    }

    public function index(Request $request): Response
    {
        $this->authorize('leaves.view');

        $filters = [
            'status' => $request->get('status', ''),
            'employee_id' => $request->get('employee_id', ''),
        ];

        $requests = $this->leaveRequestModel->allWithDetails($filters);
        $leaveTypes = $this->leaveTypeModel->where('is_active', 1);
        $employees = Database::fetchAll("SELECT id, employee_code, CONCAT(first_name_ar, ' ', last_name_ar) as name FROM `employees` WHERE deleted_at IS NULL");

        // Leave balances summary
        $balances = Database::fetchAll(
            "SELECT b.*, e.employee_code, CONCAT(e.first_name_ar, ' ', e.last_name_ar) as employee_name, t.name_ar as leave_name, t.color
             FROM `leave_balances` b
             JOIN `employees` e ON e.id = b.employee_id
             JOIN `leave_types` t ON t.id = b.leave_type_id
             WHERE b.year = :year
             ORDER BY e.employee_code ASC",
            ['year' => date('Y')]
        );

        return $this->view('leaves/index', [
            'title' => 'إدارة الإجازات والأرصدة',
            'requests' => $requests,
            'leaveTypes' => $leaveTypes,
            'employees' => $employees,
            'balances' => $balances,
            'filters' => $filters,
        ]);
    }

    public function store(Request $request): Response
    {
        $this->authorize('leaves.request');

        $data = $this->validate($request, [
            'employee_id' => 'required|integer',
            'leave_type_id' => 'required|integer',
            'start_date' => 'required|date',
            'end_date' => 'required|date',
            'reason' => 'required',
        ]);

        $empId = (int)$data['employee_id'];
        $typeId = (int)$data['leave_type_id'];
        $startDate = $data['start_date'];
        $endDate = $data['end_date'];

        $startDt = new DateTime($startDate);
        $endDt = new DateTime($endDate);
        if ($endDt < $startDt) {
            $this->flash('error', 'تاريخ نهاية الإجازة يجب ألا يسبق تاريخ البداية.');
            return $this->redirect(url('leaves'));
        }

        $totalDays = (float)($endDt->diff($startDt)->days + 1);

        // Check for overlapping approved/pending requests
        $overlap = (int)Database::fetchColumn(
            "SELECT COUNT(*) FROM `leave_requests` 
             WHERE employee_id = :emp_id 
               AND status IN ('pending', 'approved') 
               AND ((start_date BETWEEN :start AND :end) OR (end_date BETWEEN :start AND :end))",
            ['emp_id' => $empId, 'start' => $startDate, 'end' => $endDate]
        );

        if ($overlap > 0) {
            $this->flash('error', 'يوجد طلب إجازة مسجل بالفعل يتعارض مع التواريخ المحددة.');
            return $this->redirect(url('leaves'));
        }

        $reqId = $this->leaveRequestModel->create([
            'employee_id' => $empId,
            'leave_type_id' => $typeId,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'total_days' => $totalDays,
            'reason' => trim($data['reason']),
            'status' => 'pending',
        ]);

        AuditLog::log('request_leave', 'LeaveRequest', $reqId, null, $data);
        $this->flash('success', "تم تقديم طلب الإجازة بنجاح ({$totalDays} أيام). بانتظار الاعتماد.");

        return $this->redirect(url('leaves'));
    }

    public function review(Request $request, string|int $id): Response
    {
        $this->authorize('leaves.approve');

        $action = $request->post('action'); // approve or reject
        $remarks = $request->post('rejection_reason', '');
        $userId = Auth::getInstance()->id();

        $leaveReq = $this->leaveRequestModel->findOrFail((int)$id);
        if ($leaveReq['status'] !== 'pending') {
            $this->flash('error', 'تم البت في هذا الطلب مسبقاً.');
            return $this->redirect(url('leaves'));
        }

        if ($action === 'approve') {
            try {
                // Deduct balance and update status inside transaction
                $this->balanceService->deductBalance(
                    (int)$leaveReq['employee_id'],
                    (int)$leaveReq['leave_type_id'],
                    (float)$leaveReq['total_days'],
                    (int)$id,
                    'خصم رصيد الإجازة المعتمدة'
                );

                $this->leaveRequestModel->update((int)$id, [
                    'status' => 'approved',
                    'approved_by' => $userId,
                    'approved_at' => date('Y-m-d H:i:s'),
                ]);

                // Recalculate attendance for the leave dates
                (new AttendanceCalculationService())->recalculatePeriod($leaveReq['start_date'], $leaveReq['end_date']);

                AuditLog::log('approve_leave', 'LeaveRequest', $id, $leaveReq);
                $this->flash('success', 'تم اعتماد طلب الإجازة وخصم الرصيد بنجاح.');
            } catch (\Throwable $e) {
                $this->flash('error', $e->getMessage());
            }
        } else {
            $this->leaveRequestModel->update((int)$id, [
                'status' => 'rejected',
                'rejection_reason' => $remarks,
            ]);

            AuditLog::log('reject_leave', 'LeaveRequest', $id, $leaveReq);
            $this->flash('info', 'تم رفض طلب الإجازة.');
        }

        return $this->redirect(url('leaves'));
    }
}
