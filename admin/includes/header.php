<?php
declare(strict_types=1);

$adminPage = $adminPage ?? '';
?>
<!DOCTYPE html>
<html lang="sl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= h($pageTitle ?? 'Admin') ?> · <?= h(SITE_NAME) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@600&family=Outfit:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= h(base_url('assets/css/style.css')) ?>">
    <link rel="stylesheet" href="<?= h(base_url('assets/css/admin.css')) ?>">
</head>
<body class="admin-body">
<aside class="admin-side">
    <a class="logo" href="dashboard.php">
        <span class="logo-mark">H</span>
        <span><strong>Hanko</strong><em>Admin</em></span>
    </a>
    <nav>
        <a class="<?= $adminPage === 'dashboard' ? 'is-active' : '' ?>" href="dashboard.php">Pregled</a>
        <a class="<?= $adminPage === 'rezervacije' ? 'is-active' : '' ?>" href="rezervacije.php">Rezervacije</a>
        <a class="<?= $adminPage === 'prevozi' ? 'is-active' : '' ?>" href="prevozi.php">Prevozi</a>
        <a href="<?= h(base_url('index.php')) ?>" target="_blank" rel="noreferrer">Spletna stran</a>
        <a href="logout.php">Odjava</a>
    </nav>
</aside>
<div class="admin-main">
    <header class="admin-top">
        <h1><?= h($pageTitle ?? 'Admin') ?></h1>
        <span><?= h($_SESSION['admin_user'] ?? 'admin') ?></span>
    </header>
    <div class="admin-content">
