<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Models\AuditLog;
use App\Models\User;

class AuthController extends Controller
{
    private User $userModel;

    public function __construct()
    {
        $this->userModel = new User();
    }

    public function showLogin(Request $request): Response
    {
        if (Auth::getInstance()->check()) {
            return $this->redirect(url('dashboard'));
        }

        return $this->view('auth/login', [
            'title' => 'تسجيل الدخول | نظام الموارد البشرية والرواتب',
        ], 'layouts/auth');
    }

    public function login(Request $request): Response
    {
        $data = $this->validate($request, [
            'login' => 'required',
            'password' => 'required|min:6',
        ]);

        $ip = $request->ip();
        $throttleKey = 'login_attempts_' . md5($ip . '_' . strtolower($data['login']));
        $attempts = (int)Session::get($throttleKey, 0);

        $maxAttempts = (int)config('security.rate_limiting.login_max_attempts', 5);
        if ($attempts >= $maxAttempts) {
            $lockoutMinutes = (int)config('security.rate_limiting.login_lockout_minutes', 15);
            $this->flash('error', "تم تجاوز الحد الأقصى لمحاولات تسجيل الدخول. يرجى الانتظار {$lockoutMinutes} دقيقة.");
            return $this->redirect(url('login'));
        }

        $login = trim($data['login']);
        $user = Database::fetchOne(
            "SELECT * FROM `users` WHERE (`email` = :email OR `username` = :username) AND `deleted_at` IS NULL LIMIT 1",
            ['email' => $login, 'username' => $login]
        );

        if (!$user || !password_verify($data['password'], $user['password_hash'])) {
            Session::set($throttleKey, $attempts + 1);
            AuditLog::log('login_failed', 'User', $user['id'] ?? null, null, ['login' => $login, 'ip' => $ip]);
            $this->flash('error', 'بيانات الاعتماد المدخلة غير صحيحة.');
            return $this->redirect(url('login'));
        }

        if ((int)$user['is_active'] !== 1) {
            $this->flash('error', 'هذا الحساب معطل حالياً. يرجى مراجعة إدارة الموارد البشرية.');
            return $this->redirect(url('login'));
        }

        // Reset rate limiter on success
        Session::remove($throttleKey);

        // Perform login and session regeneration
        Auth::getInstance()->login($user);
        AuditLog::log('login_success', 'User', $user['id']);

        $this->flash('success', "مرحباً بك مجدداً، {$user['full_name_ar']}!");

        // If employee role only, redirect to ESS
        if (Auth::getInstance()->hasRole('employee') && !Auth::getInstance()->hasRole(['super-admin', 'hr-manager', 'payroll-manager', 'attendance-manager'])) {
            return $this->redirect(url('ess'));
        }

        return $this->redirect(url('dashboard'));
    }

    public function logout(Request $request): Response
    {
        $userId = Auth::getInstance()->id();
        if ($userId) {
            AuditLog::log('logout', 'User', $userId);
        }

        Auth::getInstance()->logout();
        $this->flash('info', 'تم تسجيل الخروج بنجاح.');

        return $this->redirect(url('login'));
    }
}
