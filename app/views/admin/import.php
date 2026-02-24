<h4 class="mb-3">Import Data</h4>

<?php if (!empty($success)): ?><div class="alert alert-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>
<?php if (!empty($error)): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>
<?php if (!empty($info)): ?><div class="alert alert-info"><?= htmlspecialchars($info) ?></div><?php endif; ?>

<div class="card shadow-sm">
  <div class="card-header brand fw-semibold">Upload File Excel/CSV</div>
  <div class="card-body">
    <form method="post" action="<?= u('/admin/import') ?>" enctype="multipart/form-data" class="row g-3">
      <div class="col-md-4">
        <label class="form-label">Jenis Import</label>
        <select name="import_type" class="form-select" required>
          <option value="students">Siswa</option>
          <option value="classes">Kelas</option>
          <option value="teachers">Guru (Wali Kelas / Guru Agama)</option>
        </select>
        <div class="form-text">Pisahkan CSV boleh <code>,</code> atau <code>;</code>.</div>
        <div class="form-text">Jika file CSV sekolah kamu kolom <b>kelas</b> berisi angka (contoh: 10) dan kolom <b>tahun</b> berisi nama kelas (contoh: X BUSANA 1), sistem akan otomatis menyesuaikan (swap) dan memakai tahun berjalan.</div>
        <div class="form-text"><b>Catatan:</b> Import bersifat <i>upsert</i> (username sudah ada akan diupdate).</div>
      </div>

      <div class="col-md-8">
        <label class="form-label">File</label>
        <input type="file" name="file" class="form-control" accept=".xlsx,.xls,.csv" required>
        <div class="form-text">
          Jika <b>XLSX</b> tidak terbaca, jalankan <code>composer install</code> (PhpSpreadsheet). Alternatif: simpan sebagai <b>CSV</b>.
        </div>
        <div class="form-text">Jika file CSV sekolah kamu kolom <b>kelas</b> berisi angka (contoh: 10) dan kolom <b>tahun</b> berisi nama kelas (contoh: X BUSANA 1), sistem akan otomatis menyesuaikan (swap) dan memakai tahun berjalan.</div>
        <div class="form-text"><b>Catatan:</b> Import bersifat <i>upsert</i> (username sudah ada akan diupdate).</div>
      </div>

      <div class="col-12">
        <button class="btn btn-brand">Import</button>
        <a class="btn btn-outline-success" href="<?= u('/admin') ?>">Kembali</a>
      </div>
    </form>
  </div>
</div>

<div class="row g-3 mt-1">
  <div class="col-lg-4">
    <div class="card shadow-sm">
      <div class="card-header brand fw-semibold">Contoh CSV - Siswa</div>
      <div class="card-body">
<pre class="mb-0">nis,nama,username,password,kelas,tahun,gender,phone,address
12345,Ahmad,ahmad.7a,123456,7A,2026,L,08123456789,Jl. Mawar
12346,Siti,siti.7a,123456,7A,2026,P,08123456780,Jl. Melati</pre>
      </div>
    </div>
  </div>

  <div class="col-lg-4">
    <div class="card shadow-sm">
      <div class="card-header brand fw-semibold">Contoh CSV - Kelas</div>
      <div class="card-body">
<pre class="mb-0">kelas,tahun
7A,2026
7B,2026
8A,2026</pre>
      </div>
    </div>
  </div>

  <div class="col-lg-4">
    <div class="card shadow-sm">
      <div class="card-header brand fw-semibold">Contoh CSV - Guru</div>
      <div class="card-body">
<pre class="mb-0">nama,username,nip,password,roles
Bpk Ali,ali.wk,1987654321,123456,homeroom
Ibu Sari,sari.agama,1990123456,123456,religion_teacher
Bpk Kepala Sekolah,kepsek,196501011990031001,123456,principal</pre>
        <div class="form-text mt-2">
          Roles yang diterima: <code>homeroom</code> (wali kelas), <code>religion_teacher</code> (guru agama), <code>principal</code> (kepala sekolah), <code>admin</code>.
        </div>
        <div class="form-text">Jika file CSV sekolah kamu kolom <b>kelas</b> berisi angka (contoh: 10) dan kolom <b>tahun</b> berisi nama kelas (contoh: X BUSANA 1), sistem akan otomatis menyesuaikan (swap) dan memakai tahun berjalan.</div>
        <div class="form-text"><b>Catatan:</b> Import bersifat <i>upsert</i> (username sudah ada akan diupdate).</div>
      </div>
    </div>
  </div>
</div>
