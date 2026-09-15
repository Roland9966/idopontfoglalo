<section class="card form-card">
    <h1>Elfelejtett jelszó</h1>
    <p class="muted">Adja meg a fiókjához tartozó e-mail-címet. Ha a fiók használható, elküldjük az új jelszó beállításához szükséges hivatkozást.</p>
    <?php if ($errors): ?>
        <div class="alert alert-error" role="alert">
            <ul class="error-list">
                <?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>
    <form method="post" action="<?= e(url('forgot-password')) ?>">
        <?= csrf_field() ?>
        <div class="field">
            <label for="email">E-mail-cím</label>
            <input id="email" name="email" type="email" autocomplete="email" required maxlength="190" value="<?= e($email) ?>">
        </div>
        <button class="button" type="submit">Visszaállító levél kérése</button>
    </form>
    <p><a href="<?= e(url('login')) ?>">Vissza a bejelentkezéshez</a></p>
</section>
