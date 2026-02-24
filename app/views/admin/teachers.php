<h4 class="mb-3">Kelola Guru</h4>

<?php if (!empty($success)): ?><div class="alert alert-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>
<?php if (!empty($error)): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>

<form class="row g-2 mb-3 d-print-none" method="get" action="<?= u('/admin/teachers') ?>">
  <div class="col-md-6">
    <input class="form-control" name="q" placeholder="Cari nama/username/NIP/roles" value="<?= htmlspecialchars($q) ?>">
  </div>
  <div class="col-md-2">
    <button class="btn btn-outline-success w-100">Cari</button>
  </div>
  <div class="col-md-4 text-end">
    <a class="btn btn-outline-success" href="<?= u('/admin') ?>">Kembali</a>
  </div>
</form>

<div class="card shadow-sm">
  <div class="card-header brand fw-semibold">Daftar Guru</div>
  <div class="card-body">
    <div class="table-responsive">
      <table class="table table-sm align-middle">
        <thead>
          <tr>
            <th style="width:70px">ID</th>
            <th>Nama</th>
            <th style="width:160px">Username</th>
            <th style="width:170px">NIP</th>
            <th>Roles</th>
            <th style="width:110px">Aktif</th>
            <th style="width:90px"></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($rows as $r): ?>
            <tr>
              <td><?= (int)$r['id'] ?></td>
              <td><?= htmlspecialchars($r['name']) ?></td>
              <td><?= htmlspecialchars($r['username']) ?></td>
              <td><?= htmlspecialchars((string)($r['nip'] ?? '')) ?></td>
              <td><?= htmlspecialchars((string)$r['roles']) ?></td>
              <td><?= (int)$r['is_active'] ? 'Ya' : 'Tidak' ?></td>
              <td><a class="btn btn-sm btn-outline-success" href="<?= u('/admin/teachers/edit?id='.(int)$r['id']) ?>">Edit</a></td>
            </tr>
          <?php endforeach; ?>
          <?php if (empty($rows)): ?>
            <tr><td colspan="7" class="text-center text-muted">Tidak ada data.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
