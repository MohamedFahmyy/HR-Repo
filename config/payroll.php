<?php

declare(strict_types=1);

return [
    'currency' => $_ENV['PAYROLL_CURRENCY'] ?? 'SAR',
    'currency_symbol' => $_ENV['PAYROLL_CURRENCY_SYMBOL'] ?? 'ر.س',
    'decimal_precision' => (int)($_ENV['PAYROLL_DECIMAL_PRECISION'] ?? 2),
    'default_days_per_month' => (int)($_ENV['PAYROLL_DEFAULT_DAYS_PER_MONTH'] ?? 30),
    'hours_per_day' => (int)($_ENV['PAYROLL_HOURS_PER_DAY'] ?? 8),
    'allowances' => [
        'housing' => ['name' => 'بدل سكن / Housing Allowance', 'taxable' => false],
        'transportation' => ['name' => 'بدل مواصلات / Transportation Allowance', 'taxable' => false],
        'food' => ['name' => 'بدل وجبة / Food Allowance', 'taxable' => false],
        'mobile' => ['name' => 'بدل اتصال / Mobile Allowance', 'taxable' => false],
        'nature_of_work' => ['name' => 'بدل طبيعة عمل / Nature of Work', 'taxable' => false],
    ],
    'deductions' => [
        'absence' => ['name' => 'خصم غياب / Absence Deduction'],
        'lateness' => ['name' => 'خصم تأخير / Lateness Deduction'],
        'unpaid_leave' => ['name' => 'إجازة بدون راتب / Unpaid Leave Deduction'],
        'advance' => ['name' => 'سلفة / Salary Advance'],
        'disciplinary' => ['name' => 'جزاء إداري / Disciplinary Action'],
    ]
];
