<section class="card form-card">
    <h1>Új jelszó beállítása</h1>
    <p class="muted">Adjon meg egy új, biztonságos jelszót. A hivatkozás sikeres mentés után nem használható fel ismét.</p>
    <?php if ($errors): ?>
        <div class="alert alert-error" role="alert">
            <ul class="error-list">
                <?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>
    <form method="post" action="<?= e(url('reset-password', ['token' => $token])) ?>">
        <?= csrf_field() ?>
        <div class="field">
            <label for="password">Új jelszó</label>
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
            <label for="password_confirmation">Új jelszó ismét</label>
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
        <button class="button" type="submit">Új jelszó mentése</button>
    </form>
</section>
