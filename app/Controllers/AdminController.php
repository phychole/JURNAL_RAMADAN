<?php
namespace App\Controllers;

use App\Core\BaseController;
use App\Core\Auth;
use App\Models\ClassRoom;
use App\Models\User;
use App\Models\StudentProfile;

class AdminController extends BaseController {

  public function index(): void {
    Auth::requireRole(['admin']);
    $this->view('admin/index', [
      'info' => $this->getFlash('info'),
      'success' => $this->getFlash('success'),
      'error' => $this->getFlash('error'),
    ]);
  }

  public function classes(): void {
    Auth::requireRole(['admin']);
    $this->view('admin/classes', [
      'classes' => ClassRoom::all(),
      'success' => $this->getFlash('success'),
      'error' => $this->getFlash('error'),
    ]);
  }

  public function createClass(): void {
    Auth::requireRole(['admin']);
    $name = trim($_POST['name'] ?? '');
    $year = (int)($_POST['year'] ?? 0);

    if ($name === '' || $year <= 0) {
      $this->flash('error', 'Nama kelas dan tahun wajib.');
      $this->redirect('/admin/classes');
    }

    try {
      ClassRoom::create($name, $year);
      $this->flash('success', 'Kelas berhasil ditambahkan.');
    } catch (\Throwable $e) {
      $this->flash('error', 'Gagal menambahkan kelas: ' . $e->getMessage());
    }

    $this->redirect('/admin/classes');
  }

  /**
   * Hapus kelas:
   * - normal: hanya jika tidak dipakai siswa/mapping
   * - force=1: hapus mapping wali kelas & guru agama. Jika masih ada siswa, wajib pilih move_to_class_id untuk memindahkan siswa.
   */
  public function deleteClass(): void {
    Auth::requireRole(['admin']);

    $id = (int)($_POST['id'] ?? 0);
    $force = (int)($_POST['force'] ?? 0) === 1;
    $moveTo = (int)($_POST['move_to_class_id'] ?? 0);

    if ($id <= 0) {
      $this->flash('error', 'ID kelas tidak valid.');
      $this->redirect('/admin/classes');
    }

    $db = db();

    try {
      $db->beginTransaction();

      if ($force) {
        // bersihkan mapping
        $db->prepare("DELETE FROM class_homerooms WHERE class_room_id=?")->execute([$id]);
        $db->prepare("DELETE FROM religion_teacher_classes WHERE class_room_id=?")->execute([$id]);

        // cek siswa, kalau ada wajib pindahkan
        $stmt = $db->prepare("SELECT COUNT(*) AS cnt FROM student_profiles WHERE class_room_id=?");
        $stmt->execute([$id]);
        $cnt = (int)($stmt->fetch()['cnt'] ?? 0);

        if ($cnt > 0) {
          if ($moveTo <= 0) {
            throw new \RuntimeException("Masih ada {$cnt} siswa pada kelas ini. Pilih kelas tujuan untuk memindahkan siswa.");
          }
          if ($moveTo === $id) {
            throw new \RuntimeException("Kelas tujuan pemindahan tidak boleh sama.");
          }
          $chk = $db->prepare("SELECT id FROM class_rooms WHERE id=?");
          $chk->execute([$moveTo]);
          if (!$chk->fetch()) {
            throw new \RuntimeException("Kelas tujuan pemindahan tidak ditemukan.");
          }

          $db->prepare("UPDATE student_profiles SET class_room_id=? WHERE class_room_id=?")->execute([$moveTo, $id]);
        }
      }

      // hapus kelas
      $stmt = $db->prepare("DELETE FROM class_rooms WHERE id=?");
      $stmt->execute([$id]);

      if ($stmt->rowCount() > 0) {
        $db->commit();
        $this->flash('success', $force ? 'Kelas berhasil dihapus (paksa). Mapping dibersihkan.' : 'Kelas berhasil dihapus.');
      } else {
        $db->rollBack();
        $this->flash('error', 'Kelas tidak ditemukan.');
      }

    } catch (\Throwable $e) {
      if ($db->inTransaction()) $db->rollBack();
      $this->flash('error', 'Gagal menghapus kelas. ' . $e->getMessage());
    }

    $this->redirect('/admin/classes');
  }

