<?php

declare(strict_types=1);

define('APP_ROOT', dirname(__DIR__));
require APP_ROOT . '/app/bootstrap.php';

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

function upsert_demo_user(string $role, string $name, string $email, string $password, ?string $studentIndex = null): int
{
    $roleStmt = db()->prepare('SELECT id FROM roles WHERE code=:code');
    $roleStmt->execute(['code' => $role]);
    $roleId = (int) $roleStmt->fetchColumn();
    if (!$roleId) {
        throw new RuntimeException('Hiányzó szerepkör: ' . $role);
    }
    $find = db()->prepare('SELECT id FROM users WHERE email=:email');
    $find->execute(['email' => $email]);
    $userId = (int) $find->fetchColumn();
    $parameters = [
        'role_id' => $roleId,
        'name' => $name,
        'student_index' => $studentIndex,
        'email' => $email,
        'hash' => password_hash($password, PASSWORD_DEFAULT),
    ];
    if ($userId) {
        $stmt = db()->prepare(
            "UPDATE users SET role_id=:role_id,name=:name,student_index=:student_index,
                password_hash=:hash,status='active',email_verified_at=COALESCE(email_verified_at,UTC_TIMESTAMP()),updated_at=UTC_TIMESTAMP()
             WHERE id=:id"
        );
        unset($parameters['email']);
        $parameters['id'] = $userId;
        $stmt->execute($parameters);
        return $userId;
    }

    $stmt = db()->prepare(
        "INSERT INTO users(role_id,name,student_index,email,email_verified_at,password_hash,status,created_at,updated_at)
         VALUES(:role_id,:name,:student_index,:email,UTC_TIMESTAMP(),:hash,'active',UTC_TIMESTAMP(),UTC_TIMESTAMP())"
    );
    $stmt->execute($parameters);
    return (int) db()->lastInsertId();
}

$adminId = upsert_demo_user('admin', 'Rendszer Adminisztrátor', 'admin@iskola.local', 'Admin123!');
$providerId = upsert_demo_user('provider', 'Minta Tanár', 'tanar@iskola.local', 'Tanar123!');
upsert_demo_user('user', 'Minta Diák', 'diak@iskola.local', 'Diak123!', '26000000');

$services = db()->query('SELECT id,duration_minutes FROM services WHERE is_active=1 ORDER BY id')->fetchAll();
foreach ($services as $service) {
    $assign = db()->prepare(
        'INSERT INTO provider_services(provider_id,service_id) VALUES(:provider_id,:service_id)
         ON DUPLICATE KEY UPDATE service_id=VALUES(service_id)'
    );
    $assign->execute(['provider_id' => $providerId, 'service_id' => $service['id']]);
}

$existing = db()->prepare('SELECT COUNT(*) FROM slots WHERE provider_id=:provider_id AND starts_at>UTC_TIMESTAMP()');
$existing->execute(['provider_id' => $providerId]);
if ((int) $existing->fetchColumn() === 0) {
    $localZone = new DateTimeZone((string) config('timezone'));
    $utcZone = new DateTimeZone('UTC');
    $dayOffsets = [1, 2, 3, 5, 7];
    foreach ($services as $index => $service) {
        foreach ($dayOffsets as $offset) {
            $start = new DateTimeImmutable('today 09:00', $localZone);
            $start = $start->modify('+' . ($offset + $index) . ' days')->modify('+' . ($index * 60) . ' minutes');
            $end = $start->modify('+' . (int) $service['duration_minutes'] . ' minutes');
            $stmt = db()->prepare(
                "INSERT INTO slots(service_id,provider_id,starts_at,ends_at,status,created_at,updated_at)
                 VALUES(:service_id,:provider_id,:starts_at,:ends_at,'available',UTC_TIMESTAMP(),UTC_TIMESTAMP())"
            );
            $stmt->execute([
                'service_id' => $service['id'], 'provider_id' => $providerId,
                'starts_at' => $start->setTimezone($utcZone)->format('Y-m-d H:i:s'),
                'ends_at' => $end->setTimezone($utcZone)->format('Y-m-d H:i:s'),
            ]);
        }
    }
}

fwrite(STDOUT, "A mintaadatok elkészültek.\n\n");
fwrite(STDOUT, "Admin: admin@iskola.local / Admin123!\n");
fwrite(STDOUT, "Időpontgazda: tanar@iskola.local / Tanar123!\n");
fwrite(STDOUT, "Foglaló: diak@iskola.local / Diak123!\n");
fwrite(STDOUT, "Minta indexszám: 26000000\n");
fwrite(STDOUT, "Éles használat előtt minden mintajelszót változtasson meg.\n");
