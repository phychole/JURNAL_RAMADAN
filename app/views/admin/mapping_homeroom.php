<h4 class="mb-3">Mapping Wali Kelas → Kelas</h4>

<?php if (!empty($success)): ?><div class="alert alert-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>
<?php if (!empty($error)): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>

<div class="card shadow-sm">
  <div class="card-header brand fw-semibold">Kelola Mapping (Edit langsung di tabel)</div>
  <div class="card-body">
    <div class="table-responsive">
      <table class="table table-sm align-middle">
        <thead>
          <tr>
            <th style="width:240px">Kelas</th>
            <th style="width:320px">Wali Kelas Saat Ini</th>
            <th>Ubah Wali Kelas</th>
            <th style="width:180px" class="text-end">Aksi</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($classes as $c): ?>
            <?php $wk = $mapByClass[(int)$c['id']] ?? null; ?>
            <tr>
              <td class="fw-semibold"><?= htmlspecialchars($c['name'].' '.$c['year']) ?></td>
              <td>
                <?php if (!$wk): ?>
                  <span class="text-muted">-</span>
                <?php else: ?>
                  <span class="badge text-bg-success">
                    <?= htmlspecialchars($wk['name']) ?>
                    <?php if (!empty($wk['nip'])): ?> · NIP <?= htmlspecialchars($wk['nip']) ?><?php endif; ?>
                  </span>
                <?php endif; ?>
              </td>
              <td>
                <form method="post" action="<?= u('/admin/mapping-homeroom/save') ?>" class="d-flex gap-2">
                  <input type="hidden" name="class_room_id" value="<?= (int)$c['id'] ?>">
                  <select class="form-select form-select-sm" name="homeroom_user_id" required>
                    <option value="">-- pilih wali kelas --</option>
                    <?php foreach ($homerooms as $h): ?>
                      <option value="<?= (int)$h['id'] ?>" <?= ($wk && (int)$wk['id']===(int)$h['id']) ? 'selected':'' ?>>
                        <?= htmlspecialchars($h['name'].' ('.$h['username'].')') ?><?= !empty($h['nip']) ? ' - NIP '.$h['nip'] : '' ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                  <button class="btn btn-sm btn-brand">Simpan</button>
                </form>
              </td>
              <td class="text-end">
                <form method="post" action="<?= u('/admin/mapping-homeroom/delete') ?>" onsubmit="return confirm('Hapus mapping wali kelas untuk kelas ini?')">
                  <input type="hidden" name="class_room_id" value="<?= (int)$c['id'] ?>">
                  <button class="btn btn-sm btn-outline-danger">Hapus</button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <div class="mt-2 d-flex justify-content-end">
      <a class="btn btn-outline-success" href="<?= u('/admin') ?>">Kembali</a>
    </div>
  </div>
</div>
