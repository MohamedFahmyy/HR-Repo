<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Core\Database;
use PDO;

class DatabaseSeeder
{
    public function run(PDO $pdo): void
    {
        echo "Seeding Roles and Permissions...\n";
        $this->seedRolesAndPermissions($pdo);

        echo "Seeding Application Settings...\n";
        $this->seedSettings($pdo);

        echo "Seeding Organization Structure (Branches, Departments, Positions)...\n";
        $this->seedOrganization($pdo);

        echo "Seeding Attendance Policies, Shifts, and Calendars...\n";
        $this->seedShiftsAndPolicies($pdo);

        echo "Seeding Leave Types and Salary Components...\n";
        $this->seedLeavesAndSalaryComponents($pdo);

        echo "Seeding Users and Employees...\n";
        $this->seedUsersAndEmployees($pdo);

        echo "Seeding Biometric Devices and Employee Mappings...\n";
        $this->seedBiometricDevicesAndMappings($pdo);

        echo "Seeding Attendance Records and Sample Punches...\n";
        $this->seedAttendanceData($pdo);

        echo "Seeding Leave Balances and Sample Requests...\n";
        $this->seedLeaveBalancesAndRequests($pdo);

        echo "Seeding Payroll Periods and Sample Payroll Run...\n";
        $this->seedPayrollData($pdo);

        echo "Seeding Vacancies and Onboarding Tasks...\n";
        $this->seedRecruitmentAndOnboarding($pdo);
    }

