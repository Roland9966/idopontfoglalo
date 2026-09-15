<div class="page-heading">
    <div>
        <h1>Üdvözöljük, <?= e($currentUser['name']) ?>!</h1>
        <p class="muted"><?= e($currentUser['role_name']) ?></p>
    </div>
    <a class="button" href="<?= e(url('appointments')) ?>">Új foglalás</a>
</div>

<div class="grid">
    <section class="card">
        <h2>Közelgő foglalások</h2>
        <div class="stat"><?= (int) ($summary['upcoming'] ?? 0) ?></div>
        <a href="<?= e(url('my-bookings')) ?>">Foglalásaim megtekintése</a>
    </section>
    <section class="card">
        <h2>Összes foglalás</h2>
        <div class="stat"><?= (int) ($summary['total'] ?? 0) ?></div>
        <span class="muted">A korábbi és lemondott foglalásokkal együtt</span>
    </section>
    <section class="card">
        <h2>Lemondási szabály</h2>
        <div class="stat"><?= (int) config('cancellation_hours') ?> óra</div>
        <span class="muted">A felhasználó eddig mondhatja le saját időpontját.</span>
    </section>
</div>

<?php if (in_array($currentUser['role_code'], ['provider', 'admin'], true)): ?>
    <h2>Kezelői műveletek</h2>
    <div class="button-row">
        <a class="button button-secondary" href="<?= e(url('provider-slots')) ?>">Időablakok kezelése</a>
        <a class="button button-secondary" href="<?= e(url('manage-bookings')) ?>">Foglalások kezelése</a>
        <?php if ($currentUser['role_code'] === 'admin'): ?>
            <a class="button button-secondary" href="<?= e(url('admin-export')) ?>">CSV-export</a>
        <?php endif; ?>
    </div>
<?php endif; ?>

