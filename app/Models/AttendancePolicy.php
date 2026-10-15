<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class AttendancePolicy extends Model
{
    protected string $table = 'attendance_policies';
    protected array $fillable = [
        'name_ar',
        'name_en',
        'grace_period_minutes',
        'early_departure_grace_minutes',
        'half_day_threshold_hours',
        'full_day_hours',
        'overtime_minimum_minutes',
        'overtime_regular_rate',
        'overtime_rest_day_rate',
        'overtime_holiday_rate',
        'is_default'
    ];
}
