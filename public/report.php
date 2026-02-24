<?php
// halaman ini dipanggil dari controller PublicReportController@index
// jadi config/app.php sudah ter-load lewat public/index.php saat routing.
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Statistik Pengisian Jurnal Ramadan</title>

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
    .rank-badge{
      width:28px; height:28px; border-radius:10px;
      display:inline-flex; align-items:center; justify-content:center;
      background:var(--brand-soft); color:#0f5132; font-weight:700;
    }
    .sticky-head thead th { position: sticky; top: 0; background: #fff; z-index: 2; }
  </style>
</head>
<body>

<div class="container py-4">

  <div class="d-flex flex-wrap align-items-end justify-content-between gap-2 mb-3">
    <div>
      <div class="h3 title mb-0">📊 Statistik Pengisian Jurnal Ramadan <?= htmlspecialchars((string)RAMADAN_HIJRI_YEAR) ?> H</div>
      <div class="small-muted">SMK Negeri 2 Lumajang</div>
    </div>
    <div class="d-flex gap-2">
      <a class="btn btn-outline-secondary pill" href="<?= (defined('BASE_URL')?BASE_URL:'') ?>/login">Login</a>
      <button class="btn btn-brand pill" onclick="location.reload()">Refresh</button>
    </div>
  </div>

  <!-- FILTER: TINGKAT SAJA -->
  <div class="card shadow-soft p-3 mb-3">
    <form class="row g-2 align-items-end" onsubmit="event.preventDefault(); reloadAll();">
      <div class="col-md-4">
        <label class="form-label small mb-1">Filter Tingkat</label>
        <select id="level" class="form-select">
          <option value="">Semua Tingkat</option>
          <option value="X">X</option>
          <option value="XI">XI</option>
          <option value="XII">XII</option>
        </select>
      </div>
      <div class="col-md-3">
        <label class="form-label small mb-1">Dari Hari</label>
        <input id="from_day" type="number" min="1" max="<?= (int)RAMADAN_DAYS ?>" value="1" class="form-control">
      </div>
      <div class="col-md-3">
        <label class="form-label small mb-1">Sampai Hari</label>
        <input id="to_day" type="number" min="1" max="<?= (int)RAMADAN_DAYS ?>" value="<?= (int)RAMADAN_DAYS ?>" class="form-control">
      </div>
      <div class="col-md-2 d-grid">
        <button class="btn btn-brand">Terapkan</button>
      </div>
    </form>
    <div class="small-muted mt-2">
      Menampilkan statistik berdasarkan tingkat (X / XI / XII). Ranking & tabel rekap akan mengikuti filter ini.
    </div>
  </div>

  <!-- Top 5 + Bottom 5 -->
  <div class="row g-3 mb-3">
    <div class="col-lg-6">
      <div class="card shadow-soft p-3">
        <div class="fw-semibold mb-2">🥇 Top 5 Ranking Terajin Mengisi</div>
        <div id="topFive" class="d-flex flex-column gap-2"></div>
      </div>
    </div>
    <div class="col-lg-6">
      <div class="card shadow-soft p-3">
        <div class="fw-semibold mb-2">🥉 Top 5 Ranking Termalas Mengisi</div>
        <div id="bottomFive" class="d-flex flex-column gap-2"></div>
      </div>
    </div>
  </div>

  <!-- Ranking overall -->
  <div class="card shadow-soft p-3 mb-3">
    <div class="fw-semibold mb-2">🏆 Ranking Kelas Overall</div>
    <div id="rankingBox" class="d-flex flex-column gap-2" style="max-height: 320px; overflow:auto;"></div>
  </div>

  <!-- Grafik pengisian harian: pilih kelas dari hasil filter -->
  <div class="card shadow-soft p-3 mb-3">
    <div class="fw-semibold mb-2">📈 Grafik Pengisian Harian</div>
    <div class="row g-2 align-items-end">
      <div class="col-md-6">
        <label class="form-label small mb-1">Kelas (otomatis terfilter)</label>
        <select id="class_id" class="form-select"></select>
      </div>
      <div class="col-md-6">
        <div class="small-muted mb-2 d-flex justify-content-between flex-wrap gap-2">
          <div>Kelas : <span id="selInfo" class="fw-semibold">-</span></div>
          <div>Chart = jumlah siswa yang mengisi per hari</div>
        </div>
      </div>
    </div>
    <canvas id="lineChart" height="110" class="mt-2"></canvas>
  </div>

  <!-- Advanced row -->
  <div class="row g-3 mt-2">
    <div class="col-lg-8">
      <div class="card shadow-soft p-3">
        <div class="fw-semibold mb-2">🥇 Top 5 Kelas Ramadan</div>
        <div id="leaderboard" class="d-flex flex-column gap-2"></div>
      </div>
    </div>
    <div class="col-lg-4">
      <div class="card shadow-soft p-3">
        <div class="fw-semibold mb-2">🧩 Distribusi Kepatuhan (Top 5)</div>
        <canvas id="pieChart" height="120"></canvas>
        <div class="small-muted mt-2">Rajin ≥70%, Sedang 40–69%, Rendah &lt;40% (berdasarkan skor overall)</div>
      </div>
    </div>
  </div>

  <!-- TABEL REKAP (seperti contoh Excel) -->
  <div class="card shadow-soft p-3 mt-3">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
      <div class="fw-semibold">🧾 Rekap Pengisian Harian per Kelas</div>
      <div class="small-muted">Hari 1 s/d <?= (int)RAMADAN_DAYS ?> • Mengikuti filter tingkat</div>
    </div>
    <div class="small-muted mt-1">Kolom hari menunjukkan jumlah siswa yang mengisi jurnal pada hari tersebut.</div>

    <!-- Tinggi tabel diperpanjang agar nyaman minimal ±15 kelas -->
    <div class="table-responsive sticky-head mt-3" style="min-height:520px; max-height:620px; overflow:auto;">
      <table class="table table-sm table-bordered align-middle mb-0">
        <thead id="rekapHead"></thead>
        <tbody id="rekapBody"></tbody>
      </table>
    </div>
  </div>

  <div class="text-center small-muted mt-4">
    © Tim IT SMKN 2 Lumajang 2026
  </div>
</div>

<script>
const BASE_URL = "<?= (defined('BASE_URL') ? BASE_URL : '') ?>";
const DAYS = <?= (int)RAMADAN_DAYS ?>;

let lineChart, pieChart;

// cache agar dropdown tidak rebuild saat klik kelas
let _lastLevel = null;
let _lastFromDay = null;
let _lastToDay = null;

function esc(s){
  return (s ?? '').toString().replace(/[&<>"']/g, m => ({
    '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'
  }[m]));
}

