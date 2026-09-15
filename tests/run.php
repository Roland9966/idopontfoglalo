<?php

declare(strict_types=1);

define('APP_ROOT', dirname(__DIR__));
require APP_ROOT . '/app/bootstrap.php';

$passed = 0;
$failed = 0;

function test_case(string $name, callable $test): void
{
    global $passed, $failed;
    try {
        $test();
        $passed++;
        fwrite(STDOUT, "[PASS] {$name}\n");
    } catch (Throwable $exception) {
        $failed++;
        fwrite(STDOUT, "[FAIL] {$name}: {$exception->getMessage()}\n");
    }
}

function expect_true(bool $condition, string $message = 'A feltétel nem teljesült.'): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

test_case('A lemondás 25 órával a kezdés előtt engedélyezett', static function (): void {
    $start = (new DateTimeImmutable('now', new DateTimeZone('UTC')))->modify('+25 hours')->format('Y-m-d H:i:s');
    expect_true(cancellation_is_allowed($start, 24));
});

test_case('A lemondás 23 órával a kezdés előtt nem engedélyezett', static function (): void {
    $start = (new DateTimeImmutable('now', new DateTimeZone('UTC')))->modify('+23 hours')->format('Y-m-d H:i:s');
    expect_true(!cancellation_is_allowed($start, 24));
});

test_case('A megerősített foglalás teljesítettre állítható', static function (): void {
    expect_true(valid_booking_transition('confirmed', 'completed'));
});

test_case('A lemondott foglalás nem állítható vissza megerősítettre', static function (): void {
    expect_true(!valid_booking_transition('cancelled', 'confirmed'));
});

test_case('Az indexszám egységes formára alakítható', static function (): void {
    expect_true(normalize_student_index(' 26221111 ') === '26221111');
});

test_case('Az indexszám formátumának ellenőrzése működik', static function (): void {
    expect_true(valid_student_index('26221111'));
    expect_true(!valid_student_index('2622111'));
    expect_true(!valid_student_index('262211111'));
    expect_true(!valid_student_index('262A1111'));
});

test_case('Az aktiváló token formátuma 64 hexadecimális karakter', static function (): void {
    $token = bin2hex(random_bytes(32));
    expect_true(preg_match('/\A[a-f0-9]{64}\z/D', $token) === 1);
    expect_true(hash_equals(hash('sha256', $token), hash('sha256', $token)));
});

test_case('A jelszószabály ellenőrzése működik', static function (): void {
    expect_true(valid_password('Biztonsagos123'));
    expect_true(!valid_password('rovid1A'));
    expect_true(!valid_password('csakkisbetu123'));
    expect_true(!valid_password('CSAKNAGYBETU123'));
    expect_true(!valid_password('NincsBenneSzam'));
});

test_case('A jelszó-visszaállító token formátuma biztonságos', static function (): void {
    $token = bin2hex(random_bytes(32));
    expect_true(strlen($token) === 64);
    expect_true(preg_match('/\A[a-f0-9]{64}\z/D', $token) === 1);
    expect_true(strlen(hash('sha256', $token)) === 64);
});

