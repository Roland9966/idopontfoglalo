<?php

declare(strict_types=1);

use App\Services\Mailer;

define('APP_ROOT', dirname(__DIR__));
require APP_ROOT . '/app/bootstrap.php';

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

$stmt = db()->query(
    "SELECT n.*,b.id AS booking_number,s.starts_at,sv.name AS service_name
     FROM notifications n JOIN bookings b ON b.id=n.booking_id
     JOIN slots s ON s.id=b.slot_id JOIN services sv ON sv.id=s.service_id
     WHERE n.status IN ('pending','failed') AND n.attempts<3 ORDER BY n.created_at LIMIT 50"
);
$items = $stmt->fetchAll();
$mailer = new Mailer();
$sent = 0;

foreach ($items as $item) {
    $subject = $item['event_type'] === 'booking_cancelled' ? 'Foglalás lemondva' : 'Foglalás visszaigazolása';
    $body = sprintf(
        "%s\nFoglalási azonosító: #%d\nSzolgáltatás: %s\nIdőpont: %s\n",
        $subject,
        $item['booking_number'],
        $item['service_name'],
        utc_to_local($item['starts_at'])
    );

    try {
        $mailer->send((string) $item['recipient'], $subject, $body);
        $update = db()->prepare(
            "UPDATE notifications SET status='sent',attempts=attempts+1,last_error=NULL,sent_at=UTC_TIMESTAMP() WHERE id=:id"
        );
        $update->execute(['id' => $item['id']]);
        $sent++;
    } catch (Throwable $exception) {
        $update = db()->prepare(
            "UPDATE notifications SET status='failed',attempts=attempts+1,last_error=:error WHERE id=:id"
        );
        $update->execute(['error' => mb_substr($exception->getMessage(), 0, 500), 'id' => $item['id']]);
    }
}

fwrite(STDOUT, sprintf("Feldolgozva: %d; sikeres: %d.\n", count($items), $sent));
