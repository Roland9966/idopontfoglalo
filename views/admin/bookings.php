<div class="page-heading">
    <div>
        <h1>Foglalások kezelése</h1>
        <p class="muted">A jogosultsági körbe tartozó foglalások állapota követhető és módosítható.</p>
    </div>
    <?php if ($currentUser['role_code'] === 'admin'): ?><a class="button button-secondary" href="<?= e(url('admin-export')) ?>">CSV-export</a><?php endif; ?>
</div>

<?php if (!$bookings): ?>
    <div class="empty-state">Nincs megjeleníthető foglalás.</div>
<?php else: ?>
    <div class="table-wrap">
        <table class="responsive booking-admin-table">
            <thead><tr><th>ID</th><th>Időpont</th><th>Szolgáltatás</th><th>Foglaló</th><th>Időpontgazda</th><th>Állapot</th><th>Művelet</th></tr></thead>
            <tbody>
            <?php foreach ($bookings as $booking): ?>
                <tr>
                    <td data-label="ID"><strong class="booking-number">#<?= (int) $booking['id'] ?></strong></td>
                    <td data-label="Időpont">
                        <div class="booking-cell-content">
                            <strong class="booking-primary"><?= e(utc_to_local($booking['starts_at'], 'Y. m. d.')) ?></strong>
                            <span class="booking-secondary"><?= e(utc_to_local($booking['starts_at'], 'H:i')) ?>–<?= e(utc_to_local($booking['ends_at'], 'H:i')) ?></span>
                        </div>
                    </td>
                    <td data-label="Szolgáltatás"><strong class="booking-primary"><?= e($booking['service_name']) ?></strong></td>
                    <td data-label="Foglaló">
                        <div class="booking-cell-content">
                            <strong class="booking-primary"><?= e($booking['customer_name']) ?></strong>
                            <span class="booking-secondary"><strong>Indexszám:</strong> <?= e($booking['customer_student_index'] ?: 'nincs megadva') ?></span>
                            <span class="booking-secondary"><?= e($booking['email']) ?></span>
                        </div>
                    </td>
                    <td data-label="Időpontgazda"><strong class="booking-primary"><?= e($booking['provider_name']) ?></strong></td>
                    <td data-label="Állapot"><span class="badge badge-<?= e($booking['status']) ?>"><?= e(booking_status_label($booking['status'])) ?></span></td>
                    <td data-label="Művelet">
                        <?php if ($booking['status'] === 'confirmed'): ?>
                            <form class="booking-status-form" method="post" action="<?= e(url('booking-status')) ?>">
                                <?= csrf_field() ?>
                                <input type="hidden" name="booking_id" value="<?= (int) $booking['id'] ?>">
                                <div class="compact-field">
                                    <label for="status-<?= (int) $booking['id'] ?>">Új állapot</label>
                                    <select id="status-<?= (int) $booking['id'] ?>" name="status" required>
                                        <option value="completed">Teljesített</option>
                                        <option value="no_show">Nem jelent meg</option>
                                        <option value="cancelled">Lemondott</option>
                                    </select>
                                </div>
                                <button class="button button-small" type="submit">Állapot mentése</button>
                            </form>
                        <?php else: ?><span class="booking-secondary">Nincs további művelet</span><?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
