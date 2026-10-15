<?php

declare(strict_types=1);

use App\Controllers\AttendanceController;
use App\Controllers\AuthController;
use App\Controllers\BiometricDeviceController;
use App\Controllers\DashboardController;
use App\Controllers\DepartmentController;
use App\Controllers\EmployeeController;
use App\Controllers\EmployeeSelfServiceController;
use App\Controllers\LeaveController;
use App\Controllers\OvertimeController;
use App\Controllers\PayrollController;
use App\Controllers\PerformanceController;
use App\Controllers\RecruitmentController;
use App\Controllers\ReportController;
use App\Controllers\SettingsController;
use App\Controllers\ShiftController;
use App\Core\Response;
use App\Middleware\AuthMiddleware;
use App\Middleware\CsrfMiddleware;
use App\Middleware\GuestMiddleware;

/** @var \App\Core\Router $router */

// System Health Endpoint
$router->get('/health', function () {
    return Response::json([
        'status' => 'healthy',
        'system' => 'Enterprise HR & Payroll System',
        'php_version' => PHP_VERSION,
        'timestamp' => date('c'),
    ]);
});

// Language Switcher
$router->get('/lang', [SettingsController::class, 'switchLanguage']);

// Biometric Hardware Direct PUSH / ADMS Endpoints (Bypasses CSRF)
$router->get('/api/zkteco/iclock/cdata', [BiometricDeviceController::class, 'handleAdmsPush']);
$router->post('/api/zkteco/iclock/cdata', [BiometricDeviceController::class, 'handleAdmsPush']);
$router->get('/api/zkteco/iclock/getrequest', [BiometricDeviceController::class, 'handleAdmsGetRequest']);
$router->post('/api/zkteco/iclock/devicecmd', [BiometricDeviceController::class, 'handleAdmsDeviceCmd']);

// Guest Authentication Routes
$router->get('/login', [AuthController::class, 'showLogin'], [GuestMiddleware::class]);
$router->post('/login', [AuthController::class, 'login'], [GuestMiddleware::class, CsrfMiddleware::class]);
$router->post('/logout', [AuthController::class, 'logout'], [AuthMiddleware::class, CsrfMiddleware::class]);

// Default Redirect
$router->get('/', function () {
    return Response::redirect(url('dashboard'));
});

// Authenticated Application Routes (Protected by AuthMiddleware & CsrfMiddleware)
$router->group(['middleware' => [AuthMiddleware::class, CsrfMiddleware::class]], function (\App\Core\Router $r) {
    // Dashboard
    $r->get('/dashboard', [DashboardController::class, 'index']);

    // Employee Management
    $r->get('/employees', [EmployeeController::class, 'index']);
    $r->get('/employees/create', [EmployeeController::class, 'create']);
    $r->post('/employees', [EmployeeController::class, 'store']);
    $r->get('/employees/{id}', [EmployeeController::class, 'show']);
    $r->get('/employees/{id}/edit', [EmployeeController::class, 'edit']);
    $r->post('/employees/{id}', [EmployeeController::class, 'update']);
    $r->post('/employees/{id}/delete', [EmployeeController::class, 'destroy']);
    $r->post('/employees/{id}/map-device', [EmployeeController::class, 'mapDevice']);

    // Organization (Departments, Positions, Branches)
    $r->get('/departments', [DepartmentController::class, 'index']);
    $r->post('/departments', [DepartmentController::class, 'storeDepartment']);
    $r->post('/positions', [DepartmentController::class, 'storePosition']);
    $r->post('/branches', [DepartmentController::class, 'storeBranch']);

    // ZKTeco Biometric Devices
    $r->get('/devices', [BiometricDeviceController::class, 'index']);
    $r->post('/devices', [BiometricDeviceController::class, 'store']);
    $r->get('/devices/{id}/test', [BiometricDeviceController::class, 'testConnection']);
    $r->post('/devices/{id}/test', [BiometricDeviceController::class, 'testConnection']);
    $r->get('/devices/{id}/sync', [BiometricDeviceController::class, 'sync']);
    $r->post('/devices/{id}/sync', [BiometricDeviceController::class, 'sync']);

    // Attendance Management
    $r->get('/attendance', [AttendanceController::class, 'index']);
    $r->post('/attendance/calculate', [AttendanceController::class, 'calculate']);
    $r->post('/attendance/manual', [AttendanceController::class, 'storeManual']);
    $r->get('/attendance/adjustments', [AttendanceController::class, 'adjustments']);
    $r->post('/attendance/adjustments/{id}/review', [AttendanceController::class, 'reviewAdjustment']);

    // Shifts & Calendar
    $r->get('/shifts', [ShiftController::class, 'index']);
    $r->post('/shifts', [ShiftController::class, 'storeShift']);
    $r->post('/shifts/assign', [ShiftController::class, 'assignShift']);

    // Leaves Management
    $r->get('/leaves', [LeaveController::class, 'index']);
    $r->post('/leaves', [LeaveController::class, 'store']);
    $r->post('/leaves/{id}/review', [LeaveController::class, 'review']);

    // Overtime Management
    $r->get('/overtime', [OvertimeController::class, 'index']);
    $r->post('/overtime', [OvertimeController::class, 'store']);
    $r->post('/overtime/{id}/review', [OvertimeController::class, 'review']);

    // Payroll Engine & Payslips
    $r->get('/payroll', [PayrollController::class, 'index']);
    $r->post('/payroll/periods', [PayrollController::class, 'storePeriod']);
    $r->post('/payroll/calculate', [PayrollController::class, 'calculate']);
    $r->get('/payroll/runs/{id}', [PayrollController::class, 'showRun']);
    $r->post('/payroll/runs/{id}/approve', [PayrollController::class, 'approveRun']);
    $r->post('/payroll/runs/{id}/lock', [PayrollController::class, 'lockRun']);
    $r->get('/payroll/runs/{id}/export', [PayrollController::class, 'exportCsv']);
    $r->get('/payroll/items/{id}/payslip', [PayrollController::class, 'payslip']);

    // Recruitment & HR Administration
    $r->get('/recruitment', [RecruitmentController::class, 'index']);
    $r->post('/recruitment/vacancies', [RecruitmentController::class, 'storeVacancy']);
    $r->post('/recruitment/candidates', [RecruitmentController::class, 'storeCandidate']);
    $r->post('/recruitment/candidates/{id}/status', [RecruitmentController::class, 'updateCandidateStatus']);
    $r->post('/recruitment/candidates/{id}/convert', [RecruitmentController::class, 'convertToEmployee']);

    // Performance Appraisals
    $r->get('/performance', [PerformanceController::class, 'index']);
    $r->post('/performance', [PerformanceController::class, 'store']);

    // Reports & Analytics
    $r->get('/reports', [ReportController::class, 'index']);

    // System Settings & Audit Logs
    $r->get('/settings', [SettingsController::class, 'index']);
    $r->post('/settings', [SettingsController::class, 'update']);

    // Employee Self-Service (ESS)
    $r->get('/ess', [EmployeeSelfServiceController::class, 'index']);
    $r->post('/ess/leave', [EmployeeSelfServiceController::class, 'submitLeave']);
    $r->post('/ess/adjustment', [EmployeeSelfServiceController::class, 'submitAdjustment']);
});
