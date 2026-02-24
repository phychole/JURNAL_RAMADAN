<?php
namespace App\Controllers;

use App\Core\BaseController;
use App\Core\Auth;
use App\Models\ClassRoom;

class ReportController extends BaseController
{
  /* =========================================================
   * Helpers Auth multi-role
   * ========================================================= */
  private function rolesOf(array $user): array
  {
    $raw = (string)($user['roles'] ?? '');
    $arr = array_filter(array_map('trim', explode(',', $raw)));
    return array_values(array_unique($arr));
  }

  private function hasAnyRole(array $user, array $need): bool
  {
    $roles = $this->rolesOf($user);
    foreach ($need as $r) {
      if (in_array($r, $roles, true)) return true;
    }
    return false;
  }

  private function requireAnyRole(array $need): array
  {
    $user = Auth::user();
    if (!$user) {
      $this->redirect('/login');
    }
    if (!$this->hasAnyRole($user, $need)) {
      http_response_code(403);
      echo 'Akses ditolak.';
      exit;
    }
    return $user;
  }

  private function clampDay(int $d): int
  {
    if ($d < 1) return 1;
    $max = (int)RAMADAN_DAYS;
    if ($d > $max) return $max;
    return $d;
  }

  /* =========================================================
   * Report Siswa (HTML)
   * ========================================================= */
  public function studentReport(): void
  {
    $user = $this->requireAnyRole(['student']);

    $fromDay = $this->clampDay((int)($_GET['from_day'] ?? 1));
    $toDay   = $this->clampDay((int)($_GET['to_day'] ?? (int)RAMADAN_DAYS));
    if ($toDay < $fromDay) $toDay = $fromDay;

    $details = $this->getStudentDetails((int)$user['id'], $fromDay, $toDay);
    $studentInfo = $this->getStudentInfo((int)$user['id']);

    $classId = isset($studentInfo['class_room_id']) ? (int)$studentInfo['class_room_id'] : null;
    $sign = $this->getSignatories($classId);

    $this->view('reports/student_report', [
      'user' => $user,
      'studentInfo' => $studentInfo,
      'fromDay' => $fromDay,
      'toDay' => $toDay,
      'details' => $details,
      'sign' => $sign,
    ]);
  }

  /* =========================================================
   * Export XLS Siswa
   * ========================================================= */
  public function studentXls(): void
  {
    $user = $this->requireAnyRole(['student']);

    $fromDay = $this->clampDay((int)($_GET['from_day'] ?? 1));
    $toDay   = $this->clampDay((int)($_GET['to_day'] ?? (int)RAMADAN_DAYS));
    if ($toDay < $fromDay) $toDay = $fromDay;

    $details = $this->getStudentDetails((int)$user['id'], $fromDay, $toDay);
    $studentInfo = $this->getStudentInfo((int)$user['id']);

    $filename = 'laporan-siswa-' . preg_replace('/[^a-zA-Z0-9._-]+/', '-', (string)$user['username']) . '-ramadan.xls';
    header('Content-Type: application/vnd.ms-excel; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: max-age=0');

    echo "<html><head><meta charset='utf-8'></head><body>";
    echo '<h3>Laporan Kegiatan Siswa Selama Bulan Ramadan ' . htmlspecialchars((string)RAMADAN_HIJRI_YEAR) . " H</h3>";
    echo '<div><b>NISN/NIS:</b> ' . htmlspecialchars((string)($studentInfo['nis'] ?? '-')) . '</div>';
    echo '<div><b>Nama:</b> ' . htmlspecialchars((string)($studentInfo['name'] ?? $user['name'])) . '</div>';
    echo '<div><b>Kelas:</b> ' . htmlspecialchars((string)($studentInfo['class_label'] ?? '-')) . '</div>';
    echo '<div><b>Periode:</b> Hari ' . (int)$fromDay . ' s/d ' . (int)$toDay . ' Ramadan ' . htmlspecialchars((string)RAMADAN_HIJRI_YEAR) . " H</div><br>";

    echo $this->renderStudentDetailsTable($details);
    echo '</body></html>';
  }

