<?php

declare(strict_types=1);

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Database;

function config(?string $path = null, mixed $default = null): mixed
{
    $config = $GLOBALS['app_config'] ?? [];
    if ($path === null || $path === '') {
        return $config;
    }
    foreach (explode('.', $path) as $segment) {
        if (!is_array($config) || !array_key_exists($segment, $config)) {
            return $default;
        }
        $config = $config[$segment];
    }
    return $config;
}

function db(): PDO
{
    return Database::connection();
}

function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function app_url(): string
{
    $configured = (string) config('url', '');
    if ($configured !== '') {
        return $configured;
    }
    $scriptName = $_SERVER['SCRIPT_NAME'] ?? '/index.php';
    $directory = rtrim(str_replace('\\', '/', dirname($scriptName)), '/');
    return $directory === '.' ? '' : $directory;
}

function url(string $route = 'home', array $params = []): string
{
    $query = http_build_query(array_merge(['route' => $route], $params));
    return app_url() . '/index.php?' . $query;
}

function redirect(string $route, array $params = []): never
{
    header('Location: ' . url($route, $params));
    exit;
}

function render(string $view, array $data = []): void
{
    extract($data, EXTR_SKIP);
    $currentUser = Auth::user();
    $pageTitle = $title ?? config('name');
    require APP_ROOT . '/views/layout/header.php';
    require APP_ROOT . '/views/' . $view . '.php';
    require APP_ROOT . '/views/layout/footer.php';
}

function flash(string $type, string $message): void
{
    $_SESSION['_flash'][] = ['type' => $type, 'message' => $message];
}

function pull_flashes(): array
{
    $items = $_SESSION['_flash'] ?? [];
    unset($_SESSION['_flash']);
    return $items;
}

function post(string $key, mixed $default = ''): mixed
{
    return $_POST[$key] ?? $default;
}

function normalize_student_index(string $value): string
{
    return trim($value);
}

function valid_student_index(string $value): bool
{
    return preg_match('/\A[0-9]{8}\z/D', $value) === 1;
}

function valid_password(string $value): bool
{
    return strlen($value) >= 10
        && preg_match('/[A-ZÁÉÍÓÖŐÚÜŰ]/u', $value) === 1
        && preg_match('/[a-záéíóöőúüű]/u', $value) === 1
        && preg_match('/\d/', $value) === 1;
}

function get_param(string $key, mixed $default = ''): mixed
{
    return $_GET[$key] ?? $default;
}

function csrf_field(): string
{
    return Csrf::field();
}

function require_authentication(): array
{
    $user = Auth::user();
    if ($user === null) {
        flash('warning', 'A művelethez be kell jelentkezni.');
        redirect('login');
    }
    return $user;
}

function require_roles(string ...$roles): array
{
    $user = require_authentication();
    if (!in_array($user['role_code'], $roles, true)) {
        http_response_code(403);
        render('errors/error', ['title' => 'Hozzáférés megtagadva', 'message' => 'Ehhez a művelethez nincs jogosultsága.']);
        exit;
    }
    return $user;
}

function local_input_to_utc(string $value): ?string
{
    $local = DateTimeImmutable::createFromFormat('!Y-m-d\TH:i', $value, new DateTimeZone((string) config('timezone')));
    $errors = DateTimeImmutable::getLastErrors();
    if (!$local || (is_array($errors) && ($errors['warning_count'] > 0 || $errors['error_count'] > 0))) {
        return null;
    }
    return $local->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s');
}

function utc_to_local(string $value, string $format = 'Y. m. d. H:i'): string
{
    return (new DateTimeImmutable($value, new DateTimeZone('UTC')))
        ->setTimezone(new DateTimeZone((string) config('timezone')))
        ->format($format);
}

function utc_to_local_input(string $value): string
{
    return utc_to_local($value, 'Y-m-d\TH:i');
}

function cancellation_is_allowed(string $slotStartUtc, ?int $hours = null): bool
{
    $hours ??= (int) config('cancellation_hours', 24);
    $start = new DateTimeImmutable($slotStartUtc, new DateTimeZone('UTC'));
    return new DateTimeImmutable('now', new DateTimeZone('UTC')) <= $start->modify('-' . $hours . ' hours');
}

function valid_booking_transition(string $from, string $to): bool
{
    $allowed = [
        'confirmed' => ['cancelled', 'completed', 'no_show'],
        'cancelled' => [],
        'completed' => [],
        'no_show' => [],
    ];
    return in_array($to, $allowed[$from] ?? [], true);
}

function booking_status_label(string $status): string
{
    return [
        'confirmed' => 'Megerősített',
        'cancelled' => 'Lemondott',
        'completed' => 'Teljesített',
        'no_show' => 'Nem jelent meg',
    ][$status] ?? $status;
}

function slot_status_label(string $status): string
{
    return ['available' => 'Szabad', 'blocked' => 'Zárolt'][$status] ?? $status;
}

function client_ip(): ?string
{
    $ip = $_SERVER['REMOTE_ADDR'] ?? null;
    return is_string($ip) && filter_var($ip, FILTER_VALIDATE_IP) ? $ip : null;
}

function audit_log(
    ?int $actorId,
    string $eventType,
    string $entityType,
    ?int $entityId,
    ?array $oldState = null,
    ?array $newState = null
): void {
    $stmt = db()->prepare(
        'INSERT INTO audit_logs (actor_id, event_type, entity_type, entity_id, old_state, new_state, ip_address, created_at)
         VALUES (:actor_id, :event_type, :entity_type, :entity_id, :old_state, :new_state, :ip_address, UTC_TIMESTAMP())'
    );
    $stmt->execute([
        'actor_id' => $actorId,
        'event_type' => $eventType,
        'entity_type' => $entityType,
        'entity_id' => $entityId,
        'old_state' => $oldState === null ? null : json_encode($oldState, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
        'new_state' => $newState === null ? null : json_encode($newState, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
        'ip_address' => client_ip(),
    ]);
}

function queue_notification(int $bookingId, string $eventType, string $recipient): void
{
    $stmt = db()->prepare(
        "INSERT INTO notifications (booking_id, channel, event_type, recipient, status, attempts, created_at)
         VALUES (:booking_id, 'email', :event_type, :recipient, 'pending', 0, UTC_TIMESTAMP())"
    );
    $stmt->execute(['booking_id' => $bookingId, 'event_type' => $eventType, 'recipient' => $recipient]);
}

function provider_can_manage_service(int $providerId, int $serviceId): bool
{
    $stmt = db()->prepare(
        'SELECT 1 FROM provider_services WHERE provider_id = :provider_id AND service_id = :service_id LIMIT 1'
    );
    $stmt->execute(['provider_id' => $providerId, 'service_id' => $serviceId]);
    return (bool) $stmt->fetchColumn();
}

function provider_can_manage_slot(int $providerId, int $slotId): bool
{
    $stmt = db()->prepare('SELECT 1 FROM slots WHERE id = :slot_id AND provider_id = :provider_id LIMIT 1');
    $stmt->execute(['slot_id' => $slotId, 'provider_id' => $providerId]);
    return (bool) $stmt->fetchColumn();
}
