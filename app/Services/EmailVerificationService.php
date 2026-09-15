<?php

declare(strict_types=1);

namespace App\Services;

use DateTimeImmutable;
use DateTimeZone;
use PDO;
use RuntimeException;
use Throwable;

final class EmailVerificationService
{
    public function __construct(private PDO $db, private Mailer $mailer)
    {
    }

    public function issueAndSend(int $userId): void
    {
        $stmt = $this->db->prepare(
            "SELECT id,name,email,email_verified_at,status FROM users WHERE id=:id LIMIT 1"
        );
        $stmt->execute(['id' => $userId]);
        $user = $stmt->fetch();
        if (!$user || $user['status'] !== 'active') {
            throw new RuntimeException('A fiók nem alkalmas e-mailes aktiválásra.');
        }
        if (!empty($user['email_verified_at'])) {
            return;
        }

        $rawToken = bin2hex(random_bytes(32));
        $tokenHash = hash('sha256', $rawToken);
        $validHours = max(1, min(168, (int) \config('email_verification_hours', 24)));
        $expiresAt = (new DateTimeImmutable('now', new DateTimeZone('UTC')))
            ->modify('+' . $validHours . ' hours')
            ->format('Y-m-d H:i:s');

        try {
            $this->db->beginTransaction();
            $invalidate = $this->db->prepare(
                'UPDATE email_verification_tokens SET used_at=UTC_TIMESTAMP() WHERE user_id=:user_id AND used_at IS NULL'
            );
            $invalidate->execute(['user_id' => $userId]);
            $insert = $this->db->prepare(
                'INSERT INTO email_verification_tokens(user_id,token_hash,expires_at,created_at)
                 VALUES(:user_id,:token_hash,:expires_at,UTC_TIMESTAMP())'
            );
            $insert->execute(['user_id' => $userId, 'token_hash' => $tokenHash, 'expires_at' => $expiresAt]);
            $this->db->commit();
        } catch (Throwable $exception) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $exception;
        }

        $verificationUrl = \url('verify-email', ['token' => $rawToken]);
        $subject = 'Időpontfoglaló fiók aktiválása';
        $body = sprintf(
            "Kedves %s!\n\nA regisztráció befejezéséhez nyissa meg az alábbi hivatkozást:\n%s\n\n"
            . "A hivatkozás %d óráig érvényes, és csak egyszer használható fel.\n\n"
            . "Ha nem Ön kezdeményezte a regisztrációt, hagyja figyelmen kívül ezt a levelet.\n",
            $user['name'],
            $verificationUrl,
            $validHours
        );
        try {
            $this->mailer->send((string) $user['email'], $subject, $body);
        } catch (Throwable $exception) {
            $delete = $this->db->prepare(
                'DELETE FROM email_verification_tokens WHERE user_id=:user_id AND token_hash=:token_hash'
            );
            $delete->execute(['user_id' => $userId, 'token_hash' => $tokenHash]);
            throw $exception;
        }
        \audit_log($userId, 'EMAIL_VERIFICATION_SENT', 'user', $userId, null, ['expires_at' => $expiresAt]);
    }

    public function resendForEmail(string $email): bool
    {
        $stmt = $this->db->prepare(
            "SELECT id FROM users
             WHERE email=:email AND status='active' AND email_verified_at IS NULL LIMIT 1"
        );
        $stmt->execute(['email' => mb_strtolower(trim($email))]);
        $userId = (int) $stmt->fetchColumn();
        if ($userId === 0) {
            return false;
        }

        $this->issueAndSend($userId);
        return true;
    }

    public function verify(string $rawToken): bool
    {
        if (preg_match('/\A[a-f0-9]{64}\z/D', $rawToken) !== 1) {
            return false;
        }

        try {
            $this->db->beginTransaction();
            $stmt = $this->db->prepare(
                'SELECT t.id,t.user_id FROM email_verification_tokens t
                 JOIN users u ON u.id=t.user_id
                 WHERE t.token_hash=:token_hash AND t.used_at IS NULL AND t.expires_at>UTC_TIMESTAMP()
                   AND u.status=\'active\' AND u.email_verified_at IS NULL
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
                'UPDATE users SET email_verified_at=UTC_TIMESTAMP(),updated_at=UTC_TIMESTAMP() WHERE id=:id'
            );
            $updateUser->execute(['id' => $userId]);
            $useTokens = $this->db->prepare(
                'UPDATE email_verification_tokens SET used_at=UTC_TIMESTAMP() WHERE user_id=:user_id AND used_at IS NULL'
            );
            $useTokens->execute(['user_id' => $userId]);
            \audit_log($userId, 'EMAIL_VERIFIED', 'user', $userId);
            $this->db->commit();
            return true;
        } catch (Throwable $exception) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $exception;
        }
    }
}
