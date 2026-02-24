<?php
namespace App\Models;

use PDO;

class ExtraActivity {
  public static function upsert(int $studentUserId, string $date, array $data): void {
    $sql = "INSERT INTO extra_activities (student_user_id, date, pondok_ramadhan, ziarah, idulfitri_prep)
            VALUES (?,?,?,?,?)
            ON DUPLICATE KEY UPDATE pondok_ramadhan=VALUES(pondok_ramadhan),
            ziarah=VALUES(ziarah), idulfitri_prep=VALUES(idulfitri_prep)";
    $stmt = db()->prepare($sql);
    $stmt->execute([
      $studentUserId, $date,
      $data['pondok_ramadhan'] ?? null,
      $data['ziarah'] ?? null,
      $data['idulfitri_prep'] ?? null
    ]);
  }

  public static function getForStudentDate(int $studentUserId, string $date): ?array {
    $stmt = db()->prepare("SELECT * FROM extra_activities WHERE student_user_id=? AND date=?");
    $stmt->execute([$studentUserId, $date]);
    $r = $stmt->fetch(PDO::FETCH_ASSOC);
    return $r ?: null;
  }
}
