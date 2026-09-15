<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/auth.php';

$error = '';
if (is_admin()) {
    header('Location: dashboard.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user = trim((string) ($_POST['username'] ?? ''));
    $pass = (string) ($_POST['password'] ?? '');
    if (!csrf_verify((string) ($_POST['csrf'] ?? ''))) {
        $error = 'Seja je potekla. Poskusite znova.';
    } elseif (attempt_login($user, $pass)) {
        header('Location: dashboard.php');
        exit;
    } else {
        $error = 'Napačno uporabniško ime ali geslo.';
    }
}
?>
<!DOCTYPE html>
<html lang="sl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>Prijava · <?= h(SITE_NAME) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@600&family=Outfit:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= h(base_url('assets/css/style.css')) ?>">
    <link rel="stylesheet" href="<?= h(base_url('assets/css/admin.css')) ?>">
</head>
<body class="admin-login">
    <form class="panel login-card" method="post">
        <p class="eyebrow">Hanko</p>
        <h1>Admin</h1>
        <p>Pregled rezervacij in prevozov.</p>
        <?php if ($error): ?><p class="form-status is-error"><?= h($error) ?></p><?php endif; ?>
        <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
        <label>Uporabniško ime <input type="text" name="username" autocomplete="username" required></label>
        <label>Geslo <input type="password" name="password" autocomplete="current-password" required></label>
        <button class="btn" type="submit">Vstopi</button>
        <?php if (SHOW_ADMIN_HINT): ?>
            <p class="hint">Privzeto: <?= h(DEFAULT_ADMIN_USER) ?> / <?= h(DEFAULT_ADMIN_PASS) ?></p>
        <?php endif; ?>
    </form>
</body>
</html>
