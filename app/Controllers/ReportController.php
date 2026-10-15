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

        $type = $request->get('type', 'attendance');
        $startDate = $request->get('start_date', date('Y-m-01'));
        $endDate = $request->get('end_date', date('Y-m-d'));
        $departmentId = $request->get('department_id', '');

        $data = $this->getReportData($type, $startDate, $endDate, $departmentId);
        $departments = (new Department())->all('name_ar', 'ASC');

        return $this->view('reports/index', [
            'title' => 'مركز التقارير والإحصاءات الشاملة',
            'reportType' => $type,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'departmentId' => $departmentId,
            'departments' => $departments,
            'reportData' => $data,
            'filters' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'department_id' => $departmentId,
            ],
        ]);
    }

    public function export(Request $request): Response
    {
        $this->authorize('reports.view');

        $type = $request->get('type', 'attendance');
        $startDate = $request->get('start_date', date('Y-m-01'));
        $endDate = $request->get('end_date', date('Y-m-d'));
        $departmentId = $request->get('department_id', '');

        $data = $this->getReportData($type, $startDate, $endDate, $departmentId);

        $filename = "report_{$type}_" . date('Ymd_His') . ".csv";
        $output = fopen('php://memory', 'w');
        fputs($output, "\xEF\xBB\xBF"); // UTF-8 BOM

        if (!empty($data)) {
            // Write CSV headers from array keys
            fputcsv($output, array_keys($data[0]));
            foreach ($data as $row) {
                fputcsv($output, array_values($row));
            }
        } else {
            fputcsv($output, ['No data found']);
        }

        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);

        return new Response($csv, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    private function getReportData(string $type, string $startDate, string $endDate, string $departmentId): array
    {
        $data = [];

        switch ($type) {
            case 'daily_attendance':
            case 'attendance':
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
            case 'lateness':
                $sql = "SELECT a.*, e.employee_code, CONCAT(e.first_name_ar, ' ', e.last_name_ar) as employee_name, d.name_ar as department_name, s.name_ar as shift_name
                        FROM `attendance_records` a
                        JOIN `employees` e ON e.id = a.employee_id
                        LEFT JOIN `departments` d ON d.id = e.department_id
                        LEFT JOIN `shifts` s ON s.id = a.shift_id
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
            case 'absences':
                $sql = "SELECT a.*, e.employee_code, CONCAT(e.first_name_ar, ' ', e.last_name_ar) as employee_name, d.name_ar as department_name, s.name_ar as shift_name
                        FROM `attendance_records` a
                        JOIN `employees` e ON e.id = a.employee_id
                        LEFT JOIN `departments` d ON d.id = e.department_id
                        LEFT JOIN `shifts` s ON s.id = a.shift_id
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
                $sql = "SELECT a.*, e.employee_code, CONCAT(e.first_name_ar, ' ', e.last_name_ar) as employee_name, d.name_ar as department_name, s.name_ar as shift_name
                        FROM `attendance_records` a
                        JOIN `employees` e ON e.id = a.employee_id
                        LEFT JOIN `departments` d ON d.id = e.department_id
                        LEFT JOIN `shifts` s ON s.id = a.shift_id
                        WHERE a.overtime_hours > 0 AND a.date BETWEEN :start AND :end";
                $params = ['start' => $startDate, 'end' => $endDate];
                if (!empty($departmentId)) {
                    $sql .= " AND e.department_id = :dept_id";
                    $params['dept_id'] = (int)$departmentId;
                }
                $sql .= " ORDER BY a.overtime_hours DESC";
                $data = Database::fetchAll($sql, $params);
                break;

            case 'leaves':
            case 'leave_balances':
                $sql = "SELECT lr.*, e.employee_code, CONCAT(e.first_name_ar, ' ', e.last_name_ar) as employee_name, d.name_ar as department_name, t.name_ar as leave_type_name, lr.total_days as days_count,
                               COALESCE(lb.balance - lb.used, 0) as remaining_balance
                        FROM `leave_requests` lr
                        JOIN `employees` e ON e.id = lr.employee_id
                        LEFT JOIN `departments` d ON d.id = e.department_id
                        JOIN `leave_types` t ON t.id = lr.leave_type_id
                        LEFT JOIN `leave_balances` lb ON lb.employee_id = lr.employee_id AND lb.leave_type_id = lr.leave_type_id AND lb.year = YEAR(lr.start_date)
                        WHERE lr.start_date <= :end AND lr.end_date >= :start";
                $params = ['start' => $startDate, 'end' => $endDate];
                if (!empty($departmentId)) {
                    $sql .= " AND e.department_id = :dept_id";
                    $params['dept_id'] = (int)$departmentId;
                }
                $sql .= " ORDER BY lr.id DESC";
                $data = Database::fetchAll($sql, $params);
                break;

            case 'sync_logs':
            case 'biometric_sync':
                $sql = "SELECT l.*, d.name as device_name, d.ip_address, d.protocol
                        FROM `biometric_sync_logs` l
                        JOIN `biometric_devices` d ON d.id = l.device_id
                        ORDER BY l.id DESC
                        LIMIT 100";
                $data = Database::fetchAll($sql);
                break;
        }

        return $data;
    }
}

