<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use App\Core\Model;

class User extends Model
{
    protected string $table = 'users';
    protected array $fillable = [
        'username',
        'email',
        'password_hash',
        'full_name_ar',
        'full_name_en',
        'phone',
        'avatar',
        'is_active',
        'last_login_at',
        'last_login_ip'
    ];
    protected bool $softDeletes = true;

    public function findByEmail(string $email): ?array
    {
        return $this->firstWhere('email', $email);
    }

    public function findByUsername(string $username): ?array
    {
        return $this->firstWhere('username', $username);
    }

    public function findByUsernameOrEmail(string $login): ?array
    {
        $sql = "SELECT * FROM `{$this->table}` WHERE (`username` = :l1 OR `email` = :l2)";
        if ($this->softDeletes) {
            $sql .= " AND `deleted_at` IS NULL";
        }
        $sql .= " LIMIT 1";

        return Database::fetchOne($sql, ['l1' => $login, 'l2' => $login]);
    }

    public function getRoles(int $userId): array
    {
        return Database::fetchAll(
            "SELECT r.* FROM `roles` r 
             JOIN `user_roles` ur ON ur.role_id = r.id 
             WHERE ur.user_id = :user_id",
            ['user_id' => $userId]
        );
    }

    public function assignRole(int $userId, int $roleId): bool
    {
        return Database::execute(
            "INSERT IGNORE INTO `user_roles` (`user_id`, `role_id`) VALUES (:user_id, :role_id)",
            ['user_id' => $userId, 'role_id' => $roleId]
        );
    }

    public function syncRoles(int $userId, array $roleIds): void
    {
        Database::execute("DELETE FROM `user_roles` WHERE `user_id` = :user_id", ['user_id' => $userId]);
        foreach ($roleIds as $roleId) {
            $this->assignRole($userId, (int)$roleId);
        }
    }
}
