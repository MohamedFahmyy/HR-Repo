<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Models\AuditLog;
use App\Models\Branch;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Position;
use App\Models\Shift;
use App\Services\LeaveBalanceService;

class EmployeeController extends Controller
{
    private Employee $empModel;

    public function __construct()
    {
        $this->empModel = new Employee();
    }

    public function index(Request $request): Response
    {
        $this->authorize('employees.view');

        $filters = [
            'search' => $request->get('search', ''),
            'department_id' => $request->get('department_id', ''),
            'branch_id' => $request->get('branch_id', ''),
            'employment_status' => $request->get('status', ''),
        ];

        $page = (int)$request->get('page', 1);
        $paginated = $this->empModel->search($filters, $page, 15);

        $departments = (new Department())->all('name_ar', 'ASC');
        $branches = (new Branch())->all('name_ar', 'ASC');

        return $this->view('employees/index', [
            'title' => 'إدارة الموظفين',
            'employees' => $paginated['data'],
            'pagination' => $paginated,
            'departments' => $departments,
            'branches' => $branches,
            'filters' => $filters,
        ]);
    }

    public function create(Request $request): Response
    {
        $this->authorize('employees.create');

        $departments = (new Department())->all('name_ar', 'ASC');
        $branches = (new Branch())->all('name_ar', 'ASC');
        $positions = (new Position())->all('title_ar', 'ASC');
        $shifts = (new Shift())->all('name_ar', 'ASC');
        $managers = Database::fetchAll("SELECT id, CONCAT(first_name_ar, ' ', last_name_ar) as name FROM `employees` WHERE deleted_at IS NULL ORDER BY first_name_ar ASC");

        return $this->view('employees/create', [
            'title' => 'إضافة موظف جديد',
            'departments' => $departments,
            'branches' => $branches,
            'positions' => $positions,
            'shifts' => $shifts,
            'managers' => $managers,
        ]);
    }

    public function store(Request $request): Response
    {
        $this->authorize('employees.create');

        $data = $this->validate($request, [
            'employee_code' => 'required|unique:employees,employee_code',
            'first_name_ar' => 'required',
            'last_name_ar' => 'required',
            'first_name_en' => 'required',
            'last_name_en' => 'required',
            'national_id' => 'required',
            'email' => 'required|email|unique:employees,email',
            'phone' => 'required',
            'hire_date' => 'required|date',
            'basic_salary' => 'required|numeric|min:0',
        ]);

        $employeeId = $this->empModel->create([
            'employee_code' => trim($data['employee_code']),
            'first_name_ar' => trim($data['first_name_ar']),
            'last_name_ar' => trim($data['last_name_ar']),
            'first_name_en' => trim($data['first_name_en']),
            'last_name_en' => trim($data['last_name_en']),
            'national_id' => trim($data['national_id']),
            'passport_number' => $request->post('passport_number'),
            'gender' => $request->post('gender', 'male'),
            'nationality' => $request->post('nationality', 'Saudi'),
            'marital_status' => $request->post('marital_status', 'single'),
            'email' => strtolower(trim($data['email'])),
            'phone' => trim($data['phone']),
            'branch_id' => $request->post('branch_id') ?: null,
            'department_id' => $request->post('department_id') ?: null,
            'position_id' => $request->post('position_id') ?: null,
            'manager_id' => $request->post('manager_id') ?: null,
            'employment_type' => $request->post('employment_type', 'full_time'),
            'employment_status' => 'active',
            'hire_date' => $data['hire_date'],
            'probation_end_date' => $request->post('probation_end_date') ?: null,
            'basic_salary' => (float)$data['basic_salary'],
            'housing_allowance' => (float)$request->post('housing_allowance', 0),
            'transport_allowance' => (float)$request->post('transport_allowance', 0),
            'other_allowances' => (float)$request->post('other_allowances', 0),
            'bank_name' => $request->post('bank_name'),
            'iban' => $request->post('iban'),
        ]);

        // Assign shift
        $shiftId = (int)$request->post('shift_id', 1);
        Database::execute(
            "INSERT INTO `shift_assignments` (`employee_id`, `shift_id`, `start_date`, `policy_id`) VALUES (?, ?, ?, 1)",
            [$employeeId, $shiftId, $data['hire_date']]
        );

        // Biometric Mapping if specified
        $biometricPin = $request->post('biometric_pin');
        $deviceId = $request->post('biometric_device_id');
        if (!empty($biometricPin) && !empty($deviceId)) {
            Database::execute(
                "INSERT INTO `biometric_employee_mappings` (`device_id`, `employee_id`, `device_user_id`) VALUES (?, ?, ?)
                 ON DUPLICATE KEY UPDATE `device_user_id` = VALUES(`device_user_id`)",
                [$deviceId, $employeeId, trim($biometricPin)]
            );
        }

        // Initialize leave balances
        $leaveService = new LeaveBalanceService();
        $leaveService->getOrCreateBalance((int)$employeeId, 1); // Annual
        $leaveService->getOrCreateBalance((int)$employeeId, 2); // Sick
        $leaveService->getOrCreateBalance((int)$employeeId, 3); // Emergency

        AuditLog::log('create_employee', 'Employee', $employeeId, null, $data);
        $this->flash('success', "تم إضافة الموظف {$data['first_name_ar']} {$data['last_name_ar']} بنجاح.");

        return $this->redirect(url("employees/{$employeeId}"));
    }

