<?php
namespace App\Models;

use PDO;

class GoodDeed {
  public static function upsert(int $studentUserId, string $date, array $data): void {
    $sql = "INSERT INTO good_deeds (student_user_id, date, charity_amount, social_activity, reflection)
            VALUES (?,?,?,?,?)
            ON DUPLICATE KEY UPDATE charity_amount=VALUES(charity_amount),
            social_activity=VALUES(social_activity), reflection=VALUES(reflection)";
    $stmt = db()->prepare($sql);
    $stmt->execute([
      $studentUserId, $date,
      (int)($data['charity_amount'] ?? 0),
      $data['social_activity'] ?? null,
      $data['reflection'] ?? null
    ]);
  }

  public static function getForStudentDate(int $studentUserId, string $date): ?array {
    $stmt = db()->prepare("SELECT * FROM good_deeds WHERE student_user_id=? AND date=?");
    $stmt->execute([$studentUserId, $date]);
    $r = $stmt->fetch(PDO::FETCH_ASSOC);
    return $r ?: null;
  }
}
