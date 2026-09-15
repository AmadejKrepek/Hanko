<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/functions.php';

require_admin();

$rows = db()->query('SELECT * FROM transfers ORDER BY date_outbound DESC, id DESC')->fetchAll();
$routes = transfer_routes();

$pageTitle = 'Prevozi';
$adminPage = 'prevozi';
require __DIR__ . '/includes/header.php';
?>

<section class="panel">
    <div class="panel-head">
        <h2>Dodaj prevoz</h2>
    </div>
    <form class="admin-form" data-admin-form data-endpoint="<?= h(base_url('api/admin-transfer.php')) ?>">
        <input type="hidden" name="action" value="create">
        <label>Smer
            <select name="direction">
                <option value="from_airport">Iz letališča</option>
                <option value="to_airport">Na letališče</option>
                <option value="round_trip">Hin in nazaj</option>
            </select>
        </label>
        <label>Letališče
            <select name="airport">
                <?php foreach ($routes as $code => $route): ?>
                    <option value="<?= h($code) ?>"><?= h($route['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>Vozilo
            <select name="vehicle_type">
                <option value="private">Zasebno</option>
                <option value="shared">Skupno</option>
            </select>
        </label>
        <label>Datum <input type="date" name="date_outbound" required></label>
        <label>Ura <input type="time" name="time_outbound" value="10:00" required></label>
        <label>Vrnitev datum <input type="date" name="date_return"></label>
        <label>Vrnitev ura <input type="time" name="time_return"></label>
        <label>Potniki <input type="number" name="passengers" min="1" max="8" value="2"></label>
        <label>Otroci <input type="number" name="children" min="0" value="0"></label>
        <label>Prtljaga <input type="number" name="luggage" min="0" value="2"></label>
        <label>Št. leta <input type="text" name="flight_number"></label>
        <label>Ime <input type="text" name="first_name" required></label>
        <label>Priimek <input type="text" name="last_name" required></label>
        <label>E-pošta <input type="email" name="email" required></label>
        <label>Telefon <input type="tel" name="phone" required></label>
        <label class="full">Opomba <input type="text" name="message"></label>
        <button class="btn" type="submit">Shrani prevoz</button>
        <p class="form-status" data-form-status hidden></p>
    </form>
</section>

<section class="panel">
    <div class="panel-head">
        <h2>Naročeni prevozi</h2>
    </div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Gost</th>
                    <th>Smer</th>
                    <th>Proga</th>
                    <th>Kdaj</th>
                    <th>Potniki</th>
                    <th>Cena</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($rows as $row): ?>
                <tr data-row>
                    <td><?= h($row['first_name'] . ' ' . $row['last_name']) ?><br><small><?= h($row['email']) ?><br><?= h($row['phone']) ?></small></td>
                    <td><?= h(transfer_direction_label($row['direction'])) ?><br><small><?= $row['vehicle_type'] === 'private' ? 'zasebno' : 'skupno' ?></small></td>
                    <td><?= h($row['pickup']) ?> → <?= h($row['dropoff']) ?><?= $row['flight_number'] ? '<br>let ' . h($row['flight_number']) : '' ?></td>
                    <td><?= h($row['date_outbound']) ?> <?= h($row['time_outbound']) ?><?= $row['date_return'] ? '<br>nazaj ' . h($row['date_return'] . ' ' . $row['time_return']) : '' ?></td>
                    <td><?= (int) $row['passengers'] ?> + <?= (int) $row['children'] ?> otr.<br><?= (int) $row['luggage'] ?> prtl.</td>
                    <td><?= h(money_eur((float) $row['price'])) ?></td>
                    <td>
                        <select data-status-select data-endpoint="<?= h(base_url('api/admin-transfer.php')) ?>" data-id="<?= (int) $row['id'] ?>">
                            <?php foreach (['pending', 'confirmed', 'cancelled'] as $st): ?>
                                <option value="<?= $st ?>" <?= $row['status'] === $st ? 'selected' : '' ?>><?= $st ?></option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                    <td>
                        <button class="btn btn-small btn-danger" type="button" data-delete data-endpoint="<?= h(base_url('api/admin-transfer.php')) ?>" data-id="<?= (int) $row['id'] ?>">Izbriši</button>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$rows): ?><tr><td colspan="8">Ni prevozov.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
