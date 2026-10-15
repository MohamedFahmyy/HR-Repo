<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Models\AttendancePolicy;
use App\Models\AuditLog;
use App\Models\Holiday;
use App\Models\Shift;

class ShiftController extends Controller
{
    private Shift $shiftModel;
    private AttendancePolicy $policyModel;
    private Holiday $holidayModel;

    public function __construct()
    {
        $this->shiftModel = new Shift();
        $this->policyModel = new AttendancePolicy();
        $this->holidayModel = new Holiday();
    }

    public function index(Request $request): Response
    {
        $this->authorize('shifts.manage');

        $shifts = $this->shiftModel->all('id', 'ASC');
        $policies = $this->policyModel->all('id', 'ASC');
        $holidays = $this->holidayModel->all('start_date', 'ASC');
        $employees = Database::fetchAll("SELECT id, employee_code, CONCAT(first_name_ar, ' ', last_name_ar) as name FROM `employees` WHERE deleted_at IS NULL");

        $assignments = Database::fetchAll(
            "SELECT sa.*, 
                    CONCAT(e.first_name_ar, ' ', e.last_name_ar) as employee_name, e.employee_code,
                    s.name_ar as shift_name, s.start_time, s.end_time, s.color
             FROM `shift_assignments` sa
             JOIN `employees` e ON e.id = sa.employee_id
             JOIN `shifts` s ON s.id = sa.shift_id
             ORDER BY sa.id DESC
             LIMIT 50"
        );

        return $this->view('shifts/index', [
            'title' => 'إدارة الورديات وتقويم العمل',
            'shifts' => $shifts,
            'policies' => $policies,
            'holidays' => $holidays,
            'employees' => $employees,
            'assignments' => $assignments,
        ]);
    }

    public function storeShift(Request $request): Response
    {
        $this->authorize('shifts.manage');

        $data = $this->validate($request, [
            'code' => 'required|unique:shifts,code',
            'name_ar' => 'required',
            'name_en' => 'required',
            'start_time' => 'required',
            'end_time' => 'required',
            'working_hours' => 'required|numeric|min:1',
        ]);

        $isOvernight = (bool)$request->post('is_overnight', 0);
        $id = $this->shiftModel->create([
            'code' => trim($data['code']),
            'name_ar' => trim($data['name_ar']),
            'name_en' => trim($data['name_en']),
            'start_time' => $data['start_time'],
            'end_time' => $data['end_time'],
            'is_overnight' => $isOvernight ? 1 : 0,
            'working_hours' => (float)$data['working_hours'],
            'break_duration_minutes' => (int)$request->post('break_duration_minutes', 60),
            'color' => $request->post('color', '#0d6efd'),
        ]);

        AuditLog::log('create_shift', 'Shift', $id, null, $data);
        $this->flash('success', "تم إضافة الوردية {$data['name_ar']} بنجاح.");

        return $this->redirect(url('shifts'));
    }

    public function assignShift(Request $request): Response
    {
        $this->authorize('shifts.manage');

        $data = $this->validate($request, [
            'employee_id' => 'required|integer',
            'shift_id' => 'required|integer',
            'start_date' => 'required|date',
        ]);

        $id = Database::execute(
            "INSERT INTO `shift_assignments` (`employee_id`, `shift_id`, `start_date`, `end_date`, `policy_id`) 
             VALUES (:emp_id, :shift_id, :start_date, :end_date, :policy_id)",
            [
                'emp_id' => (int)$data['employee_id'],
                'shift_id' => (int)$data['shift_id'],
                'start_date' => $data['start_date'],
                'end_date' => $request->post('end_date') ?: null,
                'policy_id' => $request->post('policy_id') ?: 1,
            ]
        );

        AuditLog::log('assign_shift', 'ShiftAssignment', null, null, $data);
        $this->flash('success', 'تم تعيين الوردية للموظف بنجاح.');

        return $this->redirect(url('shifts'));
    }
}
