<?php

declare(strict_types=1);

use App\Services\BookingService;

define('APP_ROOT', dirname(__DIR__));
require APP_ROOT . '/app/bootstrap.php';

$slotId = (int) ($argv[1] ?? 0);
$userId = (int) ($argv[2] ?? 0);
$startSignal = (float) ($argv[3] ?? microtime(true));

while (microtime(true) < $startSignal) {
    usleep(1000);
}

try {
    $bookingId = (new BookingService(db()))->create($slotId, $userId, 'Konkurenciateszt');
    echo json_encode(['success' => true, 'booking_id' => $bookingId], JSON_THROW_ON_ERROR);
    exit(0);
} catch (DomainException $exception) {
    echo json_encode(['success' => false, 'message' => $exception->getMessage()], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    exit(0);
}
