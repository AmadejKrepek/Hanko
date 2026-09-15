<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

$routes = transfer_routes();
$currentPage = 'transfer';
$pageTitle = 'Prevoz na letališče · ' . SITE_NAME;
$pageDescription = 'Skupni in zasebni prevoz na letališče in iz letališča, v stilu GoOpti.';
$extraScripts = ['assets/js/transfer.js'];
require __DIR__ . '/includes/header.php';
?>

<section class="page-hero transfer-hero">
    <div class="container">
        <p class="eyebrow">Hanko Transfer</p>
        <h1>Na letališče in iz letališča</h1>
        <p class="lead">Iskanje v treh korakih: proga, vozilo, podatki. Cena se izračuna takoj. Potrdilo gre vam in na <?= h(SITE_EMAIL) ?>.</p>
    </div>
</section>

<section class="section">
    <div class="container">
        <div class="goopti" data-transfer-wizard>
            <ol class="steps">
                <li class="is-active" data-step-label="1">Proga in ura</li>
                <li data-step-label="2">Ponudba</li>
                <li data-step-label="3">Potniki</li>
            </ol>

            <form class="panel goopti-form" data-step="1">
                <div class="direction-toggle" role="radiogroup" aria-label="Smer prevoza">
                    <label><input type="radio" name="direction" value="from_airport" checked> Iz letališča</label>
                    <label><input type="radio" name="direction" value="to_airport"> Na letališče</label>
                    <label><input type="radio" name="direction" value="round_trip"> Hin in nazaj</label>
                </div>
                <div class="search-grid transfer-search">
                    <label data-airport-label>
                        <span>Letališče</span>
                        <select name="airport" required>
                            <?php foreach ($routes as $code => $route): ?>
                                <option value="<?= h($code) ?>"><?= h($route['name']) ?> · <?= (int) $route['minutes'] ?> min</option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label>
                        <span>Naslov apartmajev</span>
                        <input type="text" value="<?= h(residence_label()) ?>" readonly>
                    </label>
                    <label>
                        <span>Datum odhoda</span>
                        <input type="date" name="date_outbound" min="<?= h(date('Y-m-d')) ?>" required>
                    </label>
                    <label>
                        <span>Ura</span>
                        <input type="time" name="time_outbound" value="10:00" required>
                    </label>
                    <label class="return-fields" hidden>
                        <span>Datum vrnitve</span>
                        <input type="date" name="date_return" min="<?= h(date('Y-m-d')) ?>">
                    </label>
                    <label class="return-fields" hidden>
                        <span>Ura vrnitve</span>
                        <input type="time" name="time_return" value="14:00">
                    </label>
                    <label>
                        <span>Odrasli</span>
                        <input type="number" name="passengers" min="1" max="8" value="2">
                    </label>
                    <label>
                        <span>Otroci</span>
                        <input type="number" name="children" min="0" max="6" value="0">
                    </label>
                    <label>
                        <span>Kosi prtljage</span>
                        <input type="number" name="luggage" min="0" max="12" value="2">
                    </label>
                    <label>
                        <span>Št. leta (za prevzem)</span>
                        <input type="text" name="flight_number" placeholder="npr. JP384">
                    </label>
                </div>
                <button class="btn" type="submit">Pokaži cene</button>
            </form>

            <div class="offers" data-step="2" hidden>
                <p class="panel-kicker" data-offer-summary></p>
                <div class="offer-grid">
                    <button type="button" class="offer-card" data-vehicle="shared">
                        <span class="badge">Skupni prevoz</span>
                        <h3>Hanko Shuttle</h3>
                        <p>Deljen prevoz z drugimi gosti. Udoben kombi, fiksna cena na progo.</p>
                        <p class="offer-price" data-shared-price></p>
                        <span class="text-link">Izberi</span>
                    </button>
                    <button type="button" class="offer-card is-featured" data-vehicle="private">
                        <span class="badge">Zasebno</span>
                        <h3>Hanko Private</h3>
                        <p>Vozilo samo za vas. Prilagodimo se letu, čakanje ob zamudi do 45 min.</p>
                        <p class="offer-price" data-private-price></p>
                        <span class="text-link">Izberi</span>
                    </button>
                </div>
                <button class="btn btn-ghost" type="button" data-back>Spremeni iskanje</button>
            </div>

            <form class="panel goopti-form" data-step="3" hidden>
                <p class="quote" data-final-quote></p>
                <div class="form-row">
                    <label>Ime <input type="text" name="first_name" required></label>
                    <label>Priimek <input type="text" name="last_name" required></label>
                </div>
                <div class="form-row">
                    <label>E-pošta <input type="email" name="email" required></label>
                    <label>Telefon <input type="tel" name="phone" required></label>
                </div>
                <label>Opomba za voznika
                    <textarea name="message" rows="3" placeholder="Otroški sedež, večja prtljaga, zamuda leta…"></textarea>
                </label>
                <div class="form-actions">
                    <button class="btn btn-ghost" type="button" data-back>Nazaj na ponudbo</button>
                    <button class="btn" type="submit">Naroči prevoz</button>
                </div>
                <p class="form-status" data-form-status hidden></p>
            </form>
        </div>
    </div>
</section>

<section class="section section-alt">
    <div class="container">
        <div class="section-head">
            <p class="eyebrow">Proge</p>
            <h2>Cene v eno smer</h2>
        </div>
        <div class="price-table">
            <div class="price-row is-head"><span>Letališče</span><span>Čas</span><span>Skupni</span><span>Zasebni</span></div>
            <?php foreach ($routes as $code => $route): ?>
                <div class="price-row">
                    <span><?= h($route['name']) ?></span>
                    <span><?= (int) $route['minutes'] ?> min</span>
                    <span>od <?= (int) $route['shared'] ?> €</span>
                    <span>od <?= (int) $route['private'] ?> €</span>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
