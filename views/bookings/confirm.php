<section class="card form-card">
    <h1>Foglalás megerősítése</h1>
    <p>Kérjük, mentés előtt ellenőrizze az időpont adatait.</p>
    <dl class="definition-list">
        <dt>Foglaló</dt><dd><?= e($currentUser['name']) ?><br><span class="muted">Indexszám: <?= e($currentUser['student_index'] ?: 'nincs megadva') ?></span></dd>
        <dt>Szolgáltatás</dt><dd><?= e($slot['service_name']) ?></dd>
        <dt>Dátum</dt><dd><?= e(utc_to_local($slot['starts_at'])) ?>–<?= e(utc_to_local($slot['ends_at'], 'H:i')) ?></dd>
        <dt>Időpontgazda</dt><dd><?= e($slot['provider_name']) ?></dd>
        <dt>Helyszín</dt><dd><?= e($slot['location'] ?: 'Egyeztetés szerint') ?></dd>
    </dl>
    <form method="post" action="<?= e(url('booking-create')) ?>">
        <?= csrf_field() ?>
        <input type="hidden" name="slot_id" value="<?= (int) $slot['id'] ?>">
        <div class="field">
            <label for="note">Rövid megjegyzés <span class="muted">(nem kötelező)</span></label>
            <textarea id="note" name="note" maxlength="500" aria-describedby="note-help"></textarea>
            <span id="note-help" class="help">Ne adjon meg egészségügyi vagy más érzékeny személyes adatot.</span>
        </div>
        <div class="button-row">
            <button class="button" type="submit">Foglalás véglegesítése</button>
            <a class="button button-secondary" href="<?= e(url('appointments')) ?>">Vissza</a>
        </div>
    </form>
</section>
