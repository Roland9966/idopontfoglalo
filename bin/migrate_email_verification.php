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
     WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='users' AND COLUMN_NAME='email_verified_at'"
)->fetchColumn();

if ($columnExists === 0) {
    db()->exec('ALTER TABLE users ADD COLUMN email_verified_at DATETIME NULL AFTER email');
    db()->exec('UPDATE users SET email_verified_at=COALESCE(created_at,UTC_TIMESTAMP()) WHERE email_verified_at IS NULL');
    fwrite(STDOUT, "Az e-mail-ellenőrzési mező létrejött; a korábbi fiókok ellenőrzött állapotot kaptak.\n");
}

db()->exec(
    "CREATE TABLE IF NOT EXISTS email_verification_tokens (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        user_id BIGINT UNSIGNED NOT NULL,
        token_hash CHAR(64) NOT NULL,
        expires_at DATETIME NOT NULL,
        used_at DATETIME NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        CONSTRAINT uq_email_verification_token UNIQUE (token_hash),
        CONSTRAINT fk_email_verification_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        INDEX idx_email_verification_user (user_id, created_at),
        INDEX idx_email_verification_expiry (expires_at)
    ) ENGINE=InnoDB"
);

Database::disconnect();
fwrite(STDOUT, "Az adatbázis 0.1.6-os frissítése elkészült.\n");
