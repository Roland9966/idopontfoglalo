<?php

declare(strict_types=1);

use App\Core\Database;

define('APP_ROOT', dirname(__DIR__));
require APP_ROOT . '/app/bootstrap.php';

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

$columnExists = (int) db()->query(
    "SELECT COUNT(*) FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='users' AND COLUMN_NAME='student_index'"
)->fetchColumn();

if ($columnExists === 0) {
    db()->exec('ALTER TABLE users ADD COLUMN student_index CHAR(8) NULL AFTER name');
    fwrite(STDOUT, "Az indexszám mező létrejött.\n");
}

$indexExists = (int) db()->query(
    "SELECT COUNT(*) FROM information_schema.STATISTICS
     WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='users' AND INDEX_NAME='uq_users_student_index'"
)->fetchColumn();

if ($indexExists === 0) {
    db()->exec('ALTER TABLE users ADD UNIQUE INDEX uq_users_student_index (student_index)');
    fwrite(STDOUT, "Az indexszám egyediségi ellenőrzése létrejött.\n");
}

$demoIndex = '26000000';
$check = db()->prepare('SELECT 1 FROM users WHERE student_index=:student_index AND email<>:email LIMIT 1');
$check->execute(['student_index' => $demoIndex, 'email' => 'diak@iskola.local']);
if (!$check->fetchColumn()) {
    $update = db()->prepare(
        "UPDATE users SET student_index=:student_index,updated_at=UTC_TIMESTAMP()
         WHERE email=:email AND (student_index IS NULL OR student_index='' OR student_index='MINTA-001/2026')"
    );
    $update->execute(['student_index' => $demoIndex, 'email' => 'diak@iskola.local']);
}

Database::disconnect();
fwrite(STDOUT, "Az adatbázis 0.1.5-ös frissítése elkészült.\n");
