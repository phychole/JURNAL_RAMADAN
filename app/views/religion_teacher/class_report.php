<?php
// Default supaya tidak error
$rows = $rows ?? [];
$classes = $classes ?? [];
$classId = (int)($classId ?? 0);
$fromDay = (int)($fromDay ?? 1);
$toDay = (int)($toDay ?? RAMADAN_DAYS);
$totalDays = (int)($totalDays ?? 0);
$baseRoute = $baseRoute ?? '/religion'; // default religion
?>

<h4 class="mb-3">Rekap Kelas</h4>

<div class="card shadow-sm mb-3">
  <div class="card-header brand">
    <form class="row g-2 align-items-end" method="get" action="<?= u($baseRoute.'/class') ?>">
      
      <!-- PILIH KELAS (principal punya banyak, religion hanya mapping) -->
      <?php if (!empty($classes)): ?>
      <div class="col-md-3">
        <label class="form-label small mb-1">Kelas</label>
        <select class="form-select form-select-sm" name="class_id">
          <?php foreach ($classes as $c): ?>
            <option value="<?= (int)$c['id'] ?>"
              <?= (int)$c['id']===$classId ? 'selected':'' ?>>
              <?= htmlspecialchars($c['name'].' '.$c['year']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <?php endif; ?>

      <!-- DARI -->
      <div class="col-md-3">
        <label class="form-label small mb-1">Dari</label>
        <select name="from_day" class="form-select form-select-sm">
          <?php for($i=1;$i<=RAMADAN_DAYS;$i++): ?>
            <option value="<?= $i ?>" <?= $fromDay===$i?'selected':'' ?>>
              Hari <?= $i ?>
            </option>
          <?php endfor; ?>
        </select>
      </div>

      <!-- SAMPAI -->
      <div class="col-md-3">
        <label class="form-label small mb-1">Sampai</label>
        <select name="to_day" class="form-select form-select-sm">
          <?php for($i=1;$i<=RAMADAN_DAYS;$i++): ?>
            <option value="<?= $i ?>" <?= $toDay===$i?'selected':'' ?>>
              Hari <?= $i ?>
            </option>
          <?php endfor; ?>
        </select>
      </div>

      <!-- BUTTON -->
      <div class="col-md-3 d-flex gap-2">
        <button class="btn btn-sm btn-brand">Terapkan</button>

        <?php if ($classId > 0): ?>
        <a class="btn btn-sm btn-outline-success"
           href="<?= u($baseRoute.'/report/xls') ?>?class_id=<?= $classId ?>&from_day=<?= $fromDay ?>&to_day=<?= $toDay ?>">
          Export XLS
        </a>
        <?php endif; ?>
      </div>
    </form>
  </div>

  <div class="card-body">

    <?php if (empty($rows)): ?>
      <div class="alert alert-info">
        Tidak ada data siswa aktif pada kelas ini.
      </div>
    <?php else: ?>

    <div class="row g-3">
      <!-- TABLE -->
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
                  <td><?= htmlspecialchars($r['name']) ?></td>
                  <td><?= htmlspecialchars($r['nis']) ?></td>
                  <td><?= (int)$r['filled'] ?>/<?= $totalDays ?></td>
                  <td><?= round($r['pct']*100,1) ?>%</td>
                  <td>
                    <span class="badge <?= $r['status']==='Rajin'?'text-bg-success':'text-bg-danger' ?>">
                      <?= htmlspecialchars($r['status']) ?>
                    </span>
                  </td>
                  <td>
                    <a class="btn btn-sm btn-outline-success"
                       href="<?= u($baseRoute.'/student') ?>?class_id=<?= $classId ?>&id=<?= (int)$r['user_id'] ?>&from_day=<?= $fromDay ?>&to_day=<?= $toDay ?>">
                      Detail
                    </a>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>

      <!-- CHART -->
      <div class="col-lg-5">
        <div class="fw-semibold mb-2">Chart Rajin vs Kurang</div>
        <canvas id="pie"></canvas>

        <hr>

        <div class="fw-semibold mb-2">Jumlah Hari Terisi per Siswa</div>
        <canvas id="bar"></canvas>
      </div>
    </div>

    <?php endif; ?>

  </div>
</div>

<script>
const rows = <?= json_encode($rows, JSON_UNESCAPED_UNICODE) ?>;

if(rows.length > 0){

  const rajin = rows.filter(r => r.status === 'Rajin').length;
  const kurang = rows.length - rajin;

  new Chart(document.getElementById('pie'), {
    type: 'pie',
    data: {
      labels: ['Rajin','Kurang'],
      datasets: [{
        data: [rajin, kurang]
      }]
    }
  });

  new Chart(document.getElementById('bar'), {
    type: 'bar',
    data: {
      labels: rows.map(r => r.name),
      datasets: [{
        label: 'Hari Terisi',
        data: rows.map(r => r.filled)
      }]
    },
    options: {
      scales: {
        y: { beginAtZero: true }
      }
    }
  });

}
</script>