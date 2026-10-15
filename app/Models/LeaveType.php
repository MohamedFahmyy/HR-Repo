<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class LeaveType extends Model
{
    protected string $table = 'leave_types';
    protected array $fillable = [
        'code',
        'name_ar',
        'name_en',
        'default_days_per_year',
        'is_paid',
        'is_carry_forward',
        'max_carry_forward_days',
        'requires_attachment',
        'color',
        'is_active'
    ];
}
