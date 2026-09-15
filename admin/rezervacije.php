<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/functions.php';

require_admin();

$apartments = all_apartments();
$filter = (int) ($_GET['apartment'] ?? 0);

$sql = 'SELECT r.*, a.name AS apartment_name
        FROM reservations r
        JOIN apartments a ON a.id = r.apartment_id';
$params = [];
if ($filter) {
    $sql .= ' WHERE r.apartment_id = ?';
    $params[] = $filter;
}
$sql .= ' ORDER BY r.check_in DESC, r.id DESC';
$stmt = db()->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

$pageTitle = 'Rezervacije';
$adminPage = 'rezervacije';
require __DIR__ . '/includes/header.php';
?>

<section class="panel">
    <div class="panel-head">
        <h2>Dodaj rezervacijo ali blokado</h2>
    </div>
    <form class="admin-form" data-admin-form data-endpoint="<?= h(base_url('api/admin-reservation.php')) ?>">
        <input type="hidden" name="action" value="create">
        <label>Apartma
            <select name="apartment_id" required>
                <?php foreach ($apartments as $apt): ?>
                    <option value="<?= (int) $apt['id'] ?>"><?= h($apt['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>Prihod <input type="date" name="check_in" required></label>
        <label>Odhod <input type="date" name="check_out" required></label>
        <label>Ura prihoda <input type="time" name="arrival_time" value="15:00"></label>
        <label>Gostje <input type="number" name="guests" min="0" max="6" value="2"></label>
        <label>Ime <input type="text" name="first_name" required></label>
        <label>Priimek <input type="text" name="last_name" required></label>
        <label>E-pošta <input type="email" name="email" value="<?= h(ADMIN_NOTIFY_EMAIL) ?>"></label>
        <label>Telefon <input type="tel" name="phone" value="<?= h(SITE_PHONE) ?>"></label>
        <label>Status
            <select name="status">
                <option value="confirmed">Potrjeno</option>
                <option value="pending">V čakanju</option>
                <option value="blocked">Blokada / vzdrževanje</option>
            </select>
        </label>
        <label class="full">Opomba <input type="text" name="message"></label>
        <button class="btn" type="submit">Shrani</button>
        <p class="form-status" data-form-status hidden></p>
    </form>
</section>

<section class="panel">
    <div class="panel-head">
        <h2>Kdo je kje naročen</h2>
        <form method="get">
            <select name="apartment" onchange="this.form.submit()">
                <option value="0">Vsi apartmaji</option>
                <?php foreach ($apartments as $apt): ?>
                    <option value="<?= (int) $apt['id'] ?>" <?= $filter === (int) $apt['id'] ? 'selected' : '' ?>><?= h($apt['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </form>
    </div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Apartma</th>
                    <th>Gost</th>
                    <th>Kontakt</th>
                    <th>Termini</th>
                    <th>Gostje</th>
                    <th>Cena</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($rows as $row): ?>
                <tr data-row>
                    <td><?= h($row['apartment_name']) ?></td>
                    <td><?= h($row['first_name'] . ' ' . $row['last_name']) ?></td>
                    <td><?= h($row['email']) ?><br><?= h($row['phone']) ?></td>
                    <td><?= h($row['check_in']) ?> → <?= h($row['check_out']) ?><?= $row['arrival_time'] ? '<br>prihod ' . h($row['arrival_time']) : '' ?></td>
                    <td><?= (int) $row['guests'] ?></td>
                    <td><?= h(money_eur((float) $row['total_price'])) ?></td>
                    <td>
                        <select data-status-select data-endpoint="<?= h(base_url('api/admin-reservation.php')) ?>" data-id="<?= (int) $row['id'] ?>">
                            <?php foreach (['pending', 'confirmed', 'cancelled', 'blocked'] as $st): ?>
                                <option value="<?= $st ?>" <?= $row['status'] === $st ? 'selected' : '' ?>><?= $st ?></option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                    <td>
                        <button class="btn btn-small btn-danger" type="button" data-delete data-endpoint="<?= h(base_url('api/admin-reservation.php')) ?>" data-id="<?= (int) $row['id'] ?>">Izbriši</button>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$rows): ?><tr><td colspan="8">Ni rezervacij.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
