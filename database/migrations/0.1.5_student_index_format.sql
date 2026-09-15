-- Frissítés a 0.1.4 vagy korábbi adatbázisról a 0.1.5 verzióra.
-- Az alkalmazás ettől a verziótól pontosan 8 számjegyből álló indexszámot fogad el.
-- A fájl többször is biztonságosan importálható.

USE idopontfoglalo;

SET @column_exists := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'users'
      AND COLUMN_NAME = 'student_index'
);
SET @column_sql := IF(
    @column_exists = 0,
    'ALTER TABLE users ADD COLUMN student_index CHAR(8) NULL AFTER name',
    'SELECT 1'
);
PREPARE column_statement FROM @column_sql;
EXECUTE column_statement;
DEALLOCATE PREPARE column_statement;

SET @index_exists := (
    SELECT COUNT(*)
    FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'users'
      AND INDEX_NAME = 'uq_users_student_index'
);
SET @index_sql := IF(
    @index_exists = 0,
    'ALTER TABLE users ADD UNIQUE INDEX uq_users_student_index (student_index)',
    'SELECT 1'
);
PREPARE index_statement FROM @index_sql;
EXECUTE index_statement;
DEALLOCATE PREPARE index_statement;

UPDATE users AS demo_user
LEFT JOIN users AS existing_user
    ON existing_user.student_index = '26000000'
   AND existing_user.email <> 'diak@iskola.local'
SET demo_user.student_index = '26000000'
WHERE demo_user.email = 'diak@iskola.local'
  AND (demo_user.student_index IS NULL OR demo_user.student_index = '' OR demo_user.student_index = 'MINTA-001/2026')
  AND existing_user.id IS NULL;
