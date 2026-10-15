<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class LeaveBalanceTransaction extends Model
{
    protected string $table = 'leave_balance_transactions';
    protected bool $timestamps = false;
    protected array $fillable = [
        'employee_id',
        'leave_type_id',
        'transaction_type',
        'amount',
        'balance_after',
        'reference_type',
        'reference_id',
        'notes'
    ];
}
