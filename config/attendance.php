<?php

declare(strict_types=1);

return [
    'default_grace_period_minutes' => (int)($_ENV['ATTENDANCE_GRACE_PERIOD_MINUTES'] ?? 15),
    'half_day_threshold_hours' => (float)($_ENV['ATTENDANCE_HALF_DAY_THRESHOLD_HOURS'] ?? 4.0),
    'full_day_hours' => (float)($_ENV['ATTENDANCE_FULL_DAY_HOURS'] ?? 8.0),
    'overtime' => [
        'minimum_minutes' => (int)($_ENV['OVERTIME_MINIMUM_MINUTES'] ?? 30),
        'regular_multiplier' => (float)($_ENV['OVERTIME_REGULAR_MULTIPLIER'] ?? 1.5),
        'rest_day_multiplier' => (float)($_ENV['OVERTIME_REST_DAY_MULTIPLIER'] ?? 2.0),
        'holiday_multiplier' => (float)($_ENV['OVERTIME_HOLIDAY_MULTIPLIER'] ?? 2.5),
    ],
    'lateness_tiers' => [
        [
            'min_minutes' => 1,
            'max_minutes' => 15,
            'penalty_type' => 'warning', // first warning/grace
            'deduction_ratio' => 0.0,
        ],
        [
            'min_minutes' => 16,
            'max_minutes' => 30,
            'penalty_type' => 'quarter_day',
            'deduction_ratio' => 0.25,
        ],
        [
            'min_minutes' => 31,
            'max_minutes' => 60,
            'penalty_type' => 'half_day',
            'deduction_ratio' => 0.5,
        ],
        [
            'min_minutes' => 61,
            'max_minutes' => 9999,
            'penalty_type' => 'full_day',
            'deduction_ratio' => 1.0,
        ],
    ],
    'zkteco' => [
        'default_port' => (int)($_ENV['ZKTECO_DEFAULT_PORT'] ?? 4370),
        'timeout' => (int)($_ENV['ZKTECO_CONNECTION_TIMEOUT'] ?? 5),
        'udp_retries' => (int)($_ENV['ZKTECO_UDP_RETRIES'] ?? 3),
        'adms_auth_key' => $_ENV['ZKTECO_ADMS_AUTH_KEY'] ?? 'enterprise_adms_secret_token_2026',
    ]
];
