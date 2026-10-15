<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;

class DashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $today = date('Y-m-d');

        // 1. Employee Counts
        $totalEmployees = (int)Database::fetchColumn("SELECT COUNT(*) FROM `employees` WHERE deleted_at IS NULL");
        $activeEmployees = (int)Database::fetchColumn("SELECT COUNT(*) FROM `employees` WHERE deleted_at IS NULL AND employment_status = 'active'");

        // 2. Today's Attendance Counts
        $presentToday = (int)Database::fetchColumn("SELECT COUNT(*) FROM `attendance_records` WHERE date = :today AND status IN ('present', 'late', 'early_leave')", ['today' => $today]);
        $lateToday = (int)Database::fetchColumn("SELECT COUNT(*) FROM `attendance_records` WHERE date = :today AND status = 'late'", ['today' => $today]);
        $absentToday = (int)Database::fetchColumn("SELECT COUNT(*) FROM `attendance_records` WHERE date = :today AND status = 'absent'", ['today' => $today]);
        $onLeaveToday = (int)Database::fetchColumn("SELECT COUNT(*) FROM `attendance_records` WHERE date = :today AND status = 'on_leave'", ['today' => $today]);

        // 3. Pending Requests
        $pendingLeaves = (int)Database::fetchColumn("SELECT COUNT(*) FROM `leave_requests` WHERE status = 'pending'");
        $pendingAdjustments = (int)Database::fetchColumn("SELECT COUNT(*) FROM `attendance_adjustments` WHERE status = 'pending'");
        $pendingOvertime = (int)Database::fetchColumn("SELECT COUNT(*) FROM `overtime_requests` WHERE status = 'pending'");

        // 4. Biometric Devices Status
        $activeDevices = (int)Database::fetchColumn("SELECT COUNT(*) FROM `biometric_devices` WHERE is_active = 1");
        $deviceSuccess = (int)Database::fetchColumn("SELECT COUNT(*) FROM `biometric_devices` WHERE is_active = 1 AND last_sync_status = 'success'");

        // 5. Latest Payroll Run
        $latestPayroll = Database::fetchOne("SELECT * FROM `payroll_runs` ORDER BY id DESC LIMIT 1");

        // 6. Recent Biometric Raw Punches Feed
        $recentPunches = Database::fetchAll(
            "SELECT r.*, d.name as device_name, 
                    COALESCE(CONCAT(e.first_name_ar, ' ', e.last_name_ar), 'غير مرتبط') as employee_name,
                    e.employee_code, e.photo
             FROM `biometric_raw_events` r
             LEFT JOIN `biometric_devices` d ON d.id = r.device_id
             LEFT JOIN `biometric_employee_mappings` m ON m.device_id = r.device_id AND m.device_user_id = r.device_user_id
             LEFT JOIN `employees` e ON e.id = m.employee_id
             ORDER BY r.punch_time DESC
             LIMIT 8"
        );

        // 7. Department Headcount Distribution (for Chart.js)
        $deptDistribution = Database::fetchAll(
            "SELECT d.name_ar as label, COUNT(e.id) as value
             FROM `departments` d
             LEFT JOIN `employees` e ON e.department_id = d.id AND e.deleted_at IS NULL
             GROUP BY d.id
             HAVING value > 0"
        );

        // 8. 7-Day Attendance Trend (for Chart.js)
        $trendDates = [];
        $trendPresent = [];
        $trendLate = [];
        $trendAbsent = [];

        for ($i = 6; $i >= 0; $i--) {
            $d = date('Y-m-d', strtotime("-{$i} days"));
            $trendDates[] = date('M d', strtotime($d));

            $p = (int)Database::fetchColumn("SELECT COUNT(*) FROM `attendance_records` WHERE date = :d AND status IN ('present', 'early_leave')", ['d' => $d]);
            $l = (int)Database::fetchColumn("SELECT COUNT(*) FROM `attendance_records` WHERE date = :d AND status = 'late'", ['d' => $d]);
            $a = (int)Database::fetchColumn("SELECT COUNT(*) FROM `attendance_records` WHERE date = :d AND status = 'absent'", ['d' => $d]);

            $trendPresent[] = $p;
            $trendLate[] = $l;
            $trendAbsent[] = $a;
        }

        return $this->view('dashboard/index', [
            'title' => 'لوحة التحكم الرئيسية',
            'stats' => [
                'total_employees' => $totalEmployees,
                'active_employees' => $activeEmployees,
                'present_today' => $presentToday,
                'late_today' => $lateToday,
                'absent_today' => $absentToday,
                'on_leave_today' => $onLeaveToday,
                'pending_leaves' => $pendingLeaves,
                'pending_adjustments' => $pendingAdjustments,
                'pending_overtime' => $pendingOvertime,
                'active_devices' => $activeDevices,
                'device_success' => $deviceSuccess,
                'latest_payroll' => $latestPayroll,
            ],
            'recentPunches' => $recentPunches,
            'charts' => [
                'dept_labels' => array_column($deptDistribution, 'label'),
                'dept_values' => array_column($deptDistribution, 'value'),
                'trend_dates' => $trendDates,
                'trend_present' => $trendPresent,
                'trend_late' => $trendLate,
                'trend_absent' => $trendAbsent,
            ]
        ]);
    }
}
