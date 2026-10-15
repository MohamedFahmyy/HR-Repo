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
use App\Models\Employee;
use App\Models\LeaveBalance;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\Payslip;
use App\Models\Shift;
use DateTime;

class EmployeeSelfServiceController extends Controller
{
    private function getAuthenticatedEmployeeId(): int
    {
        $empId = Auth::getInstance()->employeeId();
        if (!$empId) {
            $this->flash('error', 'حسابك غير مرتبط بملف موظف نشط.');
            $this->redirect(url('login'))->send();
            exit;
        }
        return $empId;
    }

    public function index(Request $request): Response
    {
        $empId = $this->getAuthenticatedEmployeeId();

        $employee = (new Employee())->getDetailedProfile($empId);
        $leaveBalances = (new LeaveBalance())->getBalancesForEmployee($empId);

        // Recent punches for this employee
        $recentPunches = Database::fetchAll(
            "SELECT r.*, d.name as device_name 
             FROM `biometric_raw_events` r
             JOIN `biometric_employee_mappings` m ON m.device_id = r.device_id AND m.device_user_id = r.device_user_id
             JOIN `biometric_devices` d ON d.id = r.device_id
             WHERE m.employee_id = :id
             ORDER BY r.punch_time DESC
             LIMIT 10",
            ['id' => $empId]
        );

        // Recent attendance records
        $recentAttendance = Database::fetchAll(
            "SELECT a.*, s.name_ar as shift_name
             FROM `attendance_records` a
             LEFT JOIN `shifts` s ON s.id = a.shift_id
             WHERE a.employee_id = :id
             ORDER BY a.date DESC
             LIMIT 10",
            ['id' => $empId]
        );

        // My Leave Requests
        $myLeaveRequests = (new LeaveRequest())->allWithDetails(['employee_id' => $empId]);

        // My Attendance Adjustments
        $myAdjustments = Database::fetchAll(
            "SELECT adj.*, rev.full_name_ar as reviewer_name 
             FROM `attendance_adjustments` adj
             LEFT JOIN `users` rev ON rev.id = adj.reviewed_by
             WHERE adj.employee_id = :id
             ORDER BY adj.id DESC
             LIMIT 10",
            ['id' => $empId]
        );

        // My Payslips
        $myPayslips = (new Payslip())->getPayslipsForEmployee($empId);

        $leaveTypes = (new LeaveType())->where('is_active', 1);

        return $this->view('ess/index', [
            'title' => 'بوابة الخدمة الذاتية للموظف',
            'employee' => $employee,
            'leaveBalances' => $leaveBalances,
            'recentPunches' => $recentPunches,
            'recentAttendance' => $recentAttendance,
            'myLeaveRequests' => $myLeaveRequests,
            'myAdjustments' => $myAdjustments,
            'myPayslips' => $myPayslips,
            'leaveTypes' => $leaveTypes,
        ]);
    }

    public function submitLeave(Request $request): Response
    {
        $empId = $this->getAuthenticatedEmployeeId();

        $data = $this->validate($request, [
            'leave_type_id' => 'required|integer',
            'start_date' => 'required|date',
            'end_date' => 'required|date',
            'reason' => 'required',
        ]);

        $startDt = new DateTime($data['start_date']);
        $endDt = new DateTime($data['end_date']);
        if ($endDt < $startDt) {
            $this->flash('error', 'تاريخ نهاية الإجازة يجب ألا يسبق تاريخ البداية.');
            return $this->redirect(url('ess'));
        }

        $totalDays = (float)($endDt->diff($startDt)->days + 1);

        $id = (new LeaveRequest())->create([
            'employee_id' => $empId,
            'leave_type_id' => (int)$data['leave_type_id'],
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'],
            'total_days' => $totalDays,
            'reason' => trim($data['reason']),
            'status' => 'pending',
        ]);

        AuditLog::log('ess_request_leave', 'LeaveRequest', $id, null, $data);
        $this->flash('success', 'تم تقديم طلب الإجازة بنجاح، وسيتم إشعارك فور المراجعة.');

        return $this->redirect(url('ess'));
    }

    public function submitAdjustment(Request $request): Response
    {
        $empId = $this->getAuthenticatedEmployeeId();

        $data = $this->validate($request, [
            'date' => 'required|date',
            'adjustment_type' => 'required|in:missing_punch,excused_late,early_leave,manual_attendance,dispute',
            'reason' => 'required',
        ]);

        $userId = Auth::getInstance()->id();
        $date = $data['date'];
        $checkIn = $request->post('requested_check_in') ? ($date . ' ' . $request->post('requested_check_in')) : null;
        $checkOut = $request->post('requested_check_out') ? ($date . ' ' . $request->post('requested_check_out')) : null;

        $existing = Database::fetchOne(
            "SELECT * FROM `attendance_records` WHERE employee_id = :id AND date = :d LIMIT 1",
            ['id' => $empId, 'd' => $date]
        );

        $id = (new AttendanceAdjustment())->create([
            'record_id' => $existing['id'] ?? null,
            'employee_id' => $empId,
            'date' => $date,
            'adjustment_type' => $data['adjustment_type'],
            'original_check_in' => $existing['check_in'] ?? null,
            'original_check_out' => $existing['check_out'] ?? null,
            'requested_check_in' => $checkIn,
            'requested_check_out' => $checkOut,
            'reason' => trim($data['reason']),
            'status' => 'pending',
            'requested_by' => $userId,
        ]);

        AuditLog::log('ess_request_adjustment', 'AttendanceAdjustment', $id, null, $data);
        $this->flash('success', 'تم تقديم طلب تصحيح الحضور بنجاح لمراجعة مديرك المباشر.');

        return $this->redirect(url('ess'));
    }
}
