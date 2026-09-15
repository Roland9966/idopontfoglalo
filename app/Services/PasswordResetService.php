<?php

declare(strict_types=1);

namespace App\Services;

use DateTimeImmutable;
use DateTimeZone;
use PDO;
use Throwable;

final class PasswordResetService
{
    public function __construct(private PDO $db, private Mailer $mailer)
    {
    }

    public function requestForEmail(string $email): bool
    {
        $stmt = $this->db->prepare(
            "SELECT id,name,email FROM users
             WHERE email=:email AND status='active' AND email_verified_at IS NOT NULL LIMIT 1"
        );
        $stmt->execute(['email' => mb_strtolower(trim($email))]);
        $user = $stmt->fetch();
        if (!$user) {
            return false;
        }

        $rawToken = bin2hex(random_bytes(32));
        $tokenHash = hash('sha256', $rawToken);
        $validMinutes = max(15, min(1440, (int) \config('password_reset_minutes', 60)));
        $expiresAt = (new DateTimeImmutable('now', new DateTimeZone('UTC')))
            ->modify('+' . $validMinutes . ' minutes')
            ->format('Y-m-d H:i:s');

        try {
            $this->db->beginTransaction();
            $invalidate = $this->db->prepare(
                'UPDATE password_reset_tokens SET used_at=UTC_TIMESTAMP() WHERE user_id=:user_id AND used_at IS NULL'
            );
            $invalidate->execute(['user_id' => $user['id']]);
            $insert = $this->db->prepare(
                'INSERT INTO password_reset_tokens(user_id,token_hash,expires_at,created_at)
                 VALUES(:user_id,:token_hash,:expires_at,UTC_TIMESTAMP())'
            );
            $insert->execute([
                'user_id' => $user['id'],
                'token_hash' => $tokenHash,
                'expires_at' => $expiresAt,
            ]);
            $this->db->commit();
        } catch (Throwable $exception) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $exception;
        }

        $resetUrl = \url('reset-password', ['token' => $rawToken]);
        $subject = 'Új jelszó beállítása – Időpontfoglaló';
        $body = sprintf(
            "Kedves %s!\n\nAz új jelszó beállításához nyissa meg az alábbi hivatkozást:\n%s\n\n"
            . "A hivatkozás %d percig érvényes, és csak egyszer használható fel.\n\n"
            . "Ha nem Ön kérte a jelszó visszaállítását, hagyja figyelmen kívül ezt a levelet; a jelenlegi jelszava nem változik meg.\n",
            $user['name'],
            $resetUrl,
            $validMinutes
        );

        try {
            $this->mailer->send((string) $user['email'], $subject, $body);
        } catch (Throwable $exception) {
            $delete = $this->db->prepare(
                'DELETE FROM password_reset_tokens WHERE user_id=:user_id AND token_hash=:token_hash'
            );
            $delete->execute(['user_id' => $user['id'], 'token_hash' => $tokenHash]);
            throw $exception;
        }

        \audit_log((int) $user['id'], 'PASSWORD_RESET_REQUESTED', 'user', (int) $user['id'], null, [
            'expires_at' => $expiresAt,
        ]);
        return true;
    }

    public function tokenIsUsable(string $rawToken): bool
    {
        if (!$this->validTokenFormat($rawToken)) {
            return false;
        }

        $stmt = $this->db->prepare(
            'SELECT 1 FROM password_reset_tokens t
             JOIN users u ON u.id=t.user_id
             WHERE t.token_hash=:token_hash AND t.used_at IS NULL AND t.expires_at>UTC_TIMESTAMP()
               AND u.status=\'active\' AND u.email_verified_at IS NOT NULL
             LIMIT 1'
        );
        $stmt->execute(['token_hash' => hash('sha256', $rawToken)]);
        return (bool) $stmt->fetchColumn();
    }

    public function resetPassword(string $rawToken, string $passwordHash): bool
    {
        if (!$this->validTokenFormat($rawToken)) {
            return false;
        }

        try {
            $this->db->beginTransaction();
            $stmt = $this->db->prepare(
                'SELECT t.id,t.user_id FROM password_reset_tokens t
                 JOIN users u ON u.id=t.user_id
                 WHERE t.token_hash=:token_hash AND t.used_at IS NULL AND t.expires_at>UTC_TIMESTAMP()
                   AND u.status=\'active\' AND u.email_verified_at IS NOT NULL
                 LIMIT 1 FOR UPDATE'
            );
            $stmt->execute(['token_hash' => hash('sha256', $rawToken)]);
            $token = $stmt->fetch();
            if (!$token) {
                $this->db->rollBack();
                return false;
            }

            $userId = (int) $token['user_id'];
            $updateUser = $this->db->prepare(
                'UPDATE users SET password_hash=:password_hash,failed_login_attempts=0,locked_until=NULL,
                    updated_at=UTC_TIMESTAMP() WHERE id=:id'
            );
            $updateUser->execute(['password_hash' => $passwordHash, 'id' => $userId]);
            $useTokens = $this->db->prepare(
                'UPDATE password_reset_tokens SET used_at=UTC_TIMESTAMP() WHERE user_id=:user_id AND used_at IS NULL'
            );
            $useTokens->execute(['user_id' => $userId]);
            \audit_log($userId, 'PASSWORD_RESET_COMPLETED', 'user', $userId);
            $this->db->commit();
            return true;
        } catch (Throwable $exception) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $exception;
        }
    }

    private function validTokenFormat(string $rawToken): bool
    {
        return preg_match('/\A[a-f0-9]{64}\z/D', $rawToken) === 1;
    }
}
