<?php
namespace App\Controllers;

use App\Core\BaseController;

class PublicReportController extends BaseController
{
  private function clampDay(int $d): int {
    if ($d < 1) return 1;
    $max = (int)RAMADAN_DAYS;
    if ($d > $max) return $max;
    return $d;
  }

  // Halaman HTML dashboard publik
  public function index(): void
  {
    // halaman publik tidak butuh login
    require APP_ROOT . '/public/report.php';
  }

  // JSON chart harian per kelas + ranking overall
  public function data(): void
  {
    header('Content-Type: application/json; charset=utf-8');

    $classId = (int)($_GET['class_id'] ?? 0);
    $fromDay = $this->clampDay((int)($_GET['from_day'] ?? 1));
    $toDay   = $this->clampDay((int)($_GET['to_day'] ?? (int)RAMADAN_DAYS));
    if ($toDay < $fromDay) $toDay = $fromDay;

    // pilih kelas default (kalau tidak dipilih)
    if ($classId <= 0) {
      $r = db()->query("SELECT id FROM class_rooms ORDER BY year DESC, name ASC LIMIT 1")->fetch();
      $classId = $r ? (int)$r['id'] : 0;
    }

    // Ambil daftar kelas untuk dropdown
    $classes = db()->query("SELECT id, name, year FROM class_rooms ORDER BY year DESC, name ASC")->fetchAll();

    // Data pengisian harian = jumlah siswa yg mengisi journal per hari
    $dayData = [];
    for ($d = $fromDay; $d <= $toDay; $d++) {
      $date = date('Y-m-d', strtotime(RAMADAN_START . ' +' . ($d - 1) . ' day'));

      // hitung siswa unik yg mengisi journal pada hari itu untuk kelas tertentu
      $sql = "
        SELECT COUNT(DISTINCT sp.user_id) AS cnt
        FROM student_profiles sp
        JOIN journals j ON j.student_user_id = sp.user_id
        WHERE sp.class_room_id = ?
          AND j.date = ?
      ";
      $st = db()->prepare($sql);
      $st->execute([$classId, $date]);
      $cnt = (int)(($st->fetch()['cnt'] ?? 0));

      $dayData[] = [
        'day' => $d,
        'count' => $cnt
      ];
    }

    // Ranking overall seluruh kelas (Ramadan full)
    $days = (int)RAMADAN_DAYS;
    $start = RAMADAN_START;
    $end = date('Y-m-d', strtotime(RAMADAN_START . ' +' . ($days - 1) . ' day'));

    $sqlRank = "
      SELECT cr.id, cr.name, cr.year,
             COUNT(DISTINCT sp.user_id) total_students,
             COUNT(j.id) total_entries
      FROM class_rooms cr
      LEFT JOIN student_profiles sp ON sp.class_room_id = cr.id
      LEFT JOIN journals j ON j.student_user_id = sp.user_id
           AND j.date BETWEEN ? AND ?
      GROUP BY cr.id
      HAVING total_students > 0
    ";
    $st = db()->prepare($sqlRank);
    $st->execute([$start, $end]);
    $rankRows = $st->fetchAll();

    foreach ($rankRows as &$r) {
      $possible = (int)$r['total_students'] * $days;
      $r['score'] = $possible > 0 ? ((int)$r['total_entries'] / $possible) : 0;
      $r['label'] = trim((string)$r['name'] . ' ' . (string)$r['year']);
    }
    unset($r);

    usort($rankRows, fn($a, $b) => ($b['score'] <=> $a['score']));

    $best = $rankRows[0] ?? null;
    $worst = count($rankRows) ? $rankRows[count($rankRows) - 1] : null;

    echo json_encode([
      'classes' => $classes,
      'selected_class_id' => $classId,
      'from_day' => $fromDay,
      'to_day' => $toDay,
      'day_data' => $dayData,

      // ranking keseluruhan tetap ada
      'ranking' => $rankRows,
      'overall_best' => $best,
      'overall_worst' => $worst,
    ], JSON_UNESCAPED_UNICODE);
  }

