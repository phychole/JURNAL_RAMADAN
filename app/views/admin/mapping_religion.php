<h4 class="mb-3">Mapping Guru Agama → Kelas</h4>

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
            <th style="width:360px">Guru Agama Saat Ini</th>
            <th>Ubah Guru Agama (boleh lebih dari 1)</th>
            <th style="width:180px" class="text-end">Aksi</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($classes as $c): ?>
            <?php $arr = $byClass[(int)$c['id']] ?? []; ?>
            <?php $currentIds = array_map(fn($x)=>(int)$x['id'], $arr); ?>
            <tr>
              <td class="fw-semibold"><?= htmlspecialchars($c['name'].' '.$c['year']) ?></td>
              <td>
                <?php if (!$arr): ?>
                  <span class="text-muted">-</span>
                <?php else: ?>
                  <div class="d-flex flex-wrap gap-2">
                    <?php foreach ($arr as $g): ?>
                      <span class="badge text-bg-success">
                        <?= htmlspecialchars($g['name']) ?>
                        <?php if (!empty($g['nip'])): ?> · NIP <?= htmlspecialchars($g['nip']) ?><?php endif; ?>
                      </span>
                    <?php endforeach; ?>
                  </div>
                <?php endif; ?>
              </td>
              <td>
                <form method="post" action="<?= u('/admin/mapping-religion/save') ?>" class="d-flex gap-2 align-items-start">
                  <input type="hidden" name="class_room_id" value="<?= (int)$c['id'] ?>">
                  <select class="form-select form-select-sm" name="teacher_user_ids[]" multiple size="4">
                    <?php foreach ($teachers as $t): ?>
                      <option value="<?= (int)$t['id'] ?>" <?= in_array((int)$t['id'], $currentIds, true) ? 'selected':'' ?>>
                        <?= htmlspecialchars($t['name'].' ('.$t['username'].')') ?><?= !empty($t['nip']) ? ' - NIP '.$t['nip'] : '' ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                  <div class="d-flex flex-column gap-2">
                    <button class="btn btn-sm btn-brand">Simpan</button>
                    <div class="small text-muted">Ctrl/Shift untuk multi</div>
                  </div>
                </form>
              </td>
              <td class="text-end">
                <form method="post" action="<?= u('/admin/mapping-religion/delete') ?>" onsubmit="return confirm('Hapus semua mapping guru agama untuk kelas ini?')">
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
