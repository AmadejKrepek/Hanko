<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/functions.php';

if (!is_admin()) {
    json_response(['ok' => false, 'error' => 'Ni prijavljen.'], 401);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['ok' => false, 'error' => 'Dovoljen je samo POST.'], 405);
}

$data = posted_json() ?: $_POST;
if (!csrf_verify((string) ($data['csrf'] ?? ''))) {
    json_response(['ok' => false, 'error' => 'Seja je potekla.'], 403);
}

$action = (string) ($data['action'] ?? '');

if ($action === 'delete') {
    $id = (int) ($data['id'] ?? 0);
    db()->prepare('DELETE FROM transfers WHERE id = ?')->execute([$id]);
    json_response(['ok' => true]);
}

if ($action === 'status') {
    $id = (int) ($data['id'] ?? 0);
    $status = (string) ($data['status'] ?? '');
    if (!in_array($status, ['pending', 'confirmed', 'cancelled'], true)) {
        json_response(['ok' => false, 'error' => 'Neveljaven status.'], 422);
    }
    db()->prepare('UPDATE transfers SET status = ? WHERE id = ?')->execute([$status, $id]);
    json_response(['ok' => true]);
}

if ($action === 'create') {
    $direction = (string) ($data['direction'] ?? 'from_airport');
    $vehicle = (string) ($data['vehicle_type'] ?? 'private');
    $airport = strtoupper(trim((string) ($data['airport'] ?? 'LJU')));
    $dateOutbound = trim((string) ($data['date_outbound'] ?? ''));
    $timeOutbound = trim((string) ($data['time_outbound'] ?? '10:00'));
    $dateReturn = trim((string) ($data['date_return'] ?? ''));
    $timeReturn = trim((string) ($data['time_return'] ?? ''));
    $passengers = max(1, (int) ($data['passengers'] ?? 1));
    $children = max(0, (int) ($data['child_count'] ?? $data['children'] ?? 0));
    $luggage = max(0, (int) ($data['luggage'] ?? 1));
    $flightNumber = trim((string) ($data['flight_number'] ?? ''));
    $firstName = trim((string) ($data['first_name'] ?? 'Gost'));
    $lastName = trim((string) ($data['last_name'] ?? 'Admin'));
    $email = trim((string) ($data['email'] ?? ADMIN_NOTIFY_EMAIL));
    $phone = trim((string) ($data['phone'] ?? SITE_PHONE));
    $message = trim((string) ($data['message'] ?? ''));

    if (!in_array($direction, ['from_airport', 'to_airport', 'round_trip'], true)) {
        json_response(['ok' => false, 'error' => 'Smer ni veljavna.'], 422);
    }
    if (!isset(transfer_routes()[$airport]) || !valid_date($dateOutbound) || !valid_time($timeOutbound)) {
        json_response(['ok' => false, 'error' => 'Preverite letališče, datum in uro.'], 422);
    }
    $timeOutbound = normalize_time($timeOutbound);
    if ($timeReturn !== '' && valid_time($timeReturn)) {
        $timeReturn = normalize_time($timeReturn);
    }

    $quote = quote_transfer($airport, $direction, $vehicle === 'shared' ? 'shared' : 'private', $passengers);
    $route = transfer_routes()[$airport];
    $pickup = $direction === 'to_airport' ? residence_label() : $route['name'];
    $dropoff = $direction === 'to_airport' ? $route['name'] : residence_label();
    if ($direction === 'round_trip') {
        $pickup = $route['name'] . ' / ' . residence_label();
        $dropoff = residence_label() . ' / ' . $route['name'];
    }

    $stmt = db()->prepare(
        'INSERT INTO transfers
        (direction, vehicle_type, pickup, dropoff, date_outbound, time_outbound, date_return, time_return, passengers, children, luggage, flight_number, price, first_name, last_name, email, phone, message, status, created_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, "confirmed", ?)'
    );
    $stmt->execute([
        $direction, $vehicle === 'shared' ? 'shared' : 'private', $pickup, $dropoff,
        $dateOutbound, $timeOutbound,
        $direction === 'round_trip' && $dateReturn !== '' ? $dateReturn : null,
        $direction === 'round_trip' && $timeReturn !== '' ? $timeReturn : null,
        $passengers, $children, $luggage, $flightNumber ?: null, $quote['price'],
        $firstName, $lastName, $email, $phone, $message,
        (new DateTimeImmutable())->format('Y-m-d H:i:s'),
    ]);
    json_response(['ok' => true, 'id' => (int) db()->lastInsertId()]);
}

json_response(['ok' => false, 'error' => 'Neznana akcija.'], 400);
