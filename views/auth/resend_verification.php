<section class="card form-card">
    <h1>Aktiváló levél újraküldése</h1>
    <p class="muted">Adja meg a regisztrációkor használt e-mail-címet. Ha a fiók aktiválásra vár, új hivatkozást küldünk.</p>
    <?php if ($errors): ?>
        <div class="alert alert-error" role="alert">
            <ul class="error-list">
                <?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>
    <form method="post" action="<?= e(url('resend-verification')) ?>">
        <?= csrf_field() ?>
        <div class="field">
            <label for="email">E-mail-cím</label>
            <input id="email" name="email" type="email" autocomplete="email" required maxlength="190" value="<?= e($email) ?>">
        </div>
        <button class="button" type="submit">Új aktiváló levél kérése</button>
    </form>
    <p><a href="<?= e(url('login')) ?>">Vissza a bejelentkezéshez</a></p>
</section>
