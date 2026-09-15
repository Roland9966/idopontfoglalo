<?php

declare(strict_types=1);

use App\Core\Database;

define('APP_ROOT', dirname(__DIR__));
require APP_ROOT . '/app/bootstrap.php';

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

$email = mb_strtolower(trim($argv[1] ?? ''));
$name = trim($argv[2] ?? 'Adminisztrátor');
$password = $argv[3] ?? '';

if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 10) {
    fwrite(STDERR, "Használat: php bin/create_admin.php admin@pelda.hu \"Admin neve\" \"Legalabb10!\"\n");
    exit(1);
}

$roleId = (int) db()->query("SELECT id FROM roles WHERE code='admin'")->fetchColumn();
if (!$roleId) {
    fwrite(STDERR, "Előbb importálja a database/schema.sql és database/seed.sql fájlt.\n");
    exit(1);
}

$stmt = db()->prepare(
    "INSERT INTO users(role_id,name,email,email_verified_at,password_hash,status,created_at,updated_at)
     VALUES(:role_id,:name,:email,UTC_TIMESTAMP(),:password_hash,'active',UTC_TIMESTAMP(),UTC_TIMESTAMP())
     ON DUPLICATE KEY UPDATE role_id=VALUES(role_id),name=VALUES(name),password_hash=VALUES(password_hash),
        email_verified_at=COALESCE(email_verified_at,UTC_TIMESTAMP()),status='active'"
);
$stmt->execute([
    'role_id' => $roleId,
    'name' => $name,
    'email' => $email,
    'password_hash' => password_hash($password, PASSWORD_DEFAULT),
]);

Database::disconnect();
fwrite(STDOUT, "Az adminisztrátori fiók elkészült: {$email}\n");
