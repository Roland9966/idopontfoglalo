<section class="card form-card">
    <h1><?= e($title) ?></h1>
    <?php if (!$services): ?>
        <div class="alert alert-warning">Nincs kiválasztható szolgáltatás.</div>
    <?php else: ?>
        <form method="post" action="<?= e(url('provider-slot-save')) ?>">
            <?= csrf_field() ?>
            <?php if ($slot): ?><input type="hidden" name="slot_id" value="<?= (int) $slot['id'] ?>"><?php endif; ?>
            <div class="field">
                <label for="service_id">Szolgáltatás</label>
                <select id="service_id" name="service_id" required>
                    <?php foreach ($services as $service): ?>
                        <option value="<?= (int) $service['id'] ?>" <?= $slot && (int) $slot['service_id'] === (int) $service['id'] ? 'selected' : '' ?>><?= e($service['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="field">
                <label for="provider_id">Időpontgazda</label>
                <select id="provider_id" name="provider_id" required>
                    <?php foreach ($providers as $provider): ?>
                        <option value="<?= (int) $provider['id'] ?>" <?= $slot && (int) $slot['provider_id'] === (int) $provider['id'] ? 'selected' : '' ?>><?= e($provider['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-grid">
                <div class="field">
                    <label for="starts_at">Kezdés</label>
                    <input id="starts_at" name="starts_at" type="datetime-local" required value="<?= e($slot ? utc_to_local_input($slot['starts_at']) : '') ?>">
                </div>
                <div class="field">
                    <label for="ends_at">Befejezés</label>
                    <input id="ends_at" name="ends_at" type="datetime-local" required value="<?= e($slot ? utc_to_local_input($slot['ends_at']) : '') ?>">
                </div>
            </div>
            <div class="button-row">
                <button class="button" type="submit">Mentés</button>
                <a class="button button-secondary" href="<?= e(url('provider-slots')) ?>">Mégse</a>
            </div>
        </form>
    <?php endif; ?>
</section>

