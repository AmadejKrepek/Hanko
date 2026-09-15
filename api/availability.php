<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/functions.php';

$apartmentId = (int) ($_GET['apartment_id'] ?? 0);
$from = (string) ($_GET['from'] ?? date('Y-m-01'));
$to = (string) ($_GET['to'] ?? date('Y-m-d', strtotime('+4 months')));

if ($apartmentId < 1 || !valid_date($from) || !valid_date($to)) {
    json_response(['ok' => false, 'error' => 'Neveljavni parametri.'], 400);
}

$apartment = apartment_by_id($apartmentId);
if (!$apartment) {
    json_response(['ok' => false, 'error' => 'Apartma ne obstaja.'], 404);
}

json_response([
    'ok' => true,
    'apartment_id' => $apartmentId,
    'ranges' => booked_ranges($apartmentId, $from, $to),
]);
