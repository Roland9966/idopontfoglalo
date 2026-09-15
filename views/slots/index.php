<div class="page-heading">
    <div>
        <h1>Szabad időpontok</h1>
        <p class="muted">A lista csak a jövőbeli, aktív és még nem foglalt időpontokat tartalmazza.</p>
    </div>
</div>

<form class="filters" method="get" action="<?= e(app_url()) ?>/index.php">
    <input type="hidden" name="route" value="appointments">
    <div class="field">
        <label for="service_id">Szolgáltatás</label>
        <select id="service_id" name="service_id">
            <option value="">Mindegyik</option>
            <?php foreach ($services as $service): ?>
                <option value="<?= (int) $service['id'] ?>" <?= (int) $serviceId === (int) $service['id'] ? 'selected' : '' ?>><?= e($service['name']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="field">
        <label for="date">Dátum</label>
        <input id="date" name="date" type="date" value="<?= e($date) ?>">
    </div>
    <button class="button" type="submit">Szűrés</button>
    <a class="button button-secondary" href="<?= e(url('appointments')) ?>">Szűrés törlése</a>
</form>

<?php if (!$slots): ?>
    <div class="empty-state">A megadott feltételekkel jelenleg nincs szabad időpont.</div>
<?php else: ?>
    <div class="table-wrap">
        <table class="responsive">
            <thead><tr><th>Dátum és idő</th><th>Szolgáltatás</th><th>Időpontgazda</th><th>Helyszín</th><th>Művelet</th></tr></thead>
            <tbody>
            <?php foreach ($slots as $slot): ?>
                <tr>
                    <td data-label="Időpont"><strong><?= e(utc_to_local($slot['starts_at'])) ?></strong><br><span class="muted"><?= e(utc_to_local($slot['ends_at'], 'H:i')) ?> óráig</span></td>
                    <td data-label="Szolgáltatás"><?= e($slot['service_name']) ?></td>
                    <td data-label="Időpontgazda"><?= e($slot['provider_name']) ?></td>
                    <td data-label="Helyszín"><?= e($slot['location'] ?: 'Egyeztetés szerint') ?></td>
                    <td data-label="Művelet">
                        <?php if ($currentUser): ?>
                            <a class="button button-small" href="<?= e(url('booking-confirm', ['id' => $slot['id']])) ?>">Kiválasztom</a>
                        <?php else: ?>
                            <a class="button button-small" href="<?= e(url('login')) ?>">Belépés a foglaláshoz</a>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

