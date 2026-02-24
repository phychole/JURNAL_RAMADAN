<?php
namespace App\Core;

use App\Models\User;

class Auth {
  public static function user(): ?array {
    return $_SESSION['user'] ?? null;
  }

  public static function check(): bool {
    return isset($_SESSION['user']);
  }

  public static function roles(): array {
    $u = self::user();
    if (!$u) return [];
    $rolesStr = (string)($u['roles'] ?? ($u['role'] ?? ''));
    $parts = array_filter(array_map('trim', explode(',', $rolesStr)));
    return array_values(array_unique($parts));
  }

  public static function hasRole(string $role): bool {
    return in_array($role, self::roles(), true);
  }

  public static function requireLogin(): void {
    if (!self::check()) {
      header("Location: /login");
      exit;
    }
  }

  public static function requireRole(array $roles): void {
    self::requireLogin();
    $current = self::roles();
    foreach ($roles as $r) {
      if (in_array($r, $current, true)) return;
    }
    http_response_code(403);
    echo "403 Forbidden";
    exit;
  }

  public static function attempt(string $username, string $password): bool {
    $u = User::findByUsername($username);
    if (!$u || !(int)$u['is_active']) return false;
    if (!password_verify($password, $u['password_hash'])) return false;
    unset($u['password_hash']);

    // fallback: jika roles kosong tapi role lama ada
    if (empty($u['roles']) && !empty($u['role'])) $u['roles'] = $u['role'];

    $_SESSION['user'] = $u;
    return true;
  }

  public static function logout(): void {
    unset($_SESSION['user']);
    session_regenerate_id(true);
  }
}
