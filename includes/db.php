<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';

function db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    if (!is_dir(DATA_PATH)) {
        mkdir(DATA_PATH, 0775, true);
    }

    $isNew = !file_exists(DB_PATH);
    $pdo = new PDO('sqlite:' . DB_PATH, null, null, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
    $pdo->exec('PRAGMA foreign_keys = ON');
    $pdo->exec('PRAGMA journal_mode = WAL');

    init_schema($pdo);
    if ($isNew) {
        seed_data($pdo);
    } else {
        ensure_admin($pdo);
    }

    return $pdo;
}

function init_schema(PDO $pdo): void
{
    $pdo->exec(<<<'SQL'
        CREATE TABLE IF NOT EXISTS apartments (
            id INTEGER PRIMARY KEY,
            slug TEXT NOT NULL UNIQUE,
            name TEXT NOT NULL,
            subtitle TEXT NOT NULL,
            short_desc TEXT NOT NULL,
            description TEXT NOT NULL,
            guests INTEGER NOT NULL,
            bedrooms INTEGER NOT NULL,
            bathrooms INTEGER NOT NULL,
            size_m2 INTEGER NOT NULL,
            price_per_night REAL NOT NULL,
            cleaning_fee REAL NOT NULL DEFAULT 0,
            amenities TEXT NOT NULL,
            images TEXT NOT NULL
        );

        CREATE TABLE IF NOT EXISTS reservations (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            apartment_id INTEGER NOT NULL,
            check_in TEXT NOT NULL,
            check_out TEXT NOT NULL,
            arrival_time TEXT,
            guests INTEGER NOT NULL DEFAULT 1,
            first_name TEXT NOT NULL,
            last_name TEXT NOT NULL,
            email TEXT NOT NULL,
            phone TEXT NOT NULL,
            message TEXT,
            total_price REAL NOT NULL DEFAULT 0,
            status TEXT NOT NULL DEFAULT 'pending',
            source TEXT NOT NULL DEFAULT 'web',
            created_at TEXT NOT NULL,
            FOREIGN KEY (apartment_id) REFERENCES apartments(id)
        );

        CREATE TABLE IF NOT EXISTS transfers (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            direction TEXT NOT NULL,
            vehicle_type TEXT NOT NULL,
            pickup TEXT NOT NULL,
            dropoff TEXT NOT NULL,
            date_outbound TEXT NOT NULL,
            time_outbound TEXT NOT NULL,
            date_return TEXT,
            time_return TEXT,
            passengers INTEGER NOT NULL,
            children INTEGER NOT NULL DEFAULT 0,
            luggage INTEGER NOT NULL DEFAULT 1,
            flight_number TEXT,
            price REAL NOT NULL,
            first_name TEXT NOT NULL,
            last_name TEXT NOT NULL,
            email TEXT NOT NULL,
            phone TEXT NOT NULL,
            message TEXT,
            status TEXT NOT NULL DEFAULT 'pending',
            created_at TEXT NOT NULL
        );

        CREATE TABLE IF NOT EXISTS admin_users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            username TEXT NOT NULL UNIQUE,
            password_hash TEXT NOT NULL
        );

        CREATE INDEX IF NOT EXISTS idx_reservations_apt_dates
            ON reservations (apartment_id, check_in, check_out, status);
        CREATE INDEX IF NOT EXISTS idx_transfers_dates
            ON transfers (date_outbound, status);
    SQL);
}

function ensure_admin(PDO $pdo): void
{
    $count = (int) $pdo->query('SELECT COUNT(*) FROM admin_users')->fetchColumn();
    if ($count === 0) {
        $stmt = $pdo->prepare('INSERT INTO admin_users (username, password_hash) VALUES (?, ?)');
        $stmt->execute([DEFAULT_ADMIN_USER, password_hash(DEFAULT_ADMIN_PASS, PASSWORD_DEFAULT)]);
        return;
    }

    if (!env_bool('HANKO_RESET_ADMIN', false)) {
        return;
    }

    $existing = $pdo->query('SELECT id FROM admin_users ORDER BY id LIMIT 1')->fetch();
    if (!$existing) {
        return;
    }
    $stmt = $pdo->prepare('UPDATE admin_users SET username = ?, password_hash = ? WHERE id = ?');
    $stmt->execute([
        DEFAULT_ADMIN_USER,
        password_hash(DEFAULT_ADMIN_PASS, PASSWORD_DEFAULT),
        (int) $existing['id'],
    ]);
}

