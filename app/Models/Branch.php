<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class Branch extends Model
{
    protected string $table = 'branches';
    protected array $fillable = [
        'code',
        'name_ar',
        'name_en',
        'timezone',
        'address',
        'phone',
        'is_active'
    ];
}
