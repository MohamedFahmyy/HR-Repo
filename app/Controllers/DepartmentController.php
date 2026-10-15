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
use App\Models\Position;

class DepartmentController extends Controller
{
    private Department $deptModel;
    private Position $posModel;
    private Branch $branchModel;

    public function __construct()
    {
        $this->deptModel = new Department();
        $this->posModel = new Position();
        $this->branchModel = new Branch();
    }

    public function index(Request $request): Response
    {
        $this->authorize('departments.manage');

        $departments = $this->deptModel->allWithDetails();
        $positions = $this->posModel->allWithDepartment();
        $branches = $this->branchModel->all();
        $managers = Database::fetchAll("SELECT id, CONCAT(first_name_ar, ' ', last_name_ar) as name FROM `employees` WHERE deleted_at IS NULL");

        return $this->view('departments/index', [
            'title' => 'الهيكل التنظيمي (الأقسام والوظائف والفروع)',
            'departments' => $departments,
            'positions' => $positions,
            'branches' => $branches,
            'managers' => $managers,
        ]);
    }

    public function storeDepartment(Request $request): Response
    {
        $this->authorize('departments.manage');

        $data = $this->validate($request, [
            'code' => 'required|unique:departments,code',
            'name_ar' => 'required',
            'name_en' => 'required',
        ]);

        $id = $this->deptModel->create([
            'code' => trim($data['code']),
            'name_ar' => trim($data['name_ar']),
            'name_en' => trim($data['name_en']),
            'branch_id' => $request->post('branch_id') ?: null,
            'manager_id' => $request->post('manager_id') ?: null,
        ]);

        AuditLog::log('create_department', 'Department', $id, null, $data);
        $this->flash('success', "تم إنشاء القسم {$data['name_ar']} بنجاح.");

        return $this->redirect(url('departments'));
    }

    public function storePosition(Request $request): Response
    {
        $this->authorize('departments.manage');

        $data = $this->validate($request, [
            'code' => 'required|unique:positions,code',
            'title_ar' => 'required',
            'title_en' => 'required',
            'department_id' => 'required|integer',
        ]);

        $id = $this->posModel->create([
            'code' => trim($data['code']),
            'title_ar' => trim($data['title_ar']),
            'title_en' => trim($data['title_en']),
            'department_id' => (int)$data['department_id'],
        ]);

        AuditLog::log('create_position', 'Position', $id, null, $data);
        $this->flash('success', "تم إضافة المسمى الوظيفي {$data['title_ar']} بنجاح.");

        return $this->redirect(url('departments'));
    }

    public function storeBranch(Request $request): Response
    {
        $this->authorize('departments.manage');

        $data = $this->validate($request, [
            'code' => 'required|unique:branches,code',
            'name_ar' => 'required',
            'name_en' => 'required',
        ]);

        $id = $this->branchModel->create([
            'code' => trim($data['code']),
            'name_ar' => trim($data['name_ar']),
            'name_en' => trim($data['name_en']),
            'timezone' => $request->post('timezone', 'Asia/Riyadh'),
            'address' => $request->post('address'),
            'phone' => $request->post('phone'),
        ]);

        AuditLog::log('create_branch', 'Branch', $id, null, $data);
        $this->flash('success', "تم إضافة الفرع {$data['name_ar']} بنجاح.");

        return $this->redirect(url('departments'));
    }
}
