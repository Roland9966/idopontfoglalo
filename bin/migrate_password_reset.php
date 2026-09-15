<?php

declare(strict_types=1);

use App\Core\Database;

define('APP_ROOT', dirname(__DIR__));
require APP_ROOT . '/app/bootstrap.php';

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

db()->exec(
    "CREATE TABLE IF NOT EXISTS password_reset_tokens (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        user_id BIGINT UNSIGNED NOT NULL,
        token_hash CHAR(64) NOT NULL,
        expires_at DATETIME NOT NULL,
        used_at DATETIME NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        CONSTRAINT uq_password_reset_token UNIQUE (token_hash),
        CONSTRAINT fk_password_reset_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        INDEX idx_password_reset_user (user_id, created_at),
        INDEX idx_password_reset_expiry (expires_at)
    ) ENGINE=InnoDB"
);

$indexExists = (int) db()->query(
    "SELECT COUNT(*) FROM information_schema.STATISTICS
     WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='password_reset_tokens'
       AND INDEX_NAME='idx_password_reset_user'"
)->fetchColumn();

if ($indexExists === 0) {
    db()->exec('ALTER TABLE password_reset_tokens ADD INDEX idx_password_reset_user (user_id, created_at)');
    fwrite(STDOUT, "A jelszó-visszaállítás felhasználói indexe létrejött.\n");
}

Database::disconnect();
fwrite(STDOUT, "Az adatbázis 0.1.7-es frissítése elkészült.\n");
