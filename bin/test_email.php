<?php

declare(strict_types=1);

use App\Core\Database;
use App\Services\Mailer;

define('APP_ROOT', dirname(__DIR__));
require APP_ROOT . '/app/bootstrap.php';

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

$recipient = mb_strtolower(trim($argv[1] ?? ''));
if (!filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
    fwrite(STDERR, "Használat: php bin/test_email.php cimzett@gmail.com\n");
    exit(1);
}

$mailer = new Mailer();
$mailer->send(
    $recipient,
    'Időpontfoglaló – SMTP teszt',
    "Ez egy tesztlevél az iskolai időpontfoglaló rendszerből.\n\nHa ezt megkapta, az SMTP-beállítás működik.\n"
);

Database::disconnect();
fwrite(STDOUT, "A tesztlevél elküldése sikerült: {$recipient}\n");