    private function seedRolesAndPermissions(PDO $pdo): void
    {
        $roles = [
            ['name_ar' => 'مدير النظام الأعلى', 'name_en' => 'Super Administrator', 'slug' => 'super-admin', 'desc' => 'صلاحيات كاملة وغير مقيدة في النظام'],
            ['name_ar' => 'مدير الموارد البشرية', 'name_en' => 'HR Manager', 'slug' => 'hr-manager', 'desc' => 'إدارة الموظفين والسياسات والاعتمادات'],
            ['name_ar' => 'أخصائي موارد بشرية', 'name_en' => 'HR Officer', 'slug' => 'hr-officer', 'desc' => 'العمليات اليومية وشؤون الموظفين'],
            ['name_ar' => 'مسؤول الحضور والانصراف', 'name_en' => 'Attendance Manager', 'slug' => 'attendance-manager', 'desc' => 'متابعة أجهزة البصمة وسجلات الحضور والورديات'],
            ['name_ar' => 'مسؤول الرواتب', 'name_en' => 'Payroll Manager', 'slug' => 'payroll-manager', 'desc' => 'إعداد ومراجعة واعتماد مسيرات الرواتب'],
            ['name_ar' => 'مدير إدارة / قسم', 'name_en' => 'Department Manager', 'slug' => 'dept-manager', 'desc' => 'اعتماد طلبات الموظفين التابعين'],
            ['name_ar' => 'موظف', 'name_en' => 'Employee', 'slug' => 'employee', 'desc' => 'الخدمة الذاتية وتقديم الطلبات'],
            ['name_ar' => 'مدقق حسابات', 'name_en' => 'Auditor', 'slug' => 'auditor', 'desc' => 'الاطلاع على التقارير وسجلات التدقيق'],
        ];

        foreach ($roles as $r) {
            $stmt = $pdo->prepare("INSERT INTO `roles` (`name_ar`, `name_en`, `slug`, `description`) 
                VALUES (:name_ar, :name_en, :slug, :desc) 
                ON DUPLICATE KEY UPDATE `name_ar` = VALUES(`name_ar`), `name_en` = VALUES(`name_en`)");
            $stmt->execute($r);
        }

        $permissions = [
            // Employees
            ['name_ar' => 'عرض الموظفين', 'name_en' => 'View Employees', 'slug' => 'employees.view', 'group' => 'employees'],
            ['name_ar' => 'إنشاء موظف', 'name_en' => 'Create Employee', 'slug' => 'employees.create', 'group' => 'employees'],
            ['name_ar' => 'تعديل موظف', 'name_en' => 'Edit Employee', 'slug' => 'employees.edit', 'group' => 'employees'],
            ['name_ar' => 'حذف موظف', 'name_en' => 'Delete Employee', 'slug' => 'employees.delete', 'group' => 'employees'],
            ['name_ar' => 'عرض رواتب الموظفين', 'name_en' => 'View Employee Salaries', 'slug' => 'employees.view_salary', 'group' => 'employees'],
            // Departments
            ['name_ar' => 'إدارة الأقسام والوظائف', 'name_en' => 'Manage Departments', 'slug' => 'departments.manage', 'group' => 'departments'],
            // Devices
            ['name_ar' => 'عرض أجهزة البصمة', 'name_en' => 'View Devices', 'slug' => 'devices.view', 'group' => 'devices'],
            ['name_ar' => 'إدارة أجهزة البصمة', 'name_en' => 'Manage Devices', 'slug' => 'devices.manage', 'group' => 'devices'],
            ['name_ar' => 'مزامنة أجهزة البصمة', 'name_en' => 'Sync Devices', 'slug' => 'devices.sync', 'group' => 'devices'],
            // Attendance
            ['name_ar' => 'عرض الحضور والانصراف', 'name_en' => 'View Attendance', 'slug' => 'attendance.view', 'group' => 'attendance'],
            ['name_ar' => 'احتساب الحضور', 'name_en' => 'Calculate Attendance', 'slug' => 'attendance.calculate', 'group' => 'attendance'],
            ['name_ar' => 'تعديل سجلات الحضور', 'name_en' => 'Adjust Attendance', 'slug' => 'attendance.adjust', 'group' => 'attendance'],
            ['name_ar' => 'اعتماد تصحيحات الحضور', 'name_en' => 'Approve Attendance Adjustments', 'slug' => 'attendance.approve', 'group' => 'attendance'],
            // Shifts
            ['name_ar' => 'إدارة الورديات', 'name_en' => 'Manage Shifts', 'slug' => 'shifts.manage', 'group' => 'shifts'],
            // Leaves
            ['name_ar' => 'عرض الإجازات', 'name_en' => 'View Leaves', 'slug' => 'leaves.view', 'group' => 'leaves'],
            ['name_ar' => 'طلب إجازة', 'name_en' => 'Request Leave', 'slug' => 'leaves.request', 'group' => 'leaves'],
            ['name_ar' => 'اعتماد الإجازات', 'name_en' => 'Approve Leaves', 'slug' => 'leaves.approve', 'group' => 'leaves'],
            // Overtime
            ['name_ar' => 'عرض العمل الإضافي', 'name_en' => 'View Overtime', 'slug' => 'overtime.view', 'group' => 'overtime'],
            ['name_ar' => 'اعتماد العمل الإضافي', 'name_en' => 'Approve Overtime', 'slug' => 'overtime.approve', 'group' => 'overtime'],
            // Payroll
            ['name_ar' => 'عرض الرواتب', 'name_en' => 'View Payroll', 'slug' => 'payroll.view', 'group' => 'payroll'],
            ['name_ar' => 'احتساب الرواتب', 'name_en' => 'Calculate Payroll', 'slug' => 'payroll.calculate', 'group' => 'payroll'],
            ['name_ar' => 'اعتماد الرواتب', 'name_en' => 'Approve Payroll', 'slug' => 'payroll.approve', 'group' => 'payroll'],
            ['name_ar' => 'قفل الرواتب', 'name_en' => 'Lock Payroll', 'slug' => 'payroll.lock', 'group' => 'payroll'],
            ['name_ar' => 'تصدير الرواتب', 'name_en' => 'Export Payroll', 'slug' => 'payroll.export', 'group' => 'payroll'],
            // Recruitment & Performance
            ['name_ar' => 'إدارة التوظيف', 'name_en' => 'Manage Recruitment', 'slug' => 'recruitment.manage', 'group' => 'recruitment'],
            ['name_ar' => 'تقييم الأداء', 'name_en' => 'Manage Performance', 'slug' => 'performance.manage', 'group' => 'performance'],
            // Reports & Settings
            ['name_ar' => 'عرض التقارير', 'name_en' => 'View Reports', 'slug' => 'reports.view', 'group' => 'reports'],
            ['name_ar' => 'إعدادات النظام', 'name_en' => 'Manage Settings', 'slug' => 'settings.manage', 'group' => 'settings'],
            ['name_ar' => 'سجلات التدقيق', 'name_en' => 'View Audit Logs', 'slug' => 'audit.view', 'group' => 'audit'],
        ];

        foreach ($permissions as $p) {
            $stmt = $pdo->prepare("INSERT INTO `permissions` (`name_ar`, `name_en`, `slug`, `group_name`) 
                VALUES (:name_ar, :name_en, :slug, :group) 
                ON DUPLICATE KEY UPDATE `name_ar` = VALUES(`name_ar`), `name_en` = VALUES(`name_en`)");
            $stmt->execute($p);
        }

        // Link roles to permissions
        $roleMap = $pdo->query("SELECT `id`, `slug` FROM `roles`")->fetchAll(PDO::FETCH_KEY_PAIR);
        $permMap = $pdo->query("SELECT `slug`, `id` FROM `permissions`")->fetchAll(PDO::FETCH_KEY_PAIR);

        $rolePermAssignments = [
            'super-admin' => array_keys($permMap),
            'hr-manager' => [
                'employees.view', 'employees.create', 'employees.edit', 'employees.view_salary',
                'departments.manage', 'attendance.view', 'attendance.approve', 'shifts.manage',
                'leaves.view', 'leaves.approve', 'overtime.view', 'overtime.approve',
                'payroll.view', 'payroll.approve', 'recruitment.manage', 'performance.manage', 'reports.view'
            ],
            'attendance-manager' => [
                'devices.view', 'devices.manage', 'devices.sync', 'attendance.view', 'attendance.calculate',
                'attendance.adjust', 'attendance.approve', 'shifts.manage', 'overtime.view', 'overtime.approve', 'reports.view'
            ],
            'payroll-manager' => [
                'employees.view', 'employees.view_salary', 'attendance.view', 'overtime.view',
                'payroll.view', 'payroll.calculate', 'payroll.approve', 'payroll.lock', 'payroll.export', 'reports.view'
            ],
            'employee' => [
                'leaves.request'
            ]
        ];

        foreach ($rolePermAssignments as $roleSlug => $perms) {
            if (!isset($roleMap[$roleSlug])) continue;
            $roleId = $roleMap[$roleSlug];
            foreach ($perms as $pSlug) {
                if (isset($permMap[$pSlug])) {
                    $pdo->prepare("INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`) VALUES (?, ?)")
                        ->execute([$roleId, $permMap[$pSlug]]);
                }
            }
        }
    }

    private function seedSettings(PDO $pdo): void
    {
        $settings = [
            ['company_name_ar', 'مجموعة التقنية المتقدمة للأعمال', 'general', 'string', 'اسم المنشأة بالعربية'],
            ['company_name_en', 'Advanced Business Technology Group', 'general', 'string', 'اسم المنشأة بالإنجليزية'],
            ['tax_number', '310987654300003', 'general', 'string', 'الرقم الضريبي'],
            ['commercial_registration', '1010998877', 'general', 'string', 'رقم السجل التجاري'],
            ['primary_currency', 'SAR', 'payroll', 'string', 'العملة الأساسية'],
            ['timezone', 'Asia/Riyadh', 'attendance', 'string', 'المنطقة الزمنية المعتمدة'],
            ['default_grace_period', '15', 'attendance', 'integer', 'فترة السماح بالدقائق'],
            ['adms_server_port', '80', 'zkteco', 'integer', 'منفذ استقبال بيانات PUSH'],
        ];

        $stmt = $pdo->prepare("INSERT INTO `application_settings` (`setting_key`, `setting_value`, `setting_group`, `data_type`, `description`)
            VALUES (?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE `setting_value` = VALUES(`setting_value`)");

        foreach ($settings as $s) {
            $stmt->execute($s);
        }
    }

    private function seedOrganization(PDO $pdo): void
    {
        $pdo->exec("
            INSERT INTO `branches` (`code`, `name_ar`, `name_en`, `timezone`, `address`, `phone`) VALUES
            ('BR-RUH', 'المقر الرئيسي - الرياض', 'Headquarters - Riyadh', 'Asia/Riyadh', 'طريق الملك فهد، العليا، الرياض', '0112345678'),
            ('BR-JED', 'فرع المنطقة الغربية - جدة', 'Western Branch - Jeddah', 'Asia/Riyadh', 'طريق المدينة المنورة، جدة', '0129876543')
            ON DUPLICATE KEY UPDATE `name_ar` = VALUES(`name_ar`);

            INSERT INTO `departments` (`code`, `name_ar`, `name_en`, `branch_id`) VALUES
            ('DEP-IT', 'إدارة تقنية المعلومات والتحول الرقمي', 'IT & Digital Transformation', 1),
            ('DEP-HR', 'إدارة الموارد البشرية والعمليات', 'Human Resources & Operations', 1),
            ('DEP-FIN', 'الإدارة المالية والمحاسبة', 'Finance & Accounting', 1),
            ('DEP-SALES', 'إدارة المبيعات والتسويق', 'Sales & Marketing', 1),
            ('DEP-OPS', 'إدارة التشغيل والمشاريع', 'Operations & Projects', 2)
            ON DUPLICATE KEY UPDATE `name_ar` = VALUES(`name_ar`);

            INSERT INTO `positions` (`code`, `title_ar`, `title_en`, `department_id`) VALUES
            ('POS-ARCH', 'كبير مهندسي البرمجيات', 'Principal Software Architect', 1),
            ('POS-DEV', 'مطور تطبيقات أول', 'Senior Full Stack Developer', 1),
            ('POS-HRM', 'مدير الموارد البشرية', 'Human Resources Manager', 2),
            ('POS-HRO', 'أخصائي شؤون موظفين', 'HR Operations Officer', 2),
            ('POS-PAY', 'أخصائي رواتب ومستحقات', 'Payroll Specialist', 3),
            ('POS-ACC', 'محاسب عام', 'General Accountant', 3),
            ('POS-SALES', 'مدير تطوير أعمال', 'Business Development Manager', 4),
            ('POS-TECH', 'فني دعم ميداني', 'Field Support Technician', 5)
            ON DUPLICATE KEY UPDATE `title_ar` = VALUES(`title_ar`);
        ");
    }

    private function seedShiftsAndPolicies(PDO $pdo): void
    {
        $pdo->exec("
            INSERT INTO `attendance_policies` 
            (`id`, `name_ar`, `name_en`, `grace_period_minutes`, `early_departure_grace_minutes`, `half_day_threshold_hours`, `full_day_hours`, `overtime_minimum_minutes`, `overtime_regular_rate`, `overtime_rest_day_rate`, `is_default`) VALUES
            (1, 'سياسة الدوام المكتبي القياسية', 'Standard Corporate Attendance Policy', 15, 15, 4.00, 8.00, 30, 1.50, 2.00, 1),
            (2, 'سياسة الورديات الليلية والميدانية', 'Field & Night Shift Policy', 10, 10, 3.50, 7.00, 15, 1.75, 2.00, 0)
            ON DUPLICATE KEY UPDATE `name_ar` = VALUES(`name_ar`);

            INSERT INTO `shifts` (`id`, `code`, `name_ar`, `name_en`, `start_time`, `end_time`, `is_overnight`, `working_hours`, `break_duration_minutes`, `color`) VALUES
            (1, 'SH-STD', 'الوردية الصباحية العامة', 'Standard Morning Shift', '08:00:00', '17:00:00', 0, 8.00, 60, '#0d6efd'),
            (2, 'SH-EVE', 'الوردية المسائية', 'Evening Shift', '16:00:00', '00:00:00', 0, 7.00, 60, '#198754'),
            (3, 'SH-NIGHT', 'الوردية الليلية (تتجاوز منتصف الليل)', 'Night Shift (Overnight)', '22:00:00', '06:00:00', 1, 8.00, 60, '#6f42c1'),
            (4, 'SH-RAM', 'دوام شهر رمضان المبارك', 'Ramadan Schedule Shift', '09:00:00', '15:00:00', 0, 6.00, 0, '#fd7e14')
            ON DUPLICATE KEY UPDATE `name_ar` = VALUES(`name_ar`);

            INSERT INTO `work_calendars` (`id`, `name_ar`, `name_en`, `year`, `weekly_rest_days`, `is_default`) VALUES
            (1, 'التقويم الرسمي للمنشأة 2026', 'Official Corporate Calendar 2026', 2026, '5,6', 1)
            ON DUPLICATE KEY UPDATE `name_ar` = VALUES(`name_ar`);

            INSERT INTO `holidays` (`calendar_id`, `name_ar`, `name_en`, `start_date`, `end_date`, `is_recurring`) VALUES
            (1, 'يوم التأسيس السعودي', 'Saudi Founding Day', '2026-02-22', '2026-02-22', 1),
            (1, 'إجازة عيد الفطر المبارك', 'Eid Al-Fitr Holiday', '2026-03-20', '2026-03-24', 0),
            (1, 'إجازة عيد الأضحى المبارك', 'Eid Al-Adha Holiday', '2026-05-26', '2026-05-30', 0),
            (1, 'اليوم الوطني للمملكة', 'Saudi National Day', '2026-09-23', '2026-09-23', 1)
            ON DUPLICATE KEY UPDATE `name_ar` = VALUES(`name_ar`);
        ");
    }

    private function seedLeavesAndSalaryComponents(PDO $pdo): void
    {
        $pdo->exec("
            INSERT INTO `leave_types` (`id`, `code`, `name_ar`, `name_en`, `default_days_per_year`, `is_paid`, `is_carry_forward`, `max_carry_forward_days`, `color`) VALUES
            (1, 'LV-ANN', 'إجازة سنوية اعتيادية', 'Annual Vacation', 21.00, 1, 1, 10.00, '#0d6efd'),
            (2, 'LV-SICK', 'إجازة مرضية', 'Sick Leave', 30.00, 1, 0, 0.00, '#dc3545'),
            (3, 'LV-EMG', 'إجازة طارئة / عارضة', 'Emergency Leave', 5.00, 1, 0, 0.00, '#ffc107'),
            (4, 'LV-UNP', 'إجازة بدون راتب', 'Unpaid Leave', 30.00, 0, 0, 0.00, '#6c757d'),
            (5, 'LV-MAT', 'إجازة أمومة ووضع', 'Maternity Leave', 70.00, 1, 0, 0.00, '#e83e8c'),
            (6, 'LV-COMP', 'إجازة تعويضية', 'Compensatory Leave', 0.00, 1, 0, 0.00, '#20c997')
            ON DUPLICATE KEY UPDATE `name_ar` = VALUES(`name_ar`);

            INSERT INTO `salary_components` (`code`, `name_ar`, `name_en`, `type`, `is_fixed`, `default_amount`) VALUES
            ('BASIC', 'الراتب الأساسي', 'Basic Salary', 'earning', 1, 0.00),
            ('HOUSING', 'بدل سكن', 'Housing Allowance', 'earning', 1, 0.00),
            ('TRANSPORT', 'بدل انتقال ومواصلات', 'Transport Allowance', 'earning', 1, 0.00),
            ('MOBILE', 'بدل اتصال', 'Mobile Allowance', 'earning', 1, 0.00),
            ('NATURE', 'بدل طبيعة عمل ومخاطر', 'Nature of Work Allowance', 'earning', 1, 0.00),
            ('BONUS', 'مكافأة تميز وإنجاز', 'Performance Bonus', 'earning', 0, 0.00),
            ('OVERTIME', 'أجر ساعات العمل الإضافي', 'Overtime Pay', 'earning', 0, 0.00),
            ('ABS_DEDUCT', 'خصم أيام الغياب', 'Absence Deduction', 'deduction', 0, 0.00),
            ('LATE_DEDUCT', 'خصم التأخير عن العمل', 'Lateness Deduction', 'deduction', 0, 0.00),
            ('UNP_DEDUCT', 'خصم إجازة غير مدفوعة', 'Unpaid Leave Deduction', 'deduction', 0, 0.00),
            ('ADVANCE', 'استقطاع سلفة شخصية', 'Salary Advance Deduction', 'deduction', 0, 0.00)
            ON DUPLICATE KEY UPDATE `name_ar` = VALUES(`name_ar`);
        ");
    }

    private function seedUsersAndEmployees(PDO $pdo): void
    {
        $passwordHash = password_hash('Admin@123456', PASSWORD_BCRYPT);
        $empPasswordHash = password_hash('Emp@123456', PASSWORD_BCRYPT);

        // 1. Super Admin
        $pdo->prepare("INSERT INTO `users` (`id`, `username`, `email`, `password_hash`, `full_name_ar`, `full_name_en`, `phone`, `is_active`)
            VALUES (1, 'admin', 'admin@enterprise.local', ?, 'م. محمد المهندس', 'Eng. Mohammed Architect', '0501112233', 1)
            ON DUPLICATE KEY UPDATE `password_hash` = VALUES(`password_hash`)")->execute([$passwordHash]);

        // Attach super-admin role
        $roleId = $pdo->query("SELECT id FROM roles WHERE slug = 'super-admin'")->fetchColumn();
        $pdo->exec("INSERT IGNORE INTO `user_roles` (`user_id`, `role_id`) VALUES (1, {$roleId})");

        // Create Admin Employee Record
        $pdo->exec("INSERT INTO `employees` 
            (`id`, `user_id`, `employee_code`, `first_name_ar`, `last_name_ar`, `first_name_en`, `last_name_en`, `national_id`, `email`, `phone`, `branch_id`, `department_id`, `position_id`, `employment_type`, `employment_status`, `hire_date`, `basic_salary`, `housing_allowance`, `transport_allowance`, `other_allowances`, `bank_name`, `iban`)
            VALUES
            (1, 1, 'EMP-0001', 'محمد', 'المهندس', 'Mohammed', 'Architect', '1088776655', 'admin@enterprise.local', '0501112233', 1, 1, 1, 'full_time', 'active', '2024-01-01', 25000.00, 6250.00, 2500.00, 1500.00, 'Al Rajhi Bank', 'SA0380000000123456789012')
            ON DUPLICATE KEY UPDATE `basic_salary` = VALUES(`basic_salary`);
        ");

        // 2. HR Manager (Nasser Al-Otaibi)
        $pdo->prepare("INSERT INTO `users` (`id`, `username`, `email`, `password_hash`, `full_name_ar`, `full_name_en`, `phone`, `is_active`)
            VALUES (2, 'nasser.hr', 'hr.manager@enterprise.local', ?, 'ناصر العتيبي', 'Nasser Al-Otaibi', '0502223344', 1)
            ON DUPLICATE KEY UPDATE `password_hash` = VALUES(`password_hash`)")->execute([$empPasswordHash]);
        $hrRoleId = $pdo->query("SELECT id FROM roles WHERE slug = 'hr-manager'")->fetchColumn();
        $pdo->exec("INSERT IGNORE INTO `user_roles` (`user_id`, `role_id`) VALUES (2, {$hrRoleId})");

        $pdo->exec("INSERT INTO `employees` 
            (`id`, `user_id`, `employee_code`, `first_name_ar`, `last_name_ar`, `first_name_en`, `last_name_en`, `national_id`, `email`, `phone`, `branch_id`, `department_id`, `position_id`, `employment_type`, `employment_status`, `hire_date`, `basic_salary`, `housing_allowance`, `transport_allowance`, `other_allowances`, `bank_name`, `iban`)
            VALUES
            (2, 2, 'EMP-0002', 'ناصر', 'العتيبي', 'Nasser', 'Al-Otaibi', '1099887766', 'hr.manager@enterprise.local', '0502223344', 1, 2, 3, 'full_time', 'active', '2024-03-01', 18000.00, 4500.00, 1800.00, 1000.00, 'SNB AlAhli', 'SA0310000000987654321012')
            ON DUPLICATE KEY UPDATE `basic_salary` = VALUES(`basic_salary`);
        ");

        // 3. Payroll Specialist (Sarah Al-Ghamdi)
        $pdo->prepare("INSERT INTO `users` (`id`, `username`, `email`, `password_hash`, `full_name_ar`, `full_name_en`, `phone`, `is_active`)
            VALUES (3, 'sarah.payroll', 'payroll@enterprise.local', ?, 'سارة الغامدي', 'Sarah Al-Ghamdi', '0503334455', 1)
            ON DUPLICATE KEY UPDATE `password_hash` = VALUES(`password_hash`)")->execute([$empPasswordHash]);
        $payrollRoleId = $pdo->query("SELECT id FROM roles WHERE slug = 'payroll-manager'")->fetchColumn();
        $pdo->exec("INSERT IGNORE INTO `user_roles` (`user_id`, `role_id`) VALUES (3, {$payrollRoleId})");

        $pdo->exec("INSERT INTO `employees` 
            (`id`, `user_id`, `employee_code`, `first_name_ar`, `last_name_ar`, `first_name_en`, `last_name_en`, `national_id`, `email`, `phone`, `branch_id`, `department_id`, `position_id`, `manager_id`, `employment_type`, `employment_status`, `hire_date`, `basic_salary`, `housing_allowance`, `transport_allowance`, `other_allowances`, `bank_name`, `iban`)
            VALUES
            (3, 3, 'EMP-0003', 'سارة', 'الغامدي', 'Sarah', 'Al-Ghamdi', '1077665544', 'payroll@enterprise.local', '0503334455', 1, 3, 5, 2, 'full_time', 'active', '2024-06-01', 12000.00, 3000.00, 1200.00, 500.00, 'Riyad Bank', 'SA0320000000556677889900')
            ON DUPLICATE KEY UPDATE `basic_salary` = VALUES(`basic_salary`);
        ");

        // 4. Senior Software Engineer (Fahad Al-Zahrani)
        $pdo->prepare("INSERT INTO `users` (`id`, `username`, `email`, `password_hash`, `full_name_ar`, `full_name_en`, `phone`, `is_active`)
            VALUES (4, 'fahad.dev', 'fahad.dev@enterprise.local', ?, 'فهد الزهراني', 'Fahad Al-Zahrani', '0504445566', 1)
            ON DUPLICATE KEY UPDATE `password_hash` = VALUES(`password_hash`)")->execute([$empPasswordHash]);
        $empRoleId = $pdo->query("SELECT id FROM roles WHERE slug = 'employee'")->fetchColumn();
        $pdo->exec("INSERT IGNORE INTO `user_roles` (`user_id`, `role_id`) VALUES (4, {$empRoleId})");

        $pdo->exec("INSERT INTO `employees` 
            (`id`, `user_id`, `employee_code`, `first_name_ar`, `last_name_ar`, `first_name_en`, `last_name_en`, `national_id`, `email`, `phone`, `branch_id`, `department_id`, `position_id`, `manager_id`, `employment_type`, `employment_status`, `hire_date`, `basic_salary`, `housing_allowance`, `transport_allowance`, `other_allowances`, `bank_name`, `iban`)
            VALUES
            (4, 4, 'EMP-0004', 'فهد', 'الزهراني', 'Fahad', 'Al-Zahrani', '1066554433', 'fahad.dev@enterprise.local', '0504445566', 1, 1, 2, 1, 'full_time', 'active', '2024-07-15', 16000.00, 4000.00, 1600.00, 800.00, 'Alinma Bank', 'SA0305000000112233445566')
            ON DUPLICATE KEY UPDATE `basic_salary` = VALUES(`basic_salary`);
        ");

        // 5. Operations Technician (Jeddah Branch - Khalid Al-Harbi)
        $pdo->prepare("INSERT INTO `users` (`id`, `username`, `email`, `password_hash`, `full_name_ar`, `full_name_en`, `phone`, `is_active`)
            VALUES (5, 'khalid.ops', 'khalid.ops@enterprise.local', ?, 'خالد الحربي', 'Khalid Al-Harbi', '0505556677', 1)
            ON DUPLICATE KEY UPDATE `password_hash` = VALUES(`password_hash`)")->execute([$empPasswordHash]);
        $pdo->exec("INSERT IGNORE INTO `user_roles` (`user_id`, `role_id`) VALUES (5, {$empRoleId})");

        $pdo->exec("INSERT INTO `employees` 
            (`id`, `user_id`, `employee_code`, `first_name_ar`, `last_name_ar`, `first_name_en`, `last_name_en`, `national_id`, `email`, `phone`, `branch_id`, `department_id`, `position_id`, `manager_id`, `employment_type`, `employment_status`, `hire_date`, `basic_salary`, `housing_allowance`, `transport_allowance`, `other_allowances`, `bank_name`, `iban`)
            VALUES
            (5, 5, 'EMP-0005', 'خالد', 'الحربي', 'Khalid', 'Al-Harbi', '1055443322', 'khalid.ops@enterprise.local', '0505556677', 2, 5, 8, 2, 'full_time', 'active', '2024-09-01', 8000.00, 2000.00, 800.00, 500.00, 'Banque Saudi Fransi', 'SA0355000000998877665544')
            ON DUPLICATE KEY UPDATE `basic_salary` = VALUES(`basic_salary`);
        ");

        // Assign standard shifts to employees
        $pdo->exec("
            INSERT INTO `shift_assignments` (`employee_id`, `shift_id`, `start_date`, `policy_id`) VALUES
            (1, 1, '2024-01-01', 1),
            (2, 1, '2024-01-01', 1),
            (3, 1, '2024-01-01', 1),
            (4, 1, '2024-01-01', 1),
            (5, 3, '2024-01-01', 2) -- Khalid works overnight shift
            ON DUPLICATE KEY UPDATE `shift_id` = VALUES(`shift_id`);
        ");
    }

    private function seedBiometricDevicesAndMappings(PDO $pdo): void
    {
        $pdo->exec("
            INSERT INTO `biometric_devices` 
            (`id`, `name`, `branch_id`, `ip_address`, `port`, `protocol`, `comm_key`, `serial_number`, `model_name`, `firmware_version`, `location_description`, `is_active`, `last_sync_status`, `last_sync_at`) VALUES
            (1, 'بوابة المقر الرئيسي - ZKTeco SilkID', 1, '192.168.1.201', 4370, 'adms_push', '0', 'CJ49202601001', 'SilkID-A400', 'Ver 8.2.4.1', 'البوابة الرئيسية - الدور الأرضي برج العليا', 1, 'success', NOW()),
            (2, 'مدخل المكاتب الإدارية - ZKTeco uFace800', 1, '192.168.1.202', 4370, 'direct_tcp', '0', 'UF800202602002', 'uFace800-Plus', 'Ver 6.6.3', 'الدور الثاني - إدارة العمليات والموارد البشرية', 1, 'success', NOW()),
            (3, 'محاكي جهاز فرع جدة (Mock Simulator)', 2, '127.0.0.1', 4370, 'mock_adapter', '0', 'MOCK-ZK-JED-03', 'ZKTeco BioStation Pro Simulator', 'Ver 9.0.0', 'فرع المنطقة الغربية بجدة (محاكاة الاختبارات والاتصال)', 1, 'success', NOW())
            ON DUPLICATE KEY UPDATE `name` = VALUES(`name`);

            INSERT INTO `biometric_employee_mappings` (`device_id`, `employee_id`, `device_user_id`, `card_number`) VALUES
            (1, 1, '1001', '889901'),
            (1, 2, '1002', '889902'),
            (1, 3, '1003', '889903'),
            (1, 4, '1004', '889904'),
            (3, 5, '5001', '889905')
            ON DUPLICATE KEY UPDATE `device_user_id` = VALUES(`device_user_id`);
        ");
    }

    private function seedAttendanceData(PDO $pdo): void
    {
        // Seed raw biometric events and calculated attendance for the current month
        $today = date('Y-m-d');
        $yesterday = date('Y-m-d', strtotime('-1 day'));
        $twoDaysAgo = date('Y-m-d', strtotime('-2 days'));

        // Biometric raw events
        $pdo->exec("
            INSERT IGNORE INTO `biometric_raw_events` (`device_id`, `device_user_id`, `punch_time`, `punch_state`, `verify_type`, `processed`) VALUES
            (1, '1001', '{$yesterday} 07:55:12', 0, 1, 1),
            (1, '1001', '{$yesterday} 17:05:40', 1, 1, 1),
            (1, '1002', '{$yesterday} 08:08:23', 0, 1, 1),
            (1, '1002', '{$yesterday} 17:02:11', 1, 1, 1),
            (1, '1003', '{$yesterday} 08:24:50', 0, 1, 1), -- Late (24 mins)
            (1, '1003', '{$yesterday} 17:00:00', 1, 1, 1),
            (1, '1004', '{$yesterday} 08:00:00', 0, 1, 1),
            (1, '1004', '{$yesterday} 19:30:00', 1, 1, 1), -- Overtime
            -- Today punches
            (1, '1001', '{$today} 07:58:30', 0, 1, 1),
            (1, '1002', '{$today} 08:05:15', 0, 1, 1),
            (1, '1003', '{$today} 08:35:00', 0, 1, 1), -- Late 35 mins
            (1, '1004', '{$today} 07:59:00', 0, 1, 1);
        ");

        // Attendance records
        $pdo->exec("
            INSERT INTO `attendance_records` 
            (`employee_id`, `date`, `shift_id`, `check_in`, `check_out`, `status`, `work_hours`, `late_minutes`, `early_departure_minutes`, `overtime_hours`, `notes`) VALUES
            (1, '{$yesterday}', 1, '{$yesterday} 07:55:12', '{$yesterday} 17:05:40', 'present', 8.00, 0, 0, 0.00, 'حضور منتظم في الوقت المحدد'),
            (2, '{$yesterday}', 1, '{$yesterday} 08:08:23', '{$yesterday} 17:02:11', 'present', 8.00, 8, 0, 0.00, 'ضمن فترة السماح (15 دقيقة)'),
            (3, '{$yesterday}', 1, '{$yesterday} 08:24:50', '{$yesterday} 17:00:00', 'late', 7.60, 24, 0, 0.00, 'تأخير 24 دقيقة (تطبيق شريحة ربع يوم)'),
            (4, '{$yesterday}', 1, '{$yesterday} 08:00:00', '{$yesterday} 19:30:00', 'present', 10.50, 0, 0, 2.50, 'عمل إضافي معتمد ساعتين ونصف'),
            (5, '{$yesterday}', 3, '{$yesterday} 22:00:00', '{$today} 06:05:00', 'present', 8.00, 0, 0, 0.00, 'وردية ليلية مكتملة بنجاح'),
            -- Today
            (1, '{$today}', 1, '{$today} 07:58:30', NULL, 'present', 4.00, 0, 0, 0.00, 'مسجل حضور'),
            (2, '{$today}', 1, '{$today} 08:05:15', NULL, 'present', 4.00, 5, 0, 0.00, 'مسجل حضور'),
            (3, '{$today}', 1, '{$today} 08:35:00', NULL, 'late', 3.50, 35, 0, 0.00, 'تأخير 35 دقيقة'),
            (4, '{$today}', 1, '{$today} 07:59:00', NULL, 'present', 4.00, 0, 0, 0.00, 'مسجل حضور'),
            (5, '{$today}', 3, NULL, NULL, 'rest_day', 0.00, 0, 0, 0.00, 'يوم راحة أسبوعية')
            ON DUPLICATE KEY UPDATE `status` = VALUES(`status`), `check_in` = VALUES(`check_in`), `check_out` = VALUES(`check_out`);
        ");
    }

    private function seedLeaveBalancesAndRequests(PDO $pdo): void
    {
        $currentYear = (int)date('Y');

        for ($empId = 1; $empId <= 5; $empId++) {
            $pdo->exec("
                INSERT INTO `leave_balances` (`employee_id`, `leave_type_id`, `year`, `opening_balance`, `accrued_days`, `used_days`, `current_balance`) VALUES
                ({$empId}, 1, {$currentYear}, 21.00, 0.00, 2.00, 19.00),
                ({$empId}, 2, {$currentYear}, 30.00, 0.00, 0.00, 30.00),
                ({$empId}, 3, {$currentYear}, 5.00, 0.00, 0.00, 5.00)
                ON DUPLICATE KEY UPDATE `current_balance` = VALUES(`current_balance`);
            ");
        }

        // Add a sample approved leave request and a pending request
        $nextWeek = date('Y-m-d', strtotime('+7 days'));
        $nextWeekEnd = date('Y-m-d', strtotime('+9 days'));

        $pdo->exec("
            INSERT INTO `leave_requests` 
            (`employee_id`, `leave_type_id`, `start_date`, `end_date`, `total_days`, `reason`, `status`, `approved_by`, `approved_at`) VALUES
            (4, 1, '{$nextWeek}', '{$nextWeekEnd}', 3.00, 'إجازة اعتيادية سنوية للسفر وقضاء التزامات عائلية', 'approved', 2, NOW())
            ON DUPLICATE KEY UPDATE `status` = VALUES(`status`);

            INSERT INTO `leave_requests` 
            (`employee_id`, `leave_type_id`, `start_date`, `end_date`, `total_days`, `reason`, `status`) VALUES
            (3, 3, '{$nextWeek}', '{$nextWeek}', 1.00, 'إجازة طارئة لمراجعة مستشفى', 'pending')
            ON DUPLICATE KEY UPDATE `status` = VALUES(`status`);
        ");
    }

    private function seedPayrollData(PDO $pdo): void
    {
        $year = date('Y');
        $month = date('m');
        $startDate = "{$year}-{$month}-01";
        $endDate = date('Y-m-t', strtotime($startDate));
        $paymentDate = "{$year}-{$month}-27";

        $pdo->exec("
            INSERT INTO `payroll_periods` (`code`, `name_ar`, `name_en`, `start_date`, `end_date`, `payment_date`, `status`) VALUES
            ('PAY-{$year}-{$month}', 'مسير رواتب شهر {$month}-{$year}', 'Payroll Period {$month}-{$year}', '{$startDate}', '{$endDate}', '{$paymentDate}', 'open')
            ON DUPLICATE KEY UPDATE `name_ar` = VALUES(`name_ar`);
        ");

        $periodId = $pdo->query("SELECT id FROM `payroll_periods` WHERE code = 'PAY-{$year}-{$month}'")->fetchColumn();

        if ($periodId) {
            $pdo->exec("
                INSERT INTO `payroll_runs` 
                (`id`, `period_id`, `run_number`, `title`, `total_gross`, `total_deductions`, `total_net`, `employee_count`, `status`, `created_at`) VALUES
                (1, {$periodId}, 'RUN-{$year}{$month}-001', 'مسير رواتب الموظفين لشهر {$month}-{$year}', 108450.00, 1850.00, 106600.00, 5, 'calculated', NOW())
                ON DUPLICATE KEY UPDATE `status` = VALUES(`status`);
            ");
        }
    }

    private function seedRecruitmentAndOnboarding(PDO $pdo): void
    {
        $pdo->exec("
            INSERT INTO `vacancies` (`title_ar`, `title_en`, `department_id`, `position_id`, `openings_count`, `employment_type`, `experience_years`, `description`, `status`) VALUES
            ('مهندس برمجيات بايثون وذكاء اصطناعي', 'AI & Python Software Engineer', 1, 2, 2, 'full_time', 3, 'تطوير نماذج ذكاء اصطناعي وتكاملات سحابية', 'published'),
            ('أخصائي استقطاب مواهب', 'Talent Acquisition Specialist', 2, 4, 1, 'full_time', 2, 'إدارة عمليات التوظيف والمقابلات واستقطاب الكفاءات', 'published')
            ON DUPLICATE KEY UPDATE `title_ar` = VALUES(`title_ar`);

            INSERT INTO `candidates` (`vacancy_id`, `first_name`, `last_name`, `email`, `phone`, `status`, `notes`) VALUES
            (1, 'عبدالرحمن', 'السالم', 'a.alsalem@example.com', '0551234567', 'interviewed', 'مرشح ممتاز اجتاز المقابلة التقنية الأولى بدرجة 92%'),
            (2, 'نورة', 'المنصور', 'noura.m@example.com', '0559876543', 'shortlisted', 'سيرة ذاتية متطابقة مع متطلبات الوظيفة وخبرة سنتين ونصف')
            ON DUPLICATE KEY UPDATE `status` = VALUES(`status`);

            INSERT INTO `notifications` (`user_id`, `title`, `message`, `link`, `is_read`) VALUES
            (1, 'مرحباً بك في نظام الموارد البشرية والرواتب', 'تم تفعيل حسابك بنجاح كمدير للنظام بصلاحيات كاملة.', '/dashboard', 0),
            (2, 'طلب إجازة جديد قيد الانتظار', 'قام الموظف سارة الغامدي بتقديم طلب إجازة طارئة يتطلب مراجعتك.', '/leaves', 0)
            ON DUPLICATE KEY UPDATE `title` = VALUES(`title`);
        ");
    }
}
