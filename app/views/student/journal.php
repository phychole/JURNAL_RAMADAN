<?php
function checked($v){ return !empty($v) ? 'checked' : ''; }

// fallback aman kalau variabel belum dikirim
$maxDayAllowed = (int)($maxDayAllowed ?? (int)$day ?? 1);
if ($maxDayAllowed < 1) $maxDayAllowed = 1;
?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <div>
    <h4 class="mb-0">Isi Buku Ramadan</h4>
    <div class="small text-muted">
      Hari Ramadan <?= (int)$day ?> • Tanggal: <?= htmlspecialchars((string)$date) ?>
      • <span class="fw-semibold">Hari ini: Hari Ramadan <?= (int)$maxDayAllowed ?></span>
    </div>
    <?php if ($profile): ?>
      <div class="text-muted small">NIS: <?= htmlspecialchars((string)$profile['nis']) ?> • Kelas: <?= htmlspecialchars((string)($profile['class_name'].' '.$profile['class_year'])) ?></div>
    <?php endif; ?>
    <?php if ((int)$maxDayAllowed < (int)RAMADAN_DAYS): ?>
      <div class="small text-muted">⚠️ Hari setelah Ramadan <?= (int)$maxDayAllowed ?> masih dikunci agar tidak bisa mengisi sebelum waktunya.</div>
    <?php endif; ?>
  </div>
  <div>
    <a class="btn btn-outline-success" href="<?= u('/student/report') ?>">Lihat Report</a>
  </div>
</div>

<?php if (!empty($success)): ?><div class="alert alert-success"><?= htmlspecialchars((string)$success) ?></div><?php endif; ?>
<?php if (!empty($error)): ?><div class="alert alert-danger"><?= htmlspecialchars((string)$error) ?></div><?php endif; ?>

