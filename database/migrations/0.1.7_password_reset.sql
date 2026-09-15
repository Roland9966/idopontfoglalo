-- Frissítés a 0.1.6 vagy korábbi adatbázisról a 0.1.7 verzióra.
-- Létrehozza a jelszó-visszaállítás egyszer használható tokentábláját.
-- A fájl többször is biztonságosan importálható.

USE idopontfoglalo;

CREATE TABLE IF NOT EXISTS password_reset_tokens (
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
) ENGINE=InnoDB;

SET @password_reset_user_index_exists := (
    SELECT COUNT(*)
    FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'password_reset_tokens'
      AND INDEX_NAME = 'idx_password_reset_user'
);
SET @password_reset_user_index_sql := IF(
    @password_reset_user_index_exists = 0,
    'ALTER TABLE password_reset_tokens ADD INDEX idx_password_reset_user (user_id, created_at)',
    'SELECT 1'
);
PREPARE password_reset_user_index_statement FROM @password_reset_user_index_sql;
EXECUTE password_reset_user_index_statement;
DEALLOCATE PREPARE password_reset_user_index_statement;