    public function show(Request $request, string|int $id): Response
    {
        $this->authorize('employees.view');

        $employee = $this->empModel->getDetailedProfile((int)$id);
        if (!$employee) {
            $this->flash('error', 'الموظف المطلوب غير موجود.');
            return $this->redirect(url('employees'));
        }

        // Biometric device mappings
        $deviceMappings = Database::fetchAll(
            "SELECT m.*, d.name as device_name, d.ip_address, d.protocol
             FROM `biometric_employee_mappings` m
             JOIN `biometric_devices` d ON d.id = m.device_id
             WHERE m.employee_id = :id",
            ['id' => $id]
        );

        // Leave balances
        $leaveBalances = (new \App\Models\LeaveBalance())->getBalancesForEmployee((int)$id);

        // Recent attendance records
        $recentAttendance = Database::fetchAll(
            "SELECT a.*, s.name_ar as shift_name
             FROM `attendance_records` a
             LEFT JOIN `shifts` s ON s.id = a.shift_id
             WHERE a.employee_id = :id
             ORDER BY a.date DESC
             LIMIT 15",
            ['id' => $id]
        );

        // Payslips
        $payslips = (new \App\Models\Payslip())->getPayslipsForEmployee((int)$id);

        $devices = (new \App\Models\BiometricDevice())->where('is_active', 1);

        return $this->view('employees/show', [
            'title' => "الملف التعريفي: {$employee['first_name_ar']} {$employee['last_name_ar']}",
            'employee' => $employee,
            'deviceMappings' => $deviceMappings,
            'leaveBalances' => $leaveBalances,
            'recentAttendance' => $recentAttendance,
            'payslips' => $payslips,
            'devices' => $devices,
        ]);
    }

    public function edit(Request $request, string|int $id): Response
    {
        $this->authorize('employees.edit');

        $employee = $this->empModel->findOrFail((int)$id);
        $departments = (new Department())->all('name_ar', 'ASC');
        $branches = (new Branch())->all('name_ar', 'ASC');
        $positions = (new Position())->all('title_ar', 'ASC');
        $shifts = (new Shift())->all('name_ar', 'ASC');
        $managers = Database::fetchAll("SELECT id, CONCAT(first_name_ar, ' ', last_name_ar) as name FROM `employees` WHERE deleted_at IS NULL AND id != :id ORDER BY first_name_ar ASC", ['id' => $id]);

        $currentShift = Database::fetchOne(
            "SELECT shift_id FROM `shift_assignments` WHERE employee_id = :id ORDER BY id DESC LIMIT 1",
            ['id' => $id]
        );

        return $this->view('employees/edit', [
            'title' => "تعديل بيانات الموظف: {$employee['first_name_ar']} {$employee['last_name_ar']}",
            'employee' => $employee,
            'departments' => $departments,
            'branches' => $branches,
            'positions' => $positions,
            'shifts' => $shifts,
            'managers' => $managers,
            'currentShiftId' => $currentShift['shift_id'] ?? 1,
        ]);
    }

