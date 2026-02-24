<h4 class="mb-3">Kelola Kelas</h4>

<?php if (!empty($success)): ?><div class="alert alert-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>
<?php if (!empty($error)): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>

<div class="row g-3">
  <div class="col-md-5">
    <div class="card shadow-sm">
      <div class="card-header brand fw-semibold">Tambah Kelas</div>
      <div class="card-body">
        <form method="post" action="<?= u('/admin/classes/create') ?>">
          <div class="mb-2">
            <label class="form-label">Nama Kelas (contoh: 7A)</label>
            <input class="form-control" name="name" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Tahun (contoh: 2026)</label>
            <input class="form-control" type="number" name="year" required>
          </div>
          <button class="btn btn-brand">Simpan</button>
          <a class="btn btn-outline-success" href="<?= u('/admin') ?>">Kembali</a>
        </form>
      </div>
    </div>

    <div class="alert alert-warning mt-3 mb-0">
      <b>Hapus Paksa:</b> akan menghapus mapping wali kelas & guru agama pada kelas tersebut.
      Jika masih ada siswa, kamu <b>wajib</b> memilih kelas tujuan untuk memindahkan siswa terlebih dahulu.
    </div>
  </div>

  <div class="col-md-7">
    <div class="card shadow-sm">
      <div class="card-header brand fw-semibold">Daftar Kelas</div>
      <div class="card-body">
        <div class="table-responsive">
          <table class="table table-sm align-middle">
            <thead>
              <tr>
                <th>ID</th>
                <th>Kelas</th>
                <th>Tahun</th>
                <th style="min-width:220px">Pindahkan Siswa ke</th>
                <th class="text-end">Aksi</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($classes as $c): ?>
                <tr>
                  <td><?= (int)$c['id'] ?></td>
                  <td><?= htmlspecialchars($c['name']) ?></td>
                  <td><?= htmlspecialchars($c['year']) ?></td>

                  <td>
                    <select class="form-select form-select-sm" form="forceDel<?= (int)$c['id'] ?>" name="move_to_class_id">
                      <option value="0">-- (jika ada siswa, wajib pilih) --</option>
                      <?php foreach ($classes as $dest): ?>
                        <?php if ((int)$dest['id'] !== (int)$c['id']): ?>
                          <option value="<?= (int)$dest['id'] ?>">
                            <?= htmlspecialchars($dest['name'].' '.$dest['year']) ?>
                          </option>
                        <?php endif; ?>
                      <?php endforeach; ?>
                    </select>
                  </td>

                  <td class="text-end">
                    <form method="post" action="<?= u('/admin/classes/delete') ?>" style="display:inline"
                          onsubmit="return confirm('Hapus kelas ini? Hanya berhasil jika tidak dipakai siswa/mapping.');">
                      <input type="hidden" name="id" value="<?= (int)$c['id'] ?>">
                      <button class="btn btn-sm btn-outline-danger">Hapus</button>
                    </form>

                    <form id="forceDel<?= (int)$c['id'] ?>" method="post" action="<?= u('/admin/classes/delete') ?>" style="display:inline"
                          onsubmit="return confirm('HAPUS PAKSA: mapping akan dihapus. Jika ada siswa, pastikan memilih kelas tujuan pemindahan. Lanjut?');">
                      <input type="hidden" name="id" value="<?= (int)$c['id'] ?>">
                      <input type="hidden" name="force" value="1">
                      <button class="btn btn-sm btn-danger">Hapus Paksa</button>
                    </form>
                  </td>
                </tr>
              <?php endforeach; ?>
              <?php if (empty($classes)): ?>
                <tr><td colspan="5" class="text-center text-muted">Belum ada kelas.</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>
