<div class="row justify-content-center">
  <div class="col-md-5">
    <div class="card shadow-sm">
      <div class="card-header brand">
        <div class="fw-semibold">Login</div>
        <div class="small text-muted">Gunakan akun admin/siswa/wali kelas/guru agama.</div>
      </div>
      <div class="card-body">
        <?php if (!empty($error)): ?>
          <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <?php if (!empty($info)): ?>
          <div class="alert alert-info"><?= htmlspecialchars($info) ?></div>
        <?php endif; ?>

        <form method="post" action="<?= u('/login') ?>">
          <div class="mb-3">
            <label class="form-label">Username</label>
            <input name="username" class="form-control" autocomplete="username" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Password</label>
            <input name="password" type="password" class="form-control" autocomplete="current-password" required>
          </div>
          <button class="btn btn-brand w-100">Masuk</button>
        </form>

        <hr>
        <div class="small text-muted">
         “Login dengan semangat, jalani dengan amanah, tutup hari dengan keberkahan.”
        </div>
      </div>
    </div>
  </div>
</div>
