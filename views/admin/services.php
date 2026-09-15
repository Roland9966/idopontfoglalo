<div class="page-heading">
    <div>
        <h1>Szolgáltatások kezelése</h1>
        <p class="muted">Az inaktív szolgáltatások nem jelennek meg a foglalók számára.</p>
    </div>
</div>

<section class="card">
    <h2>Új szolgáltatás</h2>
    <form method="post" action="<?= e(url('admin-service-save')) ?>">
        <?= csrf_field() ?>
        <div class="form-grid">
            <div class="field"><label for="new-name">Megnevezés</label><input id="new-name" name="name" required maxlength="140"></div>
            <div class="field"><label for="new-duration">Időtartam percben</label><input id="new-duration" name="duration_minutes" type="number" min="5" max="480" value="30" required></div>
        </div>
        <div class="field"><label for="new-location">Helyszín vagy kapcsolódási mód</label><input id="new-location" name="location" maxlength="190"></div>
        <div class="field"><label for="new-description">Leírás</label><textarea id="new-description" name="description" maxlength="1000"></textarea></div>
        <button class="button" type="submit">Létrehozás</button>
    </form>
</section>

<h2>Meglévő szolgáltatások</h2>
<div class="stack">
    <?php foreach ($services as $service): ?>
        <section class="card">
            <form method="post" action="<?= e(url('admin-service-save')) ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="service_id" value="<?= (int) $service['id'] ?>">
                <div class="form-grid">
                    <div class="field"><label for="name-<?= (int) $service['id'] ?>">Megnevezés</label><input id="name-<?= (int) $service['id'] ?>" name="name" value="<?= e($service['name']) ?>" required maxlength="140"></div>
                    <div class="field"><label for="duration-<?= (int) $service['id'] ?>">Időtartam percben</label><input id="duration-<?= (int) $service['id'] ?>" name="duration_minutes" type="number" min="5" max="480" value="<?= (int) $service['duration_minutes'] ?>" required></div>
                </div>
                <div class="field"><label for="location-<?= (int) $service['id'] ?>">Helyszín</label><input id="location-<?= (int) $service['id'] ?>" name="location" value="<?= e($service['location']) ?>" maxlength="190"></div>
                <div class="field"><label for="description-<?= (int) $service['id'] ?>">Leírás</label><textarea id="description-<?= (int) $service['id'] ?>" name="description" maxlength="1000"><?= e($service['description']) ?></textarea></div>
                <div class="button-row">
                    <button class="button button-small" type="submit">Módosítás mentése</button>
                    <span class="badge badge-<?= $service['is_active'] ? 'active' : 'inactive' ?>"><?= $service['is_active'] ? 'Aktív' : 'Inaktív' ?></span>
                </div>
            </form>
            <form method="post" action="<?= e(url('admin-service-toggle')) ?>" data-confirm="Biztosan megváltoztatja a szolgáltatás állapotát?">
                <?= csrf_field() ?>
                <input type="hidden" name="service_id" value="<?= (int) $service['id'] ?>">
                <button class="button button-secondary button-small" type="submit"><?= $service['is_active'] ? 'Inaktiválás' : 'Aktiválás' ?></button>
            </form>
        </section>
    <?php endforeach; ?>
</div>

