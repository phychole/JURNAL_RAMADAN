<?php
namespace App\Models;

use PDO;

class ClassRoom {
  public static function all(): array {
    return db()->query("SELECT * FROM class_rooms ORDER BY year DESC, name ASC")->fetchAll(PDO::FETCH_ASSOC);
  }

  public static function find(int $id): ?array {
    $stmt = db()->prepare("SELECT * FROM class_rooms WHERE id=? LIMIT 1");
    $stmt->execute([$id]);
    $r = $stmt->fetch(PDO::FETCH_ASSOC);
    return $r ?: null;
  }
  public static function findByNameYear(string $name, int $year): ?array {
    $stmt = db()->prepare("SELECT * FROM class_rooms WHERE name=? AND year=?");
    $stmt->execute([$name, $year]);
    $r = $stmt->fetch(PDO::FETCH_ASSOC);
    return $r ?: null;
  }
  public static function create(string $name, int $year): int {
    $stmt = db()->prepare("INSERT INTO class_rooms (name, year) VALUES (?,?)");
    $stmt->execute([$name, $year]);
    return intval(db()->lastInsertId());
  }

public static function deleteById(int $id): bool {
  $db = db();
  $stmt = $db->prepare("DELETE FROM class_rooms WHERE id = ?");
  $stmt->execute([$id]);
  return $stmt->rowCount() > 0;
}
public static function findById(int $id): ?array {
  $st = db()->prepare("SELECT * FROM class_rooms WHERE id=? LIMIT 1");
  $st->execute([$id]);
  $r = $st->fetch();
  return $r ?: null;
}


}
