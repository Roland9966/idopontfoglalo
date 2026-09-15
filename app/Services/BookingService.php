<?php

declare(strict_types=1);

namespace App\Services;

use DateTimeImmutable;
use DateTimeZone;
use DomainException;
use PDO;
use PDOException;

final class BookingService
{
    public function __construct(private readonly PDO $db)
    {
    }

    public function create(int $slotId, int $userId, string $note = ''): int
    {
        $note = trim($note);
        if (mb_strlen($note) > 500) {
            throw new DomainException('A megjegyzés legfeljebb 500 karakter lehet.');
        }

        try {
            $this->db->beginTransaction();

            $slotStmt = $this->db->prepare(
                'SELECT s.*, sv.name AS service_name, u.name AS provider_name
                 FROM slots s
                 JOIN services sv ON sv.id = s.service_id
                 JOIN users u ON u.id = s.provider_id
                 WHERE s.id = :id FOR UPDATE'
            );
            $slotStmt->execute(['id' => $slotId]);
            $slot = $slotStmt->fetch();

            if (!$slot || $slot['status'] !== 'available') {
                throw new DomainException('A kiválasztott időpont nem foglalható.');
            }
            if (new DateTimeImmutable($slot['starts_at'], new DateTimeZone('UTC')) <= new DateTimeImmutable('now', new DateTimeZone('UTC'))) {
                throw new DomainException('Múltbeli időpont nem foglalható.');
            }

            $existingStmt = $this->db->prepare(
                "SELECT id FROM bookings WHERE slot_id = :slot_id AND status = 'confirmed' LIMIT 1 FOR UPDATE"
            );
            $existingStmt->execute(['slot_id' => $slotId]);
            if ($existingStmt->fetchColumn()) {
                throw new DomainException('Az időpontot időközben más lefoglalta. Kérjük, válasszon másikat.');
            }

            $insert = $this->db->prepare(
                "INSERT INTO bookings (slot_id, user_id, status, note, created_at, updated_at)
                 VALUES (:slot_id, :user_id, 'confirmed', :note, UTC_TIMESTAMP(), UTC_TIMESTAMP())"
            );
            $insert->execute(['slot_id' => $slotId, 'user_id' => $userId, 'note' => $note === '' ? null : $note]);
            $bookingId = (int) $this->db->lastInsertId();

            $emailStmt = $this->db->prepare('SELECT email FROM users WHERE id = :id');
            $emailStmt->execute(['id' => $userId]);
            $recipient = (string) $emailStmt->fetchColumn();

            \audit_log($userId, 'BOOKING_CREATED', 'booking', $bookingId, null, [
                'status' => 'confirmed',
                'slot_id' => $slotId,
            ]);
            \queue_notification($bookingId, 'booking_confirmed', $recipient);
            $this->db->commit();
            return $bookingId;
        } catch (PDOException $exception) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            if ($exception->getCode() === '23000') {
                throw new DomainException('Az időpontot időközben más lefoglalta. Kérjük, válasszon másikat.', 0, $exception);
            }
            throw $exception;
        } catch (DomainException $exception) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $exception;
        }
    }

    public function cancel(int $bookingId, array $actor): void
    {
        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare(
                'SELECT b.*, s.starts_at, s.provider_id, u.email
                 FROM bookings b
                 JOIN slots s ON s.id = b.slot_id
                 JOIN users u ON u.id = b.user_id
                 WHERE b.id = :id FOR UPDATE'
            );
            $stmt->execute(['id' => $bookingId]);
            $booking = $stmt->fetch();
            if (!$booking) {
                throw new DomainException('A foglalás nem található.');
            }

            $isOwner = (int) $booking['user_id'] === (int) $actor['id'];
            $isManager = $actor['role_code'] === 'admin'
                || ($actor['role_code'] === 'provider' && (int) $booking['provider_id'] === (int) $actor['id']);
            if (!$isOwner && !$isManager) {
                throw new DomainException('Más felhasználó foglalása nem módosítható.');
            }
            if ($booking['status'] !== 'confirmed') {
                throw new DomainException('Csak megerősített foglalás mondható le.');
            }
            if (!$isManager && !\cancellation_is_allowed($booking['starts_at'])) {
                throw new DomainException('A foglalás a kezdés előtti 24 órán belül már nem mondható le.');
            }

            $update = $this->db->prepare(
                "UPDATE bookings SET status = 'cancelled', cancelled_at = UTC_TIMESTAMP(), updated_at = UTC_TIMESTAMP() WHERE id = :id"
            );
            $update->execute(['id' => $bookingId]);
            \audit_log((int) $actor['id'], 'BOOKING_CANCELLED', 'booking', $bookingId,
                ['status' => 'confirmed'], ['status' => 'cancelled']);
            \queue_notification($bookingId, 'booking_cancelled', (string) $booking['email']);
            $this->db->commit();
        } catch (\Throwable $exception) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $exception;
        }
    }

    public function changeStatus(int $bookingId, string $newStatus, array $actor): void
    {
        if (!in_array($newStatus, ['cancelled', 'completed', 'no_show'], true)) {
            throw new DomainException('Érvénytelen célállapot.');
        }

        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare(
                'SELECT b.*, s.provider_id, u.email FROM bookings b
                 JOIN slots s ON s.id = b.slot_id JOIN users u ON u.id = b.user_id
                 WHERE b.id = :id FOR UPDATE'
            );
            $stmt->execute(['id' => $bookingId]);
            $booking = $stmt->fetch();
            if (!$booking) {
                throw new DomainException('A foglalás nem található.');
            }
            $allowed = $actor['role_code'] === 'admin'
                || ($actor['role_code'] === 'provider' && (int) $booking['provider_id'] === (int) $actor['id']);
            if (!$allowed) {
                throw new DomainException('A foglalás állapotának módosításához nincs jogosultsága.');
            }
            if (!\valid_booking_transition((string) $booking['status'], $newStatus)) {
                throw new DomainException('Ez az állapotváltozás nem engedélyezett.');
            }

            $cancelledAt = $newStatus === 'cancelled' ? ', cancelled_at = UTC_TIMESTAMP()' : '';
            $update = $this->db->prepare(
                "UPDATE bookings SET status = :status, updated_at = UTC_TIMESTAMP() {$cancelledAt} WHERE id = :id"
            );
            $update->execute(['status' => $newStatus, 'id' => $bookingId]);
            \audit_log((int) $actor['id'], 'BOOKING_STATUS_CHANGED', 'booking', $bookingId,
                ['status' => $booking['status']], ['status' => $newStatus]);
            if ($newStatus === 'cancelled') {
                \queue_notification($bookingId, 'booking_cancelled', (string) $booking['email']);
            }
            $this->db->commit();
        } catch (\Throwable $exception) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $exception;
        }
    }
}

