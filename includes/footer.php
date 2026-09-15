<?php
declare(strict_types=1);
?>
</main>
<footer class="site-footer">
    <div class="container footer-grid">
        <div>
            <p class="logo-plain">Hanko Apartmaji</p>
            <p><?= h(SITE_TAGLINE) ?></p>
            <p><?= h(SITE_LOCATION) ?></p>
        </div>
        <div>
            <h3>Rezervacije</h3>
            <p><a href="mailto:<?= h(SITE_EMAIL) ?>"><?= h(SITE_EMAIL) ?></a></p>
            <p><a href="tel:<?= h(str_replace(' ', '', SITE_PHONE)) ?>"><?= h(SITE_PHONE) ?></a></p>
        </div>
        <div>
            <h3>Povezave</h3>
            <p><a href="<?= h(base_url('razpolozljivost.php')) ?>">Koledar</a></p>
            <p><a href="<?= h(base_url('prevoz.php')) ?>">Prevoz na letališče</a></p>
            <p><a href="<?= h(base_url('admin/index.php')) ?>">Admin</a></p>
        </div>
    </div>
    <p class="copyright">© <?= date('Y') ?> Hanko Apartmaji. Vse pravice pridržane.</p>
</footer>
<script>
    window.HANKO = {
        baseUrl: <?= json_encode(BASE_URL, JSON_UNESCAPED_SLASHES) ?>,
        csrf: <?= json_encode(csrf_token()) ?>,
        residence: <?= json_encode(residence_label()) ?>
    };
</script>
<script src="<?= h(base_url('assets/js/main.js')) ?>"></script>
<?php if (!empty($extraScripts)): ?>
    <?php foreach ($extraScripts as $src): ?>
        <script src="<?= h(base_url($src)) ?>"></script>
    <?php endforeach; ?>
<?php endif; ?>
</body>
</html>
