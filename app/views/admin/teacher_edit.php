<h4 class="mb-3">Edit Guru</h4>

<?php if (!empty($success)): ?><div class="alert alert-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>
<?php if (!empty($error)): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>

<?php
  $rolesStr = (string)($row['roles'] ?? '');
  $roleArr = array_filter(array_map('trim', explode(',', $rolesStr)));
  $has = fn($r) => in_array($r, $roleArr, true);
?>

<div class="card shadow-sm">
  <div class="card-header brand fw-semibold">Form Edit</div>
  <div class="card-body">
    <form method="post" action="<?= u('/admin/teachers/update') ?>" class="row g-3">
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
        <label class="form-label">NIP</label>
        <input class="form-control" name="nip" value="<?= htmlspecialchars((string)($row['nip'] ?? '')) ?>">
      </div>

      <div class="col-md-5">
        <label class="form-label">Roles (multi)</label>
        <div class="border rounded p-2">
          <div class="form-check">
            <input class="form-check-input" type="checkbox" name="roles[]" value="homeroom" id="r1" <?= $has('homeroom')?'checked':'' ?>>
            <label class="form-check-label" for="r1">Wali Kelas (homeroom)</label>
          </div>
          <div class="form-check">
            <input class="form-check-input" type="checkbox" name="roles[]" value="religion_teacher" id="r2" <?= $has('religion_teacher')?'checked':'' ?>>
            <label class="form-check-label" for="r2">Guru Agama (religion_teacher)</label>
          </div>
          <div class="form-check">
            <input class="form-check-input" type="checkbox" name="roles[]" value="principal" id="r3" <?= $has('principal')?'checked':'' ?>>
            <label class="form-check-label" for="r3">Kepala Sekolah (principal)</label>
          </div>
          <div class="form-check">
            <input class="form-check-input" type="checkbox" name="roles[]" value="admin" id="r4" <?= $has('admin')?'checked':'' ?>>
            <label class="form-check-label" for="r4">Admin (admin)</label>
          </div>
        </div>
        <div class="form-text">Boleh pilih lebih dari satu role.</div>
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
        <a class="btn btn-outline-success" href="<?= u('/admin/teachers') ?>">Kembali</a>
      </div>
    </form>
  </div>
</div>
