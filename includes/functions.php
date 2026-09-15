<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

function nights_between(string $checkIn, string $checkOut): int
{
    $start = new DateTimeImmutable($checkIn);
    $end = new DateTimeImmutable($checkOut);
    return max(0, (int) $start->diff($end)->days);
}

function quote_apartment(array $apartment, string $checkIn, string $checkOut): array
{
    $nights = nights_between($checkIn, $checkOut);
    $subtotal = $nights * (float) $apartment['price_per_night'];
    $cleaning = (float) $apartment['cleaning_fee'];
    return [
        'nights' => $nights,
        'subtotal' => $subtotal,
        'cleaning' => $cleaning,
        'total' => $subtotal + $cleaning,
    ];
}

function transfer_routes(): array
{
    return [
        'LJU' => ['name' => 'Letališče Ljubljana (LJU)', 'minutes' => 45, 'shared' => 29, 'private' => 89],
        'KLU' => ['name' => 'Letališče Celovec (KLU)', 'minutes' => 70, 'shared' => 39, 'private' => 119],
        'TRS' => ['name' => 'Letališče Trst (TRS)', 'minutes' => 95, 'shared' => 45, 'private' => 139],
        'ZAG' => ['name' => 'Letališče Zagreb (ZAG)', 'minutes' => 130, 'shared' => 49, 'private' => 159],
        'GRZ' => ['name' => 'Letališče Gradec (GRZ)', 'minutes' => 120, 'shared' => 49, 'private' => 155],
        'VCE' => ['name' => 'Letališče Benetke Marco Polo (VCE)', 'minutes' => 160, 'shared' => 59, 'private' => 189],
        'TSF' => ['name' => 'Letališče Treviso (TSF)', 'minutes' => 170, 'shared' => 59, 'private' => 195],
    ];
}

function residence_label(): string
{
    return 'Hanko Apartmaji, Gorenjska';
}

function transfer_direction_label(string $direction): string
{
    return match ($direction) {
        'from_airport' => 'Iz letališča do apartmajev',
        'to_airport' => 'Iz apartmajev na letališče',
        'round_trip' => 'Hin in nazaj',
        default => $direction,
    };
}

function quote_transfer(string $airportCode, string $direction, string $vehicle, int $passengers): array
{
    $routes = transfer_routes();
    if (!isset($routes[$airportCode])) {
        throw new InvalidArgumentException('Neznano letališče.');
    }
    $route = $routes[$airportCode];
    $base = $vehicle === 'private' ? (float) $route['private'] : (float) $route['shared'];
    if ($vehicle === 'shared' && $passengers > 3) {
        $base += ($passengers - 3) * 8;
    }
    if ($vehicle === 'private' && $passengers > 4) {
        $base += 25;
    }
    $multiplier = $direction === 'round_trip' ? 1.85 : 1.0;
    $price = round($base * $multiplier, 2);
    return [
        'airport' => $route['name'],
        'minutes' => (int) $route['minutes'],
        'price' => $price,
        'one_way' => $base,
    ];
}

function posted_json(): array
{
    $raw = file_get_contents('php://input') ?: '';
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

function valid_email(string $email): bool
{
    return (bool) filter_var($email, FILTER_VALIDATE_EMAIL);
}

function valid_date(string $date): bool
{
    $dt = DateTimeImmutable::createFromFormat('Y-m-d', $date);
    return $dt !== false && $dt->format('Y-m-d') === $date;
}

function valid_time(string $time): bool
{
    return (bool) preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d(?::[0-5]\d)?$/', $time);
}

function normalize_time(string $time): string
{
    return substr($time, 0, 5);
}