function seed_data(PDO $pdo): void
{
    $apartments = [
        [
            1,
            'lipa',
            'Apartma Lipa',
            'Intimni studio za dva',
            'Svetel studio z balkonom, kuhinjo in pogledom v gozd. Idealno za par ali solo pobeg.',
            "Apartma Lipa je zasnovan za dva gosta, ki želita mir, svetlobo in občutek doma. Leseni detajli, veliko okno proti gozdu in kuhinja, v kateri lahko pripravite zajtrk ali večerjo po izletu.\n\nZajtrk lahko zaužijete na balkonu, popoldne pa se vrnete k mehki postelji in tušu z deževno prho. Lokacija je odlična izhodiščna točka za Bled, Bohinj in Julijce, hkrati pa dovolj umaknjena za pravi oddih.",
            2, 1, 1, 38, 89, 35,
            json_encode([
                'Wi-Fi', 'Kuhinja', 'Balkon', 'Parkirišče', 'Klimatska naprava',
                'Pralni stroj', 'Posteljnina', 'Brisače', 'Sušilec za lase', 'Kavni aparat',
            ], JSON_UNESCAPED_UNICODE),
            json_encode([
                'https://images.unsplash.com/photo-1505691938895-1758d7feb511?auto=format&fit=crop&w=1600&q=80',
                'https://images.unsplash.com/photo-1560448204-e02f11c3d0e2?auto=format&fit=crop&w=1600&q=80',
                'https://images.unsplash.com/photo-1505693416388-ac5ce068fe85?auto=format&fit=crop&w=1600&q=80',
            ]),
        ],
        [
            2,
            'javor',
            'Apartma Javor',
            'Dve spalnici, terasa, štiri osebe',
            'Prostoren apartma za družino ali dva para. Terasa, dnevni prostor in popolnoma opremljena kuhinja.',
            "Apartma Javor je srce Hanko Residence. Dve ločeni spalnici, svetel dnevni prostor in terasa, kjer se dnevi podaljšajo v večere. Kuhinja je opremljena za pravo kuhanje, ne le za kavo.\n\nPrimerno za družine z otroki ali skupino prijateljev. V bližini so sprehajalne poti, jezera in restavracije. Po dogovoru uredimo tudi otroško posteljico in stolček.",
            4, 2, 1, 68, 139, 55,
            json_encode([
                'Wi-Fi', 'Kuhinja', 'Terasa', 'Parkirišče', 'TV', 'Pomivalni stroj',
                'Pralni stroj', 'Otroška posteljica po dogovoru', 'Žar', 'Kolesarnica',
            ], JSON_UNESCAPED_UNICODE),
            json_encode([
                'https://images.unsplash.com/photo-1600210492486-724fe5c67fb0?auto=format&fit=crop&w=1600&q=80',
                'https://images.unsplash.com/photo-1600585154340-0ef3c08c08be?auto=format&fit=crop&w=1600&q=80',
                'https://images.unsplash.com/photo-1600566753190-17f0baa2a6c3?auto=format&fit=crop&w=1600&q=80',
            ]),
        ],
        [
            3,
            'hrast',
            'Apartma Hrast',
            'Panorama za šest oseb',
            'Največji apartma z razgledom, tremi spalnicami in veliko teraso. Za družine in skupine.',
            "Apartma Hrast je naš največji dom. Tri spalnice, dve kopalnici in dnevni prostor, ki zbere vso družbo. Velika terasa z razgledom je namenjena dolgim zajtrkom in zvečerjim ob žaru.\n\nIdealno za večgeneracijske počitnice ali skupino prijateljev. Na voljo je dovolj prostora za prtljago, smuči ali kolesa. Ob prihodu vas lahko pobiramo tudi na letališču.",
            6, 3, 2, 98, 189, 75,
            json_encode([
                'Wi-Fi', 'Kuhinja', 'Velika terasa', 'Parkirišče', 'Dve kopalnici',
                'Žar', 'Kolesarnica', 'Sušilnica', 'Smart TV', 'Klima', 'Sejf',
            ], JSON_UNESCAPED_UNICODE),
            json_encode([
                'https://images.unsplash.com/photo-1613490493576-7fde62b353d2?auto=format&fit=crop&w=1600&q=80',
                'https://images.unsplash.com/photo-1600607687939-ce8a6c25118c?auto=format&fit=crop&w=1600&q=80',
                'https://images.unsplash.com/photo-1600585154526-990dced4db0d?auto=format&fit=crop&w=1600&q=80',
            ]),
        ],
    ];

    $stmt = $pdo->prepare(
        'INSERT INTO apartments
        (id, slug, name, subtitle, short_desc, description, guests, bedrooms, bathrooms, size_m2, price_per_night, cleaning_fee, amenities, images)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
    );
    foreach ($apartments as $row) {
        $stmt->execute($row);
    }

    ensure_admin($pdo);

    $now = (new DateTimeImmutable('now'))->format('Y-m-d H:i:s');
    $blocked = $pdo->prepare(
        'INSERT INTO reservations
        (apartment_id, check_in, check_out, arrival_time, guests, first_name, last_name, email, phone, message, total_price, status, source, created_at)
        VALUES (?, ?, ?, NULL, 0, ?, ?, ?, ?, ?, 0, ?, ?, ?)'
    );
    $blocked->execute([
        2,
        (new DateTimeImmutable('+10 days'))->format('Y-m-d'),
        (new DateTimeImmutable('+14 days'))->format('Y-m-d'),
        'Blokada',
        'Admin',
        ADMIN_NOTIFY_EMAIL,
        SITE_PHONE,
        'Vzdrževanje',
        'blocked',
        'admin',
        $now,
    ]);
}

