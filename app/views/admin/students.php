<h4 class="mb-3">Kelola Siswa</h4>

<?php if (!empty($success)): ?><div class="alert alert-success"><?= htmlspecialchars((string)$success) ?></div><?php endif; ?>
<?php if (!empty($error)): ?><div class="alert alert-danger"><?= htmlspecialchars((string)$error) ?></div><?php endif; ?>

<form class="row g-2 mb-3 d-print-none" method="get" action="<?= u('/admin/students') ?>">
  <div class="col-md-6">
    <input
      class="form-control"
      name="q"
      placeholder="Cari nama/username/NIS/kelas"
      value="<?= htmlspecialchars((string)($q ?? '')) ?>"
    >
  </div>
  <div class="col-md-2">
    <button class="btn btn-outline-success w-100">Cari</button>
  </div>
  <div class="col-md-4 text-end">
    <a class="btn btn-outline-success" href="<?= u('/admin') ?>">Kembali</a>
  </div>
</form>

<div class="card shadow-sm">
  <div class="card-header brand fw-semibold d-flex justify-content-between align-items-center">
    <span>Daftar Siswa</span>
    <?php if (!empty($total)): ?>
      <span class="small text-muted">Total: <?= (int)$total ?> data</span>
    <?php endif; ?>
  </div>

  <div class="card-body">
    <div class="table-responsive">
      <table class="table table-sm align-middle">
        <thead>
          <tr>
            <th style="width:70px">No</th>
            <th>Nama</th>
            <th style="width:160px">Username</th>
            <th style="width:140px">NISN/NIS</th>
            <th style="width:160px">Kelas</th>
            <th style="width:110px">Aktif</th>
            <th style="width:240px">Aksi</th>
          </tr>
        </thead>

        <tbody>
          <?php $no = (int)($offset ?? 0) + 1; ?>
          <?php foreach (($rows ?? []) as $r): ?>
            <?php $isActive = ((int)($r['is_active'] ?? 0) === 1); ?>
            <tr>
              <td><?= $no++ ?></td>
              <td><?= htmlspecialchars((string)($r['name'] ?? '')) ?></td>
              <td><?= htmlspecialchars((string)($r['username'] ?? '')) ?></td>
              <td><?= htmlspecialchars((string)($r['nis'] ?? '-')) ?></td>
              <td><?= htmlspecialchars(trim((string)($r['class_name'] ?? '') . ' ' . (string)($r['class_year'] ?? ''))) ?></td>
              <td><?= $isActive ? 'Ya' : 'Tidak' ?></td>
              <td class="d-flex gap-1 flex-wrap">
                <a class="btn btn-sm btn-outline-success" href="<?= u('/admin/students/edit?id='.(int)$r['id']) ?>">Edit</a>

                <?php if ($isActive): ?>
                  <form method="post" action="<?= u('/admin/students/deactivate') ?>" onsubmit="return confirm('Nonaktifkan siswa ini? (data tetap tersimpan)')">
                    <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                    <button class="btn btn-sm btn-outline-warning" type="submit">Nonaktifkan</button>
                  </form>
                <?php else: ?>
                  <form method="post" action="<?= u('/admin/students/activate') ?>" onsubmit="return confirm('Aktifkan kembali siswa ini?')">
                    <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                    <button class="btn btn-sm btn-outline-primary" type="submit">Aktifkan</button>
                  </form>
                <?php endif; ?>

                <!-- Hapus permanen: tampilkan hanya jika sudah nonaktif supaya aman -->
                <?php if (!$isActive): ?>
                  <form method="post" action="<?= u('/admin/students/delete') ?>" onsubmit="return confirm('HAPUS PERMANEN siswa ini? Data profile akan dihapus. (Isian jurnal tidak ikut dihapus kecuali kamu ubah di controller)')">
                    <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                    <button class="btn btn-sm btn-outline-danger" type="submit">Hapus</button>
                  </form>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>

          <?php if (empty($rows)): ?>
            <tr><td colspan="7" class="text-center text-muted">Tidak ada data.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

    <?php if (!empty($totalPages) && (int)$totalPages > 1): ?>
      <nav class="d-print-none mt-3">
        <ul class="pagination pagination-sm justify-content-end mb-0">
          <?php
            $current = (int)($page ?? 1);
            if ($current < 1) $current = 1;

            $qParam = trim((string)($q ?? ''));
            $base = u('/admin/students');

            $mk = function(int $p) use ($base, $qParam): string {
              $qs = 'page='.(int)$p;
              if ($qParam !== '') $qs .= '&q='.urlencode($qParam);
              return $base.'?'.$qs;
            };
          ?>

          <li class="page-item <?= $current <= 1 ? 'disabled' : '' ?>">
            <a class="page-link" href="<?= $mk(max(1, $current-1)) ?>">«</a>
          </li>

          <?php
            $start = max(1, $current - 2);
            $end   = min((int)$totalPages, $current + 2);

            if ($start > 1) {
              echo '<li class="page-item"><a class="page-link" href="'.$mk(1).'">1</a></li>';
              if ($start > 2) echo '<li class="page-item disabled"><span class="page-link">…</span></li>';
            }

            for ($p=$start; $p<=$end; $p++) {
              $active = ($p === $current) ? 'active' : '';
              echo '<li class="page-item '.$active.'"><a class="page-link" href="'.$mk($p).'">'.$p.'</a></li>';
            }

            if ($end < (int)$totalPages) {
              if ($end < (int)$totalPages - 1) echo '<li class="page-item disabled"><span class="page-link">…</span></li>';
              echo '<li class="page-item"><a class="page-link" href="'.$mk((int)$totalPages).'">'.(int)$totalPages.'</a></li>';
            }
          ?>

          <li class="page-item <?= $current >= (int)$totalPages ? 'disabled' : '' ?>">
            <a class="page-link" href="<?= $mk(min((int)$totalPages, $current+1)) ?>">»</a>
          </li>
        </ul>
      </nav>
    <?php endif; ?>

  </div>
</div>