<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class PayrollPeriod extends Model
{
    protected string $table = 'payroll_periods';
    protected array $fillable = [
        'code',
        'name_ar',
        'name_en',
        'start_date',
        'end_date',
        'payment_date',
        'status'
    ];
}
