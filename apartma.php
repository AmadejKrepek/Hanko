<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

$slug = (string) ($_GET['slug'] ?? '');
$apartment = $slug !== '' ? apartment_by_slug($slug) : null;
if (!$apartment) {
    http_response_code(404);
    $pageTitle = 'Apartma ni najden';
    $currentPage = 'home';
    require __DIR__ . '/includes/header.php';
    echo '<section class="section"><div class="container"><h1>Apartma ni najden</h1><p><a href="' . h(base_url('index.php')) . '">Nazaj na začetek</a></p></div></section>';
    require __DIR__ . '/includes/footer.php';
    exit;
}

$currentPage = 'home';
$pageTitle = $apartment['name'] . ' · ' . SITE_NAME;
$pageDescription = $apartment['short_desc'];
$extraScripts = ['assets/js/calendar.js'];
require __DIR__ . '/includes/header.php';
?>

<section class="page-hero">
    <div class="container">
        <p class="eyebrow"><?= h($apartment['subtitle']) ?></p>
        <h1><?= h($apartment['name']) ?></h1>
        <p class="lead"><?= h($apartment['short_desc']) ?></p>
        <ul class="meta-pills">
            <li><?= (int) $apartment['guests'] ?> oseb</li>
            <li><?= (int) $apartment['bedrooms'] ?> spalnice</li>
            <li><?= (int) $apartment['bathrooms'] ?> kopalnice</li>
            <li><?= (int) $apartment['size_m2'] ?> m²</li>
            <li><?= (int) $apartment['price_per_night'] ?> € / noč</li>
        </ul>
    </div>
</section>

<section class="section">
    <div class="container gallery">
        <?php foreach ($apartment['images'] as $i => $src): ?>
            <figure class="<?= $i === 0 ? 'is-main' : '' ?>">
                <img src="<?= h($src) ?>" alt="<?= h($apartment['name']) ?>">
            </figure>
        <?php endforeach; ?>
    </div>
</section>

<section class="section section-alt">
    <div class="container split">
        <div>
            <h2>O apartmaju</h2>
            <?php foreach (preg_split("/\n\n/", (string) $apartment['description']) as $para): ?>
                <p><?= nl2br(h($para)) ?></p>
            <?php endforeach; ?>
            <ul class="amenity-list">
                <?php foreach ($apartment['amenities'] as $item): ?>
                    <li><?= h($item) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <div class="panel booking-panel">
            <h2>Izberite dneve</h2>
            <p>Označite prihod in odhod. Zasedeni dnevi so zaklenjeni. Ob rezervaciji pošljemo e-pošto vam in na <?= h(SITE_EMAIL) ?>.</p>
            <div
                class="calendar"
                data-calendar
                data-apartment="<?= (int) $apartment['id'] ?>"
                data-max-guests="<?= (int) $apartment['guests'] ?>"
                data-price="<?= h((string) $apartment['price_per_night']) ?>"
                data-cleaning="<?= h((string) $apartment['cleaning_fee']) ?>"
            ></div>
            <form class="booking-form" data-booking-form>
                <input type="hidden" name="apartment_id" value="<?= (int) $apartment['id'] ?>">
                <input type="hidden" name="check_in" data-check-in>
                <input type="hidden" name="check_out" data-check-out>
                <div class="form-row">
                    <label>Ura prihoda
                        <select name="arrival_time">
                            <?php for ($h = 14; $h <= 22; $h++): ?>
                                <option value="<?= sprintf('%02d:00', $h) ?>"><?= sprintf('%02d:00', $h) ?></option>
                            <?php endfor; ?>
                        </select>
                    </label>
                    <label>Gostje
                        <input type="number" name="guests" min="1" max="<?= (int) $apartment['guests'] ?>" value="2">
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
                    <textarea name="message" rows="3" placeholder="Posebne želje, ura leta, otroška posteljica…"></textarea>
                </label>
                <p class="quote" data-quote>Izberite datume na koledarju.</p>
                <button class="btn" type="submit">Pošlji rezervacijo</button>
                <p class="form-status" data-form-status hidden></p>
            </form>
        </div>
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
