<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';

$currentPage = $currentPage ?? '';
$pageTitle = $pageTitle ?? SITE_NAME;
$pageDescription = $pageDescription ?? SITE_TAGLINE;
?>
<!DOCTYPE html>
<html lang="sl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#234139">
    <title><?= h($pageTitle) ?></title>
    <meta name="description" content="<?= h($pageDescription) ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,500;0,600;1,500&family=Outfit:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= h(base_url('assets/css/style.css')) ?>">
</head>
<body>
<header class="site-header" id="top">
    <div class="container header-inner">
        <a class="logo" href="<?= h(base_url('index.php')) ?>">
            <span class="logo-mark">H</span>
            <span>
                <strong>Hanko</strong>
                <em>Apartmaji</em>
            </span>
        </a>
        <button class="nav-toggle" type="button" aria-label="Odpri meni" aria-expanded="false" aria-controls="site-nav" data-nav-toggle>
            <span></span><span></span><span></span>
        </button>
        <div class="nav-overlay" data-nav-overlay hidden></div>
        <nav class="site-nav" id="site-nav" data-nav>
            <a href="<?= h(base_url('index.php#apartmaji')) ?>" class="<?= $currentPage === 'home' ? 'is-active' : '' ?>">Apartmaji</a>
            <a href="<?= h(base_url('razpolozljivost.php')) ?>" class="<?= $currentPage === 'calendar' ? 'is-active' : '' ?>">Razpoložljivost</a>
            <a href="<?= h(base_url('prevoz.php')) ?>" class="<?= $currentPage === 'transfer' ? 'is-active' : '' ?>">Prevoz</a>
            <a href="<?= h(base_url('index.php#kontakt')) ?>">Kontakt</a>
            <a class="btn btn-small" href="<?= h(base_url('prevoz.php')) ?>">Na letališče</a>
        </nav>
    </div>
</header>
<main>
