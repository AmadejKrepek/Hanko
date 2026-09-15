<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

$apartments = all_apartments();
$selected = (int) ($_GET['apartment'] ?? 0);
$checkIn = (string) ($_GET['check_in'] ?? '');
$checkOut = (string) ($_GET['check_out'] ?? '');
$guests = (int) ($_GET['guests'] ?? 2);

$currentPage = 'calendar';
$pageTitle = 'Razpoložljivost · ' . SITE_NAME;
$pageDescription = 'Koledar zasedenosti za Apartma Lipa, Javor in Hrast.';
$extraScripts = ['assets/js/calendar.js'];
require __DIR__ . '/includes/header.php';
?>

<section class="page-hero">
    <div class="container">
        <p class="eyebrow">Koledar</p>
        <h1>Razpoložljivost treh apartmajev</h1>
        <p class="lead">Izberite apartma, označite dneve prihoda in odhoda ter uro. Zasedeni termini so sivi. Po pošiljanju gre potrdilo na vaš e-naslov in na <?= h(SITE_EMAIL) ?>.</p>
    </div>
</section>

<section class="section">
    <div class="container calendar-stack">
        <?php foreach ($apartments as $apt): ?>
            <?php if ($selected && $selected !== (int) $apt['id']) continue; ?>
            <article class="panel calendar-block" id="apt-<?= (int) $apt['id'] ?>">
                <div class="calendar-block-head">
                    <div>
                        <p class="eyebrow"><?= h($apt['subtitle']) ?></p>
                        <h2><?= h($apt['name']) ?></h2>
                        <p>do <?= (int) $apt['guests'] ?> oseb · <?= (int) $apt['price_per_night'] ?> € / noč · čiščenje <?= (int) $apt['cleaning_fee'] ?> €</p>
                    </div>
                    <a class="text-link" href="<?= h(base_url('apartma.php?slug=' . $apt['slug'])) ?>">Podrobnosti</a>
                </div>
                <div
                    class="calendar"
                    data-calendar
                    data-apartment="<?= (int) $apt['id'] ?>"
                    data-max-guests="<?= (int) $apt['guests'] ?>"
                    data-price="<?= h((string) $apt['price_per_night']) ?>"
                    data-cleaning="<?= h((string) $apt['cleaning_fee']) ?>"
                    data-check-in="<?= h($checkIn) ?>"
                    data-check-out="<?= h($checkOut) ?>"
                ></div>
                <form class="booking-form" data-booking-form>
                    <input type="hidden" name="apartment_id" value="<?= (int) $apt['id'] ?>">
                    <input type="hidden" name="check_in" data-check-in value="<?= h($checkIn) ?>">
                    <input type="hidden" name="check_out" data-check-out value="<?= h($checkOut) ?>">
                    <div class="form-row">
                        <label>Ura prihoda
                            <select name="arrival_time">
                                <?php for ($h = 14; $h <= 22; $h++): ?>
                                    <option value="<?= sprintf('%02d:00', $h) ?>"><?= sprintf('%02d:00', $h) ?></option>
                                <?php endfor; ?>
                            </select>
                        </label>
                        <label>Gostje
                            <input type="number" name="guests" min="1" max="<?= (int) $apt['guests'] ?>" value="<?= min($guests, (int) $apt['guests']) ?>">
                        </label>
                    </div>
                    <div class="form-row">
                        <label>Ime <input type="text" name="first_name" required></label>
                        <label>Priimek <input type="text" name="last_name" required></label>
                    </div>
                    <div class="form-row">
                        <label>E-pošta <input type="email" name="email" required></label>
                        <label>Telefon <input type="tel" name="phone" required></label>
                    </div>
                    <label>Sporočilo
                        <textarea name="message" rows="3"></textarea>
                    </label>
                    <p class="quote" data-quote>Izberite datume na koledarju.</p>
                    <button class="btn" type="submit">Pošlji rezervacijo</button>
                    <p class="form-status" data-form-status hidden></p>
                </form>
            </article>
        <?php endforeach; ?>
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
