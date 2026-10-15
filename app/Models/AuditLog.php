<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Auth;
use App\Core\Database;
use App\Core\Model;
use App\Core\Request;

class AuditLog extends Model
{
    protected string $table = 'audit_logs';
    protected array $fillable = [
        'user_id',
        'action',
        'entity_type',
        'entity_id',
        'old_values',
        'new_values',
        'ip_address',
        'user_agent'
    ];

    public static function log(string $action, string $entityType, string|int|null $entityId = null, ?array $oldValues = null, ?array $newValues = null): void
    {
        $userId = Auth::getInstance()->id();
        $request = new Request();

        $sql = "INSERT INTO `audit_logs` 
                (`user_id`, `action`, `entity_type`, `entity_id`, `old_values`, `new_values`, `ip_address`, `user_agent`)
                VALUES (:user_id, :action, :entity_type, :entity_id, :old_values, :new_values, :ip_address, :user_agent)";

        Database::execute($sql, [
            'user_id' => $userId,
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $entityId ? (string)$entityId : null,
            'old_values' => $oldValues ? json_encode($oldValues, JSON_UNESCAPED_UNICODE) : null,
            'new_values' => $newValues ? json_encode($newValues, JSON_UNESCAPED_UNICODE) : null,
            'ip_address' => $request->ip(),
            'user_agent' => substr($request->userAgent(), 0, 500),
        ]);
    }

    public function allWithUser(int $limit = 100): array
    {
        $sql = "SELECT a.*, u.username, u.full_name_ar, u.email
                FROM `audit_logs` a
                LEFT JOIN `users` u ON u.id = a.user_id
                ORDER BY a.id DESC
                LIMIT {$limit}";

        return Database::fetchAll($sql);
    }
}
