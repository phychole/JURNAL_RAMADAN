<?php
namespace App\Models;

use PDO;

class SermonNote {
  public static function upsert(int $studentUserId, string $date, array $data): void {
    $sql = "INSERT INTO sermon_notes (student_user_id, date, title, content)
            VALUES (?,?,?,?)
            ON DUPLICATE KEY UPDATE title=VALUES(title), content=VALUES(content)";
    $stmt = db()->prepare($sql);
    $stmt->execute([$studentUserId, $date, $data['title'] ?? null, $data['content'] ?? null]);
  }

  public static function getForStudentDate(int $studentUserId, string $date): ?array {
    $stmt = db()->prepare("SELECT * FROM sermon_notes WHERE student_user_id=? AND date=?");
    $stmt->execute([$studentUserId, $date]);
    $r = $stmt->fetch(PDO::FETCH_ASSOC);
    return $r ?: null;
  }
}