  /* =========================================================
   * Export XLS Rekap Kelas
   * (Wali Kelas / Guru Agama / Admin / Kepala Sekolah)
   * ========================================================= */
  public function classXls(): void
  {
    $user  = $this->requireAnyRole(['homeroom', 'admin', 'religion_teacher', 'principal']);
    $roles = $this->rolesOf($user);

    $classId = (int)($_GET['class_id'] ?? 0);

    // Auto classId untuk wali kelas
    if ($classId === 0 && in_array('homeroom', $roles, true)) {
      $stmt = db()->prepare('SELECT class_room_id FROM class_homerooms WHERE homeroom_user_id=? LIMIT 1');
      $stmt->execute([(int)$user['id']]);
      $row = $stmt->fetch();
      if ($row) $classId = (int)$row['class_room_id'];
    }

    // Auto classId untuk guru agama
    if ($classId === 0 && in_array('religion_teacher', $roles, true)) {
      $stmt = db()->prepare('SELECT class_room_id FROM religion_teacher_classes WHERE teacher_user_id=? ORDER BY class_room_id ASC LIMIT 1');
      $stmt->execute([(int)$user['id']]);
      $row = $stmt->fetch();
      if ($row) $classId = (int)$row['class_room_id'];
    }

    // Principal/Admin wajib pilih class_id (principal bisa semua kelas, tapi tetap harus ada param/terpilih)
    if ($classId <= 0) {
      http_response_code(400);
      echo 'class_id belum dipilih. Untuk Admin/Kepala Sekolah wajib pilih kelas. Untuk Wali Kelas/Guru Agama pastikan sudah mapping kelas.';
      return;
    }

    // Validasi akses wali kelas (kecuali admin/principal)
    if (in_array('homeroom', $roles, true) && !in_array('admin', $roles, true) && !in_array('principal', $roles, true)) {
      $stmt = db()->prepare('SELECT 1 FROM class_homerooms WHERE homeroom_user_id=? AND class_room_id=? LIMIT 1');
      $stmt->execute([(int)$user['id'], $classId]);
      if (!$stmt->fetch()) {
        http_response_code(403);
        echo 'Anda tidak berhak mengakses kelas ini (wali kelas).';
        return;
      }
    }

    // Validasi akses guru agama (kecuali admin/principal)
    if (in_array('religion_teacher', $roles, true) && !in_array('admin', $roles, true) && !in_array('principal', $roles, true)) {
      $stmt = db()->prepare('SELECT 1 FROM religion_teacher_classes WHERE teacher_user_id=? AND class_room_id=? LIMIT 1');
      $stmt->execute([(int)$user['id'], $classId]);
      if (!$stmt->fetch()) {
        http_response_code(403);
        echo 'Anda tidak berhak mengakses kelas ini (guru agama).';
        return;
      }
    }

    $fromDay = $this->clampDay((int)($_GET['from_day'] ?? 1));
    $toDay   = $this->clampDay((int)($_GET['to_day'] ?? (int)RAMADAN_DAYS));
    if ($toDay < $fromDay) $toDay = $fromDay;

    $from = date('Y-m-d', strtotime(RAMADAN_START . ' +' . ($fromDay - 1) . ' day'));
    $to   = date('Y-m-d', strtotime(RAMADAN_START . ' +' . ($toDay - 1) . ' day'));
    $totalDays = ($toDay - $fromDay + 1);

    $class = ClassRoom::find($classId);
    if (!$class) {
      http_response_code(404);
      echo 'Kelas tidak ditemukan';
      return;
    }

    // ✅ FIX: hanya siswa aktif (u.is_active=1)
    $sql = "
      SELECT u.id AS user_id, u.name, u.username, sp.nis,
        SUM(CASE WHEN j.id IS NULL THEN 0 ELSE 1 END) AS filled
      FROM student_profiles sp
      JOIN users u ON u.id=sp.user_id
      LEFT JOIN journals j
        ON j.student_user_id=u.id AND j.date BETWEEN ? AND ?
      WHERE sp.class_room_id=? AND u.is_active=1
      GROUP BY u.id, u.name, u.username, sp.nis
      ORDER BY u.name ASC
    ";
    $stmt = db()->prepare($sql);
    $stmt->execute([$from, $to, $classId]);
    $rows = $stmt->fetchAll() ?: [];

    foreach ($rows as &$r) {
      $filled = (int)$r['filled'];
      $pct = $totalDays > 0 ? ($filled / $totalDays) : 0;
      $r['pct'] = $pct;
      $r['status'] = ($pct >= 0.7) ? 'Rajin' : 'Kurang';
    }
    unset($r);

    $filename = 'rekap-kelas-' . preg_replace('/[^a-zA-Z0-9._-]+/', '-', (string)$class['name']) . '-' . (string)$class['year'] . '-ramadan.xls';
    header('Content-Type: application/vnd.ms-excel; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: max-age=0');

    echo "<html><head><meta charset='utf-8'></head><body>";
    echo '<h3>Rekap Kelas - ' . htmlspecialchars((string)$class['name'] . ' ' . (string)$class['year']) . '</h3>';
    echo '<div>Periode: Hari ' . (int)$fromDay . ' s/d ' . (int)$toDay . ' Ramadan ' . htmlspecialchars((string)RAMADAN_HIJRI_YEAR) . " H</div><br>";

    echo "<table border='1' cellpadding='4' cellspacing='0'>";
    echo '<thead><tr><th>Nama</th><th>NISN/NIS</th><th>Terisi</th><th>Persentase</th><th>Status</th></tr></thead><tbody>';
    foreach ($rows as $r) {
      echo '<tr>';
      echo '<td>' . htmlspecialchars((string)$r['name']) . '</td>';
      echo '<td>' . htmlspecialchars((string)$r['nis']) . '</td>';
      echo '<td>' . (int)$r['filled'] . '/' . (int)$totalDays . '</td>';
      echo '<td>' . round(((float)$r['pct']) * 100, 1) . '%</td>';
      echo '<td>' . htmlspecialchars((string)$r['status']) . '</td>';
      echo '</tr>';
    }
    echo '</tbody></table>';
    echo '</body></html>';
  }

