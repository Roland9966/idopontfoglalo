<section class="hero">
    <h1>Időpontfoglalás egyszerűen, egy helyen</h1>
    <p>Válasszon szabad időpontot tanári konzultációhoz, szakdolgozati egyeztetéshez vagy iskolai ügyintézéshez.</p>
    <div class="button-row">
        <a class="button button-secondary" href="<?= e(url('appointments')) ?>">Szabad időpontok megtekintése</a>
        <?php if (!$currentUser): ?>
            <a class="button" href="<?= e(url('register')) ?>">Fiók létrehozása</a>
        <?php endif; ?>
    </div>
</section>

<h2>Elérhető szolgáltatások</h2>
<?php if (!$services): ?>
    <div class="empty-state">Jelenleg nincs aktív szolgáltatás.</div>
<?php else: ?>
    <div class="grid">
        <?php foreach ($services as $service): ?>
            <article class="card">
                <h3><?= e($service['name']) ?></h3>
                <p><?= e($service['description']) ?></p>
                <?php if ($service['location']): ?><p><strong>Helyszín:</strong> <?= e($service['location']) ?></p><?php endif; ?>
                <p class="muted"><?= (int) $service['duration_minutes'] ?> perc · <?= (int) $service['free_slots'] ?> szabad időpont</p>
                <a class="button button-secondary" href="<?= e(url('appointments', ['service_id' => $service['id']])) ?>">Időpontok</a>
            </article>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