  public function importForm(): void {
    Auth::requireRole(['admin']);
    $this->view('admin/import', [
      'success' => $this->getFlash('success'),
      'error' => $this->getFlash('error'),
      'info' => $this->getFlash('info'),
    ]);
  }

  public function importProcess(): void {
    Auth::requireRole(['admin']);

    $importType = trim((string)($_POST['import_type'] ?? 'students'));
    if (!in_array($importType, ['students','classes','teachers'], true)) {
      $importType = 'students';
    }

    if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
      $this->flash('error', 'Upload gagal.');
      $this->redirect('/admin/import');
    }

    $tmp = $_FILES['file']['tmp_name'];
    $fname = $_FILES['file']['name'];
    $ext = strtolower(pathinfo($fname, PATHINFO_EXTENSION));

    $rows = [];

    try {
      // XLSX/XLS via PhpSpreadsheet (composer)
      if (($ext === 'xlsx' || $ext === 'xls') && class_exists('\\PhpOffice\\PhpSpreadsheet\\IOFactory')) {
        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($tmp);
        $sheet = $spreadsheet->getActiveSheet();
        $data = $sheet->toArray(null, true, true, true);

        $header = array_values($data[1] ?? []);
        $header = array_map(function($v){
          $k = strtolower(trim((string)$v));
          $k = str_replace("\u{FEFF}", "", $k);
          $k = ltrim($k, "\xEF\xBB\xBF");
          $k = str_replace([' ','.',"\t","\r","\n"], '', $k);
          return $k;
        }, $header);

        for ($i=2; $i<=count($data); $i++) {
          $row = array_values($data[$i] ?? []);
          if (!array_filter($row, fn($x) => trim((string)$x) !== '')) continue;
          $assoc = [];
          foreach ($header as $idx=>$h) $assoc[$h] = $row[$idx] ?? null;
          $rows[] = $assoc;
        }
      } else {
        // CSV fallback delimiter , ; atau tab
        $raw = file_get_contents($tmp);
        $firstLine = strtok((string)$raw, "\n");
        $delim = str_contains((string)$firstLine, ';') ? ';' : (str_contains((string)$firstLine, "\t") ? "\t" : ',');

        $fh = fopen($tmp, 'r');
        $header = fgetcsv($fh, 0, $delim) ?: [];
        $header = array_map(function($v){
          $k = strtolower(trim((string)$v));
          $k = str_replace("\u{FEFF}", "", $k);
          $k = ltrim($k, "\xEF\xBB\xBF");
          $k = str_replace([' ','.',"\t","\r","\n"], '', $k);
          return $k;
        }, $header);

        while (($r = fgetcsv($fh, 0, $delim)) !== false) {
          if (!array_filter($r, fn($x)=> trim((string)$x) !== '')) continue;
          $assoc = [];
          foreach ($header as $i=>$h) $assoc[$h] = $r[$i] ?? null;
          $rows[] = $assoc;
        }
        fclose($fh);
      }
    } catch (\Throwable $e) {
      $this->flash('error', 'Gagal parsing file. Jika XLSX, pastikan sudah composer install. Detail: ' . $e->getMessage());
      $this->redirect('/admin/import');
    }

    // Debug header kalau semua diskip
    $debugHeader = [];
    if (!empty($rows)) $debugHeader = array_keys($rows[0]);

    $created = 0;
    $updated = 0;
    $skipped = 0;

