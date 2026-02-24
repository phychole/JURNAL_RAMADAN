<h4 class="mb-3 d-print-none">Detail Siswa</h4>

<?php
  $nis = $student['nis'] ?? '-';
  $classLabel = trim(($student['class_name'] ?? '').' '.($student['class_year'] ?? ''));
  $classId = (int)($class_id ?? 0);
?>

<div class="print-header mb-2">
  <?php
    $bulan = ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
    $t = (int)date('d'); $m = (int)date('m'); $y = date('Y');
    $tglCetak = $t . ' ' . ($bulan[$m-1] ?? '') . ' ' . $y;
  ?>
  <div class="kop-grid">
    <div>
      <img class="kop-logo" src="<?= u('/assets/logo.png') ?>" alt="Logo Sekolah">
    </div>
    <div class="kop-text">
      <div class="kop-wrap">
        <div class="kop-line1">PEMERINTAH PROVINSI JAWA TIMUR</div>
        <div class="kop-line2">DINAS PENDIDIKAN</div>
        <div class="kop-school">SEKOLAH MENENGAH KEJURUAN</div>
        <div class="kop-small">Alamat</div>
        <div class="kop-small">Alamat Web</div>
        <div class="kop-line2">Kota</div>
      </div>
    </div>
  </div>

  <div class="kop-divider"></div>

  <h4 class="text-center fw-bold mb-2">LAPORAN KEGIATAN SISWA BULAN RAMADAN <?= htmlspecialchars(RAMADAN_HIJRI_YEAR) ?> H</h4>

  <table class="table table-borderless table-sm mb-1" style="width:auto;">
    <tr><td style="width:180px"><b>NISN/NIS</b></td><td>: <?= htmlspecialchars((string)$nis) ?></td></tr>
    <tr><td><b>Nama Siswa</b></td><td>: <?= htmlspecialchars((string)$student['name']) ?></td></tr>
    <tr><td><b>Kelas</b></td><td>: <?= htmlspecialchars((string)$classLabel) ?></td></tr>
    <tr><td><b>Periode</b></td><td>: Hari 1 s/d <?= (int)RAMADAN_DAYS ?> Ramadan <?= htmlspecialchars(RAMADAN_HIJRI_YEAR) ?> H</td></tr>
  </table>

  <div class="print-date">Lumajang, <?= htmlspecialchars($tglCetak) ?></div>
  <hr class="my-2">
</div>

<div class="card shadow-sm mb-3 d-print-none">
  <div class="card-header brand d-flex justify-content-between align-items-center">
    <div class="fw-semibold"><?= htmlspecialchars($student['name']) ?> (<?= htmlspecialchars((string)$nis) ?>)</div>
    <div class="d-flex gap-2">
      <a class="btn btn-sm btn-outline-success" href="<?= u('/religion/class') ?>?class_id=<?= $classId ?>">Kembali</a>
      <button type="button" class="btn btn-sm btn-outline-secondary" onclick="window.print()">Cetak (A4)</button>
    </div>
  </div>
  <div class="card-body">
    <div class="row g-3">
      <div class="col-md-6">
        <div class="small text-muted">Kelas</div>
        <div><?= htmlspecialchars((string)$classLabel) ?></div>
      </div>
      <div class="col-md-6">
        <div class="small text-muted">Username</div>
        <div><?= htmlspecialchars((string)$student['username']) ?></div>
      </div>
      <div class="col-12">
        <div class="alert alert-info mb-0">
          Berikut detail kegiatan siswa dari <b>Hari 1</b> sampai <b>Hari <?= (int)RAMADAN_DAYS ?></b> Ramadan <?= htmlspecialchars(RAMADAN_HIJRI_YEAR) ?> H.
        </div>
      </div>
    </div>
  </div>
</div>