async function fetchJSON(url){
  const r = await fetch(url, { cache: 'no-store' });
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
  if ((ranking || []).length === 0) box.innerHTML = `<div class="text-muted">Tidak ada data.</div>`;
}

function renderTopBottom(ranking){
  const sorted = [...(ranking || [])].sort((a,b)=> (Number(b.score||0) - Number(a.score||0)));
  const top = sorted.slice(0,5);
  const bottom = sorted.slice(-5).reverse();

  const topWrap = document.getElementById('topFive');
  const botWrap = document.getElementById('bottomFive');

  topWrap.innerHTML = '';
  botWrap.innerHTML = '';

  top.forEach((c,i)=>{
    const div = document.createElement('div');
    div.className = "d-flex justify-content-between border rounded-3 px-3 py-2 bg-white";
    div.innerHTML = `
      <div><b>#${i+1}</b> ${esc(c.label)}</div>
      <div class="fw-semibold text-success">${(Number(c.score||0)*100).toFixed(1)}%</div>
    `;
    topWrap.appendChild(div);
  });

  bottom.forEach((c,i)=>{
    const div = document.createElement('div');
    div.className = "d-flex justify-content-between border rounded-3 px-3 py-2 bg-white";
    div.innerHTML = `
      <div><b>#${i+1}</b> ${esc(c.label)}</div>
      <div class="fw-semibold text-danger">${(Number(c.score||0)*100).toFixed(1)}%</div>
    `;
    botWrap.appendChild(div);
  });

  if (top.length === 0) topWrap.innerHTML = `<div class="text-muted">Tidak ada data.</div>`;
  if (bottom.length === 0) botWrap.innerHTML = `<div class="text-muted">Tidak ada data.</div>`;
}