  // JSON advanced (Top 5) untuk leaderboard + pie
  public function advanced(): void
  {
    header('Content-Type: application/json; charset=utf-8');

    $days = (int)RAMADAN_DAYS;
    $start = RAMADAN_START;
    $end = date('Y-m-d', strtotime(RAMADAN_START . ' +' . ($days - 1) . ' day'));

    $sql = "
      SELECT cr.id, cr.name, cr.year,
             COUNT(DISTINCT sp.user_id) total_students,
             COUNT(j.id) total_entries
      FROM class_rooms cr
      LEFT JOIN student_profiles sp ON sp.class_room_id = cr.id
      LEFT JOIN journals j ON j.student_user_id = sp.user_id
           AND j.date BETWEEN ? AND ?
      GROUP BY cr.id
      HAVING total_students > 0
    ";
    $st = db()->prepare($sql);
    $st->execute([$start, $end]);
    $rows = $st->fetchAll();

    foreach ($rows as &$r) {
      $possible = (int)$r['total_students'] * $days;
      $r['score'] = $possible > 0 ? ((int)$r['total_entries'] / $possible) : 0;
      $r['label'] = trim((string)$r['name'] . ' ' . (string)$r['year']);
    }
    unset($r);

    usort($rows, fn($a, $b) => ($b['score'] <=> $a['score']));

    echo json_encode([
      'leaderboard' => array_slice($rows, 0, 5),
      'overall_best' => $rows[0] ?? null,
      'overall_worst' => count($rows) ? $rows[count($rows)-1] : null,
    ], JSON_UNESCAPED_UNICODE);
  }

