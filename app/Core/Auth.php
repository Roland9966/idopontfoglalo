<?php

declare(strict_types=1);

namespace App\Core;

use DateTimeImmutable;
use DateTimeZone;
use PDO;

final class Auth
{
    private static bool $loaded = false;
    private static ?array $currentUser = null;
    private static string $lastFailureReason = 'invalid_credentials';

    public static function user(): ?array
    {
        if (self::$loaded) {
            return self::$currentUser;
        }
        self::$loaded = true;

        $userId = $_SESSION['user_id'] ?? null;
        if (!$userId) {
            return null;
        }

        $stmt = Database::connection()->prepare(
            'SELECT u.id, u.name, u.student_index, u.email, u.email_verified_at, u.status,
                r.code AS role_code, r.name AS role_name
             FROM users u JOIN roles r ON r.id = u.role_id
             WHERE u.id = :id LIMIT 1'
        );
        $stmt->execute(['id' => $userId]);
        $user = $stmt->fetch();

        if (!$user || $user['status'] !== 'active' || empty($user['email_verified_at'])) {
            self::logout();
            return null;
        }

        self::$currentUser = $user;
        return self::$currentUser;
    }

    public static function attempt(string $email, string $password): bool
    {
        self::$lastFailureReason = 'invalid_credentials';
        $email = mb_strtolower(trim($email));
        $stmt = Database::connection()->prepare(
            'SELECT u.*, r.code AS role_code FROM users u
             JOIN roles r ON r.id = u.role_id WHERE u.email = :email LIMIT 1'
        );
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch();

        if (!$user) {
            \audit_log(null, 'LOGIN_FAILED', 'session', null, null, ['reason' => 'invalid_credentials']);
            return false;
        }

        $now = new DateTimeImmutable('now', new DateTimeZone('UTC'));
        if (!empty($user['locked_until']) && new DateTimeImmutable($user['locked_until'], new DateTimeZone('UTC')) > $now) {
            self::$lastFailureReason = 'locked';
            \audit_log((int) $user['id'], 'LOGIN_BLOCKED', 'user', (int) $user['id'], null, ['reason' => 'locked']);
            return false;
        }

        if (!password_verify($password, $user['password_hash'])) {
            $attempts = (int) $user['failed_login_attempts'] + 1;
            $lockedUntil = $attempts >= 5 ? $now->modify('+15 minutes')->format('Y-m-d H:i:s') : null;
            $update = Database::connection()->prepare(
                'UPDATE users SET failed_login_attempts = :attempts, locked_until = :locked_until WHERE id = :id'
            );
            $update->execute(['attempts' => $attempts, 'locked_until' => $lockedUntil, 'id' => $user['id']]);
            \audit_log((int) $user['id'], 'LOGIN_FAILED', 'user', (int) $user['id']);
            return false;
        }

        if ($user['status'] !== 'active') {
            self::$lastFailureReason = 'inactive';
            \audit_log((int) $user['id'], 'LOGIN_BLOCKED', 'user', (int) $user['id'], null, ['reason' => 'inactive']);
            return false;
        }

        if (empty($user['email_verified_at'])) {
            self::$lastFailureReason = 'email_unverified';
            \audit_log((int) $user['id'], 'LOGIN_BLOCKED', 'user', (int) $user['id'], null, ['reason' => 'email_unverified']);
            return false;
        }

        $update = Database::connection()->prepare(
            'UPDATE users SET failed_login_attempts = 0, locked_until = NULL, last_login_at = UTC_TIMESTAMP() WHERE id = :id'
        );
        $update->execute(['id' => $user['id']]);
        self::loginById((int) $user['id']);
        \audit_log((int) $user['id'], 'LOGIN_SUCCEEDED', 'user', (int) $user['id']);
        return true;
    }

    public static function failureReason(): string
    {
        return self::$lastFailureReason;
    }

    public static function loginById(int $userId): void
    {
        session_regenerate_id(true);
        $_SESSION['user_id'] = $userId;
        self::$loaded = false;
        self::$currentUser = null;
    }

    public static function logout(): void
    {
        $userId = $_SESSION['user_id'] ?? null;
        if ($userId) {
            \audit_log((int) $userId, 'LOGOUT', 'session', null);
        }
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
        }
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
        self::$loaded = false;
        self::$currentUser = null;
    }

    public static function hasRole(string ...$roles): bool
    {
        $user = self::user();
        return $user !== null && in_array($user['role_code'], $roles, true);
    }
}
