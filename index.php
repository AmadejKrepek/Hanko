<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

$apartments = all_apartments();
$currentPage = 'home';
$pageTitle = SITE_NAME . ' · ' . SITE_TAGLINE;
$pageDescription = 'Trije apartmaji na Gorenjskem z online koledarjem, rezervacijami in prevozom na letališče v stilu GoOpti.';
require __DIR__ . '/includes/header.php';
?>

<section class="hero">
    <div class="hero-media" style="background-image:url('https://images.unsplash.com/photo-1464822759023-fed622ff2c3b?auto=format&fit=crop&w=2000&q=80')"></div>
    <div class="hero-shade"></div>
    <div class="container hero-content">
        <p class="eyebrow">Gorenjska · Slovenija</p>
        <h1>Trije apartmaji.<br>En miren kraj za prihod.</h1>
        <p class="lead">Izberite Lipo, Javor ali Hrast, preverite razpoložljivost na koledarju in si rezervirajte dneve. Ob prihodu ali odhodu uredimo tudi prevoz na letališče in iz letališča.</p>
        <div class="hero-actions">
            <a class="btn" href="#apartmaji">Poglej apartmaje</a>
            <a class="btn btn-ghost" href="<?= h(base_url('prevoz.php')) ?>">Prevoz na letališče</a>
        </div>
    </div>
</section>

<section class="booking-bar" id="iskanje">
    <div class="container">
        <form class="search-tabs" action="<?= h(base_url('razpolozljivost.php')) ?>" method="get">
            <div class="tab-switch" role="tablist">
                <a class="is-active" href="#iskanje">Apartma</a>
                <a href="<?= h(base_url('prevoz.php')) ?>">Prevoz</a>
            </div>
            <div class="search-grid">
                <label>
                    <span>Apartma</span>
                    <select name="apartment">
                        <option value="">Vsi apartmaji</option>
                        <?php foreach ($apartments as $apt): ?>
                            <option value="<?= (int) $apt['id'] ?>"><?= h($apt['name']) ?> · do <?= (int) $apt['guests'] ?> oseb</option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>
                    <span>Prihod</span>
                    <input type="date" name="check_in" min="<?= h(date('Y-m-d')) ?>" required>
                </label>
                <label>
                    <span>Odhod</span>
                    <input type="date" name="check_out" min="<?= h(date('Y-m-d', strtotime('+1 day'))) ?>" required>
                </label>
                <label>
                    <span>Gostje</span>
                    <input type="number" name="guests" min="1" max="6" value="2">
                </label>
                <button class="btn" type="submit">Preveri koledar</button>
            </div>
        </form>
    </div>
</section>

<section class="section" id="apartmaji">
    <div class="container">
        <div class="section-head">
            <p class="eyebrow">Nastanitev</p>
            <h2>Trije apartmaji, trije ritmi bivanja</h2>
            <p>Vsak apartma ima svoj karakter, lastni vhod in parkirišče. Cene so na noč, zaključek čiščenja je vštet ob rezervaciji.</p>
        </div>
        <div class="apartment-grid">
            <?php foreach ($apartments as $apt): ?>
                <article class="apartment-card">
                    <a class="card-image" href="<?= h(base_url('apartma.php?slug=' . $apt['slug'])) ?>">
                        <img src="<?= h($apt['images'][0] ?? '') ?>" alt="<?= h($apt['name']) ?>">
                        <span class="price-chip">od <?= (int) $apt['price_per_night'] ?> € / noč</span>
                    </a>
                    <div class="card-body">
                        <p class="eyebrow"><?= h($apt['subtitle']) ?></p>
                        <h3><a href="<?= h(base_url('apartma.php?slug=' . $apt['slug'])) ?>"><?= h($apt['name']) ?></a></h3>
                        <p><?= h($apt['short_desc']) ?></p>
                        <ul class="meta-pills">
                            <li><?= (int) $apt['guests'] ?> oseb</li>
                            <li><?= (int) $apt['bedrooms'] ?> spalnice</li>
                            <li><?= (int) $apt['size_m2'] ?> m²</li>
                        </ul>
                        <a class="text-link" href="<?= h(base_url('apartma.php?slug=' . $apt['slug'])) ?>">Koledar in rezervacija</a>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="section section-alt" id="prevoz-teaser">
    <div class="container split">
        <div>
            <p class="eyebrow">GoOpti slog</p>
            <h2>Prevoz na letališče in iz letališča</h2>
            <p>Skupni ali zasebni prevoz do Ljubljane, Celovca, Trsta, Zagreba, Gradca in Benetk. Izberete smer, datum, uro in število potnikov — cena se izračuna takoj, potrdilo pa pride na e-pošto vam in nam.</p>
            <ul class="checklist">
                <li>Iz letališča do Hanko Apartmajev</li>
                <li>Iz apartmajev na letališče</li>
                <li>Hin in nazaj po ugodnejši ceni</li>
            </ul>
            <a class="btn" href="<?= h(base_url('prevoz.php')) ?>">Naroči prevoz</a>
        </div>
        <div class="panel transfer-preview">
            <p class="panel-kicker">Priljubljene proge</p>
            <?php foreach (array_slice(transfer_routes(), 0, 4) as $code => $route): ?>
                <div class="route-row">
                    <div>
                        <strong><?= h($route['name']) ?></strong>
                        <span><?= (int) $route['minutes'] ?> min · od <?= (int) $route['shared'] ?> €</span>
                    </div>
                    <span class="badge"><?= h($code) ?></span>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="section" id="kontakt">
    <div class="container split">
        <div>
            <p class="eyebrow">Kontakt</p>
            <h2>Pišite nam ali rezervirajte online</h2>
            <p>Rezervacije in prevozi se shranijo v sistem. Na <?= h(SITE_EMAIL) ?> in na vaš e-naslov gre samodejno sporočilo. V adminu lahko vnose potrdite, dodate ali zbrišete.</p>
            <p><a href="mailto:<?= h(SITE_EMAIL) ?>"><?= h(SITE_EMAIL) ?></a><br><?= h(SITE_PHONE) ?></p>
        </div>
        <form class="panel contact-form" action="mailto:<?= h(SITE_EMAIL) ?>" method="get">
            <label>Ime<input type="text" name="name" placeholder="Vaše ime"></label>
            <label>E-pošta<input type="email" name="email" placeholder="ime@email.com"></label>
            <label>Sporočilo<textarea name="body" rows="4" placeholder="Vprašanje o terminih ali prevozu"></textarea></label>
            <a class="btn" href="mailto:<?= h(SITE_EMAIL) ?>">Pošlji e-pošto</a>
        </form>
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