if (in_array('--integration', $argv, true)) {
    test_case('Konkurens foglalásnál csak egy aktív foglalás jön létre', static function (): void {
        $suffix = bin2hex(random_bytes(5));
        $indexSuffix = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $roleIds = [];
        foreach (db()->query("SELECT id,code FROM roles WHERE code IN ('user','provider')") as $row) {
            $roleIds[$row['code']] = (int) $row['id'];
        }
        if (!isset($roleIds['user'], $roleIds['provider'])) {
            throw new RuntimeException('A szerepkörök hiányoznak. Importálja a seed.sql fájlt.');
        }

        $userInsert = db()->prepare(
            "INSERT INTO users(role_id,name,student_index,email,email_verified_at,password_hash,status,created_at,updated_at)
             VALUES(:role_id,:name,:student_index,:email,UTC_TIMESTAMP(),:hash,'active',UTC_TIMESTAMP(),UTC_TIMESTAMP())"
        );
        $ids = [];
        foreach ([
            ['provider', 'Teszt időpontgazda', null],
            ['user', 'Teszt foglaló A', '91' . $indexSuffix],
            ['user', 'Teszt foglaló B', '92' . $indexSuffix],
        ] as $index => [$role, $name, $studentIndex]) {
            $userInsert->execute([
                'role_id' => $roleIds[$role], 'name' => $name,
                'student_index' => $studentIndex,
                'email' => "teszt{$index}-{$suffix}@example.test",
                'hash' => password_hash('Teszt123!', PASSWORD_DEFAULT),
            ]);
            $ids[] = (int) db()->lastInsertId();
        }
        [$providerId, $userA, $userB] = $ids;

        $serviceStmt = db()->prepare(
            "INSERT INTO services(name,description,duration_minutes,location,is_active,created_at,updated_at)
             VALUES(:name,'Konkurenciateszt',30,'Teszt',1,UTC_TIMESTAMP(),UTC_TIMESTAMP())"
        );
        $serviceStmt->execute(['name' => 'Teszt szolgáltatás ' . $suffix]);
        $serviceId = (int) db()->lastInsertId();
        db()->prepare('INSERT INTO provider_services(provider_id,service_id) VALUES(:p,:s)')
            ->execute(['p' => $providerId, 's' => $serviceId]);

        $startsAt = (new DateTimeImmutable('now', new DateTimeZone('UTC')))->modify('+2 days')->format('Y-m-d H:i:s');
        $endsAt = (new DateTimeImmutable($startsAt, new DateTimeZone('UTC')))->modify('+30 minutes')->format('Y-m-d H:i:s');
        $slotStmt = db()->prepare(
            "INSERT INTO slots(service_id,provider_id,starts_at,ends_at,status,created_at,updated_at)
             VALUES(:s,:p,:starts,:ends,'available',UTC_TIMESTAMP(),UTC_TIMESTAMP())"
        );
        $slotStmt->execute(['s' => $serviceId, 'p' => $providerId, 'starts' => $startsAt, 'ends' => $endsAt]);
        $slotId = (int) db()->lastInsertId();

        if (!function_exists('proc_open')) {
            throw new RuntimeException('A proc_open() nem érhető el, ezért a párhuzamos teszt nem indítható.');
        }
        $startSignal = microtime(true) + 1.2;
        $worker = APP_ROOT . '/tests/concurrency_worker.php';
        $processes = [];
        foreach ([$userA, $userB] as $userId) {
            $command = escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg($worker) . ' '
                . escapeshellarg((string) $slotId) . ' ' . escapeshellarg((string) $userId) . ' '
                . escapeshellarg((string) $startSignal);
            $pipes = [];
            $process = proc_open($command, [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, APP_ROOT);
            if (!is_resource($process)) {
                throw new RuntimeException('A tesztfolyamat nem indítható.');
            }
            fclose($pipes[0]);
            $processes[] = [$process, $pipes];
        }

        $results = [];
        foreach ($processes as [$process, $pipes]) {
            $stdout = stream_get_contents($pipes[1]);
            $stderr = stream_get_contents($pipes[2]);
            fclose($pipes[1]);
            fclose($pipes[2]);
            $exitCode = proc_close($process);
            $decoded = json_decode(trim($stdout), true);
            if (!is_array($decoded)) {
                throw new RuntimeException('Érvénytelen tesztkimenet: ' . trim($stderr));
            }
            $decoded['exit_code'] = $exitCode;
            $results[] = $decoded;
        }

        $countStmt = db()->prepare("SELECT COUNT(*) FROM bookings WHERE slot_id=:slot_id AND status='confirmed'");
        $countStmt->execute(['slot_id' => $slotId]);
        $activeCount = (int) $countStmt->fetchColumn();
        $successCount = count(array_filter($results, static fn (array $result): bool => ($result['success'] ?? false) === true));

        db()->prepare('DELETE FROM notifications WHERE booking_id IN (SELECT id FROM bookings WHERE slot_id=:slot)')->execute(['slot' => $slotId]);
        db()->prepare('DELETE FROM bookings WHERE slot_id=:slot')->execute(['slot' => $slotId]);
        db()->prepare('DELETE FROM slots WHERE id=:slot')->execute(['slot' => $slotId]);
        db()->prepare('DELETE FROM provider_services WHERE provider_id=:p AND service_id=:s')->execute(['p' => $providerId, 's' => $serviceId]);
        db()->prepare('DELETE FROM services WHERE id=:s')->execute(['s' => $serviceId]);
        foreach ($ids as $id) {
            db()->prepare('DELETE FROM users WHERE id=:id')->execute(['id' => $id]);
        }

        expect_true($activeCount === 1, 'Az aktív foglalások száma nem 1, hanem ' . $activeCount . '.');
        expect_true($successCount === 1, 'A sikeres foglalási folyamatok száma nem 1, hanem ' . $successCount . '.');
    });
}

fwrite(STDOUT, sprintf("\nÖsszesen: %d PASS, %d FAIL.\n", $passed, $failed));
exit($failed > 0 ? 1 : 0);
