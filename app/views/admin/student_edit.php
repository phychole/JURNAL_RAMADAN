<h4 class="mb-3">Edit Siswa</h4>

<?php if (!empty($success)): ?><div class="alert alert-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>
<?php if (!empty($error)): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>

<div class="card shadow-sm">
  <div class="card-header brand fw-semibold">Form Edit</div>
  <div class="card-body">
    <form method="post" action="<?= u('/admin/students/update') ?>" class="row g-3">
      <input type="hidden" name="id" value="<?= (int)$row['id'] ?>">

      <div class="col-md-6">
        <label class="form-label">Nama</label>
        <input class="form-control" name="name" value="<?= htmlspecialchars($row['name']) ?>" required>
      </div>

      <div class="col-md-6">
        <label class="form-label">Username</label>
        <input class="form-control" name="username" value="<?= htmlspecialchars($row['username']) ?>" required>
      </div>

      <div class="col-md-4">
        <label class="form-label">NISN/NIS</label>
        <input class="form-control" name="nis" value="<?= htmlspecialchars((string)$row['nis']) ?>" required>
      </div>

      <div class="col-md-5">
        <label class="form-label">Kelas</label>
        <select class="form-select" name="class_room_id" required>
          <?php foreach ($classes as $c): ?>
            <option value="<?= (int)$c['id'] ?>" <?= (int)$c['id']===(int)$row['class_room_id'] ? 'selected':'' ?>>
              <?= htmlspecialchars($c['name'].' '.$c['year']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="col-md-3">
        <label class="form-label">Status</label>
        <select class="form-select" name="is_active">
          <option value="1" <?= (int)$row['is_active']===1?'selected':'' ?>>Aktif</option>
          <option value="0" <?= (int)$row['is_active']===0?'selected':'' ?>>Nonaktif</option>
        </select>
      </div>

      <div class="col-12 d-flex gap-2">
        <button class="btn btn-brand">Simpan</button>
        <a class="btn btn-outline-success" href="<?= u('/admin/students') ?>">Kembali</a>
      </div>
    </form>
  </div>
</div>
