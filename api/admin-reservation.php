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
    db()->prepare('DELETE FROM reservations WHERE id = ?')->execute([$id]);
    json_response(['ok' => true]);
}

if ($action === 'status') {
    $id = (int) ($data['id'] ?? 0);
    $status = (string) ($data['status'] ?? '');
    if (!in_array($status, ['pending', 'confirmed', 'cancelled', 'blocked'], true)) {
        json_response(['ok' => false, 'error' => 'Neveljaven status.'], 422);
    }
    db()->prepare('UPDATE reservations SET status = ? WHERE id = ?')->execute([$status, $id]);
    json_response(['ok' => true]);
}

if ($action === 'create') {
    $apartmentId = (int) ($data['apartment_id'] ?? 0);
    $checkIn = trim((string) ($data['check_in'] ?? ''));
    $checkOut = trim((string) ($data['check_out'] ?? ''));
    $arrivalTime = trim((string) ($data['arrival_time'] ?? '')) ?: null;
    $guests = (int) ($data['guests'] ?? 1);
    $firstName = trim((string) ($data['first_name'] ?? 'Gost'));
    $lastName = trim((string) ($data['last_name'] ?? 'Admin'));
    $email = trim((string) ($data['email'] ?? ADMIN_NOTIFY_EMAIL));
    $phone = trim((string) ($data['phone'] ?? SITE_PHONE));
    $message = trim((string) ($data['message'] ?? ''));
    $status = (string) ($data['status'] ?? 'confirmed');
    if (!in_array($status, ['pending', 'confirmed', 'blocked'], true)) {
        $status = 'confirmed';
    }

    $apartment = apartment_by_id($apartmentId);
    if (!$apartment || !valid_date($checkIn) || !valid_date($checkOut) || $checkOut <= $checkIn) {
        json_response(['ok' => false, 'error' => 'Preverite apartma in datume.'], 422);
    }
    if (reservation_overlaps($apartmentId, $checkIn, $checkOut)) {
        json_response(['ok' => false, 'error' => 'Termin se prekriva z obstoječo rezervacijo.'], 409);
    }

    $quote = quote_apartment($apartment, $checkIn, $checkOut);
    $stmt = db()->prepare(
        'INSERT INTO reservations
        (apartment_id, check_in, check_out, arrival_time, guests, first_name, last_name, email, phone, message, total_price, status, source, created_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, "admin", ?)'
    );
    $stmt->execute([
        $apartmentId, $checkIn, $checkOut, $arrivalTime, max(0, $guests),
        $firstName, $lastName, $email, $phone, $message,
        $status === 'blocked' ? 0 : $quote['total'],
        $status,
        (new DateTimeImmutable())->format('Y-m-d H:i:s'),
    ]);
    json_response(['ok' => true, 'id' => (int) db()->lastInsertId()]);
}

json_response(['ok' => false, 'error' => 'Neznana akcija.'], 400);
