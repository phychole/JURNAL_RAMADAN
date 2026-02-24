<h4 class="mb-3">Admin Panel</h4>

<?php if (!empty($success)): ?><div class="alert alert-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>
<?php if (!empty($error)): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>
<?php if (!empty($info)): ?><div class="alert alert-info"><?= htmlspecialchars($info) ?></div><?php endif; ?>

<div class="row g-3">
  <div class="col-md-6">
    <div class="card shadow-sm">
      <div class="card-header brand fw-semibold">Master Data</div>
      <div class="card-body d-flex flex-column gap-2">
        <a class="btn btn-outline-success" href="<?= u('/admin/classes') ?>">Kelola Kelas</a>
        <a class="btn btn-outline-success" href="<?= u('/admin/students') ?>">Kelola Siswa</a>
        <a class="btn btn-outline-success" href="<?= u('/admin/teachers') ?>">Kelola Guru</a>
        <a class="btn btn-outline-success" href="<?= u('/admin/import') ?>">Import Siswa (Excel/CSV)</a>
      </div>
    </div>
  </div>
  <div class="col-md-6">
    <div class="card shadow-sm">
      <div class="card-header brand fw-semibold">Mapping Hak Akses</div>
      <div class="card-body d-flex flex-column gap-2">
        <a class="btn btn-outline-success" href="<?= u('/admin/mapping-homeroom') ?>">Mapping Wali Kelas → Kelas</a>
        <a class="btn btn-outline-success" href="<?= u('/admin/mapping-religion') ?>">Mapping Guru Agama → Kelas</a>
      </div>
    </div>
  </div>
</div>

<div class="alert alert-warning mt-3">
  <b>Catatan:</b> untuk fitur PDF & import XLSX, jalankan <code>composer install</code> agar dompdf & phpspreadsheet aktif.
</div>
