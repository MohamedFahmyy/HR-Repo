<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class BiometricSyncLog extends Model
{
    protected string $table = 'biometric_sync_logs';
    protected bool $timestamps = false;
    protected array $fillable = [
        'device_id',
        'sync_type',
        'status',
        'records_fetched',
        'records_inserted',
        'error_message',
        'duration_seconds'
    ];
}
