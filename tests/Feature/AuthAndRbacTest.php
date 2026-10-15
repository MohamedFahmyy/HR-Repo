<?php

declare(strict_types=1);

namespace Tests\Feature;

use PHPUnit\Framework\TestCase;
use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Database;
use App\Models\User;

class AuthAndRbacTest extends TestCase
{
    private Auth $auth;

    protected function setUp(): void
    {
        parent::setUp();
        $this->auth = Auth::getInstance();
    }

    public function testPasswordHashingAndVerification(): void
    {
        $rawPassword = 'SecureEnterprisePassword@2026';
        $hashed = password_hash($rawPassword, PASSWORD_DEFAULT);

        $this->assertNotSame($rawPassword, $hashed);
        $this->assertTrue(password_verify($rawPassword, $hashed));
        $this->assertFalse(password_verify('WrongPassword123', $hashed));
    }

    public function testAuthenticationWithValidCredentials(): void
    {
        $userModel = new User();
        $admin = $userModel->findByUsernameOrEmail('admin@enterprise.local');
        $this->assertNotNull($admin, 'Seeded admin user must exist.');

        $authSuccess = $this->auth->attempt('admin@enterprise.local', 'Admin@123456');
        $this->assertTrue($authSuccess);
        $this->assertTrue($this->auth->check());
        $this->assertSame((int)$admin['id'], $this->auth->id());

        // Test RBAC permissions
        $this->assertTrue($this->auth->hasRole('super_admin'));
        $this->assertTrue($this->auth->can('manage_employees'));
        $this->assertTrue($this->auth->can('manage_payroll'));
        $this->assertTrue($this->auth->can('view_reports'));

        // Logout
        $this->auth->logout();
        $this->assertFalse($this->auth->check());
    }

    public function testAuthenticationWithInvalidCredentialsFails(): void
    {
        $authSuccess = $this->auth->attempt('admin@enterprise.local', 'TotallyWrongPassword');
        $this->assertFalse($authSuccess);
        $this->assertFalse($this->auth->check());
    }

    public function testCsrfTokenGenerationAndValidation(): void
    {
        $token = Csrf::generate();
        $this->assertNotEmpty($token);
        $this->assertSame(64, strlen($token));

        $this->assertTrue(Csrf::validate($token));
        $this->assertFalse(Csrf::validate('invalid-token-12345'));
        $this->assertFalse(Csrf::validate(''));
    }

    public function testSqlInjectionResistanceViaPreparedStatements(): void
    {
        // SQL injection payload attempting authentication bypass
        $maliciousInput = "' OR '1'='1' --";

        $userModel = new User();
        $result = $userModel->findByUsernameOrEmail($maliciousInput);
        $this->assertNull($result, 'SQL injection payload must return null when executed through prepared statements.');

        // Attempting injection into Database::fetchAll
        $safeResult = Database::fetchAll("SELECT * FROM `users` WHERE `email` = :email", ['email' => $maliciousInput]);
        $this->assertEmpty($safeResult);
    }
}
