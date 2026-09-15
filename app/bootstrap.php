<?php

declare(strict_types=1);

if (!defined('APP_ROOT')) {
    define('APP_ROOT', dirname(__DIR__));
}

function load_env_file(string $path): void
{
    if (!is_file($path)) {
        return;
    }

    foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
            continue;
        }
        [$key, $value] = array_map('trim', explode('=', $line, 2));
        if ($key === '' || getenv($key) !== false) {
            continue;
        }
        $value = trim($value, "\"'");
        putenv($key . '=' . $value);
        $_ENV[$key] = $value;
    }
}

function env_value(string $key, mixed $default = null): mixed
{
    $value = getenv($key);
    return $value === false ? $default : $value;
}

load_env_file(APP_ROOT . '/.env');

$GLOBALS['app_config'] = require APP_ROOT . '/config/app.php';

require_once APP_ROOT . '/app/Core/Database.php';
require_once APP_ROOT . '/app/Core/Csrf.php';
require_once APP_ROOT . '/app/Core/Auth.php';
require_once APP_ROOT . '/app/helpers.php';
require_once APP_ROOT . '/app/Services/Mailer.php';
require_once APP_ROOT . '/app/Services/EmailVerificationService.php';
require_once APP_ROOT . '/app/Services/PasswordResetService.php';
require_once APP_ROOT . '/app/Services/BookingService.php';

date_default_timezone_set('UTC');

if (PHP_SAPI !== 'cli') {
    $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    session_name('IDOPONTFOGLALO_SESSION');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => $secure,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }

    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('Referrer-Policy: same-origin');
    header("Content-Security-Policy: default-src 'self'; style-src 'self'; script-src 'self'; img-src 'self' data:; form-action 'self'; base-uri 'self'; frame-ancestors 'none'");
}

set_exception_handler(static function (Throwable $exception): void {
    $line = sprintf(
        "[%s] %s in %s:%d%s",
        gmdate('c'),
        $exception->getMessage(),
        $exception->getFile(),
        $exception->getLine(),
        PHP_EOL
    );
    @file_put_contents(APP_ROOT . '/storage/app.log', $line, FILE_APPEND | LOCK_EX);

    if (PHP_SAPI === 'cli') {
        fwrite(STDERR, $line);
        return;
    }

    http_response_code(500);
    header('Content-Type: text/html; charset=UTF-8');
    $message = config('debug') ? $exception->getMessage() : 'Váratlan hiba történt. Kérjük, próbálja újra később.';
    $safeMessage = htmlspecialchars($message, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    echo '<!doctype html><html lang="hu"><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">'
        . '<title>Hiba</title><body><main><h1>Hiba</h1><p>' . $safeMessage . '</p></main></body></html>';
});
