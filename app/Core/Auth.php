<?php

declare(strict_types=1);

namespace App\Core;

class Auth
{
    private static ?Auth $instance = null;
    private ?array $user = null;
    private ?array $roles = null;
    private ?array $permissions = null;

    public function __construct()
    {
        $this->loadUserFromSession();
    }

    public static function getInstance(): Auth
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function loadUserFromSession(): void
    {
        $userId = Session::get('auth_user_id');
        if ($userId) {
            $user = Database::fetchOne(
                "SELECT u.*, e.id as employee_id, e.employee_code, e.first_name_ar, e.last_name_ar, e.first_name_en, e.last_name_en, e.photo, e.department_id, e.position_id
                 FROM `users` u
                 LEFT JOIN `employees` e ON e.user_id = u.id AND e.deleted_at IS NULL
                 WHERE u.id = :id AND u.is_active = 1 AND u.deleted_at IS NULL
                 LIMIT 1",
                ['id' => $userId]
            );

            if ($user) {
                $this->user = $user;
                $this->loadRolesAndPermissions((int)$user['id']);
            } else {
                $this->logout();
            }
        }
    }

    private function loadRolesAndPermissions(int $userId): void
    {
        // Fetch roles
        $rolesData = Database::fetchAll(
            "SELECT r.slug
             FROM `roles` r
             JOIN `user_roles` ur ON ur.role_id = r.id
             WHERE ur.user_id = :user_id",
            ['user_id' => $userId]
        );
        $this->roles = array_column($rolesData, 'slug');

        // Fetch permissions from role_permissions
        $permsData = Database::fetchAll(
            "SELECT DISTINCT p.slug
             FROM `permissions` p
             JOIN `role_permissions` rp ON rp.permission_id = p.id
             JOIN `user_roles` ur ON ur.role_id = rp.role_id
             WHERE ur.user_id = :user_id",
            ['user_id' => $userId]
        );
        $this->permissions = array_column($permsData, 'slug');
    }

    public function check(): bool
    {
        return $this->user !== null;
    }

    public function guest(): bool
    {
        return !$this->check();
    }

    public function user(): ?array
    {
        return $this->user;
    }

    public function id(): ?int
    {
        return $this->user ? (int)$this->user['id'] : null;
    }

    public function employeeId(): ?int
    {
        return $this->user && !empty($this->user['employee_id']) ? (int)$this->user['employee_id'] : null;
    }

    public function hasRole(string|array $roles): bool
    {
        if (!$this->check()) {
            return false;
        }

        // Super Admin has all roles
        if (in_array('super-admin', $this->roles ?? [], true) || in_array('super_admin', $this->roles ?? [], true)) {
            return true;
        }

        $roles = is_array($roles) ? $roles : [$roles];
        foreach ($roles as $role) {
            $normRole = str_replace('_', '-', $role);
            if (in_array($role, $this->roles ?? [], true) || in_array($normRole, $this->roles ?? [], true)) {
                return true;
            }
        }
        return false;
    }

    public function can(string $permission): bool
    {
        if (!$this->check()) {
            return false;
        }

        // Super Admin has all permissions
        if (in_array('super-admin', $this->roles ?? [], true) || in_array('super_admin', $this->roles ?? [], true)) {
            return true;
        }

        $normPerm = str_replace('_', '.', $permission);
        return in_array($permission, $this->permissions ?? [], true) || in_array($normPerm, $this->permissions ?? [], true);
    }

    public function attempt(string $login, string $password): bool
    {
        $user = Database::fetchOne(
            "SELECT * FROM `users` WHERE (`email` = :l1 OR `username` = :l2) AND `is_active` = 1 AND `deleted_at` IS NULL LIMIT 1",
            ['l1' => $login, 'l2' => $login]
        );

        if ($user && password_verify($password, $user['password_hash'])) {
            $this->login($user);
            return true;
        }

        return false;
    }

    public function login(array $user): void
    {
        Session::regenerate();
        Session::set('auth_user_id', $user['id']);
        $this->user = $user;
        $this->loadRolesAndPermissions((int)$user['id']);

        // Update last login
        Database::execute(
            "UPDATE `users` SET `last_login_at` = :now, `last_login_ip` = :ip WHERE `id` = :id",
            [
                'now' => date('Y-m-d H:i:s'),
                'ip' => (new Request())->ip(),
                'id' => $user['id']
            ]
        );
    }

    public function logout(): void
    {
        $this->user = null;
        $this->roles = null;
        $this->permissions = null;
        Session::remove('auth_user_id');
        Session::destroy();
        Session::regenerate();
    }
}
