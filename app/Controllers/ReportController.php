<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Models\Department;

class ReportController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('reports.view');

        $type = $request->get('type', 'daily_attendance');
        $startDate = $request->get('start_date', date('Y-m-01'));
        $endDate = $request->get('end_date', date('Y-m-d'));
        $departmentId = $request->get('department_id', '');

        $data = [];
        $departments = (new Department())->all('name_ar', 'ASC');

        switch ($type) {
            case 'daily_attendance':
                $sql = "SELECT a.*, e.employee_code, CONCAT(e.first_name_ar, ' ', e.last_name_ar) as employee_name, d.name_ar as department_name, s.name_ar as shift_name
                        FROM `attendance_records` a
                        JOIN `employees` e ON e.id = a.employee_id
                        LEFT JOIN `departments` d ON d.id = e.department_id
                        LEFT JOIN `shifts` s ON s.id = a.shift_id
                        WHERE a.date BETWEEN :start AND :end";
                $params = ['start' => $startDate, 'end' => $endDate];
                if (!empty($departmentId)) {
                    $sql .= " AND e.department_id = :dept_id";
                    $params['dept_id'] = (int)$departmentId;
                }
                $sql .= " ORDER BY a.date DESC, e.employee_code ASC";
                $data = Database::fetchAll($sql, $params);
                break;

            case 'late_arrivals':
                $sql = "SELECT a.*, e.employee_code, CONCAT(e.first_name_ar, ' ', e.last_name_ar) as employee_name, d.name_ar as department_name
                        FROM `attendance_records` a
                        JOIN `employees` e ON e.id = a.employee_id
                        LEFT JOIN `departments` d ON d.id = e.department_id
                        WHERE a.status = 'late' AND a.date BETWEEN :start AND :end";
                $params = ['start' => $startDate, 'end' => $endDate];
                if (!empty($departmentId)) {
                    $sql .= " AND e.department_id = :dept_id";
                    $params['dept_id'] = (int)$departmentId;
                }
                $sql .= " ORDER BY a.late_minutes DESC";
                $data = Database::fetchAll($sql, $params);
                break;

            case 'absence':
                $sql = "SELECT a.*, e.employee_code, CONCAT(e.first_name_ar, ' ', e.last_name_ar) as employee_name, d.name_ar as department_name
                        FROM `attendance_records` a
                        JOIN `employees` e ON e.id = a.employee_id
                        LEFT JOIN `departments` d ON d.id = e.department_id
                        WHERE a.status = 'absent' AND a.date BETWEEN :start AND :end";
                $params = ['start' => $startDate, 'end' => $endDate];
                if (!empty($departmentId)) {
                    $sql .= " AND e.department_id = :dept_id";
                    $params['dept_id'] = (int)$departmentId;
                }
                $sql .= " ORDER BY a.date DESC";
                $data = Database::fetchAll($sql, $params);
                break;

            case 'overtime':
                $sql = "SELECT a.*, e.employee_code, CONCAT(e.first_name_ar, ' ', e.last_name_ar) as employee_name, d.name_ar as department_name
                        FROM `attendance_records` a
                        JOIN `employees` e ON e.id = a.employee_id
                        LEFT JOIN `departments` d ON d.id = e.department_id
                        WHERE a.overtime_hours > 0 AND a.date BETWEEN :start AND :end";
                $params = ['start' => $startDate, 'end' => $endDate];
                if (!empty($departmentId)) {
                    $sql .= " AND e.department_id = :dept_id";
                    $params['dept_id'] = (int)$departmentId;
                }
                $sql .= " ORDER BY a.overtime_hours DESC";
                $data = Database::fetchAll($sql, $params);
                break;

            case 'leave_balances':
                $sql = "SELECT b.*, e.employee_code, CONCAT(e.first_name_ar, ' ', e.last_name_ar) as employee_name, d.name_ar as department_name, t.name_ar as leave_name
                        FROM `leave_balances` b
                        JOIN `employees` e ON e.id = b.employee_id
                        LEFT JOIN `departments` d ON d.id = e.department_id
                        JOIN `leave_types` t ON t.id = b.leave_type_id
                        WHERE b.year = :year";
                $params = ['year' => date('Y')];
                if (!empty($departmentId)) {
                    $sql .= " AND e.department_id = :dept_id";
                    $params['dept_id'] = (int)$departmentId;
                }
                $sql .= " ORDER BY e.employee_code ASC";
                $data = Database::fetchAll($sql, $params);
                break;

            case 'biometric_sync':
                $sql = "SELECT l.*, d.name as device_name, d.ip_address, d.protocol
                        FROM `biometric_sync_logs` l
                        JOIN `biometric_devices` d ON d.id = l.device_id
                        ORDER BY l.id DESC
                        LIMIT 100";
                $data = Database::fetchAll($sql);
                break;
        }

        return $this->view('reports/index', [
            'title' => 'مركز التقارير والإحصاءات الشاملة',
            'reportType' => $type,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'departmentId' => $departmentId,
            'departments' => $departments,
            'reportData' => $data,
        ]);
    }
}
