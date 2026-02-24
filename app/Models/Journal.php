<?php
namespace App\Models;

use PDO;

class Journal {
  public static function upsert(int $studentUserId, string $date, array $data): void {
    $sql = "INSERT INTO journals
      (student_user_id, date, shubuh, dzuhur, ashar, maghrib, isya, tarawih, witir, tadarus_pages, fasting, notes)
      VALUES (?,?,?,?,?,?,?,?,?,?,?,?)
      ON DUPLICATE KEY UPDATE
        shubuh=VALUES(shubuh), dzuhur=VALUES(dzuhur), ashar=VALUES(ashar),
        maghrib=VALUES(maghrib), isya=VALUES(isya), tarawih=VALUES(tarawih),
        witir=VALUES(witir), tadarus_pages=VALUES(tadarus_pages), fasting=VALUES(fasting),
        notes=VALUES(notes)";
    $stmt = db()->prepare($sql);
    $stmt->execute([
      $studentUserId, $date,
      (int)($data['shubuh'] ?? 0),
      (int)($data['dzuhur'] ?? 0),
      (int)($data['ashar'] ?? 0),
      (int)($data['maghrib'] ?? 0),
      (int)($data['isya'] ?? 0),
      (int)($data['tarawih'] ?? 0),
      (int)($data['witir'] ?? 0),
      (int)($data['tadarus_pages'] ?? 0),
      (int)($data['fasting'] ?? 0),
      $data['notes'] ?? null
    ]);
  }

  public static function getForStudentDate(int $studentUserId, string $date): ?array {
    $stmt = db()->prepare("SELECT * FROM journals WHERE student_user_id=? AND date=?");
    $stmt->execute([$studentUserId, $date]);
    $r = $stmt->fetch(PDO::FETCH_ASSOC);
    return $r ?: null;
  }

  public static function filledDaysCount(int $studentUserId, string $from, string $to): int {
    $stmt = db()->prepare("SELECT COUNT(*) AS c FROM journals WHERE student_user_id=? AND date BETWEEN ? AND ?");
    $stmt->execute([$studentUserId, $from, $to]);
    return intval($stmt->fetch(PDO::FETCH_ASSOC)['c'] ?? 0);
  }

  public static function listDates(int $studentUserId, string $from, string $to): array {
    $stmt = db()->prepare("SELECT date FROM journals WHERE student_user_id=? AND date BETWEEN ? AND ? ORDER BY date");
    $stmt->execute([$studentUserId, $from, $to]);
    return array_map(fn($r) => $r['date'], $stmt->fetchAll(PDO::FETCH_ASSOC));
  }
}
