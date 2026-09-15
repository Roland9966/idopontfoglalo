<div class="page-heading">
    <div>
        <h1>Auditnapló</h1>
        <p class="muted">A legutóbbi 300 bejelentkezési, jogosultsági és állapotváltozási esemény.</p>
    </div>
</div>

<?php if (!$logs): ?>
    <div class="empty-state">Még nincs naplóbejegyzés.</div>
<?php else: ?>
    <div class="table-wrap">
        <table class="responsive">
            <thead><tr><th>Időpont</th><th>Esemény</th><th>Végrehajtó</th><th>Objektum</th><th>Állapotváltozás</th></tr></thead>
            <tbody>
            <?php foreach ($logs as $log): ?>
                <tr>
                    <td data-label="Időpont" class="nowrap"><?= e(utc_to_local($log['created_at'])) ?></td>
                    <td data-label="Esemény"><strong><?= e($log['event_type']) ?></strong></td>
                    <td data-label="Végrehajtó"><?= e($log['actor_name'] ?: 'Ismeretlen/rendszer') ?></td>
                    <td data-label="Objektum"><?= e($log['entity_type']) ?><?= $log['entity_id'] ? ' #' . (int) $log['entity_id'] : '' ?></td>
                    <td data-label="Változás"><span class="help"><?= e($log['old_state'] ?: '–') ?> → <?= e($log['new_state'] ?: '–') ?></span></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

