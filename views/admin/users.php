<div class="page-heading">
    <div>
        <h1>Felhasználók kezelése</h1>
        <p class="muted">A szerepkör és a fiók állapota kizárólag adminisztrátorként módosítható.</p>
    </div>
</div>

<div class="table-wrap">
    <table class="responsive user-admin-table">
        <thead><tr><th>Felhasználó</th><th>Szerepkör és állapot</th><th>Hozzárendelt szolgáltatások</th><th>Művelet</th></tr></thead>
        <tbody>
        <?php foreach ($users as $user): ?>
            <tr>
                <td data-label="Felhasználó">
                    <strong><?= e($user['name']) ?></strong><br>
                    <?php if (!empty($user['student_index'])): ?><span class="muted">Indexszám: <?= e($user['student_index']) ?></span><br><?php endif; ?>
                    <span class="muted"><?= e($user['email']) ?></span><br>
                    <?php if (!empty($user['email_verified_at'])): ?>
                        <span class="badge badge-active">E-mail aktiválva</span>
                    <?php else: ?>
                        <span class="badge badge-no_show">Aktiválásra vár</span>
                    <?php endif; ?>
                </td>
                <td data-label="Jogosultság">
                    <form class="admin-cell-form" method="post" action="<?= e(url('admin-user-update')) ?>">
                        <?= csrf_field() ?>
                        <input type="hidden" name="user_id" value="<?= (int) $user['id'] ?>">
                        <div class="admin-form-fields">
                            <div class="compact-field">
                                <label for="role-<?= (int) $user['id'] ?>">Szerepkör</label>
                                <select id="role-<?= (int) $user['id'] ?>" name="role_id">
                                    <?php foreach ($roles as $role): ?>
                                        <option value="<?= (int) $role['id'] ?>" <?= $user['role_code'] === $role['code'] ? 'selected' : '' ?>><?= e($role['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="compact-field">
                                <label for="user-status-<?= (int) $user['id'] ?>">Állapot</label>
                                <select id="user-status-<?= (int) $user['id'] ?>" name="status">
                                    <option value="active" <?= $user['status'] === 'active' ? 'selected' : '' ?>>Aktív</option>
                                    <option value="inactive" <?= $user['status'] === 'inactive' ? 'selected' : '' ?>>Inaktív</option>
                                </select>
                            </div>
                        </div>
                        <div class="admin-form-actions">
                            <button class="button button-small" type="submit">Jogosultság mentése</button>
                        </div>
                    </form>
                </td>
                <td data-label="Szolgáltatások"><span class="assigned-services"><?= e($user['assigned_services'] ?: 'Nincs hozzárendelés') ?></span></td>
                <td data-label="Hozzárendelés">
                    <?php if (in_array($user['role_code'], ['provider', 'admin'], true)): ?>
                        <form class="admin-cell-form" method="post" action="<?= e(url('admin-provider-assignment')) ?>">
                            <?= csrf_field() ?>
                            <input type="hidden" name="provider_id" value="<?= (int) $user['id'] ?>">
                            <div class="admin-form-fields">
                                <div class="compact-field">
                                    <label for="service-<?= (int) $user['id'] ?>">Szolgáltatás</label>
                                    <select id="service-<?= (int) $user['id'] ?>" name="service_id" required>
                                        <?php foreach ($services as $service): ?><option value="<?= (int) $service['id'] ?>"><?= e($service['name']) ?></option><?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="compact-field">
                                    <label for="assignment-<?= (int) $user['id'] ?>">Művelet</label>
                                    <select id="assignment-<?= (int) $user['id'] ?>" name="assignment_action">
                                        <option value="add">Hozzárendelés</option>
                                        <option value="remove">Eltávolítás</option>
                                    </select>
                                </div>
                            </div>
                            <div class="admin-form-actions">
                                <button class="button button-small" type="submit">Hozzárendelés mentése</button>
                            </div>
                        </form>
                    <?php else: ?><span class="muted">Ehhez a szerepkörhöz nem tartozik szolgáltatáskezelés.</span><?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
