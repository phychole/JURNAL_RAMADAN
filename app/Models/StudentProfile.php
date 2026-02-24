<?php
namespace App\Models;

use PDO;

class StudentProfile {
  public static function findByUserId(int $userId): ?array {
    $stmt = db()->prepare("SELECT sp.*, cr.name AS class_name, cr.year AS class_year
                           FROM student_profiles sp
                           JOIN class_rooms cr ON cr.id = sp.class_room_id
                           WHERE sp.user_id=?");
    $stmt->execute([$userId]);
    $r = $stmt->fetch(PDO::FETCH_ASSOC);
    return $r ?: null;
  }

  public static function studentsByClass(int $classRoomId): array {
    $stmt = db()->prepare("SELECT u.id AS user_id, u.name, sp.nis, sp.gender
                           FROM student_profiles sp
                           JOIN users u ON u.id = sp.user_id
                           WHERE sp.class_room_id=?
                           ORDER BY u.name");
    $stmt->execute([$classRoomId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }

  public static function create(array $data): int {
    $stmt = db()->prepare("INSERT INTO student_profiles (user_id, class_room_id, nis, gender, phone, address)
                           VALUES (?,?,?,?,?,?)");
    $stmt->execute([
      $data['user_id'], $data['class_room_id'], $data['nis'],
      $data['gender'] ?? null, $data['phone'] ?? null, $data['address'] ?? null
    ]);
    return intval(db()->lastInsertId());
  }
}