<div class="card shadow-sm">
  <div class="card-header brand d-flex justify-content-between align-items-center">
    <div class="fw-semibold">Pengisian Harian</div>

    <form method="get" action="<?= u('/student/journal') ?>" class="d-flex gap-2 align-items-center">
      <select name="day" class="form-select form-select-sm">
        <?php for($d=1; $d<= (int)RAMADAN_DAYS; $d++): ?>
          <?php
            $disabled = ($d > $maxDayAllowed) ? 'disabled' : '';
            $selected = ((int)$day === $d) ? 'selected' : '';
            $label = "Hari Ramadan {$d}";
            if ($d > $maxDayAllowed) $label .= " (terkunci)";
          ?>
          <option value="<?= $d ?>" <?= $selected ?> <?= $disabled ?>><?= htmlspecialchars($label) ?></option>
        <?php endfor; ?>
      </select>
      <button class="btn btn-sm btn-brand">Buka</button>
    </form>
  </div>

  <div class="card-body">
    <form method="post" action="<?= u('/student/journal/save') ?>">
      <input type="hidden" name="day" value="<?= (int)$day ?>">

      <ul class="nav nav-tabs" role="tablist">
        <li class="nav-item" role="presentation"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-jurnal" type="button">Jurnal Ibadah</button></li>
        <li class="nav-item" role="presentation"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-ceramah" type="button">Ringkasan Ceramah</button></li>
        <li class="nav-item" role="presentation"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-kebaikan" type="button">Kebaikan & Refleksi</button></li>
        <li class="nav-item" role="presentation"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-tambahan" type="button">Laporan Tambahan</button></li>
      </ul>

      <div class="tab-content border border-top-0 p-3">
        <div class="tab-pane fade show active" id="tab-jurnal">
          <div class="row g-3">
            <div class="col-md-6">
              <div class="fw-semibold mb-2">Salat 5 Waktu</div>
              <div class="form-check"><input class="form-check-input" type="checkbox" name="shubuh" value="1" <?= checked($journal['shubuh'] ?? 0) ?>><label class="form-check-label">Subuh</label></div>
              <div class="form-check"><input class="form-check-input" type="checkbox" name="dzuhur" value="1" <?= checked($journal['dzuhur'] ?? 0) ?>><label class="form-check-label">Dzuhur</label></div>
              <div class="form-check"><input class="form-check-input" type="checkbox" name="ashar" value="1" <?= checked($journal['ashar'] ?? 0) ?>><label class="form-check-label">Ashar</label></div>
              <div class="form-check"><input class="form-check-input" type="checkbox" name="maghrib" value="1" <?= checked($journal['maghrib'] ?? 0) ?>><label class="form-check-label">Maghrib</label></div>
              <div class="form-check"><input class="form-check-input" type="checkbox" name="isya" value="1" <?= checked($journal['isya'] ?? 0) ?>><label class="form-check-label">Isya</label></div>
            </div>

            <div class="col-md-6">
              <div class="fw-semibold mb-2">Ibadah Ramadan</div>
              <div class="form-check"><input class="form-check-input" type="checkbox" name="fasting" value="1" <?= checked($journal['fasting'] ?? 0) ?>><label class="form-check-label">Puasa</label></div>
              <div class="form-check"><input class="form-check-input" type="checkbox" name="tarawih" value="1" <?= checked($journal['tarawih'] ?? 0) ?>><label class="form-check-label">Tarawih</label></div>
              <div class="form-check"><input class="form-check-input" type="checkbox" name="witir" value="1" <?= checked($journal['witir'] ?? 0) ?>><label class="form-check-label">Witir</label></div>
              <div class="mt-2">
                <label class="form-label">Tadarus (jumlah halaman)</label>
                <input class="form-control" type="number" min="0" name="tadarus_pages" value="<?= htmlspecialchars((string)($journal['tadarus_pages'] ?? 0)) ?>">
              </div>
            </div>

            <div class="col-12">
              <label class="form-label">Catatan singkat</label>
              <input class="form-control" name="notes" value="<?= htmlspecialchars((string)($journal['notes'] ?? '')) ?>">
            </div>
          </div>
        </div>

        <div class="tab-pane fade" id="tab-ceramah">
          <div class="mb-3">
            <label class="form-label">Judul / Tema</label>
            <input class="form-control" name="title" value="<?= htmlspecialchars((string)($sermon['title'] ?? '')) ?>">
          </div>
          <div class="mb-3">
            <label class="form-label">Poin penting ceramah / kultum</label>
            <textarea class="form-control" name="content" rows="6"><?= htmlspecialchars((string)($sermon['content'] ?? '')) ?></textarea>
          </div>
        </div>

        <div class="tab-pane fade" id="tab-kebaikan">
          <div class="row g-3">
            <div class="col-12">
              <label class="form-label">Kegiatan sosial / kebaikan</label>
              <textarea class="form-control" name="social_activity" rows="4"><?= htmlspecialchars((string)($good['social_activity'] ?? '')) ?></textarea>
            </div>
            <div class="col-12">
              <label class="form-label">Refleksi harian</label>
              <textarea class="form-control" name="reflection" rows="4"><?= htmlspecialchars((string)($good['reflection'] ?? '')) ?></textarea>
            </div>
          </div>
        </div>

        <div class="tab-pane fade" id="tab-tambahan">
          <div class="mb-3">
            <label class="form-label">Kegiatan Pondok Ramadan</label>
            <textarea class="form-control" name="pondok_ramadhan" rows="3"><?= htmlspecialchars((string)($extra['pondok_ramadhan'] ?? '')) ?></textarea>
          </div>
          <div class="mb-3">
            <label class="form-label">Ziarah</label>
            <textarea class="form-control" name="ziarah" rows="3"><?= htmlspecialchars((string)($extra['ziarah'] ?? '')) ?></textarea>
          </div>
          <div class="mb-3">
            <label class="form-label">Persiapan Idulfitri</label>
            <textarea class="form-control" name="idulfitri_prep" rows="3"><?= htmlspecialchars((string)($extra['idulfitri_prep'] ?? '')) ?></textarea>
          </div>
        </div>
      </div>

      <div class="d-flex justify-content-end mt-3">
        <button class="btn btn-brand">Simpan</button>
      </div>
    </form>
  </div>
</div>
