<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/config.php';

$type = (string) ($_GET['type'] ?? 'rezervacija');
$currentPage = '';
$pageTitle = 'Hvala · ' . SITE_NAME;
require __DIR__ . '/includes/header.php';
?>
<section class="section">
    <div class="container narrow">
        <p class="eyebrow">Potrjeno sprejeto</p>
        <h1>Hvala, povpraševanje je oddano.</h1>
        <?php if ($type === 'prevoz'): ?>
            <p>Prevoz smo zabeležili. Kopijo ste prejeli na svoj e-naslov, mi pa na <?= h(SITE_EMAIL) ?>. Kmalu vam sporočimo voznika in točen čas prevzema.</p>
        <?php else: ?>
            <p>Rezervacijo smo zabeležili. Kopijo ste prejeli na svoj e-naslov, mi pa na <?= h(SITE_EMAIL) ?>. Status lahko spremljamo v adminu, vi pa počakate na našo potrditev.</p>
        <?php endif; ?>
        <div class="hero-actions">
            <a class="btn" href="<?= h(base_url('index.php')) ?>">Na začetek</a>
            <a class="btn btn-ghost" href="<?= h(base_url('prevoz.php')) ?>">Še prevoz</a>
        </div>
    </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
