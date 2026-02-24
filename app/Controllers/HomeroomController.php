<?php
namespace App\Controllers;

use App\Core\BaseController;
use App\Core\Auth;

class HomeroomController extends BaseController
{
  private function clampDay(int $d): int
  {
    if ($d < 1) return 1;
    $max = (int)RAMADAN_DAYS;
    if ($d > $max) return $max;
    return $d;
  }

  private function getMyClassId(int $userId): int
  {
    $stmt = db()->prepare("SELECT class_room_id FROM class_homerooms WHERE homeroom_user_id=? LIMIT 1");
    $stmt->execute([$userId]);
    $row = $stmt->fetch();
    return (int)($row['class_room_id'] ?? 0);
  }

  public function classReport(): void
  {
    Auth::requireRole(['homeroom']);
    $user = Auth::user();

    $classId = $this->getMyClassId((int)$user['id']);
    if ($classId <= 0) {
      $this->view('homeroom/no_class', []);
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
      LEFT JOIN journals j
        ON j.student_user_id = u.id AND j.date BETWEEN ? AND ?
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

    $this->view('homeroom/class_report', [
      'user' => $user,
      'classId' => $classId,
      'fromDay' => $fromDay,
      'toDay' => $toDay,
      'from' => $from,
      'to' => $to,
      'totalDays' => $totalDays,
      'rows' => $rows,
    ]);
  }

  public function studentDetail(): void
  {
    Auth::requireRole(['homeroom']);
    $user = Auth::user();

    $classId = $this->getMyClassId((int)$user['id']);
    if ($classId <= 0) {
      http_response_code(403);
      echo "Forbidden";
      return;
    }

    $studentId = (int)($_GET['id'] ?? 0);
    if ($studentId <= 0) {
      http_response_code(400);
      echo "Bad request";
      return;
    }

    // ✅ only active student in my class
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

    $this->view('reports/student_report', [
      'user' => $user,
      'studentInfo' => [
        'name' => $student['name'],
        'nis' => $student['nis'],
        'class_room_id' => $student['class_room_id'],
        'class_name' => $student['class_name'],
        'class_year' => $student['class_year'],
        'class_label' => trim((string)$student['class_name'] . ' ' . (string)$student['class_year']),
      ],
      'fromDay' => $fromDay,
      'toDay' => $toDay,
      'details' => $details,
      'sign' => [
        'homeroom' => ['name' => $user['name'] ?? '', 'nip' => $user['nip'] ?? ''],
        'religion' => [],
        'principal' => null,
      ],
      'backUrl' => '/homeroom/class?from_day='.$fromDay.'&to_day='.$toDay,
    ]);
  }
}