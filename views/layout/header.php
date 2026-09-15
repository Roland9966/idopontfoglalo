<!doctype html>
<html lang="hu">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Oktatási intézményben használható online időpontfoglaló rendszer.">
    <title><?= e($pageTitle) ?> – <?= e(config('name')) ?></title>
    <link rel="stylesheet" href="<?= e(app_url()) ?>/assets/css/app.css">
    <script src="<?= e(app_url()) ?>/assets/js/app.js" defer></script>
</head>
<body>
<a class="skip-link" href="#main-content">Ugrás a tartalomra</a>
<header class="site-header">
    <div class="container header-row">
        <a class="brand" href="<?= e(url('home')) ?>">
            <img class="brand-logo" src="<?= e(app_url()) ?>/assets/images/zold_logo.jpg" alt="" width="46" height="46">
            <span>Időpontfoglaló</span>
        </a>
        <button class="nav-toggle" type="button" aria-controls="main-nav" aria-expanded="false">Menü</button>
        <nav id="main-nav" class="main-nav" aria-label="Fő navigáció">
            <a href="<?= e(url('appointments')) ?>">Szabad időpontok</a>
            <?php if ($currentUser): ?>
                <a href="<?= e(url('dashboard')) ?>">Áttekintés</a>
                <a href="<?= e(url('my-bookings')) ?>">Foglalásaim</a>
                <?php if (in_array($currentUser['role_code'], ['provider', 'admin'], true)): ?>
                    <a href="<?= e(url('provider-slots')) ?>">Időablakok</a>
                    <a href="<?= e(url('manage-bookings')) ?>">Foglalások kezelése</a>
                <?php endif; ?>
                <?php if ($currentUser['role_code'] === 'admin'): ?>
                    <a href="<?= e(url('admin-services')) ?>">Szolgáltatások</a>
                    <a href="<?= e(url('admin-users')) ?>">Felhasználók</a>
                    <a href="<?= e(url('admin-audit')) ?>">Napló</a>
                <?php endif; ?>
                <form class="inline-form" method="post" action="<?= e(url('logout')) ?>">
                    <?= csrf_field() ?>
                    <button class="link-button" type="submit">Kijelentkezés</button>
                </form>
            <?php else: ?>
                <a href="<?= e(url('login')) ?>">Bejelentkezés</a>
                <a class="nav-cta" href="<?= e(url('register')) ?>">Regisztráció</a>
            <?php endif; ?>
        </nav>
    </div>
</header>

<main id="main-content" class="container main-content" tabindex="-1">
    <?php foreach (pull_flashes() as $flash): ?>
        <div class="alert alert-<?= e($flash['type']) ?>" role="status"><?= e($flash['message']) ?></div>
    <?php endforeach; ?>
