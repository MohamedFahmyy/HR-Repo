<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Models\AuditLog;
use App\Models\Candidate;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Position;
use App\Models\Vacancy;

class RecruitmentController extends Controller
{
    private Vacancy $vacancyModel;
    private Candidate $candidateModel;

    public function __construct()
    {
        $this->vacancyModel = new Vacancy();
        $this->candidateModel = new Candidate();
    }

    public function index(Request $request): Response
    {
        $this->authorize('recruitment.manage');

        $vacancies = $this->vacancyModel->allWithDetails();
        $candidates = $this->candidateModel->allWithVacancy();
        $departments = (new Department())->all('name_ar', 'ASC');
        $positions = (new Position())->all('title_ar', 'ASC');

        return $this->view('recruitment/index', [
            'title' => 'إدارة التوظيف واستقطاب الكفاءات',
            'vacancies' => $vacancies,
            'candidates' => $candidates,
            'departments' => $departments,
            'positions' => $positions,
        ]);
    }

    public function storeVacancy(Request $request): Response
    {
        $this->authorize('recruitment.manage');

        $data = $this->validate($request, [
            'title_ar' => 'required',
            'title_en' => 'required',
            'department_id' => 'required|integer',
            'openings_count' => 'required|integer|min:1',
        ]);

        $id = $this->vacancyModel->create([
            'title_ar' => trim($data['title_ar']),
            'title_en' => trim($data['title_en']),
            'department_id' => (int)$data['department_id'],
            'position_id' => $request->post('position_id') ?: null,
            'openings_count' => (int)$data['openings_count'],
            'employment_type' => $request->post('employment_type', 'full_time'),
            'experience_years' => (int)$request->post('experience_years', 1),
            'description' => $request->post('description'),
            'status' => 'published',
        ]);

        AuditLog::log('create_vacancy', 'Vacancy', $id, null, $data);
        $this->flash('success', "تم نشر الفرصة الوظيفية {$data['title_ar']} بنجاح.");

        return $this->redirect(url('recruitment'));
    }

    public function storeCandidate(Request $request): Response
    {
        $this->authorize('recruitment.manage');

        $data = $this->validate($request, [
            'first_name' => 'required',
            'last_name' => 'required',
            'email' => 'required|email',
            'phone' => 'required',
            'vacancy_id' => 'required|integer',
        ]);

        $id = $this->candidateModel->create([
            'vacancy_id' => (int)$data['vacancy_id'],
            'first_name' => trim($data['first_name']),
            'last_name' => trim($data['last_name']),
            'email' => strtolower(trim($data['email'])),
            'phone' => trim($data['phone']),
            'status' => 'applied',
            'notes' => $request->post('notes'),
        ]);

        AuditLog::log('create_candidate', 'Candidate', $id, null, $data);
        $this->flash('success', "تم تسجيل المرشح {$data['first_name']} {$data['last_name']} بنجاح.");

        return $this->redirect(url('recruitment'));
    }

    public function updateCandidateStatus(Request $request, string|int $id): Response
    {
        $this->authorize('recruitment.manage');

        $status = $request->post('status');
        $this->candidateModel->update((int)$id, ['status' => $status]);

        AuditLog::log('update_candidate_status', 'Candidate', $id, null, ['status' => $status]);
        $this->flash('success', 'تم تحديث مرحلة المرشح بنجاح.');

        return $this->redirect(url('recruitment'));
    }

    public function convertToEmployee(Request $request, string|int $candidateId): Response
    {
        $this->authorize('employees.create');

        $cand = $this->candidateModel->findOrFail((int)$candidateId);
        $vacancy = $cand['vacancy_id'] ? $this->vacancyModel->find((int)$cand['vacancy_id']) : null;

        $newCode = 'EMP-' . sprintf('%04d', ((int)Database::fetchColumn("SELECT COUNT(*) FROM `employees`") + 1));

        $empId = (new Employee())->create([
            'employee_code' => $newCode,
            'first_name_ar' => $cand['first_name'],
            'last_name_ar' => $cand['last_name'],
            'first_name_en' => $cand['first_name'],
            'last_name_en' => $cand['last_name'],
            'national_id' => '10' . rand(10000000, 99999999),
            'email' => $cand['email'],
            'phone' => $cand['phone'],
            'department_id' => $vacancy['department_id'] ?? null,
            'position_id' => $vacancy['position_id'] ?? null,
            'hire_date' => date('Y-m-d'),
            'basic_salary' => 10000.00,
            'housing_allowance' => 2500.00,
            'transport_allowance' => 1000.00,
            'employment_status' => 'active',
        ]);

        $this->candidateModel->update((int)$candidateId, [
            'status' => 'hired',
            'converted_to_employee_id' => $empId,
        ]);

        AuditLog::log('convert_candidate_to_employee', 'Employee', $empId, null, ['candidate_id' => $candidateId]);
        $this->flash('success', "تم تحويل المرشح إلى موظف رسمي بنجاح برقم وظيفي ({$newCode}).");

        return $this->redirect(url("employees/{$empId}"));
    }
}
