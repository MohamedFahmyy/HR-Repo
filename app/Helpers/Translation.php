<?php

declare(strict_types=1);

namespace App\Helpers;

use App\Core\Session;

class Translation
{
    private static array $strings = [
        'ar' => [
            'app_name' => 'نظام الموارد البشرية والرواتب الذكي',
            'dashboard' => 'لوحة التحكم',
            'employees' => 'الموظفون',
            'departments' => 'الأقسام والوظائف',
            'attendance' => 'الحضور والانصراف',
            'devices' => 'أجهزة البصمة ZKTeco',
            'shifts' => 'الورديات ومواعيد العمل',
            'leaves' => 'الإجازات',
            'overtime' => 'العمل الإضافي',
            'payroll' => 'الرواتب والمسيرات',
            'recruitment' => 'التوظيف والاستقطاب',
            'performance' => 'تقييم الأداء',
            'reports' => 'التقارير والإحصاءات',
            'settings' => 'إعدادات النظام',
            'ess' => 'بوابة الموظف الذاتية',
            'profile' => 'الملف الشخصي',
            'logout' => 'تسجيل الخروج',
            'login' => 'تسجيل الدخول',
            'email' => 'البريد الإلكتروني',
            'password' => 'كلمة المرور',
            'remember_me' => 'تذكرني',
            'save' => 'حفظ',
            'cancel' => 'إلغاء',
            'delete' => 'حذف',
            'edit' => 'تعديل',
            'view' => 'عرض',
            'create' => 'إضافة جديد',
            'search' => 'بحث...',
            'filter' => 'تصفية',
            'export' => 'تصدير',
            'print' => 'طباعة',
            'actions' => 'الإجراءات',
            'status' => 'الحالة',
            'date' => 'التاريخ',
            'success' => 'تم بنجاح',
            'error' => 'حدث خطأ',
            'active' => 'نشط',
            'inactive' => 'غير نشط',
            'total_employees' => 'إجمالي الموظفين',
            'present_today' => 'حضور اليوم',
            'absent_today' => 'غياب اليوم',
            'late_today' => 'تأخير اليوم',
            'on_leave' => 'في إجازة',
            'pending_requests' => 'طلبات قيد المراجعة',
            'device_status' => 'حالة أجهزة البصمة',
            'total_payroll' => 'إجمالي مسير الرواتب',
            'check_in' => 'تسجيل حضور',
            'check_out' => 'تسجيل انصراف',
        ],
        'en' => [
            'app_name' => 'Enterprise HR & Payroll System',
            'dashboard' => 'Dashboard',
            'employees' => 'Employees',
            'departments' => 'Departments & Positions',
            'attendance' => 'Attendance',
            'devices' => 'ZKTeco Biometric Devices',
            'shifts' => 'Shifts & Schedules',
            'leaves' => 'Leaves & Time-off',
            'overtime' => 'Overtime',
            'payroll' => 'Payroll & Payslips',
            'recruitment' => 'Recruitment',
            'performance' => 'Performance',
            'reports' => 'Reports & Analytics',
            'settings' => 'Settings',
            'ess' => 'Employee Self-Service',
            'profile' => 'My Profile',
            'logout' => 'Logout',
            'login' => 'Login',
            'email' => 'Email',
            'password' => 'Password',
            'remember_me' => 'Remember Me',
            'save' => 'Save',
            'cancel' => 'Cancel',
            'delete' => 'Delete',
            'edit' => 'Edit',
            'view' => 'View',
            'create' => 'Create New',
            'search' => 'Search...',
            'filter' => 'Filter',
            'export' => 'Export',
            'print' => 'Print',
            'actions' => 'Actions',
            'status' => 'Status',
            'date' => 'Date',
            'success' => 'Success',
            'error' => 'Error',
            'active' => 'Active',
            'inactive' => 'Inactive',
            'total_employees' => 'Total Employees',
            'present_today' => 'Present Today',
            'absent_today' => 'Absent Today',
            'late_today' => 'Late Today',
            'on_leave' => 'On Leave',
            'pending_requests' => 'Pending Requests',
            'device_status' => 'Biometric Devices',
            'total_payroll' => 'Total Payroll',
            'check_in' => 'Check In',
            'check_out' => 'Check Out',
        ]
    ];

    public static function getLocale(): string
    {
        return Session::get('locale', config('app.locale', 'ar'));
    }

    public static function setLocale(string $locale): void
    {
        if (in_array($locale, ['ar', 'en'], true)) {
            Session::set('locale', $locale);
        }
    }

    public static function get(string $key, array $replace = []): string
    {
        $locale = self::getLocale();
        $fallback = config('app.fallback_locale', 'en');

        $line = self::$strings[$locale][$key] ?? self::$strings[$fallback][$key] ?? $key;

        foreach ($replace as $placeholder => $value) {
            $line = str_replace(':' . $placeholder, (string)$value, $line);
        }

        return $line;
    }
}
