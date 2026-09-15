<?php

declare(strict_types=1);

use App\Core\Auth;
use App\Core\Csrf;
use App\Services\BookingService;
use App\Services\EmailVerificationService;
use App\Services\Mailer;
use App\Services\PasswordResetService;

define('APP_ROOT', dirname(__DIR__));
require APP_ROOT . '/app/bootstrap.php';

$route = (string) get_param('route', 'home');
$bookingService = new BookingService(db());
$emailVerificationService = new EmailVerificationService(db(), new Mailer());
$passwordResetService = new PasswordResetService(db(), new Mailer());

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !Csrf::validate(is_string(post('_token')) ? post('_token') : null)) {
    flash('error', 'A biztonsági token lejárt vagy érvénytelen. Kérjük, próbálja újra.');
    redirect(Auth::user() ? 'dashboard' : 'login');
}

switch ($route) {
    case 'home':
        $services = db()->query(
            "SELECT sv.*,
                SUM(CASE WHEN s.status = 'available' AND s.starts_at > UTC_TIMESTAMP()
                    AND NOT EXISTS (SELECT 1 FROM bookings b WHERE b.slot_id = s.id AND b.status = 'confirmed')
                    THEN 1 ELSE 0 END) AS free_slots
             FROM services sv
             LEFT JOIN slots s ON s.service_id = sv.id
             WHERE sv.is_active = 1
             GROUP BY sv.id
             ORDER BY sv.name"
        )->fetchAll();
        render('home', ['title' => 'Kezdőlap', 'services' => $services]);
        break;

    case 'login':
        if (Auth::user()) {
            redirect('dashboard');
        }
        $email = '';
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $email = mb_strtolower(trim((string) post('email')));
            if (Auth::attempt($email, (string) post('password'))) {
                flash('success', 'Sikeres bejelentkezés.');
                redirect('dashboard');
            }
            $failureMessage = match (Auth::failureReason()) {
                'email_unverified' => 'A fiók még nincs aktiválva. Nyissa meg az e-mailben kapott hivatkozást, vagy kérjen új aktiváló levelet.',
                'locked' => 'A fiók túl sok sikertelen próbálkozás miatt ideiglenesen zárolva van.',
                'inactive' => 'A fiók inaktív. Kérjük, forduljon az adminisztrátorhoz.',
                default => 'A megadott e-mail-cím vagy jelszó hibás.',
            };
            flash(Auth::failureReason() === 'email_unverified' ? 'warning' : 'error', $failureMessage);
        }
        render('auth/login', ['title' => 'Bejelentkezés', 'email' => $email]);
        break;

    case 'register':
        if (Auth::user()) {
            redirect('dashboard');
        }
        $values = ['name' => '', 'student_index' => '', 'email' => ''];
        $errors = [];
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $values['name'] = trim((string) post('name'));
            $values['student_index'] = normalize_student_index((string) post('student_index'));
            $values['email'] = mb_strtolower(trim((string) post('email')));
            $password = (string) post('password');
            if (mb_strlen($values['name']) < 2 || mb_strlen($values['name']) > 120) {
                $errors[] = 'A név 2–120 karakter hosszú legyen.';
            }
            if (!filter_var($values['email'], FILTER_VALIDATE_EMAIL)) {
                $errors[] = 'Érvényes e-mail-címet adjon meg.';
            }
            if (!valid_student_index($values['student_index'])) {
                $errors[] = 'Az indexszámnak pontosan 8 számjegyből kell állnia.';
            }
            if (!valid_password($password)) {
                $errors[] = 'A jelszó legalább 10 karakteres legyen, és tartalmazzon kisbetűt, nagybetűt és számot.';
            }
            if (!hash_equals($password, (string) post('password_confirmation'))) {
                $errors[] = 'A két jelszó nem egyezik.';
            }

            if (!$errors) {
                $duplicate = db()->prepare(
                    'SELECT
                        EXISTS(SELECT 1 FROM users WHERE email=:email) AS email_exists,
                        EXISTS(SELECT 1 FROM users WHERE student_index=:student_index) AS student_index_exists'
                );
                $duplicate->execute([
                    'email' => $values['email'],
                    'student_index' => $values['student_index'],
                ]);
                $existing = $duplicate->fetch() ?: [];
                if (!empty($existing['email_exists'])) {
                    $errors[] = 'Ezzel az e-mail-címmel már létezik fiók.';
                }
                if (!empty($existing['student_index_exists'])) {
                    $errors[] = 'Ezzel az indexszámmal már létezik fiók.';
                }
            }

            if (!$errors) {
                try {
                    $roleId = (int) db()->query("SELECT id FROM roles WHERE code = 'user'")->fetchColumn();
                    $stmt = db()->prepare(
                        "INSERT INTO users (role_id, name, student_index, email, email_verified_at, password_hash, status, created_at, updated_at)
                         VALUES (:role_id, :name, :student_index, :email, NULL, :password_hash, 'active', UTC_TIMESTAMP(), UTC_TIMESTAMP())"
                    );
                    $stmt->execute([
                        'role_id' => $roleId,
                        'name' => $values['name'],
                        'student_index' => $values['student_index'],
                        'email' => $values['email'],
                        'password_hash' => password_hash($password, PASSWORD_DEFAULT),
                    ]);
                    $userId = (int) db()->lastInsertId();
                    audit_log($userId, 'USER_REGISTERED', 'user', $userId, null, ['role' => 'user']);
                    try {
                        $emailVerificationService->issueAndSend($userId);
                        $message = config('mail.driver') === 'log'
                            ? 'A fiók létrejött. Helyi tesztmódban az aktiváló hivatkozás a storage/mail.log fájlba került.'
                            : 'A fiók létrejött. Az aktiváló hivatkozást elküldtük a megadott e-mail-címre.';
                        flash('success', $message);
                        redirect('login');
                    } catch (Throwable $mailException) {
                        @file_put_contents(
                            APP_ROOT . '/storage/app.log',
                            sprintf("[%s] Aktiváló levél küldési hiba: %s%s", gmdate('c'), $mailException->getMessage(), PHP_EOL),
                            FILE_APPEND | LOCK_EX
                        );
                        audit_log($userId, 'EMAIL_VERIFICATION_SEND_FAILED', 'user', $userId);
                        flash('error', 'A fiók létrejött, de az aktiváló levél küldése nem sikerült. Ellenőrizze a levelezési beállításokat, majd kérjen új aktiváló levelet.');
                        redirect('resend-verification');
                    }
                } catch (PDOException $exception) {
                    if ($exception->getCode() === '23000') {
                        $errors[] = 'Az e-mail-cím vagy az indexszám már használatban van.';
                    } else {
                        throw $exception;
                    }
                }
            }
        }
        render('auth/register', ['title' => 'Regisztráció', 'values' => $values, 'errors' => $errors]);
        break;

    case 'verify-email':
        if ($emailVerificationService->verify((string) get_param('token'))) {
            flash('success', 'Az e-mail-cím aktiválása sikerült. Most már bejelentkezhet.');
        } else {
            flash('error', 'Az aktiváló hivatkozás érvénytelen, lejárt vagy már felhasználták. Kérjen új aktiváló levelet.');
        }
        redirect('login');

    case 'resend-verification':
        if (Auth::user()) {
            redirect('dashboard');
        }
        $email = '';
        $errors = [];
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $email = mb_strtolower(trim((string) post('email')));
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors[] = 'Érvényes e-mail-címet adjon meg.';
            } else {
                $lastRequestAt = (int) ($_SESSION['last_email_verification_request_at'] ?? 0);
                if ($lastRequestAt > time() - 60) {
                    $errors[] = 'Új aktiváló levelet legfeljebb percenként lehet kérni. Kérjük, várjon egy keveset.';
                }
            }
            if (!$errors) {
                $_SESSION['last_email_verification_request_at'] = time();
                try {
                    $emailVerificationService->resendForEmail($email);
                    $message = config('mail.driver') === 'log'
                        ? 'Ha a fiók aktiválásra vár, az új hivatkozás a storage/mail.log fájlba került.'
                        : 'Ha a fiók aktiválásra vár, új aktiváló levelet küldtünk a megadott címre.';
                    flash('success', $message);
                    redirect('login');
                } catch (Throwable $mailException) {
                    @file_put_contents(
                        APP_ROOT . '/storage/app.log',
                        sprintf("[%s] Aktiváló levél újraküldési hiba: %s%s", gmdate('c'), $mailException->getMessage(), PHP_EOL),
                        FILE_APPEND | LOCK_EX
                    );
                    $errors[] = 'Az aktiváló levél küldése nem sikerült. Ellenőrizze a levelezési beállításokat, majd próbálja újra.';
                }
            }
        }
        render('auth/resend_verification', ['title' => 'Aktiváló levél újraküldése', 'email' => $email, 'errors' => $errors]);
        break;

    case 'forgot-password':
        if (Auth::user()) {
            redirect('dashboard');
        }
        $email = '';
        $errors = [];
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $email = mb_strtolower(trim((string) post('email')));
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors[] = 'Érvényes e-mail-címet adjon meg.';
            } else {
                $lastRequestAt = (int) ($_SESSION['last_password_reset_request_at'] ?? 0);
                if ($lastRequestAt > time() - 60) {
                    $errors[] = 'Új jelszó-visszaállító levelet legfeljebb percenként lehet kérni. Kérjük, várjon egy keveset.';
                }
            }
            if (!$errors) {
                $_SESSION['last_password_reset_request_at'] = time();
                try {
                    $passwordResetService->requestForEmail($email);
                } catch (Throwable $mailException) {
                    @file_put_contents(
                        APP_ROOT . '/storage/app.log',
                        sprintf("[%s] Jelszó-visszaállító levél küldési hiba: %s%s", gmdate('c'), $mailException->getMessage(), PHP_EOL),
                        FILE_APPEND | LOCK_EX
                    );
                }
                $message = config('mail.driver') === 'log'
                    ? 'Ha a megadott címhez használható fiók tartozik, a jelszó-visszaállító hivatkozás a storage/mail.log fájlba került.'
                    : 'Ha a megadott címhez használható fiók tartozik, elküldtük a jelszó-visszaállító levelet.';
                flash('success', $message);
                redirect('login');
            }
        }
        render('auth/forgot_password', ['title' => 'Elfelejtett jelszó', 'email' => $email, 'errors' => $errors]);
        break;

    case 'reset-password':
        if (Auth::user()) {
            redirect('dashboard');
        }
        $token = (string) get_param('token');
        $errors = [];
        if (!$passwordResetService->tokenIsUsable($token)) {
            flash('error', 'A jelszó-visszaállító hivatkozás érvénytelen, lejárt vagy már felhasználták. Kérjen új hivatkozást.');
            redirect('forgot-password');
        }
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $password = (string) post('password');
            if (!valid_password($password)) {
                $errors[] = 'A jelszó legalább 10 karakteres legyen, és tartalmazzon kisbetűt, nagybetűt és számot.';
            }
            if (!hash_equals($password, (string) post('password_confirmation'))) {
                $errors[] = 'A két jelszó nem egyezik.';
            }
            if (!$errors) {
                if ($passwordResetService->resetPassword($token, password_hash($password, PASSWORD_DEFAULT))) {
                    flash('success', 'Az új jelszó beállítása sikerült. Most már bejelentkezhet.');
                    redirect('login');
                }
                flash('error', 'A jelszó-visszaállító hivatkozás érvénytelen, lejárt vagy már felhasználták. Kérjen új hivatkozást.');
                redirect('forgot-password');
            }
        }
        render('auth/reset_password', ['title' => 'Új jelszó beállítása', 'token' => $token, 'errors' => $errors]);
        break;

    case 'logout':
        require_authentication();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('dashboard');
        }
        Auth::logout();
        header('Location: ' . url('home'));
        exit;

    case 'dashboard':
        $user = require_authentication();
        $stmt = db()->prepare(
            "SELECT
                SUM(CASE WHEN b.status = 'confirmed' AND s.starts_at > UTC_TIMESTAMP() THEN 1 ELSE 0 END) AS upcoming,
                COUNT(b.id) AS total
             FROM bookings b JOIN slots s ON s.id = b.slot_id WHERE b.user_id = :user_id"
        );
        $stmt->execute(['user_id' => $user['id']]);
        $summary = $stmt->fetch() ?: ['upcoming' => 0, 'total' => 0];
        render('dashboard', ['title' => 'Áttekintés', 'summary' => $summary]);
        break;

    case 'appointments':
        $serviceId = filter_var(get_param('service_id', ''), FILTER_VALIDATE_INT) ?: null;
        $date = (string) get_param('date', '');
        $where = ["s.status = 'available'", 'sv.is_active = 1', 's.starts_at > UTC_TIMESTAMP()',
            "NOT EXISTS (SELECT 1 FROM bookings b WHERE b.slot_id = s.id AND b.status = 'confirmed')"];
        $params = [];
        if ($serviceId) {
            $where[] = 's.service_id = :service_id';
            $params['service_id'] = $serviceId;
        }
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            $start = local_input_to_utc($date . 'T00:00');
            if ($start) {
                $where[] = 's.starts_at >= :date_start AND s.starts_at < :date_end';
                $params['date_start'] = $start;
                $params['date_end'] = (new DateTimeImmutable($start, new DateTimeZone('UTC')))->modify('+1 day')->format('Y-m-d H:i:s');
            }
        }
        $stmt = db()->prepare(
            'SELECT s.*, sv.name AS service_name, sv.location, u.name AS provider_name
             FROM slots s JOIN services sv ON sv.id = s.service_id JOIN users u ON u.id = s.provider_id
             WHERE ' . implode(' AND ', $where) . ' ORDER BY s.starts_at LIMIT 100'
        );
        $stmt->execute($params);
        $slots = $stmt->fetchAll();
        $services = db()->query("SELECT id, name FROM services WHERE is_active = 1 ORDER BY name")->fetchAll();
        render('slots/index', [
            'title' => 'Szabad időpontok', 'slots' => $slots, 'services' => $services,
            'serviceId' => $serviceId, 'date' => $date,
        ]);
        break;

    case 'booking-confirm':
        require_authentication();
        $slotId = filter_var(get_param('id'), FILTER_VALIDATE_INT);
        $stmt = db()->prepare(
            "SELECT s.*, sv.name AS service_name, sv.location, u.name AS provider_name
             FROM slots s JOIN services sv ON sv.id = s.service_id JOIN users u ON u.id = s.provider_id
             WHERE s.id = :id AND s.status = 'available' AND s.starts_at > UTC_TIMESTAMP()
             AND NOT EXISTS (SELECT 1 FROM bookings b WHERE b.slot_id = s.id AND b.status = 'confirmed')"
        );
        $stmt->execute(['id' => $slotId]);
        $slot = $stmt->fetch();
        if (!$slot) {
            flash('error', 'A kiválasztott időpont már nem érhető el.');
            redirect('appointments');
        }
        render('bookings/confirm', ['title' => 'Foglalás megerősítése', 'slot' => $slot]);
        break;

    case 'booking-create':
        $user = require_authentication();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('appointments');
        }
        try {
            $bookingId = $bookingService->create((int) post('slot_id'), (int) $user['id'], (string) post('note'));
            flash('success', 'A foglalás sikeresen létrejött. Azonosító: #' . $bookingId);
            redirect('my-bookings');
        } catch (DomainException $exception) {
            flash('error', $exception->getMessage());
            redirect('appointments');
        }

    case 'my-bookings':
        $user = require_authentication();
        $stmt = db()->prepare(
            'SELECT b.*, s.starts_at, s.ends_at, sv.name AS service_name, sv.location, u.name AS provider_name
             FROM bookings b JOIN slots s ON s.id = b.slot_id JOIN services sv ON sv.id = s.service_id
             JOIN users u ON u.id = s.provider_id WHERE b.user_id = :user_id
             ORDER BY s.starts_at DESC'
        );
        $stmt->execute(['user_id' => $user['id']]);
        render('bookings/mine', ['title' => 'Saját foglalásaim', 'bookings' => $stmt->fetchAll()]);
        break;

    case 'booking-cancel':
        $user = require_authentication();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('my-bookings');
        }
        try {
            $bookingService->cancel((int) post('booking_id'), $user);
            flash('success', 'A foglalást lemondta; az időpont ismét foglalható.');
        } catch (DomainException $exception) {
            flash('error', $exception->getMessage());
        }
        redirect(Auth::hasRole('admin', 'provider') ? 'manage-bookings' : 'my-bookings');

    case 'provider-slots':
        $user = require_roles('provider', 'admin');
        if ($user['role_code'] === 'admin') {
            $services = db()->query("SELECT id, name FROM services WHERE is_active = 1 ORDER BY name")->fetchAll();
            $slots = db()->query(
                "SELECT s.*, sv.name AS service_name, u.name AS provider_name,
                    EXISTS(SELECT 1 FROM bookings b WHERE b.slot_id=s.id AND b.status='confirmed') AS has_booking
                 FROM slots s JOIN services sv ON sv.id=s.service_id JOIN users u ON u.id=s.provider_id
                 ORDER BY s.starts_at DESC LIMIT 200"
            )->fetchAll();
        } else {
            $servicesStmt = db()->prepare(
                'SELECT sv.id, sv.name FROM services sv JOIN provider_services ps ON ps.service_id=sv.id
                 WHERE ps.provider_id=:id AND sv.is_active=1 ORDER BY sv.name'
            );
            $servicesStmt->execute(['id' => $user['id']]);
            $services = $servicesStmt->fetchAll();
            $slotsStmt = db()->prepare(
                "SELECT s.*, sv.name AS service_name, u.name AS provider_name,
                    EXISTS(SELECT 1 FROM bookings b WHERE b.slot_id=s.id AND b.status='confirmed') AS has_booking
                 FROM slots s JOIN services sv ON sv.id=s.service_id JOIN users u ON u.id=s.provider_id
                 WHERE s.provider_id=:id ORDER BY s.starts_at DESC LIMIT 200"
            );
            $slotsStmt->execute(['id' => $user['id']]);
            $slots = $slotsStmt->fetchAll();
        }
        render('provider/slots', ['title' => 'Időablakok kezelése', 'slots' => $slots, 'services' => $services]);
        break;

    case 'provider-slot-form':
        $user = require_roles('provider', 'admin');
        $slotId = filter_var(get_param('id'), FILTER_VALIDATE_INT) ?: null;
        $slot = null;
        if ($slotId) {
            $stmt = db()->prepare('SELECT * FROM slots WHERE id=:id');
            $stmt->execute(['id' => $slotId]);
            $slot = $stmt->fetch();
            if (!$slot || ($user['role_code'] !== 'admin' && (int) $slot['provider_id'] !== (int) $user['id'])) {
                flash('error', 'Az időablak nem található vagy nem módosítható.');
                redirect('provider-slots');
            }
        }
        if ($user['role_code'] === 'admin') {
            $services = db()->query("SELECT id,name FROM services WHERE is_active=1 ORDER BY name")->fetchAll();
            $providers = db()->query(
                "SELECT u.id,u.name FROM users u JOIN roles r ON r.id=u.role_id
                 WHERE r.code IN ('provider','admin') AND u.status='active' ORDER BY u.name"
            )->fetchAll();
        } else {
            $stmt = db()->prepare(
                'SELECT sv.id,sv.name FROM services sv JOIN provider_services ps ON ps.service_id=sv.id
                 WHERE ps.provider_id=:id AND sv.is_active=1 ORDER BY sv.name'
            );
            $stmt->execute(['id' => $user['id']]);
            $services = $stmt->fetchAll();
            $providers = [['id' => $user['id'], 'name' => $user['name']]];
        }
        render('provider/slot_form', [
            'title' => $slot ? 'Időablak módosítása' : 'Új időablak',
            'slot' => $slot, 'services' => $services, 'providers' => $providers,
        ]);
        break;

    case 'provider-slot-save':
        $user = require_roles('provider', 'admin');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('provider-slots');
        }
        $slotId = filter_var(post('slot_id'), FILTER_VALIDATE_INT) ?: null;
        $serviceId = (int) post('service_id');
        $providerId = $user['role_code'] === 'admin' ? (int) post('provider_id') : (int) $user['id'];
        $startsAt = local_input_to_utc((string) post('starts_at'));
        $endsAt = local_input_to_utc((string) post('ends_at'));
        try {
            if (!$startsAt || !$endsAt || $endsAt <= $startsAt) {
                throw new DomainException('A kezdési és befejezési időpont érvénytelen.');
            }
            if (new DateTimeImmutable($startsAt, new DateTimeZone('UTC')) <= new DateTimeImmutable('now', new DateTimeZone('UTC'))) {
                throw new DomainException('Csak jövőbeli időablak menthető.');
            }
            if ($user['role_code'] !== 'admin' && !provider_can_manage_service($providerId, $serviceId)) {
                throw new DomainException('Ehhez a szolgáltatáshoz nincs kezelési jogosultsága.');
            }

            db()->beginTransaction();
            $exclude = '';
            $params = ['provider_id' => $providerId, 'starts_at' => $startsAt, 'ends_at' => $endsAt];
            if ($slotId) {
                $own = db()->prepare('SELECT * FROM slots WHERE id=:id FOR UPDATE');
                $own->execute(['id' => $slotId]);
                $old = $own->fetch();
                if (!$old || ($user['role_code'] !== 'admin' && (int) $old['provider_id'] !== (int) $user['id'])) {
                    throw new DomainException('Az időablak nem módosítható.');
                }
                $active = db()->prepare("SELECT 1 FROM bookings WHERE slot_id=:id AND status='confirmed' LIMIT 1");
                $active->execute(['id' => $slotId]);
                if ($active->fetchColumn()) {
                    throw new DomainException('Foglalt időablak időpontja nem módosítható.');
                }
                $exclude = ' AND id <> :slot_id';
                $params['slot_id'] = $slotId;
            }
            $overlap = db()->prepare(
                'SELECT id FROM slots WHERE provider_id=:provider_id AND starts_at < :ends_at AND ends_at > :starts_at'
                . $exclude . ' LIMIT 1 FOR UPDATE'
            );
            $overlap->execute($params);
            if ($overlap->fetchColumn()) {
                throw new DomainException('Az időablak átfedésben van az időpontgazda egy másik időablakával.');
            }

            if ($slotId) {
                $stmt = db()->prepare(
                    'UPDATE slots SET service_id=:service_id,provider_id=:provider_id,starts_at=:starts_at,ends_at=:ends_at,
                     updated_at=UTC_TIMESTAMP() WHERE id=:id'
                );
                $stmt->execute([
                    'service_id' => $serviceId, 'provider_id' => $providerId,
                    'starts_at' => $startsAt, 'ends_at' => $endsAt, 'id' => $slotId,
                ]);
                audit_log((int) $user['id'], 'SLOT_UPDATED', 'slot', $slotId, $old, [
                    'service_id' => $serviceId, 'provider_id' => $providerId, 'starts_at' => $startsAt, 'ends_at' => $endsAt,
                ]);
                $message = 'Az időablak módosítása sikerült.';
            } else {
                $stmt = db()->prepare(
                    "INSERT INTO slots(service_id,provider_id,starts_at,ends_at,status,created_at,updated_at)
                     VALUES(:service_id,:provider_id,:starts_at,:ends_at,'available',UTC_TIMESTAMP(),UTC_TIMESTAMP())"
                );
                $stmt->execute([
                    'service_id' => $serviceId, 'provider_id' => $providerId,
                    'starts_at' => $startsAt, 'ends_at' => $endsAt,
                ]);
                $slotId = (int) db()->lastInsertId();
                audit_log((int) $user['id'], 'SLOT_CREATED', 'slot', $slotId, null, ['status' => 'available']);
                $message = 'Az új időablak létrejött.';
            }
            db()->commit();
            flash('success', $message);
        } catch (DomainException $exception) {
            if (db()->inTransaction()) {
                db()->rollBack();
            }
            flash('error', $exception->getMessage());
        }
        redirect('provider-slots');

    case 'provider-slot-toggle':
        $user = require_roles('provider', 'admin');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('provider-slots');
        }
        $slotId = (int) post('slot_id');
        try {
            db()->beginTransaction();
            $stmt = db()->prepare('SELECT * FROM slots WHERE id=:id FOR UPDATE');
            $stmt->execute(['id' => $slotId]);
            $slot = $stmt->fetch();
            if (!$slot || ($user['role_code'] !== 'admin' && (int) $slot['provider_id'] !== (int) $user['id'])) {
                throw new DomainException('Az időablak nem módosítható.');
            }
            $newStatus = $slot['status'] === 'available' ? 'blocked' : 'available';
            if ($newStatus === 'blocked') {
                $active = db()->prepare("SELECT 1 FROM bookings WHERE slot_id=:id AND status='confirmed' LIMIT 1");
                $active->execute(['id' => $slotId]);
                if ($active->fetchColumn()) {
                    throw new DomainException('Foglalt időablak csak a foglalás rendezése után zárolható.');
                }
            }
            $update = db()->prepare('UPDATE slots SET status=:status,updated_at=UTC_TIMESTAMP() WHERE id=:id');
            $update->execute(['status' => $newStatus, 'id' => $slotId]);
            audit_log((int) $user['id'], 'SLOT_STATUS_CHANGED', 'slot', $slotId,
                ['status' => $slot['status']], ['status' => $newStatus]);
            db()->commit();
            flash('success', 'Az időablak állapota megváltozott.');
        } catch (DomainException $exception) {
            if (db()->inTransaction()) {
                db()->rollBack();
            }
            flash('error', $exception->getMessage());
        }
        redirect('provider-slots');

    case 'manage-bookings':
        $user = require_roles('provider', 'admin');
        $where = $user['role_code'] === 'admin' ? '1=1' : 's.provider_id=:provider_id';
        $stmt = db()->prepare(
            'SELECT b.*,s.starts_at,s.ends_at,sv.name AS service_name,u.name AS customer_name,
                u.student_index AS customer_student_index,u.email,
                p.name AS provider_name
             FROM bookings b JOIN slots s ON s.id=b.slot_id JOIN services sv ON sv.id=s.service_id
             JOIN users u ON u.id=b.user_id JOIN users p ON p.id=s.provider_id
             WHERE ' . $where . ' ORDER BY s.starts_at DESC LIMIT 300'
        );
        $stmt->execute($user['role_code'] === 'admin' ? [] : ['provider_id' => $user['id']]);
        render('admin/bookings', ['title' => 'Foglalások kezelése', 'bookings' => $stmt->fetchAll()]);
        break;

    case 'booking-status':
        $user = require_roles('provider', 'admin');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('manage-bookings');
        }
        try {
            $bookingService->changeStatus((int) post('booking_id'), (string) post('status'), $user);
            flash('success', 'A foglalás állapota megváltozott.');
        } catch (DomainException $exception) {
            flash('error', $exception->getMessage());
        }
        redirect('manage-bookings');

    case 'admin-services':
        require_roles('admin');
        $services = db()->query('SELECT * FROM services ORDER BY name')->fetchAll();
        render('admin/services', ['title' => 'Szolgáltatások kezelése', 'services' => $services]);
        break;

    case 'admin-service-save':
        $user = require_roles('admin');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('admin-services');
        }
        $id = filter_var(post('service_id'), FILTER_VALIDATE_INT) ?: null;
        $name = trim((string) post('name'));
        $description = trim((string) post('description'));
        $duration = (int) post('duration_minutes');
        $location = trim((string) post('location'));
        if (mb_strlen($name) < 3 || mb_strlen($name) > 140 || $duration < 5 || $duration > 480) {
            flash('error', 'A szolgáltatás neve vagy időtartama érvénytelen.');
            redirect('admin-services');
        }
        if ($id) {
            $stmt = db()->prepare(
                'UPDATE services SET name=:name,description=:description,duration_minutes=:duration,location=:location,
                 updated_at=UTC_TIMESTAMP() WHERE id=:id'
            );
            $stmt->execute(compact('name', 'description', 'duration', 'location', 'id'));
            audit_log((int) $user['id'], 'SERVICE_UPDATED', 'service', $id);
        } else {
            $stmt = db()->prepare(
                'INSERT INTO services(name,description,duration_minutes,location,is_active,created_at,updated_at)
                 VALUES(:name,:description,:duration,:location,1,UTC_TIMESTAMP(),UTC_TIMESTAMP())'
            );
            $stmt->execute(compact('name', 'description', 'duration', 'location'));
            $id = (int) db()->lastInsertId();
            audit_log((int) $user['id'], 'SERVICE_CREATED', 'service', $id);
        }
        flash('success', 'A szolgáltatás mentése sikerült.');
        redirect('admin-services');

    case 'admin-service-toggle':
        $user = require_roles('admin');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('admin-services');
        }
        $id = (int) post('service_id');
        $stmt = db()->prepare('UPDATE services SET is_active=IF(is_active=1,0,1),updated_at=UTC_TIMESTAMP() WHERE id=:id');
        $stmt->execute(['id' => $id]);
        audit_log((int) $user['id'], 'SERVICE_STATUS_CHANGED', 'service', $id);
        flash('success', 'A szolgáltatás állapota megváltozott.');
        redirect('admin-services');

    case 'admin-users':
        require_roles('admin');
        $users = db()->query(
            "SELECT u.id,u.name,u.student_index,u.email,u.email_verified_at,u.status,u.created_at,
                r.code AS role_code,r.name AS role_name,
                GROUP_CONCAT(sv.name ORDER BY sv.name SEPARATOR ', ') AS assigned_services
             FROM users u JOIN roles r ON r.id=u.role_id
             LEFT JOIN provider_services ps ON ps.provider_id=u.id LEFT JOIN services sv ON sv.id=ps.service_id
             GROUP BY u.id ORDER BY u.name"
        )->fetchAll();
        $roles = db()->query('SELECT id,code,name FROM roles ORDER BY id')->fetchAll();
        $services = db()->query('SELECT id,name FROM services WHERE is_active=1 ORDER BY name')->fetchAll();
        render('admin/users', ['title' => 'Felhasználók kezelése', 'users' => $users, 'roles' => $roles, 'services' => $services]);
        break;

    case 'admin-user-update':
        $admin = require_roles('admin');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('admin-users');
        }
        $userId = (int) post('user_id');
        $roleId = (int) post('role_id');
        $status = in_array(post('status'), ['active', 'inactive'], true) ? (string) post('status') : 'inactive';
        if ($userId === (int) $admin['id'] && $status !== 'active') {
            flash('error', 'A saját adminisztrátori fiók nem inaktiválható.');
            redirect('admin-users');
        }
        $stmt = db()->prepare('UPDATE users SET role_id=:role_id,status=:status,updated_at=UTC_TIMESTAMP() WHERE id=:id');
        $stmt->execute(['role_id' => $roleId, 'status' => $status, 'id' => $userId]);
        audit_log((int) $admin['id'], 'USER_ACCESS_UPDATED', 'user', $userId, null, ['role_id' => $roleId, 'status' => $status]);
        flash('success', 'A felhasználó jogosultsága frissült.');
        redirect('admin-users');

    case 'admin-provider-assignment':
        $admin = require_roles('admin');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('admin-users');
        }
        $providerId = (int) post('provider_id');
        $serviceId = (int) post('service_id');
        $action = (string) post('assignment_action');
        $check = db()->prepare(
            "SELECT 1 FROM users u JOIN roles r ON r.id=u.role_id WHERE u.id=:id AND r.code IN ('provider','admin')"
        );
        $check->execute(['id' => $providerId]);
        if (!$check->fetchColumn()) {
            flash('error', 'Csak időpontgazdához vagy adminisztrátorhoz rendelhető szolgáltatás.');
            redirect('admin-users');
        }
        if ($action === 'remove') {
            $stmt = db()->prepare('DELETE FROM provider_services WHERE provider_id=:provider_id AND service_id=:service_id');
            $event = 'PROVIDER_SERVICE_REMOVED';
        } else {
            $stmt = db()->prepare(
                'INSERT INTO provider_services(provider_id,service_id) VALUES(:provider_id,:service_id)
                 ON DUPLICATE KEY UPDATE service_id=VALUES(service_id)'
            );
            $event = 'PROVIDER_SERVICE_ASSIGNED';
        }
        $stmt->execute(['provider_id' => $providerId, 'service_id' => $serviceId]);
        audit_log((int) $admin['id'], $event, 'user', $providerId, null, ['service_id' => $serviceId]);
        flash('success', 'A szolgáltatás-hozzárendelés frissült.');
        redirect('admin-users');

    case 'admin-audit':
        require_roles('admin');
        $logs = db()->query(
            'SELECT a.*,u.name AS actor_name FROM audit_logs a LEFT JOIN users u ON u.id=a.actor_id
             ORDER BY a.created_at DESC LIMIT 300'
        )->fetchAll();
        render('admin/audit', ['title' => 'Auditnapló', 'logs' => $logs]);
        break;

    case 'admin-export':
        require_roles('admin');
        $stmt = db()->query(
            'SELECT b.id,sv.name AS service_name,p.name AS provider_name,u.name AS customer_name,u.student_index,u.email,
                s.starts_at,s.ends_at,b.status,b.created_at
             FROM bookings b JOIN slots s ON s.id=b.slot_id JOIN services sv ON sv.id=s.service_id
             JOIN users p ON p.id=s.provider_id JOIN users u ON u.id=b.user_id ORDER BY s.starts_at'
        );
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="foglalasok_' . gmdate('Ymd_His') . '.csv"');
        echo "\xEF\xBB\xBF";
        $output = fopen('php://output', 'wb');
        fputcsv($output, ['Azonosító', 'Szolgáltatás', 'Időpontgazda', 'Foglaló', 'Indexszám', 'E-mail', 'Kezdés', 'Befejezés', 'Állapot', 'Létrehozva'], ';');
        while ($row = $stmt->fetch()) {
            $row['starts_at'] = utc_to_local($row['starts_at']);
            $row['ends_at'] = utc_to_local($row['ends_at']);
            $row['status'] = booking_status_label($row['status']);
            fputcsv($output, array_values($row), ';');
        }
        fclose($output);
        exit;

    default:
        http_response_code(404);
        render('errors/404', ['title' => 'Az oldal nem található']);
}