async function loadAdvanced(){
  const adv = await fetchJSON(`${BASE_URL}/public/report/advanced`);

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
  if ((adv.leaderboard || []).length === 0) wrap.innerHTML = `<div class="text-muted">Tidak ada data.</div>`;

  const scores = (adv.leaderboard || []).map(x => Number(x.score||0));
  const rajin = scores.filter(s => s >= 0.7).length;
  const sedang = scores.filter(s => s >= 0.4 && s < 0.7).length;
  const rendah = scores.filter(s => s < 0.4).length;

  const ctx = document.getElementById('pieChart');
  if (pieChart) pieChart.destroy();
  pieChart = new Chart(ctx, {
    type: 'pie',
    data: { labels: ['Rajin', 'Sedang', 'Rendah'], datasets: [{ data: [rajin, sedang, rendah] }] }
  });
}

/** =============================
 *  1) BUILD DROPDOWN KELAS (hanya saat filter berubah)
 *  ============================= */
async function buildClassDropdown(){
  const level = (document.getElementById('level')?.value || '').trim();
  const fromDay = Number(document.getElementById('from_day').value || 1);
  const toDay = Number(document.getElementById('to_day').value || DAYS);

  // kalau filter sama persis, jangan rebuild
  if (_lastLevel === level && _lastFromDay === fromDay && _lastToDay === toDay) return;

  _lastLevel = level;
  _lastFromDay = fromDay;
  _lastToDay = toDay;

  // ambil data dasar dari endpoint existing
  const data = await fetchJSON(`${BASE_URL}/public/report/data?class_id=&from_day=${encodeURIComponent(fromDay)}&to_day=${encodeURIComponent(toDay)}`);

  // filter classes by tingkat (client-side)
  const classesFiltered = (data.classes || []).filter(c => {
    if (!level) return true;
    const nm = (c.name || '').trim();
    return nm === level || nm.startsWith(level + ' ');
  });

  // rebuild dropdown kelas (sekali per perubahan filter)
  const sel = document.getElementById('class_id');
  const previous = sel.value; // coba pertahankan pilihan sebelumnya
  sel.innerHTML = '';

  classesFiltered.forEach(c => {
    const opt = document.createElement('option');
    opt.value = c.id;
    opt.textContent = `${c.name} ${c.year || ''}`.trim();
    sel.appendChild(opt);
  });

  // set value: jika pilihan sebelumnya masih ada, pakai itu, kalau tidak pakai pertama
  const stillExists = Array.from(sel.options).some(o => o.value === previous);
  if (stillExists) sel.value = previous;
  else if (sel.options.length > 0) sel.value = sel.options[0].value;

  // ranking ikut filter tingkat
  const rankingFiltered = (data.ranking || []).filter(r => {
    if (!level) return true;
    const label = (r.label || '').trim();
    return label === level || label.startsWith(level + ' ');
  });

  renderRanking(rankingFiltered);
  renderTopBottom(rankingFiltered);

  // setelah dropdown siap, load chart sesuai kelas terpilih
  await loadLineChart();
  await loadRekapTable(); // kalau kamu pakai tabel rekap
  await loadAdvanced();   // tetap
}

/** =============================
 *  2) LOAD CHART GARIS (jalan saat pilih kelas)
 *  ============================= */
