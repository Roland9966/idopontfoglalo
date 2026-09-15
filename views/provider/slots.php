<div class="page-heading">
    <div>
        <h1>Időablakok kezelése</h1>
        <p class="muted">Szabad időablak létrehozása, módosítása és zárolása.</p>
    </div>
    <a class="button" href="<?= e(url('provider-slot-form')) ?>">Új időablak</a>
</div>

<?php if (!$services): ?>
    <div class="alert alert-warning">Nincs kezelhető aktív szolgáltatás. Az adminisztrátornak előbb szolgáltatást kell rendelnie az időpontgazdához.</div>
<?php endif; ?>

<?php if (!$slots): ?>
    <div class="empty-state">Még nincs létrehozott időablak.</div>
<?php else: ?>
    <div class="table-wrap">
        <table class="responsive">
            <thead><tr><th>Időpont</th><th>Szolgáltatás</th><th>Időpontgazda</th><th>Állapot</th><th>Foglalás</th><th>Műveletek</th></tr></thead>
            <tbody>
            <?php foreach ($slots as $slot): ?>
                <tr>
                    <td data-label="Időpont"><?= e(utc_to_local($slot['starts_at'])) ?>–<?= e(utc_to_local($slot['ends_at'], 'H:i')) ?></td>
                    <td data-label="Szolgáltatás"><?= e($slot['service_name']) ?></td>
                    <td data-label="Időpontgazda"><?= e($slot['provider_name']) ?></td>
                    <td data-label="Állapot"><span class="badge badge-<?= e($slot['status']) ?>"><?= e(slot_status_label($slot['status'])) ?></span></td>
                    <td data-label="Foglalás"><?= (int) $slot['has_booking'] === 1 ? 'Foglalva' : 'Nincs' ?></td>
                    <td data-label="Műveletek">
                        <div class="button-row">
                            <?php if (!(int) $slot['has_booking']): ?>
                                <a class="button button-secondary button-small" href="<?= e(url('provider-slot-form', ['id' => $slot['id']])) ?>">Módosítás</a>
                                <form method="post" action="<?= e(url('provider-slot-toggle')) ?>" data-confirm="Biztosan megváltoztatja az időablak állapotát?">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="slot_id" value="<?= (int) $slot['id'] ?>">
                                    <button class="button button-small <?= $slot['status'] === 'available' ? 'button-danger' : 'button-secondary' ?>" type="submit">
                                        <?= $slot['status'] === 'available' ? 'Zárolás' : 'Megnyitás' ?>
                                    </button>
                                </form>
                            <?php else: ?>
                                <a class="button button-secondary button-small" href="<?= e(url('manage-bookings')) ?>">Foglalás kezelése</a>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

