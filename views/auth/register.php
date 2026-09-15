<section class="card form-card">
    <h1>Regisztráció</h1>
    <p class="muted">A rendszer csak a foglaláshoz és az intézményi azonosításhoz szükséges alapadatokat tárolja.</p>
    <?php if ($errors): ?>
        <div class="alert alert-error" role="alert">
            <ul class="error-list">
                <?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>
    <form method="post" action="<?= e(url('register')) ?>">
        <?= csrf_field() ?>
        <div class="field">
            <label for="name">Teljes név</label>
            <input id="name" name="name" type="text" autocomplete="name" required maxlength="120" value="<?= e($values['name']) ?>">
        </div>
        <div class="field">
            <label for="student_index">Indexszám</label>
            <input id="student_index" name="student_index" type="text" inputmode="numeric" pattern="[0-9]{8}" autocomplete="off" required minlength="8" maxlength="8" placeholder="26221111" title="Pontosan 8 számjegy." value="<?= e($values['student_index']) ?>" aria-describedby="student-index-help">
            <span id="student-index-help" class="help">Az intézmény által kiadott, pontosan 8 számjegyből álló hallgatói azonosító. Példa: 26221111.</span>
        </div>
        <div class="field">
            <label for="email">E-mail-cím</label>
            <input id="email" name="email" type="email" autocomplete="email" required maxlength="190" value="<?= e($values['email']) ?>">
        </div>
        <div class="field">
            <label for="password">Jelszó</label>
            <div class="password-control">
                <input id="password" name="password" type="password" autocomplete="new-password" required minlength="10">
                <button class="password-toggle" type="button" data-password-toggle="password" aria-label="Jelszó megjelenítése" aria-pressed="false" title="Jelszó megjelenítése">
                    <svg viewBox="0 0 24 24" width="22" height="22" aria-hidden="true" focusable="false">
                        <path d="M2.5 12s3.5-6 9.5-6 9.5 6 9.5 6-3.5 6-9.5 6-9.5-6-9.5-6Z"></path>
                        <circle cx="12" cy="12" r="2.7"></circle>
                        <path class="password-toggle-slash" d="m4 4 16 16"></path>
                    </svg>
                </button>
            </div>
            <span class="help">Legalább 10 karakter, kisbetű, nagybetű és szám.</span>
        </div>
        <div class="field">
            <label for="password_confirmation">Jelszó ismét</label>
            <div class="password-control">
                <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" required minlength="10">
                <button class="password-toggle" type="button" data-password-toggle="password_confirmation" aria-label="Jelszó megjelenítése" aria-pressed="false" title="Jelszó megjelenítése">
                    <svg viewBox="0 0 24 24" width="22" height="22" aria-hidden="true" focusable="false">
                        <path d="M2.5 12s3.5-6 9.5-6 9.5 6 9.5 6-3.5 6-9.5 6-9.5-6-9.5-6Z"></path>
                        <circle cx="12" cy="12" r="2.7"></circle>
                        <path class="password-toggle-slash" d="m4 4 16 16"></path>
                    </svg>
                </button>
            </div>
        </div>
        <button class="button" type="submit">Fiók létrehozása</button>
    </form>
    <p>Már regisztrált, de nem kapta meg a levelet? <a href="<?= e(url('resend-verification')) ?>">Aktiváló levél újraküldése</a></p>
</section>