  /* =========================================================
   * Helpers Data
   * ========================================================= */
  private function getSignatories(?int $classId): array
  {
    $res = [
      'homeroom' => null,
      'religion' => [],
      'principal' => null,
    ];

    if ($classId) {
      $stmt = db()->prepare("
        SELECT u.name, u.nip
        FROM class_homerooms ch
        JOIN users u ON u.id = ch.homeroom_user_id
        WHERE ch.class_room_id = ?
        LIMIT 1
      ");
      $stmt->execute([$classId]);
      $res['homeroom'] = $stmt->fetch() ?: null;

      $stmt = db()->prepare("
        SELECT u.name, u.nip
        FROM religion_teacher_classes rtc
        JOIN users u ON u.id = rtc.teacher_user_id
        WHERE rtc.class_room_id = ?
        ORDER BY u.name
      ");
      $stmt->execute([$classId]);
      $res['religion'] = $stmt->fetchAll() ?: [];
    }

    $stmt = db()->prepare("
      SELECT name, nip
      FROM users
      WHERE FIND_IN_SET('principal', roles) AND is_active = 1
      ORDER BY id DESC
      LIMIT 1
    ");
    $stmt->execute();
    $res['principal'] = $stmt->fetch() ?: null;

    return $res;
  }

  private function getStudentInfo(int $studentUserId): array
  {
    $stmt = db()->prepare("
      SELECT u.name, sp.nis, sp.class_room_id,
             cr.name AS class_name,
             cr.year AS class_year
      FROM users u
      LEFT JOIN student_profiles sp ON sp.user_id = u.id
      LEFT JOIN class_rooms cr ON cr.id = sp.class_room_id
      WHERE u.id = ?
      LIMIT 1
    ");
    $stmt->execute([$studentUserId]);
    $row = $stmt->fetch() ?: [];
    $row['class_label'] = trim((string)($row['class_name'] ?? '') . ' ' . (string)($row['class_year'] ?? ''));
    return $row;
  }

  private function getStudentDetails(int $studentUserId, int $fromDay, int $toDay): array
  {
    $fromDate = date('Y-m-d', strtotime(RAMADAN_START . ' +' . ($fromDay - 1) . ' day'));
    $toDate   = date('Y-m-d', strtotime(RAMADAN_START . ' +' . ($toDay - 1) . ' day'));

    $j = db()->prepare('SELECT * FROM journals WHERE student_user_id=? AND date BETWEEN ? AND ?');
    $j->execute([$studentUserId, $fromDate, $toDate]);
    $jByDate = [];
    foreach (($j->fetchAll() ?: []) as $r) $jByDate[$r['date']] = $r;

    $s = db()->prepare('SELECT * FROM sermon_notes WHERE student_user_id=? AND date BETWEEN ? AND ?');
    $s->execute([$studentUserId, $fromDate, $toDate]);
    $sByDate = [];
    foreach (($s->fetchAll() ?: []) as $r) $sByDate[$r['date']] = $r;

    $g = db()->prepare('SELECT * FROM good_deeds WHERE student_user_id=? AND date BETWEEN ? AND ?');
    $g->execute([$studentUserId, $fromDate, $toDate]);
    $gByDate = [];
    foreach (($g->fetchAll() ?: []) as $r) $gByDate[$r['date']] = $r;

    $e = db()->prepare('SELECT * FROM extra_activities WHERE student_user_id=? AND date BETWEEN ? AND ?');
    $e->execute([$studentUserId, $fromDate, $toDate]);
    $eByDate = [];
    foreach (($e->fetchAll() ?: []) as $r) $eByDate[$r['date']] = $r;

    $details = [];
    for ($d = $fromDay; $d <= $toDay; $d++) {
      $date = date('Y-m-d', strtotime(RAMADAN_START . ' +' . ($d - 1) . ' day'));
      $details[] = [
        'day' => $d,
        'date' => $date,
        'journal' => $jByDate[$date] ?? null,
        'sermon' => $sByDate[$date] ?? null,
        'good' => $gByDate[$date] ?? null,
        'extra' => $eByDate[$date] ?? null,
      ];
    }
    return $details;
  }

  private function renderStudentDetailsTable(array $details): string
  {
    $yes = function ($v) { return ((int)$v === 1) ? 'YA' : ''; };

    $html = "<table border='1' cellpadding='4' cellspacing='0'>";
    $html .= '<thead><tr>'
      . '<th>Hari Ramadan</th>'
      . '<th>Shubuh</th><th>Dzuhur</th><th>Ashar</th><th>Maghrib</th><th>Isya</th>'
      . '<th>Tarawih</th><th>Witir</th><th>Puasa</th><th>Tadarus (hlm)</th>'
      . '<th>Catatan</th>'
      . '<th>Judul Ceramah</th><th>Ringkasan</th>'
      . '<th>Sosial</th><th>Refleksi</th>'
      . '<th>Pondok</th><th>Ziarah</th><th>Persiapan Idulfitri</th>'
      . '</tr></thead><tbody>';

    foreach ($details as $d) {
      $j = $d['journal'] ?? [];
      $s = $d['sermon'] ?? [];
      $g = $d['good'] ?? [];
      $e = $d['extra'] ?? [];

      $dayLabel = 'Hari ' . (int)$d['day'] . ' Ramadan ' . (string)RAMADAN_HIJRI_YEAR . ' H';

      $html .= '<tr>';
      $html .= '<td>' . htmlspecialchars($dayLabel) . '</td>';
      $html .= '<td>' . $yes($j['shubuh'] ?? 0) . '</td>';
      $html .= '<td>' . $yes($j['dzuhur'] ?? 0) . '</td>';
      $html .= '<td>' . $yes($j['ashar'] ?? 0) . '</td>';
      $html .= '<td>' . $yes($j['maghrib'] ?? 0) . '</td>';
      $html .= '<td>' . $yes($j['isya'] ?? 0) . '</td>';
      $html .= '<td>' . $yes($j['tarawih'] ?? 0) . '</td>';
      $html .= '<td>' . $yes($j['witir'] ?? 0) . '</td>';
      $html .= '<td>' . $yes($j['fasting'] ?? 0) . '</td>';
      $html .= '<td>' . htmlspecialchars((string)($j['tadarus_pages'] ?? '0')) . '</td>';
      $html .= '<td>' . htmlspecialchars((string)($j['notes'] ?? '')) . '</td>';
      $html .= '<td>' . htmlspecialchars((string)($s['title'] ?? '')) . '</td>';
      $html .= '<td>' . htmlspecialchars((string)($s['content'] ?? '')) . '</td>';
      $html .= '<td>' . htmlspecialchars((string)($g['social_activity'] ?? '')) . '</td>';
      $html .= '<td>' . htmlspecialchars((string)($g['reflection'] ?? '')) . '</td>';
      $html .= '<td>' . htmlspecialchars((string)($e['pondok_ramadhan'] ?? '')) . '</td>';
      $html .= '<td>' . htmlspecialchars((string)($e['ziarah'] ?? '')) . '</td>';
      $html .= '<td>' . htmlspecialchars((string)($e['idulfitri_prep'] ?? '')) . '</td>';
      $html .= '</tr>';
    }

    $html .= '</tbody></table>';
    return $html;
  }
}