  public function table(): void
{
  header('Content-Type: application/json; charset=utf-8');

  $days  = (int)(defined('RAMADAN_DAYS') ? RAMADAN_DAYS : 30);
  $start = (string)(defined('RAMADAN_START') ? RAMADAN_START : date('Y-m-d'));

  // tingkat: ALL | X | XI | XII
  $tingkat = strtoupper(trim((string)($_GET['tingkat'] ?? 'ALL')));
  $allowed = ['ALL','X','XI','XII'];
  if (!in_array($tingkat, $allowed, true)) $tingkat = 'ALL';

  // Filter kelas berdasarkan prefix nama kelas (X / XI / XII)
  // Contoh nama: "X BUSANA 1", "XI TKJ 2", "XII RPL 1"
  $where = '';
  $params = [];

  if ($tingkat !== 'ALL') {
    $where = "WHERE cr.name LIKE ?";
    $params[] = $tingkat . " %";
  }

  // 1) Ambil daftar kelas + total siswa AKTIF per kelas
  $sqlClasses = "
    SELECT 
      cr.id,
      cr.name,
      cr.year,
      COUNT(u.id) AS total_students
    FROM class_rooms cr
    LEFT JOIN student_profiles sp ON sp.class_room_id = cr.id
    LEFT JOIN users u ON u.id = sp.user_id AND u.is_active = 1
    {$where}
    GROUP BY cr.id, cr.name, cr.year
    ORDER BY cr.name ASC, cr.year ASC
  ";
  $stmtC = db()->prepare($sqlClasses);
  $stmtC->execute($params);
  $classes = $stmtC->fetchAll() ?: [];

  // 2) Ambil jumlah siswa AKTIF yang mengisi jurnal per kelas per hari
  // day_no = DATEDIFF(j.date, RAMADAN_START)+1
  // Jika tingkat != ALL, filter juga via class_rooms.name LIKE "X %"
  $whereAgg = "";
  $paramsAgg = [$start, $start, $start, $days - 1, $days];

  if ($tingkat !== 'ALL') {
    $whereAgg = " AND cr.name LIKE ? ";
    $paramsAgg[] = $tingkat . " %";
  }

  $sqlAgg = "
    SELECT
      sp.class_room_id AS class_id,
      (DATEDIFF(j.date, ?) + 1) AS day_no,
      COUNT(DISTINCT j.student_user_id) AS cnt
    FROM journals j
    JOIN users u ON u.id = j.student_user_id AND u.is_active = 1
    JOIN student_profiles sp ON sp.user_id = j.student_user_id
    JOIN class_rooms cr ON cr.id = sp.class_room_id
    WHERE j.date BETWEEN ? AND DATE_ADD(?, INTERVAL ? DAY)
      {$whereAgg}
    GROUP BY sp.class_room_id, day_no
    HAVING day_no BETWEEN 1 AND ?
  ";
  $stmt = db()->prepare($sqlAgg);
  $stmt->execute($paramsAgg);
  $agg = $stmt->fetchAll() ?: [];

  // 3) Buat map [class_id][day] = cnt
  $map = [];
  foreach ($agg as $r) {
    $cid = (int)($r['class_id'] ?? 0);
    $day = (int)($r['day_no'] ?? 0);
    $cnt = (int)($r['cnt'] ?? 0);
    if ($cid <= 0 || $day < 1 || $day > $days) continue;
    $map[$cid][$day] = $cnt;
  }

  // 4) Susun rows final tabel
  $rows = [];
  $no = 1;
  foreach ($classes as $c) {
    $cid = (int)$c['id'];
    $label = trim((string)$c['name'] . ' ' . (string)$c['year']);

    $dayCounts = [];
    for ($d = 1; $d <= $days; $d++) {
      $dayCounts[] = (int)($map[$cid][$d] ?? 0);
    }

    $rows[] = [
      'no' => $no++,
      'class_id' => $cid,
      'kelas' => $label,
      'total_students' => (int)($c['total_students'] ?? 0),
      'day_counts' => $dayCounts, // index 0=hari1
    ];
  }

  echo json_encode([
    'days' => $days,
    'tingkat' => $tingkat,
    'rows' => $rows,
    'ramadan_hijri_year' => defined('RAMADAN_HIJRI_YEAR') ? RAMADAN_HIJRI_YEAR : null,
  ], JSON_UNESCAPED_UNICODE);
}
public function rekap(): void
{
  header('Content-Type: application/json; charset=utf-8');

  $level = trim((string)($_GET['level'] ?? '')); // '', X, XI, XII
  $days = (int)RAMADAN_DAYS;

  $db = db();

  // Filter tingkat berdasarkan nama kelas: "X BUSANA 1", "XI ...", dst
  $whereLevel = "";
  $paramsLevel = [];

  if ($level !== '') {
    // cocokkan nama kelas sama dengan "X" atau awalan "X "
    $whereLevel = " AND (cr.name = ? OR cr.name LIKE ?) ";
    $paramsLevel = [$level, $level.' %'];
  }

  // Ambil semua kelas + jumlah siswa aktif
  $sqlClasses = "
    SELECT cr.id, cr.name, cr.year,
           COUNT(sp.user_id) AS total_students
    FROM class_rooms cr
    LEFT JOIN student_profiles sp ON sp.class_room_id = cr.id
    LEFT JOIN users u ON u.id = sp.user_id AND u.is_active = 1 AND FIND_IN_SET('student', u.roles)
    WHERE 1=1
    {$whereLevel}
    GROUP BY cr.id, cr.name, cr.year
    ORDER BY cr.name ASC, cr.year ASC
  ";
  $stmt = $db->prepare($sqlClasses);
  $stmt->execute($paramsLevel);
  $classes = $stmt->fetchAll() ?: [];

  // Siapkan map day_counts default 0
  $map = [];
  foreach ($classes as $c) {
    $cid = (int)$c['id'];
    $map[$cid] = [
      'kelas' => trim((string)$c['name'].' '.(string)$c['year']),
      'total_students' => (int)$c['total_students'],
      'day_counts' => array_fill(0, $days, 0),
    ];
  }

  if (!empty($classes)) {
    $classIds = array_map(fn($x) => (int)$x['id'], $classes);
    $in = implode(',', array_fill(0, count($classIds), '?'));

    // Hitung per kelas per tanggal: jumlah siswa aktif yang mengisi jurnal (distinct)
    // date range Ramadan: RAMADAN_START + 0..days-1
    $start = date('Y-m-d', strtotime(RAMADAN_START));
    $end   = date('Y-m-d', strtotime(RAMADAN_START . ' +'.($days-1).' day'));

    $sql = "
      SELECT sp.class_room_id AS class_id, j.date,
             COUNT(DISTINCT j.student_user_id) AS cnt
      FROM journals j
      JOIN student_profiles sp ON sp.user_id = j.student_user_id
      JOIN users u ON u.id = j.student_user_id
      WHERE j.date BETWEEN ? AND ?
        AND u.is_active = 1
        AND FIND_IN_SET('student', u.roles)
        AND sp.class_room_id IN ({$in})
      GROUP BY sp.class_room_id, j.date
    ";

    $params = array_merge([$start, $end], $classIds);
    $st = $db->prepare($sql);
    $st->execute($params);

    foreach (($st->fetchAll() ?: []) as $r) {
      $cid = (int)$r['class_id'];
      $date = (string)$r['date'];
      $cnt = (int)$r['cnt'];

      // konversi date -> hari ke 1..N
      $dayIndex = (int)floor((strtotime($date) - strtotime($start)) / 86400); // 0-based
      if ($dayIndex >= 0 && $dayIndex < $days && isset($map[$cid])) {
        $map[$cid]['day_counts'][$dayIndex] = $cnt;
      }
    }
  }

  // Output rows dengan nomor
  $outRows = [];
  $no = 1;
  foreach ($map as $cid => $r) {
    $outRows[] = [
      'no' => $no++,
      'kelas' => $r['kelas'],
      'total_students' => $r['total_students'],
      'day_counts' => $r['day_counts'],
    ];
  }

  echo json_encode([
    'days' => $days,
    'rows' => $outRows,
  ], JSON_UNESCAPED_UNICODE);

  return;
}
}