function apartment_by_id(int $id): ?array
{
    $stmt = db()->prepare('SELECT * FROM apartments WHERE id = ?');
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    return $row ? hydrate_apartment($row) : null;
}

function apartment_by_slug(string $slug): ?array
{
    $stmt = db()->prepare('SELECT * FROM apartments WHERE slug = ?');
    $stmt->execute([$slug]);
    $row = $stmt->fetch();
    return $row ? hydrate_apartment($row) : null;
}

function all_apartments(): array
{
    $rows = db()->query('SELECT * FROM apartments ORDER BY id')->fetchAll();
    return array_map('hydrate_apartment', $rows);
}

function hydrate_apartment(array $row): array
{
    $row['amenities'] = json_decode((string) $row['amenities'], true) ?: [];
    $row['images'] = json_decode((string) $row['images'], true) ?: [];
    return $row;
}

function reservation_overlaps(int $apartmentId, string $checkIn, string $checkOut, ?int $ignoreId = null): bool
{
    $sql = 'SELECT COUNT(*) FROM reservations
            WHERE apartment_id = ?
              AND status != "cancelled"
              AND check_in < ?
              AND check_out > ?';
    $params = [$apartmentId, $checkOut, $checkIn];
    if ($ignoreId) {
        $sql .= ' AND id != ?';
        $params[] = $ignoreId;
    }
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return (int) $stmt->fetchColumn() > 0;
}

function booked_ranges(int $apartmentId, string $from, string $to): array
{
    $stmt = db()->prepare(
        'SELECT id, check_in, check_out, status FROM reservations
         WHERE apartment_id = ?
           AND status != "cancelled"
           AND check_in < ?
           AND check_out > ?
         ORDER BY check_in'
    );
    $stmt->execute([$apartmentId, $to, $from]);
    return $stmt->fetchAll();
}
