<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/functions.php';

require_admin();

$apartments = all_apartments();
$upcoming = db()->query(
    'SELECT r.*, a.name AS apartment_name
     FROM reservations r
     JOIN apartments a ON a.id = r.apartment_id
     WHERE r.status != "cancelled" AND r.check_out >= date("now")
     ORDER BY r.check_in
     LIMIT 8'
)->fetchAll();
$transfers = db()->query(
    'SELECT * FROM transfers
     WHERE status != "cancelled" AND date_outbound >= date("now")
     ORDER BY date_outbound, time_outbound
     LIMIT 8'
)->fetchAll();

$counts = [
    'pending_res' => (int) db()->query('SELECT COUNT(*) FROM reservations WHERE status = "pending"')->fetchColumn(),
    'pending_tr' => (int) db()->query('SELECT COUNT(*) FROM transfers WHERE status = "pending"')->fetchColumn(),
    'confirmed_res' => (int) db()->query('SELECT COUNT(*) FROM reservations WHERE status = "confirmed" AND check_out >= date("now")')->fetchColumn(),
];

$pageTitle = 'Pregled';
$adminPage = 'dashboard';
require __DIR__ . '/includes/header.php';
?>

<section class="stat-grid">
    <article class="panel"><p>Čakajoče rezervacije</p><strong><?= $counts['pending_res'] ?></strong></article>
    <article class="panel"><p>Potrjene prihodnje</p><strong><?= $counts['confirmed_res'] ?></strong></article>
    <article class="panel"><p>Čakajoči prevozi</p><strong><?= $counts['pending_tr'] ?></strong></article>
</section>

<section class="admin-split">
    <article class="panel">
        <div class="panel-head">
            <h2>Prihajajoče rezervacije</h2>
            <a href="rezervacije.php">Vse</a>
        </div>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Apartma</th><th>Gost</th><th>Termini</th><th>Status</th></tr></thead>
                <tbody>
                <?php foreach ($upcoming as $row): ?>
                    <tr>
                        <td><?= h($row['apartment_name']) ?></td>
                        <td><?= h($row['first_name'] . ' ' . $row['last_name']) ?><br><small><?= h($row['email']) ?></small></td>
                        <td><?= h($row['check_in']) ?> → <?= h($row['check_out']) ?></td>
                        <td><span class="status status-<?= h($row['status']) ?>"><?= h($row['status']) ?></span></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$upcoming): ?><tr><td colspan="4">Ni prihajajočih rezervacij.</td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>
    </article>
    <article class="panel">
        <div class="panel-head">
            <h2>Prihajajoči prevozi</h2>
            <a href="prevozi.php">Vse</a>
        </div>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Smer</th><th>Gost</th><th>Kdaj</th><th>Status</th></tr></thead>
                <tbody>
                <?php foreach ($transfers as $row): ?>
                    <tr>
                        <td><?= h(transfer_direction_label($row['direction'])) ?></td>
                        <td><?= h($row['first_name'] . ' ' . $row['last_name']) ?></td>
                        <td><?= h($row['date_outbound']) ?> <?= h($row['time_outbound']) ?></td>
                        <td><span class="status status-<?= h($row['status']) ?>"><?= h($row['status']) ?></span></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$transfers): ?><tr><td colspan="4">Ni prihajajočih prevozov.</td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>
    </article>
</section>

<section class="panel">
    <h2>Apartmaji</h2>
    <div class="mini-apts">
        <?php foreach ($apartments as $apt): ?>
            <div>
                <strong><?= h($apt['name']) ?></strong>
                <span>do <?= (int) $apt['guests'] ?> oseb · <?= (int) $apt['price_per_night'] ?> € / noč</span>
            </div>
        <?php endforeach; ?>
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
