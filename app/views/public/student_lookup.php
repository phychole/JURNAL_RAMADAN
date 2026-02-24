<h4 class="mb-3">Pencarian NISN Siswa</h4>

<div class="card shadow-sm mb-3">
  <div class="card-header brand fw-semibold">
    Cari berdasarkan nama
  </div>
  <div class="card-body">
    <form class="row g-2" method="get" action="<?= u('/public/student-lookup') ?>">
      <div class="col-md-8">
        <input
          class="form-control"
          name="q"
          placeholder="Ketik nama anda (min 2 huruf) lebih baik jika nama lengkap"
          value="<?= htmlspecialchars((string)($q ?? '')) ?>"
          autocomplete="off"
        >
        <div class="form-text">
          Data akan tampil setelah anda mengetik nama, lebih baik dengan nama lengkap
        </div>
      </div>
      <div class="col-md-2 d-grid">
        <button class="btn btn-brand">Cari</button>
      </div>
      <div class="col-md-2 d-grid">
        <a class="btn btn-outline-secondary" href="<?= u('/public/student-lookup') ?>">Reset</a>
      </div>
    </form>

    <?php if (!empty($error)): ?>
      <div class="alert alert-warning mt-3 mb-0"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
  </div>
</div>

<?php if (!empty($searched) && empty($error)): ?>
  <div class="card shadow-sm">
    <div class="card-header brand fw-semibold d-flex justify-content-between align-items-center">
      <span>Hasil Pencarian</span>
      <span class="small text-muted">Maks 50 hasil</span>
    </div>
    <div class="card-body">
      <?php if (empty($rows)): ?>
        <div class="text-muted">Tidak ada data yang cocok.</div>
      <?php else: ?>
        <div class="table-responsive">
          <table class="table table-sm align-middle">
            <thead>
              <tr>
                <th style="width:70px">No</th>
                <th>Nama</th>
                <th style="width:220px">NISN</th>
              </tr>
            </thead>
            <tbody>
              <?php $no = 1; ?>
              <?php foreach ($rows as $r): ?>
                <tr>
                  <td><?= $no++ ?></td>
                  <td><?= htmlspecialchars((string)($r['name'] ?? '')) ?></td>
                  <td class="fw-semibold"><?= htmlspecialchars((string)($r['nis'] ?? '-')) ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>
  </div>
<?php endif; ?>