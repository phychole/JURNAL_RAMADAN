<?php
namespace App\Models;

use PDO;

class User {
  public static function findByUsername(string $username): ?array {
    $stmt = db()->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
  }

  public static function find(int $id): ?array {
    $stmt = db()->prepare("SELECT id,name,username,role,is_active FROM users WHERE id=?");
    $stmt->execute([$id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
  }

  public static function create(array $data): int {
    $stmt = db()->prepare("INSERT INTO users (name, username, password_hash, roles, nip, is_active) VALUES (?,?,?,?,?,?)");
    $stmt->execute([
      $data['name'],
      $data['username'],
      $data['password_hash'],
      $data['roles'],
      $data['nip'] ?? null,
      $data['is_active'] ?? 1
    ]);
    return intval(db()->lastInsertId());
  }

  
public static function listStudents(string $q = ''): array {
  $sql = "SELECT u.id,u.name,u.username,u.roles,u.nip,u.is_active, sp.nis, sp.class_room_id, cr.name AS class_name, cr.year AS class_year
          FROM users u
          JOIN student_profiles sp ON sp.user_id=u.id
          JOIN class_rooms cr ON cr.id=sp.class_room_id
          WHERE FIND_IN_SET('student', u.roles) ";
  $params = [];
  if ($q !== '') {
    $sql .= " AND (u.name LIKE ? OR u.username LIKE ? OR sp.nis LIKE ? OR cr.name LIKE ?)";
    $like = "%{$q}%";
    $params = [$like,$like,$like,$like];
  }
  $sql .= " ORDER BY cr.year DESC, cr.name, u.name";
  $st = db()->prepare($sql);
  $st->execute($params);
  return $st->fetchAll();
}

public static function listTeachers(string $q = ''): array {
  $sql = "SELECT id,name,username,roles,nip,is_active FROM users
          WHERE (FIND_IN_SET('homeroom', roles) OR FIND_IN_SET('religion_teacher', roles) OR FIND_IN_SET('principal', roles))";
  $params = [];
  if ($q !== '') {
    $sql .= " AND (name LIKE ? OR username LIKE ? OR nip LIKE ? OR roles LIKE ?)";
    $like = "%{$q}%";
    $params = [$like,$like,$like,$like];
  }
  $sql .= " ORDER BY name";
  $st = db()->prepare($sql);
  $st->execute($params);
  return $st->fetchAll();
}

public static function updateUser(int $id, array $data): void {
  $sql = "UPDATE users SET name=?, username=?, roles=?, nip=?, is_active=? WHERE id=?";
  $st = db()->prepare($sql);
  $st->execute([
    $data['name'],
    $data['username'],
    $data['roles'],
    $data['nip'] ?? null,
    $data['is_active'] ?? 1,
    $id
  ]);
}

  public static function allTeachers(string $role): array {
    $stmt = db()->prepare("SELECT id,name,username,roles,nip FROM users WHERE FIND_IN_SET(?, roles) AND is_active=1 ORDER BY name");
    $stmt->execute([$role]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }
  public static function listStudentsPaged(string $q, int $limit, int $offset): array {
  $db = db();

  $like = '%' . $q . '%';

  $where = "WHERE FIND_IN_SET('student', u.roles)";
  $params = [];

  if ($q !== '') {
    $where .= " AND (
      u.name LIKE ? OR u.username LIKE ? OR sp.nis LIKE ? OR cr.name LIKE ? OR CAST(cr.year AS CHAR) LIKE ?
    )";
    $params = [$like, $like, $like, $like, $like];
  }

  // total
  $sqlTotal = "
    SELECT COUNT(*) AS cnt
    FROM users u
    LEFT JOIN student_profiles sp ON sp.user_id=u.id
    LEFT JOIN class_rooms cr ON cr.id=sp.class_room_id
    $where
  ";
  $st = $db->prepare($sqlTotal);
  $st->execute($params);
  $total = (int)($st->fetch()['cnt'] ?? 0);

  // rows
  $sql = "
    SELECT u.id, u.name, u.username, u.is_active,
           sp.nis, cr.name AS class_name, cr.year AS class_year
    FROM users u
    LEFT JOIN student_profiles sp ON sp.user_id=u.id
    LEFT JOIN class_rooms cr ON cr.id=sp.class_room_id
    $where
    ORDER BY 
  cr.year ASC,
  cr.name ASC,
  u.name ASC
LIMIT $limit OFFSET $offset
  ";
  $st2 = $db->prepare($sql);
  $st2->execute($params);
  $rows = $st2->fetchAll();

  return ['rows' => $rows ?: [], 'total' => $total];
}
}
