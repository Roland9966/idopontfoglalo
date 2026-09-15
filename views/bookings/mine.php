<div class="page-heading">
    <div>
        <h1>Saját foglalásaim</h1>
        <p class="muted">A jövőbeli és korábbi foglalások kizárólag a bejelentkezett felhasználó számára láthatók.</p>
    </div>
    <a class="button" href="<?= e(url('appointments')) ?>">Új foglalás</a>
</div>

<?php if (!$bookings): ?>
    <div class="empty-state">Még nincs rögzített foglalása.</div>
<?php else: ?>
    <div class="table-wrap">
        <table class="responsive">
            <thead><tr><th>Azonosító</th><th>Időpont</th><th>Szolgáltatás</th><th>Időpontgazda</th><th>Állapot</th><th>Művelet</th></tr></thead>
            <tbody>
            <?php foreach ($bookings as $booking): ?>
                <tr>
                    <td data-label="Azonosító">#<?= (int) $booking['id'] ?></td>
                    <td data-label="Időpont"><strong><?= e(utc_to_local($booking['starts_at'])) ?></strong><br><span class="muted"><?= e(utc_to_local($booking['ends_at'], 'H:i')) ?> óráig</span></td>
                    <td data-label="Szolgáltatás"><?= e($booking['service_name']) ?><br><span class="muted"><?= e($booking['location'] ?: 'Egyeztetés szerint') ?></span></td>
                    <td data-label="Időpontgazda"><?= e($booking['provider_name']) ?></td>
                    <td data-label="Állapot"><span class="badge badge-<?= e($booking['status']) ?>"><?= e(booking_status_label($booking['status'])) ?></span></td>
                    <td data-label="Művelet">
                        <?php if ($booking['status'] === 'confirmed' && cancellation_is_allowed($booking['starts_at'])): ?>
                            <form method="post" action="<?= e(url('booking-cancel')) ?>" data-confirm="Biztosan lemondja ezt a foglalást?">
                                <?= csrf_field() ?>
                                <input type="hidden" name="booking_id" value="<?= (int) $booking['id'] ?>">
                                <button class="button button-danger button-small" type="submit">Lemondás</button>
                            </form>
                        <?php elseif ($booking['status'] === 'confirmed'): ?>
                            <span class="muted">A lemondási határidő lejárt.</span>
                        <?php else: ?>–<?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

