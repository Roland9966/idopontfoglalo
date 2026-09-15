-- Frissítés a 0.1.5 vagy korábbi adatbázisról a 0.1.6 verzióra.
-- A korábban létrehozott fiókokat ellenőrzöttnek jelöli, az új fiókoknál aktiválás szükséges.
-- A fájl többször is biztonságosan importálható.

USE idopontfoglalo;

SET @verification_column_exists := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'users'
      AND COLUMN_NAME = 'email_verified_at'
);
SET @verification_column_sql := IF(
    @verification_column_exists = 0,
    'ALTER TABLE users ADD COLUMN email_verified_at DATETIME NULL AFTER email',
    'SELECT 1'
);
PREPARE verification_column_statement FROM @verification_column_sql;
EXECUTE verification_column_statement;
DEALLOCATE PREPARE verification_column_statement;

SET @mark_existing_users_sql := IF(
    @verification_column_exists = 0,
    'UPDATE users SET email_verified_at=COALESCE(created_at,UTC_TIMESTAMP()) WHERE email_verified_at IS NULL',
    'SELECT 1'
);
PREPARE mark_existing_users_statement FROM @mark_existing_users_sql;
EXECUTE mark_existing_users_statement;
DEALLOCATE PREPARE mark_existing_users_statement;

CREATE TABLE IF NOT EXISTS email_verification_tokens (
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
) ENGINE=InnoDB;
