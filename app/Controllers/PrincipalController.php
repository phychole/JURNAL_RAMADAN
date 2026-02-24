<?php
namespace App\Controllers;

use App\Core\BaseController;
use App\Core\Auth;
use App\Models\ClassRoom;

class PrincipalController extends BaseController
{
  private function rolesOf(array $user): array
  {
    $raw = (string)($user['roles'] ?? '');
    $arr = array_filter(array_map('trim', explode(',', $raw)));
    return array_values(array_unique($arr));
  }

  private function requirePrincipal(): array
  {
    $user = Auth::user();
    if (!$user) $this->redirect('/login');

    $roles = $this->rolesOf($user);
    if (!in_array('principal', $roles, true) && !in_array('admin', $roles, true)) {
      http_response_code(403);
      echo 'Forbidden';
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

  public function classReport(): void
  {
    $user = $this->requirePrincipal();

    $classes = ClassRoom::all();
    if (empty($classes)) {
      $this->view('religion_teacher/class_report', [
        'user' => $user,
        'classes' => [],
        'classId' => 0,
        'fromDay' => 1,
        'toDay' => (int)RAMADAN_DAYS,
        'from' => '',
        'to' => '',
        'totalDays' => 0,
        'rows' => [],
        'baseRoute' => '/principal', // penting untuk view
      ]);
      return;
    }

    $classId = (int)($_GET['class_id'] ?? 0);
    if ($classId <= 0) $classId = (int)($classes[0]['id'] ?? 0);

    $fromDay = $this->clampDay((int)($_GET['from_day'] ?? 1));
    $toDay   = $this->clampDay((int)($_GET['to_day'] ?? (int)RAMADAN_DAYS));
    if ($toDay < $fromDay) $toDay = $fromDay;

    $from = date('Y-m-d', strtotime(RAMADAN_START . ' +' . ($fromDay - 1) . ' day'));
    $to   = date('Y-m-d', strtotime(RAMADAN_START . ' +' . ($toDay - 1) . ' day'));
    $totalDays = ($toDay - $fromDay + 1);

    // Validasi classId exist
    $classOk = false;
    foreach ($classes as $c) {
      if ((int)$c['id'] === $classId) { $classOk = true; break; }
    }
    if (!$classOk) $classId = (int)($classes[0]['id'] ?? 0);

    $sql = "
      SELECT u.id AS user_id, u.name, u.username, sp.nis,
        SUM(CASE WHEN j.id IS NULL THEN 0 ELSE 1 END) AS filled
      FROM student_profiles sp
      JOIN users u ON u.id = sp.user_id
      LEFT JOIN journals j
        ON j.student_user_id = u.id AND j.date BETWEEN ? AND ?
      WHERE sp.class_room_id = ?
      GROUP BY u.id, u.name, u.username, sp.nis
      ORDER BY u.name ASC
    ";
    $stmt = db()->prepare($sql);
    $stmt->execute([$from, $to, $classId]);
    $rows = $stmt->fetchAll() ?: [];

    foreach ($rows as &$r) {
      $filled = (int)($r['filled'] ?? 0);
      $pct = $totalDays > 0 ? ($filled / $totalDays) : 0;
      $r['pct'] = $pct;
      $r['status'] = ($pct >= 0.7) ? 'Rajin' : 'Kurang';
    }
    unset($r);

    $this->view('religion_teacher/class_report', [
      'user' => $user,
      'classes' => $classes,
      'classId' => $classId,
      'fromDay' => $fromDay,
      'toDay' => $toDay,
      'from' => $from,
      'to' => $to,
      'totalDays' => $totalDays,
      'rows' => $rows,
      'baseRoute' => '/principal', // penting untuk view
    ]);
  }

  public function studentDetail(): void
  {
    $user = $this->requirePrincipal();

    $classId = (int)($_GET['class_id'] ?? 0);
    $studentUserId = (int)($_GET['id'] ?? 0);

    if ($classId <= 0 || $studentUserId <= 0) {
      http_response_code(400);
      echo "class_id dan id wajib";
      return;
    }

    $fromDay = $this->clampDay((int)($_GET['from_day'] ?? 1));
    $toDay   = $this->clampDay((int)($_GET['to_day'] ?? (int)RAMADAN_DAYS));
    if ($toDay < $fromDay) $toDay = $fromDay;

    // Pakai ReportController untuk render student report? Aman kalau kamu punya view reports/student_report
    // Di sini kita panggil ReportController helper minimal via query langsung supaya self-contained.

    // info siswa
    $stmt = db()->prepare("
      SELECT u.id, u.name, sp.nis, sp.class_room_id, cr.name AS class_name, cr.year AS class_year
      FROM users u
      LEFT JOIN student_profiles sp ON sp.user_id=u.id
      LEFT JOIN class_rooms cr ON cr.id=sp.class_room_id
      WHERE u.id=? LIMIT 1
    ");
    $stmt->execute([$studentUserId]);
    $studentInfo = $stmt->fetch() ?: [];
    $studentInfo['class_label'] = trim((string)($studentInfo['class_name'] ?? '') . ' ' . (string)($studentInfo['class_year'] ?? ''));

    // details harian (ambil dari ReportController pattern)
    $fromDate = date('Y-m-d', strtotime(RAMADAN_START . ' +' . ($fromDay - 1) . ' day'));
    $toDate   = date('Y-m-d', strtotime(RAMADAN_START . ' +' . ($toDay - 1) . ' day'));

    $j = db()->prepare("SELECT * FROM journals WHERE student_user_id=? AND date BETWEEN ? AND ?");
    $j->execute([$studentUserId, $fromDate, $toDate]);
    $jByDate = [];
    foreach (($j->fetchAll() ?: []) as $r) $jByDate[$r['date']] = $r;

    $s = db()->prepare("SELECT * FROM sermon_notes WHERE student_user_id=? AND date BETWEEN ? AND ?");
    $s->execute([$studentUserId, $fromDate, $toDate]);
    $sByDate = [];
    foreach (($s->fetchAll() ?: []) as $r) $sByDate[$r['date']] = $r;

    $g = db()->prepare("SELECT * FROM good_deeds WHERE student_user_id=? AND date BETWEEN ? AND ?");
    $g->execute([$studentUserId, $fromDate, $toDate]);
    $gByDate = [];
    foreach (($g->fetchAll() ?: []) as $r) $gByDate[$r['date']] = $r;

    $e = db()->prepare("SELECT * FROM extra_activities WHERE student_user_id=? AND date BETWEEN ? AND ?");
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

    // signatories (opsional) – kalau kamu sudah punya helper di ReportController bisa dipakai, tapi di sini cukup null
    $sign = [
      'homeroom' => null,
      'religion' => [],
      'principal' => ['name' => $user['name'] ?? '', 'nip' => $user['nip'] ?? ''],
    ];

    $this->view('reports/student_report', [
      'user' => $user,
      'studentInfo' => $studentInfo,
      'fromDay' => $fromDay,
      'toDay' => $toDay,
      'details' => $details,
      'sign' => $sign,
      'backUrl' => '/principal/class?class_id='.$classId.'&from_day='.$fromDay.'&to_day='.$toDay,
    ]);
  }
}