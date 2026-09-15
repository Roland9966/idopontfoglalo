<?php

declare(strict_types=1);

return [
    'name' => 'Iskolai időpontfoglaló',
    'env' => env_value('APP_ENV', 'local'),
    'debug' => filter_var(env_value('APP_DEBUG', 'false'), FILTER_VALIDATE_BOOL),
    'url' => rtrim((string) env_value('APP_URL', ''), '/'),
    'timezone' => env_value('APP_TIMEZONE', 'Europe/Belgrade'),
    'cancellation_hours' => (int) env_value('CANCELLATION_HOURS', '24'),
    'email_verification_hours' => (int) env_value('EMAIL_VERIFICATION_HOURS', '24'),
    'password_reset_minutes' => (int) env_value('PASSWORD_RESET_MINUTES', '60'),
    'db' => [
        'host' => env_value('DB_HOST', '127.0.0.1'),
        'port' => (int) env_value('DB_PORT', '3306'),
        'name' => env_value('DB_NAME', 'idopontfoglalo'),
        'user' => env_value('DB_USER', 'root'),
        'pass' => env_value('DB_PASS', ''),
    ],
    'mail' => [
        'driver' => env_value('MAIL_DRIVER', 'log'),
        'from_address' => env_value('MAIL_FROM_ADDRESS', env_value('MAIL_FROM', 'no-reply@iskola.local')),
        'from_name' => env_value('MAIL_FROM_NAME', 'Időpontfoglaló'),
        'smtp' => [
            'host' => env_value('SMTP_HOST', 'smtp.gmail.com'),
            'port' => (int) env_value('SMTP_PORT', '587'),
            'encryption' => env_value('SMTP_ENCRYPTION', 'tls'),
            'username' => env_value('SMTP_USERNAME', ''),
            'password' => env_value('SMTP_PASSWORD', ''),
            'timeout' => (int) env_value('SMTP_TIMEOUT', '20'),
        ],
    ],
];
