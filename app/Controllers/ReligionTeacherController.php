<?php
namespace App\Controllers;

use App\Core\BaseController;
use App\Core\Auth;

class ReligionTeacherController extends BaseController
{
  private function getSignatories(int $classId): array
  {
    $res = ['homeroom'=>null,'religion'=>[],'principal'=>null];

    $stmt = db()->prepare("SELECT u.name, u.nip
      FROM class_homerooms ch
      JOIN users u ON u.id=ch.homeroom_user_id
      WHERE ch.class_room_id=? LIMIT 1");
    $stmt->execute([$classId]);
    $res['homeroom'] = $stmt->fetch() ?: null;

    $stmt = db()->prepare("SELECT u.name, u.nip
      FROM religion_teacher_classes rtc
      JOIN users u ON u.id=rtc.teacher_user_id
      WHERE rtc.class_room_id=? ORDER BY u.name");
    $stmt->execute([$classId]);
    $res['religion'] = $stmt->fetchAll() ?: [];

    $stmt = db()->prepare("SELECT name, nip
      FROM users
      WHERE FIND_IN_SET('principal', roles) AND is_active=1
      ORDER BY id DESC LIMIT 1");
    $stmt->execute();
    $res['principal'] = $stmt->fetch() ?: null;

    return $res;
  }

  private function myClasses(int $teacherId): array
  {
    $stmt = db()->prepare("
      SELECT cr.*
      FROM religion_teacher_classes rtc
      JOIN class_rooms cr ON cr.id=rtc.class_room_id
      WHERE rtc.teacher_user_id=?
      ORDER BY cr.year DESC, cr.name ASC
    ");
    $stmt->execute([$teacherId]);
    return $stmt->fetchAll() ?: [];
  }

  private function canAccessClass(int $teacherId, int $classId): bool
  {
    $stmt = db()->prepare("SELECT 1 FROM religion_teacher_classes WHERE teacher_user_id=? AND class_room_id=? LIMIT 1");
    $stmt->execute([$teacherId, $classId]);
    return (bool)$stmt->fetch();
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
    Auth::requireRole(['religion_teacher']);
    $user = Auth::user();
    $teacherId = (int)$user['id'];

    $classes = $this->myClasses($teacherId);
    if (!$classes) {
      $this->view('religion_teacher/no_class', []);
      return;
    }

    $classId = (int)($_GET['class_id'] ?? 0);
    if ($classId <= 0) $classId = (int)($classes[0]['id'] ?? 0);

    if (!$this->canAccessClass($teacherId, $classId)) {
      http_response_code(403);
      echo "Forbidden";
      return;
    }

    $fromDay = $this->clampDay((int)($_GET['from_day'] ?? 1));
    $toDay   = $this->clampDay((int)($_GET['to_day'] ?? (int)RAMADAN_DAYS));
    if ($toDay < $fromDay) $toDay = $fromDay;

    $from = date('Y-m-d', strtotime(RAMADAN_START . ' +' . ($fromDay - 1) . ' day'));
    $to   = date('Y-m-d', strtotime(RAMADAN_START . ' +' . ($toDay - 1) . ' day'));
    $totalDays = ($toDay - $fromDay + 1);

    // ✅ FIX: hanya siswa aktif
    $sql = "
      SELECT u.id AS user_id, u.name, u.username, sp.nis,
        SUM(CASE WHEN j.id IS NULL THEN 0 ELSE 1 END) AS filled
      FROM student_profiles sp
      JOIN users u ON u.id = sp.user_id
      LEFT JOIN journals j ON j.student_user_id = u.id AND j.date BETWEEN ? AND ?
      WHERE sp.class_room_id = ? AND u.is_active=1
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
      'baseRoute' => '/religion', // kalau view mau dipakai universal
    ]);
  }

  public function studentDetail(): void
  {
    Auth::requireRole(['religion_teacher']);
    $user = Auth::user();
    $teacherId = (int)$user['id'];

    $classId = (int)($_GET['class_id'] ?? 0);
    $studentId = (int)($_GET['id'] ?? 0);
    if ($classId <= 0 || $studentId <= 0) {
      http_response_code(400);
      echo "Bad request";
      return;
    }

    if (!$this->canAccessClass($teacherId, $classId)) {
      http_response_code(403);
      echo "Forbidden";
      return;
    }

    // ✅ Fix: detail juga hanya kalau siswa aktif
    $stmt = db()->prepare("
      SELECT sp.*, u.name, u.username, u.is_active,
             cr.name AS class_name, cr.year AS class_year
      FROM student_profiles sp
      JOIN users u ON u.id=sp.user_id
      JOIN class_rooms cr ON cr.id=sp.class_room_id
      WHERE sp.user_id=? AND sp.class_room_id=? AND u.is_active=1
      LIMIT 1
    ");
    $stmt->execute([$studentId, $classId]);
    $student = $stmt->fetch();
    if (!$student) {
      http_response_code(404);
      echo "Siswa tidak ditemukan / nonaktif";
      return;
    }

    $fromDay = $this->clampDay((int)($_GET['from_day'] ?? 1));
    $toDay   = $this->clampDay((int)($_GET['to_day'] ?? (int)RAMADAN_DAYS));
    if ($toDay < $fromDay) $toDay = $fromDay;

    $fromDate = date('Y-m-d', strtotime(RAMADAN_START . ' +' . ($fromDay - 1) . ' day'));
    $toDate   = date('Y-m-d', strtotime(RAMADAN_START . ' +' . ($toDay - 1) . ' day'));

    $journals = db()->prepare("SELECT * FROM journals WHERE student_user_id=? AND date BETWEEN ? AND ?");
    $journals->execute([$studentId, $fromDate, $toDate]);
    $jByDate = [];
    foreach (($journals->fetchAll() ?: []) as $r) $jByDate[$r['date']] = $r;

    $sermons = db()->prepare("SELECT * FROM sermon_notes WHERE student_user_id=? AND date BETWEEN ? AND ?");
    $sermons->execute([$studentId, $fromDate, $toDate]);
    $sByDate = [];
    foreach (($sermons->fetchAll() ?: []) as $r) $sByDate[$r['date']] = $r;

    $goods = db()->prepare("SELECT * FROM good_deeds WHERE student_user_id=? AND date BETWEEN ? AND ?");
    $goods->execute([$studentId, $fromDate, $toDate]);
    $gByDate = [];
    foreach (($goods->fetchAll() ?: []) as $r) $gByDate[$r['date']] = $r;

    $extras = db()->prepare("SELECT * FROM extra_activities WHERE student_user_id=? AND date BETWEEN ? AND ?");
    $extras->execute([$studentId, $fromDate, $toDate]);
    $eByDate = [];
    foreach (($extras->fetchAll() ?: []) as $r) $eByDate[$r['date']] = $r;

    $days = [];
    for ($d = $fromDay; $d <= $toDay; $d++) {
      $date = date('Y-m-d', strtotime(RAMADAN_START . ' +' . ($d - 1) . ' day'));
      $days[] = [
        'day' => $d,
        'date' => $date,
        'journal' => $jByDate[$date] ?? null,
        'sermon' => $sByDate[$date] ?? null,
        'good' => $gByDate[$date] ?? null,
        'extra' => $eByDate[$date] ?? null,
      ];
    }

    $this->view('religion_teacher/student_detail', [
      'student' => $student,
      'class_id' => $classId,
      'days' => $days,
      'sign' => $this->getSignatories((int)$student['class_room_id']),
      'fromDay' => $fromDay,
      'toDay' => $toDay,
    ]);
  }
}