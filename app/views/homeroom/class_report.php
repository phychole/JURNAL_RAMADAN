<?php
// ====== SAFE DEFAULTS (anti Undefined variable) ======
$user      = $user ?? null;
$classes   = $classes ?? [];
$classId   = (int)($classId ?? 0);
$class     = $class ?? null; // bisa null
$rows      = $rows ?? [];
$totalDays = (int)($totalDays ?? 0);

$fromDay = (int)($fromDay ?? 1);
$toDay   = (int)($toDay ?? (defined('RAMADAN_DAYS') ? RAMADAN_DAYS : 30));
$from    = $from ?? '';
$to      = $to ?? '';

// ====== LABEL KELAS (fallback) ======
$classLabel = '';
if (is_array($class) && isset($class['name'])) {
  $classLabel = trim((string)$class['name'] . ' ' . (string)($class['year'] ?? ''));
} else {
  // fallback cari dari $classes
  foreach ($classes as $c) {
    if ((int)($c['id'] ?? 0) === $classId) {
      $classLabel = trim((string)($c['name'] ?? '') . ' ' . (string)($c['year'] ?? ''));
      break;
    }
  }
}
if ($classLabel === '') $classLabel = '(Kelas belum dipilih / belum dimapping)';
?>

<h4 class="mb-3">Rekap Kelas (Wali Kelas)</h4>

<div class="card shadow-sm mb-3">
  <div class="card-header brand">
    <form class="row g-2 align-items-end" method="get" action="<?= u('/homeroom/class') ?>">

      <?php if (!empty($classes)): ?>
      <div class="col-md-3">
        <label class="form-label small mb-1">Kelas</label>
        <select class="form-select form-select-sm" name="class_id">
          <?php foreach ($classes as $c): ?>
            <option value="<?= (int)$c['id'] ?>" <?= (int)$c['id']===$classId ? 'selected':'' ?>>
              <?= htmlspecialchars(trim((string)$c['name'].' '.(string)$c['year'])) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <?php endif; ?>

      <div class="col-md-3">
        <label class="form-label small mb-1">Dari</label>
        <select name="from_day" class="form-select form-select-sm">
          <?php for($i=1;$i<=RAMADAN_DAYS;$i++): ?>
            <option value="<?= $i ?>" <?= $fromDay===$i?'selected':'' ?>>Hari <?= $i ?></option>
          <?php endfor; ?>
        </select>
      </div>

      <div class="col-md-3">
        <label class="form-label small mb-1">Sampai</label>
        <select name="to_day" class="form-select form-select-sm">
          <?php for($i=1;$i<=RAMADAN_DAYS;$i++): ?>
            <option value="<?= $i ?>" <?= $toDay===$i?'selected':'' ?>>Hari <?= $i ?></option>
          <?php endfor; ?>
        </select>
      </div>

      <div class="col-md-3 d-flex gap-2">
        <button class="btn btn-sm btn-brand">Terapkan</button>

        <?php if ($classId > 0): ?>
          <a class="btn btn-sm btn-outline-success"
             href="<?= u('/homeroom/report/xls') ?>?class_id=<?= $classId ?>&from_day=<?= $fromDay ?>&to_day=<?= $toDay ?>">
            Export XLS
          </a>
          <button type="button" class="btn btn-sm btn-outline-secondary" onclick="window.print()">
            Cetak
          </button>
        <?php endif; ?>
      </div>
    </form>

    <div class="small text-muted mt-2">
      Kelas: <b><?= htmlspecialchars($classLabel) ?></b>
      <?php if ($fromDay && $toDay): ?>
        • Periode: Hari <?= (int)$fromDay ?> s/d <?= (int)$toDay ?>
      <?php endif; ?>
    </div>
  </div>

  <div class="card-body">

    <?php if (empty($rows)): ?>
      <div class="alert alert-info mb-0">
        Tidak ada data siswa pada kelas ini (atau kelas belum dimapping).
      </div>
    <?php else: ?>

      <div class="row g-3">
        <div class="col-lg-7">
          <div class="table-responsive">
            <table class="table table-sm align-middle">
              <thead>
                <tr>
                  <th>Nama</th>
                  <th>NIS</th>
                  <th>Terisi</th>
                  <th>%</th>
                  <th>Status</th>
                  <th></th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($rows as $r): ?>
                  <tr>
                    <td><?= htmlspecialchars((string)$r['name']) ?></td>
                    <td><?= htmlspecialchars((string)($r['nis'] ?? '-')) ?></td>
                    <td><?= (int)($r['filled'] ?? 0) ?>/<?= (int)$totalDays ?></td>
                    <td><?= round(((float)($r['pct'] ?? 0))*100, 1) ?>%</td>
                    <td>
                      <span class="badge <?= ($r['status'] ?? '')==='Rajin' ? 'text-bg-success' : 'text-bg-danger' ?>">
                        <?= htmlspecialchars((string)($r['status'] ?? '-')) ?>
                      </span>
                    </td>
                    <td>
                      <a class="btn btn-sm btn-outline-success"
                         href="<?= u('/homeroom/student') ?>?class_id=<?= $classId ?>&id=<?= (int)$r['user_id'] ?>&from_day=<?= $fromDay ?>&to_day=<?= $toDay ?>">
                        Detail
                      </a>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>

        <div class="col-lg-5">
          <div class="fw-semibold mb-2">Chart Rajin vs Kurang</div>
          <canvas id="pie"></canvas>
          <hr>
          <div class="fw-semibold mb-2">Jumlah Hari Terisi per Siswa</div>
          <canvas id="bar"></canvas>
        </div>
      </div>

      <script>
      const rows = <?= json_encode($rows, JSON_UNESCAPED_UNICODE) ?>;
      if(rows.length){
        const rajin = rows.filter(r => r.status === 'Rajin').length;
        const kurang = rows.length - rajin;

        new Chart(document.getElementById('pie'), {
          type: 'pie',
          data: { labels: ['Rajin','Kurang'], datasets: [{ data: [rajin, kurang] }] }
        });

        new Chart(document.getElementById('bar'), {
          type: 'bar',
          data: {
            labels: rows.map(r => r.name),
            datasets: [{ label: 'Hari Terisi', data: rows.map(r => Number(r.filled||0)) }]
          },
          options: { scales: { y: { beginAtZero: true } } }
        });
      }
      </script>

    <?php endif; ?>

  </div>
</div>