async function loadLineChart(){
  const fromDay = Number(document.getElementById('from_day').value || 1);
  const toDay = Number(document.getElementById('to_day').value || DAYS);

  const sel = document.getElementById('class_id');
  const classId = sel.value || '';

  const selectedOpt = sel.options[sel.selectedIndex];
  document.getElementById('selInfo').textContent = selectedOpt ? selectedOpt.text : '-';

  if (!classId) {
    if (lineChart) lineChart.destroy();
    return;
  }

  const chartData = await fetchJSON(`${BASE_URL}/public/report/data?class_id=${encodeURIComponent(classId)}&from_day=${encodeURIComponent(fromDay)}&to_day=${encodeURIComponent(toDay)}`);

  const labels = (chartData.day_data || []).map(x => `Hari ${x.day}`);
  const counts = (chartData.day_data || []).map(x => Number(x.count || 0));

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
      scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }
    }
  });
}

/** =============================
 *  OPTIONAL: TABEL REKAP
 *  (kalau kamu sudah bikin /public/report/rekap)
 *  ============================= */
function renderRekapTable(payload){
  const days = Number(payload?.days || <?= (int)RAMADAN_DAYS ?>);
  const rows = payload?.rows || [];

  const head = document.getElementById('rekapHead');
  const body = document.getElementById('rekapBody');

  // HEADER
  let h = `<tr>
    <th rowspan="2" class="text-center align-middle" style="min-width:60px">No</th>
    <th rowspan="2" class="align-middle" style="min-width:220px">Kelas</th>
    <th rowspan="2" class="text-center align-middle" style="min-width:120px">Jumlah Siswa</th>
    <th colspan="${days}" class="text-center">Siswa Mengisi Jurnal Hari ke</th>
  </tr>
  <tr>`;
  for(let d=1; d<=days; d++){
    h += `<th class="text-center" style="min-width:44px">${d}</th>`;
  }
  h += `</tr>`;
  head.innerHTML = h;

  // BODY
  body.innerHTML = '';
  rows.forEach(r => {
    const tr = document.createElement('tr');
    const total = Number(r.total_students || 0);
    const dayCounts = r.day_counts || [];

    let tds = `
      <td class="text-center">${Number(r.no||0)}</td>
      <td>${esc(r.kelas)}</td>
      <td class="text-center fw-semibold">${total}</td>
    `;

    for(let i=0; i<days; i++){
      const val = Number(dayCounts[i] || 0);

      // Tentukan warna
      let bgClass = '';
      if (val === 0) {
        bgClass = 'bg-danger-subtle text-danger fw-semibold';
      } else if (val === total && total > 0) {
        bgClass = 'bg-success-subtle text-success fw-semibold';
      } else {
        bgClass = 'bg-warning-subtle text-dark fw-semibold';
      }

      tds += `<td class="text-center ${bgClass}">${val}</td>`;
    }

    tr.innerHTML = tds;
    body.appendChild(tr);
  });

  if(rows.length === 0){
    body.innerHTML = `<tr><td colspan="${3 + days}" class="text-center text-muted">Tidak ada data.</td></tr>`;
  }
}

async function loadRekapTable(){
  // kalau belum bikin endpoint /public/report/rekap, boleh kamu comment pemanggilannya di buildClassDropdown()
  const level = (document.getElementById('level')?.value || '').trim();
  const payload = await fetchJSON(`${BASE_URL}/public/report/rekap?level=${encodeURIComponent(level)}`);
  renderRekapTable(payload);
}

/** =============================
 *  EVENT LISTENERS
 *  ============================= */

// saat dropdown kelas berubah -> HANYA reload chart garis
document.getElementById('class_id').addEventListener('change', async () => {
  await loadLineChart();
});

// saat filter (tingkat/from/to) disubmit -> rebuild dropdown + ranking + chart
async function reloadAll(){
  await buildClassDropdown();
}

reloadAll();
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
