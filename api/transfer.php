<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/mail.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['ok' => false, 'error' => 'Dovoljen je samo POST.'], 405);
}

$data = posted_json();
if (!$data) {
    $data = $_POST;
}

if (!csrf_verify((string) ($data['csrf'] ?? ''))) {
    json_response(['ok' => false, 'error' => 'Seja je potekla. Osvežite stran.'], 403);
}

$direction = (string) ($data['direction'] ?? '');
$vehicle = (string) ($data['vehicle_type'] ?? '');
$airport = strtoupper(trim((string) ($data['airport'] ?? '')));
$dateOutbound = trim((string) ($data['date_outbound'] ?? ''));
$timeOutbound = trim((string) ($data['time_outbound'] ?? ''));
$dateReturn = trim((string) ($data['date_return'] ?? ''));
$timeReturn = trim((string) ($data['time_return'] ?? ''));
$passengers = (int) ($data['passengers'] ?? 0);
$children = (int) ($data['children'] ?? 0);
$luggage = (int) ($data['luggage'] ?? 0);
$flightNumber = trim((string) ($data['flight_number'] ?? ''));
$firstName = trim((string) ($data['first_name'] ?? ''));
$lastName = trim((string) ($data['last_name'] ?? ''));
$email = trim((string) ($data['email'] ?? ''));
$phone = trim((string) ($data['phone'] ?? ''));
$message = trim((string) ($data['message'] ?? ''));
$quoteOnly = !empty($data['quote_only']);

if (!in_array($direction, ['from_airport', 'to_airport', 'round_trip'], true)) {
    json_response(['ok' => false, 'error' => 'Izberite smer prevoza.'], 422);
}
if (!in_array($vehicle, ['shared', 'private'], true) && !$quoteOnly) {
    json_response(['ok' => false, 'error' => 'Izberite tip vozila.'], 422);
}
if (!isset(transfer_routes()[$airport])) {
    json_response(['ok' => false, 'error' => 'Izberite letališče.'], 422);
}
if (!valid_date($dateOutbound) || !valid_time($timeOutbound)) {
    json_response(['ok' => false, 'error' => 'Datum in ura odhoda nista veljavna.'], 422);
}
$timeOutbound = normalize_time($timeOutbound);
if ($direction === 'round_trip') {
    if (!valid_date($dateReturn) || !valid_time($timeReturn)) {
        json_response(['ok' => false, 'error' => 'Za hin in nazaj vnesite tudi vrnitev.'], 422);
    }
    $timeReturn = normalize_time($timeReturn);
}
if ($passengers < 1 || $passengers > 8) {
    json_response(['ok' => false, 'error' => 'Število potnikov mora biti med 1 in 8.'], 422);
}

$quotes = [
    'shared' => quote_transfer($airport, $direction, 'shared', $passengers),
    'private' => quote_transfer($airport, $direction, 'private', $passengers),
];

if ($quoteOnly) {
    json_response(['ok' => true, 'quotes' => $quotes, 'route' => transfer_routes()[$airport]]);
}

if ($firstName === '' || $lastName === '' || !valid_email($email) || $phone === '') {
    json_response(['ok' => false, 'error' => 'Izpolnite ime, priimek, e-pošto in telefon.'], 422);
}

$route = transfer_routes()[$airport];
$pickup = $direction === 'to_airport' ? residence_label() : $route['name'];
$dropoff = $direction === 'to_airport' ? $route['name'] : residence_label();
if ($direction === 'round_trip') {
    $pickup = $route['name'] . ' / ' . residence_label();
    $dropoff = residence_label() . ' / ' . $route['name'];
}

$price = $quotes[$vehicle]['price'];
$stmt = db()->prepare(
    'INSERT INTO transfers
    (direction, vehicle_type, pickup, dropoff, date_outbound, time_outbound, date_return, time_return, passengers, children, luggage, flight_number, price, first_name, last_name, email, phone, message, status, created_at)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, "pending", ?)'
);
$stmt->execute([
    $direction, $vehicle, $pickup, $dropoff, $dateOutbound, $timeOutbound,
    $direction === 'round_trip' ? $dateReturn : null,
    $direction === 'round_trip' ? $timeReturn : null,
    $passengers, $children, $luggage, $flightNumber ?: null, $price,
    $firstName, $lastName, $email, $phone, $message,
    (new DateTimeImmutable())->format('Y-m-d H:i:s'),
]);

$transfer = [
    'id' => (int) db()->lastInsertId(),
    'direction' => $direction,
    'vehicle_type' => $vehicle,
    'pickup' => $pickup,
    'dropoff' => $dropoff,
    'date_outbound' => $dateOutbound,
    'time_outbound' => $timeOutbound,
    'date_return' => $direction === 'round_trip' ? $dateReturn : null,
    'time_return' => $direction === 'round_trip' ? $timeReturn : null,
    'passengers' => $passengers,
    'children' => $children,
    'luggage' => $luggage,
    'flight_number' => $flightNumber,
    'price' => $price,
    'first_name' => $firstName,
    'last_name' => $lastName,
    'email' => $email,
    'phone' => $phone,
    'message' => $message,
];

notify_transfer($transfer);

json_response([
    'ok' => true,
    'id' => $transfer['id'],
    'price' => $price,
    'redirect' => base_url('hvala.php?type=prevoz'),
]);