    // IMPORT: CLASSES
   // IMPORT: CLASSES
if ($importType === 'classes') {
  foreach ($rows as $r) {

    $kelasRaw = trim((string)($r['kelas'] ?? $r['class'] ?? $r['name'] ?? ''));
    $tahunRaw = trim((string)($r['tahun'] ?? $r['year'] ?? ''));

    // Heuristik CSV yang sering ketukar:
    // Contoh: kelas=10 ; tahun="X BUSANA 1"
    // - Jika 'tahun' bukan angka tapi 'kelas' angka (1-12),
    //   maka: kelasName = tahunRaw, tahunYear = tahun berjalan
    $kelasName = $kelasRaw;
    $tahunYear = (int)$tahunRaw;

    if ($tahunYear <= 0) {
      $kelasNum = (int)$kelasRaw;
      if ($kelasNum > 0 && $kelasNum <= 12 && $tahunRaw !== '' && !ctype_digit($tahunRaw)) {
        $kelasName = $tahunRaw;
        $tahunYear = (int)date('Y');
      } else {
        // fallback: kalau tahun kosong/tidak valid, pakai tahun berjalan
        $tahunYear = (int)date('Y');
      }
    }

    // validasi
    if ($kelasName === '' || $tahunYear <= 0) { 
      $skipped++; 
      continue; 
    }

    // kalau sudah ada, skip dianggap "updated"
    if (ClassRoom::findByNameYear($kelasName, $tahunYear)) { 
      $updated++; 
      continue; 
    }

    ClassRoom::create($kelasName, $tahunYear);
    $created++;
  }

  $this->flash('success', "Import KELAS selesai. Dibuat: {$created}, Sudah ada: {$updated}, Dilewati: {$skipped}.");
  $this->redirect('/admin/import');
}
    // IMPORT: TEACHERS (UPsert)
    if ($importType === 'teachers') {
      foreach ($rows as $r) {
        $nama = trim((string)($r['nama'] ?? $r['name'] ?? ''));
        $username = trim((string)($r['username'] ?? ''));
        $password = (string)($r['password'] ?? '');
        $nip = trim((string)($r['nip'] ?? $r['NIP'] ?? ''));
        $rolesRaw = trim((string)($r['roles'] ?? $r['role'] ?? ''));
        $rolesArr = array_filter(array_map('trim', preg_split('/[;,]/', $rolesRaw)));
        $rolesArr = array_values(array_unique($rolesArr));
        $roles = implode(',', $rolesArr);
        $role = $roles; // kompatibilitas variabel lama

        if ($nama === '' || $username === '' || $roles === '') { $skipped++; continue; }
        foreach ($rolesArr as $rr) { if (!in_array($rr, ['homeroom','religion_teacher','principal','admin'], true)) { $skipped++; continue 2; } }

        $existing = User::findByUsername($username);
        if ($existing) {
          $uid = (int)$existing['id'];
          db()->prepare("UPDATE users SET name=?, roles=?, nip=? WHERE id=?")->execute([$nama, $roles, ($nip!==''?$nip:null), $uid]);
          if (trim($password) !== '') {
            db()->prepare("UPDATE users SET password_hash=? WHERE id=?")
              ->execute([password_hash($password, PASSWORD_BCRYPT), $uid]);
          }
          $updated++;
          continue;
        }

        User::create([
          'name' => $nama,
          'username' => $username,
          'password_hash' => password_hash(trim($password) !== '' ? $password : '123456', PASSWORD_BCRYPT),
          'roles' => $roles,
          'nip' => ($nip !== '' ? $nip : null),
          'is_active' => 1
        ]);

        $created++;
      }

      if (($created + $updated) === 0 && $skipped > 0) {
        $this->flash('info', 'Debug Import: header terbaca = ' . implode(', ', $debugHeader));
      }

      $this->flash('success', "Import GURU selesai (roles: homeroom / religion_teacher / principal / admin). Dibuat: {$created}, Diupdate: {$updated}, Dilewati: {$skipped}.");
      $this->redirect('/admin/import');
    }