    public function update(Request $request, string|int $id): Response
    {
        $this->authorize('employees.edit');

        $employee = $this->empModel->findOrFail((int)$id);

        $data = $this->validate($request, [
            'employee_code' => "required|unique:employees,employee_code,{$id}",
            'first_name_ar' => 'required',
            'last_name_ar' => 'required',
            'first_name_en' => 'required',
            'last_name_en' => 'required',
            'national_id' => 'required',
            'email' => "required|email|unique:employees,email,{$id}",
            'phone' => 'required',
            'hire_date' => 'required|date',
            'basic_salary' => 'required|numeric|min:0',
        ]);

        $this->empModel->update((int)$id, [
            'employee_code' => trim($data['employee_code']),
            'first_name_ar' => trim($data['first_name_ar']),
            'last_name_ar' => trim($data['last_name_ar']),
            'first_name_en' => trim($data['first_name_en']),
            'last_name_en' => trim($data['last_name_en']),
            'national_id' => trim($data['national_id']),
            'passport_number' => $request->post('passport_number'),
            'gender' => $request->post('gender', 'male'),
            'nationality' => $request->post('nationality', 'Saudi'),
            'marital_status' => $request->post('marital_status', 'single'),
            'email' => strtolower(trim($data['email'])),
            'phone' => trim($data['phone']),
            'branch_id' => $request->post('branch_id') ?: null,
            'department_id' => $request->post('department_id') ?: null,
            'position_id' => $request->post('position_id') ?: null,
            'manager_id' => $request->post('manager_id') ?: null,
            'employment_type' => $request->post('employment_type', 'full_time'),
            'employment_status' => $request->post('employment_status', 'active'),
            'hire_date' => $data['hire_date'],
            'probation_end_date' => $request->post('probation_end_date') ?: null,
            'basic_salary' => (float)$data['basic_salary'],
            'housing_allowance' => (float)$request->post('housing_allowance', 0),
            'transport_allowance' => (float)$request->post('transport_allowance', 0),
            'other_allowances' => (float)$request->post('other_allowances', 0),
            'bank_name' => $request->post('bank_name'),
            'iban' => $request->post('iban'),
        ]);

        // Update shift assignment if changed
        $shiftId = (int)$request->post('shift_id', 1);
        Database::execute(
            "INSERT INTO `shift_assignments` (`employee_id`, `shift_id`, `start_date`, `policy_id`) VALUES (?, ?, CURDATE(), 1)",
            [$id, $shiftId]
        );

        AuditLog::log('update_employee', 'Employee', $id, $employee, $data);
        $this->flash('success', 'تم تحديث بيانات الموظف بنجاح.');

        return $this->redirect(url("employees/{$id}"));
    }

    public function destroy(Request $request, string|int $id): Response
    {
        $this->authorize('employees.delete');

        $employee = $this->empModel->findOrFail((int)$id);
        $this->empModel->delete((int)$id);

        AuditLog::log('archive_employee', 'Employee', $id, $employee);
        $this->flash('success', "تم أرشفة الموظف {$employee['first_name_ar']} بنجاح، مع حفظ كامل سجلات الحضور والرواتب السابقة.");

        return $this->redirect(url('employees'));
    }

    public function mapDevice(Request $request, string|int $id): Response
    {
        $this->authorize('employees.edit');

        $deviceId = (int)$request->post('device_id');
        $pin = trim((string)$request->post('device_user_id'));

        if (!empty($deviceId) && !empty($pin)) {
            Database::execute(
                "INSERT INTO `biometric_employee_mappings` (`device_id`, `employee_id`, `device_user_id`)
                 VALUES (:device_id, :emp_id, :pin)
                 ON DUPLICATE KEY UPDATE `device_user_id` = VALUES(`device_user_id`)",
                ['device_id' => $deviceId, 'emp_id' => $id, 'pin' => $pin]
            );
            $this->flash('success', "تم ربط رقم البصمة ({$pin}) بالجهاز بنجاح.");
        }

        return $this->redirect(url("employees/{$id}"));
    }
}
