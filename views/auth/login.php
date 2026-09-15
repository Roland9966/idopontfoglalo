<section class="card form-card">
    <h1>Bejelentkezés</h1>
    <p class="muted">A foglaláshoz és a saját időpontok kezeléséhez jelentkezzen be.</p>
    <form method="post" action="<?= e(url('login')) ?>">
        <?= csrf_field() ?>
        <div class="field">
            <label for="email">E-mail-cím</label>
            <input id="email" name="email" type="email" autocomplete="email" required maxlength="190" value="<?= e($email) ?>">
        </div>
        <div class="field">
            <label for="password">Jelszó</label>
            <div class="password-control">
                <input id="password" name="password" type="password" autocomplete="current-password" required>
                <button class="password-toggle" type="button" data-password-toggle="password" aria-label="Jelszó megjelenítése" aria-pressed="false" title="Jelszó megjelenítése">
                    <svg viewBox="0 0 24 24" width="22" height="22" aria-hidden="true" focusable="false">
                        <path d="M2.5 12s3.5-6 9.5-6 9.5 6 9.5 6-3.5 6-9.5 6-9.5-6-9.5-6Z"></path>
                        <circle cx="12" cy="12" r="2.7"></circle>
                        <path class="password-toggle-slash" d="m4 4 16 16"></path>
                    </svg>
                </button>
            </div>
        </div>
        <button class="button" type="submit">Bejelentkezés</button>
    </form>
    <p>Elfelejtette a jelszavát? <a href="<?= e(url('forgot-password')) ?>">Új jelszó kérése</a></p>
    <p>Még nincs fiókja? <a href="<?= e(url('register')) ?>">Regisztráció</a></p>
    <p>Nem érkezett meg az aktiváló levél? <a href="<?= e(url('resend-verification')) ?>">Új levél kérése</a></p>
</section>