    // IMPORT: STUDENTS (UPsert)
    foreach ($rows as $r) {
      $nis = trim((string)($r['nis'] ?? $r['nisn'] ?? ''));
      $nama = trim((string)($r['nama'] ?? $r['name'] ?? ''));
      $username = trim((string)($r['username'] ?? ''));
      $password = (string)($r['password'] ?? '');
        $nip = trim((string)($r['nip'] ?? $r['NIP'] ?? ''));
      $kelas = trim((string)($r['kelas'] ?? $r['class'] ?? ''));
      $tahun = (int)($r['tahun'] ?? $r['year'] ?? 0);
      $gender = trim((string)($r['gender'] ?? ''));
      $phone = trim((string)($r['phone'] ?? ''));
      $address = trim((string)($r['address'] ?? ''));

      if ($nis === '' || $nama === '' || $username === '' || $kelas === '' || $tahun <= 0) { $skipped++; continue; }

      $c = ClassRoom::findByNameYear($kelas, $tahun);
      $classId = $c ? (int)$c['id'] : ClassRoom::create($kelas, $tahun);

      $existing = User::findByUsername($username);
      if ($existing) {
        $uid = (int)$existing['id'];

        db()->prepare("UPDATE users SET name=? WHERE id=?")->execute([$nama, $uid]);

        $stmt = db()->prepare("SELECT id FROM student_profiles WHERE user_id=?");
        $stmt->execute([$uid]);
        $sp = $stmt->fetch();

        if ($sp) {
          db()->prepare("UPDATE student_profiles 
                         SET class_room_id=?, nis=?, gender=?, phone=?, address=? 
                         WHERE user_id=?")
            ->execute([$classId, $nis, $gender ?: null, $phone ?: null, $address ?: null, $uid]);
        } else {
          StudentProfile::create([
            'user_id' => $uid,
            'class_room_id' => $classId,
            'nis' => $nis,
            'gender' => $gender ?: null,
            'phone' => $phone ?: null,
            'address' => $address ?: null
          ]);
        }

        if (trim($password) !== '') {
          db()->prepare("UPDATE users SET password_hash=? WHERE id=?")
            ->execute([password_hash($password, PASSWORD_BCRYPT), $uid]);
        }

        $updated++;
        continue;
      }

      $uid = User::create([
        'name' => $nama,
        'username' => $username,
        'password_hash' => password_hash(trim($password) !== '' ? $password : '123456', PASSWORD_BCRYPT),
        'roles' => 'student',
        'is_active' => 1
      ]);

      StudentProfile::create([
        'user_id' => $uid,
        'class_room_id' => $classId,
        'nis' => $nis,
        'gender' => $gender ?: null,
        'phone' => $phone ?: null,
        'address' => $address ?: null
      ]);

      $created++;
    }

    if (($created + $updated) === 0 && $skipped > 0) {
      $this->flash('info', 'Debug Import: header terbaca = ' . implode(', ', $debugHeader));
    }

    $this->flash('success', "Import SISWA selesai. Dibuat: {$created}, Diupdate: {$updated}, Dilewati: {$skipped}.");
    $this->redirect('/admin/import');
  }


public function students(): void {
  Auth::requireRole(['admin']);

  $q = trim((string)($_GET['q'] ?? ''));
  $page = (int)($_GET['page'] ?? 1);
  if ($page < 1) $page = 1;

  $limit = 20;
  $offset = ($page - 1) * $limit;

  $result = User::listStudentsPaged($q, $limit, $offset);
  $rows = $result['rows'];
  $total = (int)$result['total'];
  $totalPages = (int)ceil($total / $limit);

  $classes = ClassRoom::all();

  $this->view('admin/students', [
    'rows' => $rows,
    'classes' => $classes,
    'q' => $q,
    'page' => $page,
    'limit' => $limit,
    'offset' => $offset,
    'total' => $total,
    'totalPages' => $totalPages,
    'success' => $this->getFlash('success'),
    'error' => $this->getFlash('error'),
  ]);
}

public function editStudent(): void {
  Auth::requireRole(['admin']);
  $id = (int)($_GET['id'] ?? 0);
  if (!$id) { $this->redirect('/admin/students'); }
  $stmt = db()->prepare("SELECT u.id,u.name,u.username,u.roles,u.nip,u.is_active, sp.nis, sp.class_room_id
                         FROM users u JOIN student_profiles sp ON sp.user_id=u.id WHERE u.id=? LIMIT 1");
  $stmt->execute([$id]);
  $row = $stmt->fetch();
  if (!$row) { $this->flash('error','Siswa tidak ditemukan'); $this->redirect('/admin/students'); }
  $this->view('admin/student_edit', [
    'row' => $row,
    'classes' => ClassRoom::all(),
    'error' => $this->getFlash('error'),
    'success' => $this->getFlash('success'),
  ]);
}

public function updateStudent(): void {
  Auth::requireRole(['admin']);
  $id = (int)($_POST['id'] ?? 0);
  if (!$id) { $this->redirect('/admin/students'); }

  $name = trim((string)($_POST['name'] ?? ''));
  $username = trim((string)($_POST['username'] ?? ''));
  $nis = trim((string)($_POST['nis'] ?? ''));
  $classId = (int)($_POST['class_room_id'] ?? 0);
  $isActive = (int)($_POST['is_active'] ?? 1);

  if ($name==='' || $username==='' || $nis==='' || $classId<=0) {
    $this->flash('error','Nama, username, NISN/NIS, dan kelas wajib.');
    $this->redirect('/admin/students/edit?id='.$id);
  }



  
  // update users + student_profiles
  User::updateUser($id, [
    'name' => $name,
    'username' => $username,
    'roles' => 'student',
    'nip' => null,
    'is_active' => $isActive
  ]);

  $st = db()->prepare("UPDATE student_profiles SET nis=?, class_room_id=? WHERE user_id=?");
  $st->execute([$nis, $classId, $id]);

  $this->flash('success','Data siswa berhasil diupdate.');
  $this->redirect('/admin/students');
}
public function deactivateStudent(): void {
  Auth::requireRole(['admin']);

  $id = (int)($_POST['id'] ?? 0);
  if ($id <= 0) {
    $this->flash('error', 'ID tidak valid.');
    $this->redirect('/admin/students');
  }

  db()->prepare("UPDATE users SET is_active=0 WHERE id=? AND FIND_IN_SET('student', roles)")
     ->execute([$id]);

  $this->flash('success', 'Siswa berhasil dinonaktifkan.');
  $this->redirect('/admin/students');
}

public function activateStudent(): void {
  Auth::requireRole(['admin']);

  $id = (int)($_POST['id'] ?? 0);
  if ($id <= 0) {
    $this->flash('error', 'ID tidak valid.');
    $this->redirect('/admin/students');
  }

  db()->prepare("UPDATE users SET is_active=1 WHERE id=? AND FIND_IN_SET('student', roles)")
     ->execute([$id]);

  $this->flash('success', 'Siswa berhasil diaktifkan.');
  $this->redirect('/admin/students');
}

public function deleteStudent(): void {
  Auth::requireRole(['admin']);

  $id = (int)($_POST['id'] ?? 0);
  if ($id <= 0) {
    $this->flash('error', 'ID tidak valid.');
    $this->redirect('/admin/students');
  }

  $db = db();
  try {
    $db->beginTransaction();

    // pastikan user student ada
    $st = $db->prepare("SELECT id FROM users WHERE id=? AND FIND_IN_SET('student', roles) LIMIT 1");
    $st->execute([$id]);
    $u = $st->fetch();
    if (!$u) {
      $db->rollBack();
      $this->flash('error', 'Siswa tidak ditemukan.');
      $this->redirect('/admin/students');
    }

    // hapus profile siswa
    $db->prepare("DELETE FROM student_profiles WHERE user_id=?")->execute([$id]);

    // NOTE: isian jurnal TIDAK dihapus (arsip aman).
    // Kalau kamu mau ikut hapus, baru aktifkan baris di bawah:
    // $db->prepare("DELETE FROM journals WHERE student_user_id=?")->execute([$id]);
    // $db->prepare("DELETE FROM sermon_notes WHERE student_user_id=?")->execute([$id]);
    // $db->prepare("DELETE FROM good_deeds WHERE student_user_id=?")->execute([$id]);
    // $db->prepare("DELETE FROM extra_activities WHERE student_user_id=?")->execute([$id]);

    // hapus user
    $db->prepare("DELETE FROM users WHERE id=?")->execute([$id]);

    $db->commit();
    $this->flash('success', 'Siswa berhasil dihapus permanen.');
  } catch (\Throwable $e) {
    if ($db->inTransaction()) $db->rollBack();
    $this->flash('error', 'Gagal hapus siswa: ' . $e->getMessage());
  }

  $this->redirect('/admin/students');
}
public function teachers(): void {
  Auth::requireRole(['admin']);
  $q = trim((string)($_GET['q'] ?? ''));
  $rows = User::listTeachers($q);
  $this->view('admin/teachers', [
    'rows' => $rows,
    'q' => $q,
    'success' => $this->getFlash('success'),
    'error' => $this->getFlash('error'),
  ]);
}

public function editTeacher(): void {
  Auth::requireRole(['admin']);
  $id = (int)($_GET['id'] ?? 0);
  if (!$id) { $this->redirect('/admin/teachers'); }
  $stmt = db()->prepare("SELECT id,name,username,roles,nip,is_active FROM users WHERE id=? LIMIT 1");
  $stmt->execute([$id]);
  $row = $stmt->fetch();
  if (!$row) { $this->flash('error','Guru tidak ditemukan'); $this->redirect('/admin/teachers'); }
  $this->view('admin/teacher_edit', [
    'row' => $row,
    'error' => $this->getFlash('error'),
    'success' => $this->getFlash('success'),
  ]);
}

public function updateTeacher(): void {
  Auth::requireRole(['admin']);
  $id = (int)($_POST['id'] ?? 0);
  if (!$id) { $this->redirect('/admin/teachers'); }

  $name = trim((string)($_POST['name'] ?? ''));
  $username = trim((string)($_POST['username'] ?? ''));
  $nip = trim((string)($_POST['nip'] ?? ''));
  $isActive = (int)($_POST['is_active'] ?? 1);

  // roles dari checkbox
  $rolesArr = $_POST['roles'] ?? [];
  if (!is_array($rolesArr)) $rolesArr = [];
  $rolesArr = array_values(array_unique(array_filter(array_map('trim',$rolesArr))));
  // validasi
  foreach ($rolesArr as $r) {
    if (!in_array($r, ['homeroom','religion_teacher','principal','admin'], true)) {
      $this->flash('error','Role tidak valid.');
      $this->redirect('/admin/teachers/edit?id='.$id);
    }
  }
  if ($name==='' || $username==='' || empty($rolesArr)) {
    $this->flash('error','Nama, username, dan minimal 1 role wajib.');
    $this->redirect('/admin/teachers/edit?id='.$id);
  }

  $roles = implode(',', $rolesArr);

  User::updateUser($id, [
    'name' => $name,
    'username' => $username,
    'roles' => $roles,
    'nip' => ($nip!==''?$nip:null),
    'is_active' => $isActive
  ]);

  $this->flash('success','Data guru berhasil diupdate.');
  $this->redirect('/admin/teachers');
}


  public function mappingHomeroom(): void {
    Auth::requireRole(['admin']);

    $classes = ClassRoom::all();
    $homerooms = User::allTeachers('homeroom');

    $stmt = db()->query("
      SELECT ch.class_room_id, u.id, u.name, u.username, u.nip
      FROM class_homerooms ch
      JOIN users u ON u.id = ch.homeroom_user_id
      ORDER BY ch.class_room_id
    ");
    $rows = $stmt->fetchAll();
    $mapByClass = [];
    foreach ($rows as $r) {
      $mapByClass[(int)$r['class_room_id']] = [
        'id' => (int)$r['id'],
        'name' => (string)$r['name'],
        'username' => (string)$r['username'],
        'nip' => (string)($r['nip'] ?? ''),
      ];
    }

    $this->view('admin/mapping_homeroom', [
      'classes' => $classes,
      'homerooms' => $homerooms,
      'mapByClass' => $mapByClass,
      'success' => $this->getFlash('success'),
      'error' => $this->getFlash('error'),
    ]);
  }

  public function saveMappingHomeroom(): void {
    Auth::requireRole(['admin']);
    $classId = (int)($_POST['class_room_id'] ?? 0);
    $userId = (int)($_POST['homeroom_user_id'] ?? 0);
    if (!$classId || !$userId) { $this->flash('error','Input tidak valid'); $this->redirect('/admin/mapping-homeroom'); }

    $stmt = db()->prepare("INSERT INTO class_homerooms (class_room_id, homeroom_user_id)
                           VALUES (?,?)
                           ON DUPLICATE KEY UPDATE homeroom_user_id=VALUES(homeroom_user_id)");
    $stmt->execute([$classId, $userId]);

    $this->flash('success','Mapping wali kelas tersimpan.');
    $this->redirect('/admin/mapping-homeroom');
  }

public function deleteMappingHomeroom(): void {
  Auth::requireRole(['admin']);
  $classId = (int)($_POST['class_room_id'] ?? 0);
  if (!$classId) { $this->flash('error','Kelas tidak valid'); $this->redirect('/admin/mapping-homeroom'); }

  $stmt = db()->prepare("DELETE FROM class_homerooms WHERE class_room_id=?");
  $stmt->execute([$classId]);

  $this->flash('success','Mapping wali kelas dihapus.');
  $this->redirect('/admin/mapping-homeroom');
}

public function mappingReligion(): void {
    Auth::requireRole(['admin']);
    $classes = ClassRoom::all();
    $teachers = User::allTeachers('religion_teacher');

    $stmt = db()->query("
      SELECT rtc.class_room_id, u.id, u.name, u.username, u.nip
      FROM religion_teacher_classes rtc
      JOIN users u ON u.id = rtc.teacher_user_id
      ORDER BY rtc.class_room_id, u.name
    ");
    $rows = $stmt->fetchAll();
    $byClass = [];
    foreach ($rows as $r) {
      $byClass[(int)$r['class_room_id']][] = [
        'id' => (int)$r['id'],
        'name' => (string)$r['name'],
        'username' => (string)$r['username'],
        'nip' => (string)($r['nip'] ?? ''),
      ];
    }

    $this->view('admin/mapping_religion', [
      'classes' => $classes,
      'teachers' => $teachers,
      'byClass' => $byClass,
      'success' => $this->getFlash('success'),
      'error' => $this->getFlash('error'),
    ]);
  }

  public function saveMappingReligion(): void {
    Auth::requireRole(['admin']);
    $classId = (int)($_POST['class_room_id'] ?? 0);
    $teacherIds = $_POST['teacher_user_ids'] ?? [];
    if (!$classId) { $this->flash('error','Kelas wajib'); $this->redirect('/admin/mapping-religion'); }
    if (!is_array($teacherIds)) $teacherIds = [];

    $stmt = db()->prepare("DELETE FROM religion_teacher_classes WHERE class_room_id=?");
    $stmt->execute([$classId]);

    $stmt2 = db()->prepare("INSERT INTO religion_teacher_classes (class_room_id, teacher_user_id) VALUES (?,?)");
    foreach ($teacherIds as $tid) {
      $tid = (int)$tid;
      if ($tid) $stmt2->execute([$classId, $tid]);
    }

    $this->flash('success','Mapping guru agama tersimpan.');
    $this->redirect('/admin/mapping-religion');
  }

  public function deleteMappingReligion(): void {
    Auth::requireRole(['admin']);
    $classId = (int)($_POST['class_room_id'] ?? 0);
    if (!$classId) { $this->flash('error','Kelas tidak valid'); $this->redirect('/admin/mapping-religion'); }

    $stmt = db()->prepare("DELETE FROM religion_teacher_classes WHERE class_room_id=?");
    $stmt->execute([$classId]);

    $this->flash('success','Mapping guru agama dihapus.');
    $this->redirect('/admin/mapping-religion');
  }

}
