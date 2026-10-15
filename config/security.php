<?php

declare(strict_types=1);

return [
    'session' => [
        'name' => 'ENT_HR_SESSID',
        'lifetime' => (int)($_ENV['SESSION_LIFETIME'] ?? 7200),
        'secure' => filter_var($_ENV['SESSION_SECURE_COOKIE'] ?? false, FILTER_VALIDATE_BOOLEAN),
        'httponly' => filter_var($_ENV['SESSION_HTTPONLY'] ?? true, FILTER_VALIDATE_BOOLEAN),
        'samesite' => $_ENV['SESSION_SAMESITE'] ?? 'Lax',
    ],
    'csrf' => [
        'enabled' => filter_var($_ENV['CSRF_PROTECTION'] ?? true, FILTER_VALIDATE_BOOLEAN),
        'token_length' => 32,
        'form_key' => '_csrf_token',
        'header_key' => 'X-CSRF-TOKEN',
    ],
    'rate_limiting' => [
        'login_max_attempts' => (int)($_ENV['RATE_LIMIT_LOGIN_MAX_ATTEMPTS'] ?? 5),
        'login_lockout_minutes' => (int)($_ENV['RATE_LIMIT_LOCKOUT_MINUTES'] ?? 15),
    ],
];
