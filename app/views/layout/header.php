<?php
use App\Core\Auth;
$user = Auth::user();
function u(string $path): string {
  $path = '/' . ltrim($path, '/');
  $base = defined('BASE_URL') ? BASE_URL : '';
  return $base . $path;
}
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= htmlspecialchars(APP_NAME) ?></title>

  <!-- Bootstrap (CDN) -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

  <style>
  :root{
    --brand: <?= APP_BRAND_COLOR ?>;
    --brand-soft: <?= APP_BRAND_SOFT ?>;
  }

  .navbar { background: var(--brand) !important; }
  .btn-brand { background: var(--brand) !important; color:#fff !important; border: none; }
  .badge-brand { background: var(--brand) !important; }
  .card-header.brand { background: var(--brand-soft); }
  a { color: var(--brand); }

  /* ===========================
     PRINT SETTING (A4 LANDSCAPE)
     Resmi: Kop + tanda tangan
  ============================ */
  @page { size: A4 landscape; margin: 10mm; }

  .print-header { display:none; }

  @media print {
    html, body { background:#fff !important; font-size: 11px !important; }
    .print-header { display:block !important; }

    .navbar, .btn, form, .alert, .d-print-none { display:none !important; }

    main.container { margin:0 !important; padding:0 !important; max-width:100% !important; }
    .card { box-shadow:none !important; border:none !important; }
    .card-header { background:none !important; border:none !important; }

    .table-responsive { overflow: visible !important; }

    table { width:100% !important; border-collapse: collapse !important; font-size:10.5px !important; page-break-inside:auto !important; }
    thead { display: table-header-group; }
    tr { break-inside: avoid; page-break-inside: avoid; }
    th, td { padding: 4px !important; vertical-align: top !important; }

    /* kop sekolah */
    .kop-wrap { text-align:center; line-height:1.2; }
    .kop-line1, .kop-line2 { font-weight:700; font-size:12px; }
    .kop-school { font-weight:800; font-size:14px; }
    .kop-small { font-size:10px; }
    .kop-divider { border-top:2px solid #000; margin: 6px 0 8px; }

    .kop-logo { width: 70px; height:auto; }
    .kop-grid { display:flex; align-items:center; gap:10px; }
    .kop-grid .kop-text { flex:1; }

    /* area tanda tangan */
    .sign-table { width:100%; margin-top: 14px; font-size: 11px; }
    .sign-table td { padding: 2px 6px !important; }
    .sign-space { height: 60px; }
    .print-date { text-align:right; margin-top: 6px; font-size: 11px; }
  }
</style>

</head>
<body class="bg-light">
<nav class="navbar navbar-expand-lg navbar-dark">
  <div class="container">
    <a class="navbar-brand fw-semibold" href="<?= u('/dashboard') ?>"><?= htmlspecialchars(APP_NAME) ?></a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#nav">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div id="nav" class="collapse navbar-collapse">
      <ul class="navbar-nav me-auto">
        <?php if ($user): ?>
          <?php if (\App\Core\Auth::hasRole('student')): ?>
            <li class="nav-item"><a class="nav-link" href="<?= u('/student/journal') ?>">Isi Buku</a></li>
            <li class="nav-item"><a class="nav-link" href="<?= u('/student/report') ?>">Report</a></li>
          <?php endif; ?>
          <?php if (\App\Core\Auth::hasRole('homeroom')): ?>
            <li class="nav-item"><a class="nav-link" href="<?= u('/homeroom/class') ?>">Rekap Kelas</a></li>
          <?php endif; ?>
          <?php if (\App\Core\Auth::hasRole('religion_teacher')): ?>
            <li class="nav-item"><a class="nav-link" href="<?= u('/religion/class') ?>">Rekap Kelas</a></li>
          <?php endif; ?>
          <?php if (\App\Core\Auth::hasRole('principal')): ?>
            <li class="nav-item"><a class="nav-link" href="<?= u('/principal/class') ?>">Rekap Kelas</a></li>
          <?php endif; ?>
          <?php if (\App\Core\Auth::hasRole('admin')): ?>
            <li class="nav-item"><a class="nav-link" href="<?= u('/admin') ?>">Admin</a></li>
          <?php endif; ?>
        <li class="nav-item"><a class="nav-link" href="<?= u('/material') ?>">Materi & Doa</a></li>
        <?php endif; ?>
      </ul>
      <ul class="navbar-nav">
        <?php if ($user): ?>
          <li class="nav-item"><span class="navbar-text me-3">Halo, <?= htmlspecialchars($user['name']) ?> </span></li>
          <li class="nav-item"><a class="nav-link" href="<?= u('/logout') ?>">Logout</a></li>
        <?php else: ?>
          <li class="nav-item"><a class="nav-link" href="<?= u('/public/student-lookup') ?>">Cari NISN</a></li>
           <li class="nav-item"><a class="nav-link" href="<?= u('/public/report') ?>">Statistik</a></li>
          <li class="nav-item"><a class="nav-link" href="<?= u('/login') ?>">Login</a></li>
        <?php endif; ?>
      </ul>
    </div>
  </div>
</nav>

<main class="container my-4">
