<?php
// $class_, $rows_, $from_, $to_, $totalDays_
?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<style>
  body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 12px; }
  h2 { margin: 0 0 8px 0; }
  table { width:100%; border-collapse: collapse; }
  th,td { border:1px solid #ccc; padding:6px; }
  th { background:#eaf6ef; }
</style>
</head>
<body>
  <h2>Rekap Kelas - Buku Kegiatan Ramadan</h2>
  <div>
    Kelas: <b><?= htmlspecialchars($class_['name'].' '.$class_['year']) ?></b><br>
    Periode: <b>Hari <?= (new DateTime(RAMADAN_START))->diff(new DateTime($from_))->days + 1 ?></b> s/d <b>Hari <?= (new DateTime(RAMADAN_START))->diff(new DateTime($to_))->days + 1 ?></b><br><span class="muted">(<?= htmlspecialchars($from_) ?> s/d <?= htmlspecialchars($to_) ?>)</span> (<?= (int)$totalDays_ ?> hari)
  </div>

  <table style="margin-top:12px;">
    <thead>
      <tr>
        <th>No</th><th>Nama</th><th>NIS</th><th>Hari Terisi</th><th>Persen</th><th>Status</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($rows_ as $i=>$r): ?>
        <tr>
          <td><?= $i+1 ?></td>
          <td><?= htmlspecialchars($r['name']) ?></td>
          <td><?= htmlspecialchars($r['nis']) ?></td>
          <td><?= (int)$r['filled'] ?> / <?= (int)$totalDays_ ?></td>
          <td><?= round($r['pct']*100, 1) ?>%</td>
          <td><?= htmlspecialchars($r['status']) ?></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</body>
</html>
