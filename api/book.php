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

$apartmentId = (int) ($data['apartment_id'] ?? 0);
$checkIn = trim((string) ($data['check_in'] ?? ''));
$checkOut = trim((string) ($data['check_out'] ?? ''));
$arrivalTime = trim((string) ($data['arrival_time'] ?? '15:00'));
$guests = (int) ($data['guests'] ?? 1);
$firstName = trim((string) ($data['first_name'] ?? ''));
$lastName = trim((string) ($data['last_name'] ?? ''));
$email = trim((string) ($data['email'] ?? ''));
$phone = trim((string) ($data['phone'] ?? ''));
$message = trim((string) ($data['message'] ?? ''));

$apartment = apartment_by_id($apartmentId);
if (!$apartment) {
    json_response(['ok' => false, 'error' => 'Apartma ne obstaja.'], 404);
}

if (!valid_date($checkIn) || !valid_date($checkOut) || $checkOut <= $checkIn) {
    json_response(['ok' => false, 'error' => 'Izberite veljaven prihod in odhod.'], 422);
}
if ($checkIn < date('Y-m-d')) {
    json_response(['ok' => false, 'error' => 'Prihod ne more biti v preteklosti.'], 422);
}
if (!valid_time($arrivalTime)) {
    json_response(['ok' => false, 'error' => 'Ura prihoda ni veljavna.'], 422);
}
$arrivalTime = normalize_time($arrivalTime);
if ($guests < 1 || $guests > (int) $apartment['guests']) {
    json_response(['ok' => false, 'error' => 'Število gostov presega kapaciteto apartmaja.'], 422);
}
if ($firstName === '' || $lastName === '' || !valid_email($email) || $phone === '') {
    json_response(['ok' => false, 'error' => 'Izpolnite ime, priimek, e-pošto in telefon.'], 422);
}
if (reservation_overlaps($apartmentId, $checkIn, $checkOut)) {
    json_response(['ok' => false, 'error' => 'Izbrani dnevi niso več prosti.'], 409);
}

$quote = quote_apartment($apartment, $checkIn, $checkOut);
if ($quote['nights'] < 1) {
    json_response(['ok' => false, 'error' => 'Najmanj ena nočitev.'], 422);
}

$stmt = db()->prepare(
    'INSERT INTO reservations
    (apartment_id, check_in, check_out, arrival_time, guests, first_name, last_name, email, phone, message, total_price, status, source, created_at)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, "pending", "web", ?)'
);
$stmt->execute([
    $apartmentId, $checkIn, $checkOut, $arrivalTime, $guests,
    $firstName, $lastName, $email, $phone, $message,
    $quote['total'],
    (new DateTimeImmutable())->format('Y-m-d H:i:s'),
]);

$reservation = [
    'id' => (int) db()->lastInsertId(),
    'apartment_id' => $apartmentId,
    'check_in' => $checkIn,
    'check_out' => $checkOut,
    'arrival_time' => $arrivalTime,
    'guests' => $guests,
    'first_name' => $firstName,
    'last_name' => $lastName,
    'email' => $email,
    'phone' => $phone,
    'message' => $message,
    'total_price' => $quote['total'],
];

notify_reservation($reservation, $apartment);

json_response([
    'ok' => true,
    'id' => $reservation['id'],
    'quote' => $quote,
    'redirect' => base_url('hvala.php?type=rezervacija'),
]);