<div class="card shadow-sm">
  <div class="card-header brand fw-semibold">Detail Kegiatan Harian (Hari 1–<?= (int)RAMADAN_DAYS ?>)</div>
  <div class="card-body">
    <div class="table-responsive">
      <table class="table table-sm align-middle">
        <thead>
          <tr>
            <th style="width:170px">Hari Ramadan</th>
                        <th>Jurnal Ibadah</th>
            <th>Ringkasan Ceramah</th>
            <th>Kebaikan & Refleksi</th>
            <th>Laporan Tambahan</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach (($days ?? []) as $d): ?>
            <?php
              $j = $d['journal'] ?? null;
              $s = $d['sermon'] ?? null;
              $g = $d['good'] ?? null;
              $e = $d['extra'] ?? null;

              $tadarus = $j ? (int)($j['tadarus_pages'] ?? 0) : 0;
              $yes = fn($v) => ((int)$v === 1) ? '✔' : '✘';
            ?>
            <tr>
              <td class="fw-semibold">
               <?= (int)$d['day'] ?> Ramadan <?= htmlspecialchars(RAMADAN_HIJRI_YEAR) ?> H
              </td>
              
              <td>
                <div class="small">
                  Shubuh: <?= $yes($j['shubuh'] ?? 0) ?> |
                  Dzuhur: <?= $yes($j['dzuhur'] ?? 0) ?> |
                  Ashar: <?= $yes($j['ashar'] ?? 0) ?> |
                  Maghrib: <?= $yes($j['maghrib'] ?? 0) ?> |
                  Isya: <?= $yes($j['isya'] ?? 0) ?><br>
                  Tarawih: <?= $yes($j['tarawih'] ?? 0) ?> |
                  Witir: <?= $yes($j['witir'] ?? 0) ?> |
                  Puasa: <?= $yes($j['fasting'] ?? 0) ?> |
                  Tadarus (hlm): <?= $tadarus ?><br>
                  <span class="text-muted">Catatan:</span> <?= $j ? nl2br(htmlspecialchars((string)($j['notes'] ?? ''))) : '' ?>
                </div>
              </td>
              <td>
                <div class="small">
                  <div class="fw-semibold"><?= $s ? htmlspecialchars((string)($s['title'] ?? '')) : '-' ?></div>
                  <div class="text-muted"><?= $s ? nl2br(htmlspecialchars((string)($s['content'] ?? ''))) : '' ?></div>
                </div>
              </td>
              <td>
                <div class="small">
                  <span class="text-muted">Sosial:</span> <?= $g ? nl2br(htmlspecialchars((string)($g['social_activity'] ?? ''))) : '' ?><br>
                  <span class="text-muted">Refleksi:</span> <?= $g ? nl2br(htmlspecialchars((string)($g['reflection'] ?? ''))) : '' ?>
                </div>
              </td>
              <td>
                <div class="small">
                  <span class="text-muted">Pondok:</span> <?= $e ? nl2br(htmlspecialchars((string)($e['pondok_ramadhan'] ?? ''))) : '' ?><br>
                  <span class="text-muted">Ziarah:</span> <?= $e ? nl2br(htmlspecialchars((string)($e['ziarah'] ?? ''))) : '' ?><br>
                  <span class="text-muted">Idulfitri:</span> <?= $e ? nl2br(htmlspecialchars((string)($e['idulfitri_prep'] ?? ''))) : '' ?>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
          <?php if (empty($days)): ?>
            <tr><td colspan="5" class="text-center text-muted">Belum ada data.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>


<?php
  $sign = $sign ?? ['homeroom'=>null,'religion'=>[],'principal'=>null];
  $hom = $sign['homeroom'] ?? null;
  $rels = $sign['religion'] ?? [];
  $rel1 = $rels[0] ?? null;
  $kep = $sign['principal'] ?? null;
  $fmt = function($p){ if(!$p) return ''; $n = trim((string)($p['name'] ?? '')); $nip = trim((string)($p['nip'] ?? '')); return $n . ($nip!=='' ? "
NIP. {$nip}" : ''); };
?>
<div class="d-none d-print-block">
  <table class="sign-table">
    <tr>
      <td style="width:33%; text-align:center;">Wali Kelas</td>
      <td style="width:33%; text-align:center;">Guru Agama</td>
      <td style="width:33%; text-align:center;">Kepala Sekolah</td>
    </tr>
    <tr><td class="sign-space"></td><td class="sign-space"></td><td class="sign-space"></td></tr>
    <tr>
      <td style="text-align:center;"><?= nl2br(htmlspecialchars($fmt($hom))) ?></td>
      <td style="text-align:center;"><?= nl2br(htmlspecialchars($fmt($rel1))) ?></td>
      <td style="text-align:center;"><?= nl2br(htmlspecialchars($fmt($kep))) ?></td>
    </tr>
  </table>
</div>
