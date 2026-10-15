<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Models\AuditLog;
use App\Models\PerformanceReview;

class PerformanceController extends Controller
{
    private PerformanceReview $reviewModel;

    public function __construct()
    {
        $this->reviewModel = new PerformanceReview();
    }

    public function index(Request $request): Response
    {
        $this->authorize('performance.manage');

        $reviews = $this->reviewModel->allWithDetails();
        $employees = Database::fetchAll("SELECT id, employee_code, CONCAT(first_name_ar, ' ', last_name_ar) as name FROM `employees` WHERE deleted_at IS NULL");

        return $this->view('performance/index', [
            'title' => 'تقييم الأداء ومؤشرات الإنجاز KPIs',
            'reviews' => $reviews,
            'employees' => $employees,
        ]);
    }

    public function store(Request $request): Response
    {
        $this->authorize('performance.manage');

        $data = $this->validate($request, [
            'employee_id' => 'required|integer',
            'review_period' => 'required',
            'rating' => 'required|numeric|min:1|max:5',
        ]);

        $reviewerId = Auth::getInstance()->id();
        $id = $this->reviewModel->create([
            'employee_id' => (int)$data['employee_id'],
            'reviewer_id' => $reviewerId,
            'review_period' => trim($data['review_period']),
            'rating' => (float)$data['rating'],
            'kpis_feedback' => $request->post('kpis_feedback'),
            'strengths' => $request->post('strengths'),
            'areas_for_improvement' => $request->post('areas_for_improvement'),
            'status' => 'submitted',
        ]);

        AuditLog::log('create_performance_review', 'PerformanceReview', $id, null, $data);
        $this->flash('success', 'تم حفظ تقييم الأداء بنجاح.');

        return $this->redirect(url('performance'));
    }
}
