<?php
// halaman ini dipanggil dari controller PublicReportController@index
// jadi config/app.php sudah ter-load lewat public/index.php saat routing.
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Statistik Pengisian Ramadan</title>

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>

  <style>
    :root{
      --brand: <?= defined('APP_BRAND_COLOR') ? APP_BRAND_COLOR : '#198754' ?>;
      --brand-soft: <?= defined('APP_BRAND_SOFT') ? APP_BRAND_SOFT : '#d1e7dd' ?>;
    }
    body { background: #f5f7fb; }
    .shadow-soft { box-shadow: 0 10px 30px rgba(0,0,0,.06); border: 0; border-radius: 18px; }
    .pill { border-radius: 999px; }
    .kpi { background: linear-gradient(135deg, var(--brand-soft), #fff); }
    .title { letter-spacing:.2px; }
    .text-brand { color: var(--brand); }
    .btn-brand { background: var(--brand); color:#fff; border:0; }
    .btn-brand:hover{ filter: brightness(.95); color:#fff; }
    .small-muted{ color:#6c757d; font-size:.9rem; }
    .rank-badge{ width:28px; height:28px; border-radius:10px; display:inline-flex; align-items:center; justify-content:center; background:var(--brand-soft); color:#0f5132; font-weight:700; }
  </style>
</head>
<body>

<div class="container py-4">

  <div class="d-flex flex-wrap align-items-end justify-content-between gap-2 mb-3">
    <div>
      <div class="h3 title mb-0">📊 Statistik Pengisian Ramadan</div>
      <div class="small-muted">Hari 1 s/d <?= (int)RAMADAN_DAYS ?> Ramadan <?= htmlspecialchars((string)RAMADAN_HIJRI_YEAR) ?> H</div>
    </div>
    <div class="d-flex gap-2">
      <a class="btn btn-outline-secondary pill" href="<?= (defined('BASE_URL')?BASE_URL:'') ?>/login">Login</a>
      <button class="btn btn-brand pill" onclick="location.reload()">Refresh</button>
    </div>
  </div>

  <!-- Filters -->
  <div class="card shadow-soft p-3 mb-3">
    <form class="row g-2 align-items-end" onsubmit="event.preventDefault(); reloadData();">
      <div class="col-md-5">
        <label class="form-label small mb-1">Kelas</label>
        <select id="class_id" class="form-select"></select>
      </div>
      <div class="col-md-3">
        <label class="form-label small mb-1">Dari Hari</label>
        <input id="from_day" type="number" min="1" max="<?= (int)RAMADAN_DAYS ?>" value="1" class="form-control">
      </div>
      <div class="col-md-3">
        <label class="form-label small mb-1">Sampai Hari</label>
        <input id="to_day" type="number" min="1" max="<?= (int)RAMADAN_DAYS ?>" value="<?= (int)RAMADAN_DAYS ?>" class="form-control">
      </div>
      <div class="col-md-1 d-grid">
        <button class="btn btn-brand">Go</button>
      </div>
    </form>
  </div>

  <!-- KPI -->
  <div class="row g-3 mb-3">
    <div class="col-lg-4">
      <div class="card shadow-soft p-3 kpi">
        <div class="small-muted">Kelas Terbaik (Overall)</div>
        <div id="bestClass" class="h5 mb-0">-</div>
        <div id="bestScore" class="small-muted">-</div>
      </div>
    </div>
    <div class="col-lg-4">
      <div class="card shadow-soft p-3 kpi">
        <div class="small-muted">Kelas Termalas (Overall)</div>
        <div id="worstClass" class="h5 mb-0">-</div>
        <div id="worstScore" class="small-muted">-</div>
      </div>
    </div>
    <div class="col-lg-4">
      <div class="card shadow-soft p-3 kpi">
        <div class="small-muted">Kelas Dipilih (Rentang Hari)</div>
        <div id="selInfo" class="h5 mb-0">-</div>
        <div class="small-muted">Chart = jumlah siswa yang mengisi per hari</div>
      </div>
    </div>
  </div>

  <!-- Charts -->
  <div class="row g-3">
    <div class="col-lg-8">
      <div class="card shadow-soft p-3">
        <div class="fw-semibold mb-2">📈 Pengisian Harian (Hari 1–30)</div>
        <canvas id="lineChart" height="110"></canvas>
      </div>
    </div>
    <div class="col-lg-4">
      <div class="card shadow-soft p-3">
        <div class="fw-semibold mb-2">🏆 Ranking Kelas Overall</div>
        <div id="rankingBox" class="d-flex flex-column gap-2" style="max-height: 320px; overflow:auto;"></div>
      </div>
    </div>
  </div>

  <!-- Advanced row -->
  <div class="row g-3 mt-3">
    <div class="col-lg-6">
      <div class="card shadow-soft p-3">
        <div class="fw-semibold mb-2">🥇 Top 5 Kelas Ramadan (Overall)</div>
        <div id="leaderboard" class="d-flex flex-column gap-2"></div>
      </div>
    </div>

    <div class="col-lg-6">
      <div class="card shadow-soft p-3">
        <div class="fw-semibold mb-2">🧩 Distribusi Kepatuhan (Top 5)</div>
        <canvas id="pieChart" height="120"></canvas>
        <div class="small-muted mt-2">Rajin ≥70%, Sedang 40–69%, Rendah &lt;40% (berdasarkan skor overall)</div>
      </div>
    </div>
  </div>

  <div class="text-center small-muted mt-4">
    © Tim IT SMKN 2 Lumajang 2026
  </div>
</div>

<script>
const BASE_URL = "<?= (defined('BASE_URL') ? BASE_URL : '') ?>";

let lineChart, pieChart;

function esc(s){ return (s ?? '').toString().replace(/[&<>"']/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m])); }

async function fetchJSON(url){
  const r = await fetch(url);
  return await r.json();
}

function renderRanking(ranking){
  const box = document.getElementById('rankingBox');
  box.innerHTML = '';
  (ranking || []).forEach((c, idx) => {
    const div = document.createElement('div');
    div.className = "d-flex justify-content-between align-items-center border rounded-3 px-3 py-2 bg-white";
    div.innerHTML = `
      <div class="d-flex align-items-center gap-2">
        <span class="rank-badge">${idx+1}</span>
        <div>
          <div class="fw-semibold">${esc(c.label)}</div>
          <div class="small-muted">${(c.total_students ?? 0)} siswa</div>
        </div>
      </div>
      <div class="fw-semibold text-brand">${(Number(c.score||0)*100).toFixed(1)}%</div>
    `;
    box.appendChild(div);
  });
}

async function loadAdvanced(){
  const adv = await fetchJSON(`${BASE_URL}/public/report/advanced`);

  // Leaderboard top 5
  const wrap = document.getElementById('leaderboard');
  wrap.innerHTML = '';
  (adv.leaderboard || []).forEach((c, i) => {
    const div = document.createElement('div');
    div.className = "d-flex justify-content-between border rounded-3 px-3 py-2 bg-white";
    div.innerHTML = `
      <div><b>#${i+1}</b> ${esc(c.label)}</div>
      <div class="fw-semibold">${(Number(c.score||0)*100).toFixed(1)}%</div>
    `;
    wrap.appendChild(div);
  });

  // Pie distribution (top 5)
  const scores = (adv.leaderboard || []).map(x => Number(x.score||0));
  const rajin = scores.filter(s => s >= 0.7).length;
  const sedang = scores.filter(s => s >= 0.4 && s < 0.7).length;
  const rendah = scores.filter(s => s < 0.4).length;

  const ctx = document.getElementById('pieChart');
  if (pieChart) pieChart.destroy();
  pieChart = new Chart(ctx, {
    type: 'pie',
    data: {
      labels: ['Rajin', 'Sedang', 'Rendah'],
      datasets: [{ data: [rajin, sedang, rendah] }]
    }
  });
}

async function reloadData(){
  const classId = document.getElementById('class_id').value || '';
  const fromDay = document.getElementById('from_day').value || 1;
  const toDay = document.getElementById('to_day').value || <?= (int)RAMADAN_DAYS ?>;

  const data = await fetchJSON(`${BASE_URL}/public/report/data?class_id=${encodeURIComponent(classId)}&from_day=${encodeURIComponent(fromDay)}&to_day=${encodeURIComponent(toDay)}`);

  // fill class dropdown first time
  const sel = document.getElementById('class_id');
  if (sel.options.length === 0) {
    (data.classes || []).forEach(c => {
      const opt = document.createElement('option');
      opt.value = c.id;
      opt.textContent = `${c.name} ${c.year}`;
      sel.appendChild(opt);
    });
    sel.value = data.selected_class_id;
  }

  // KPI overall best/worst
  if (data.overall_best) {
    document.getElementById('bestClass').textContent = data.overall_best.label || '-';
    document.getElementById('bestScore').textContent = (Number(data.overall_best.score||0)*100).toFixed(1) + '%';
  }
  if (data.overall_worst) {
    document.getElementById('worstClass').textContent = data.overall_worst.label || '-';
    document.getElementById('worstScore').textContent = (Number(data.overall_worst.score||0)*100).toFixed(1) + '%';
  }

  // selected info
  const selectedOpt = sel.options[sel.selectedIndex];
  document.getElementById('selInfo').textContent = selectedOpt ? selectedOpt.text : '-';

  // ranking overall tetap ada
  renderRanking(data.ranking || []);

  // line chart
  const labels = (data.day_data || []).map(x => `Hari ${x.day}`);
  const counts = (data.day_data || []).map(x => Number(x.count || 0));

  const ctx = document.getElementById('lineChart');
  if (lineChart) lineChart.destroy();
  lineChart = new Chart(ctx, {
    type: 'line',
    data: {
      labels,
      datasets: [{
        label: 'Jumlah siswa mengisi',
        data: counts,
        tension: 0.25
      }]
    },
    options: {
      responsive: true,
      scales: {
        y: { beginAtZero: true, ticks: { precision: 0 } }
      }
    }
  });

  // advanced
  await loadAdvanced();
}

reloadData();
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>