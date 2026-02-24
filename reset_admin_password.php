<?php
/**
 * Reset Password Admin (CLI)
 *
 * Cara pakai (Laragon/terminal):
 *   php reset_admin_password.php admin admin123
 *
 * Jika username tidak ada, akan dibuat.
 */

declare(strict_types=1);

require __DIR__ . '/app/config/app.php';
require __DIR__ . '/app/config/db.php';

$argv = $_SERVER['argv'] ?? [];
$username = $argv[1] ?? 'admin';
$newPass  = $argv[2] ?? 'admin123';

if (!is_string($username) || trim($username) === '') {
  echo "Username tidak valid.\n";
  exit(1);
}
if (!is_string($newPass) || trim($newPass) === '') {
  echo "Password baru tidak valid.\n";
  exit(1);
}

$username = trim($username);
$newPass = (string)$newPass;
$hash = password_hash($newPass, PASSWORD_BCRYPT);

$stmt = db()->prepare('SELECT id FROM users WHERE username=? LIMIT 1');
$stmt->execute([$username]);
$row = $stmt->fetch();

if ($row) {
  $id = (int)$row['id'];
  db()->prepare("UPDATE users SET password_hash=?, roles=IF(roles='' OR roles IS NULL,'admin',roles), is_active=1 WHERE id=?")
    ->execute([$hash, $id]);
  echo "OK. Password admin diupdate untuk '{$username}'.\n";
} else {
  db()->prepare('INSERT INTO users(name, username, password_hash, roles, is_active) VALUES (?,?,?,?,1)')
    ->execute(['Admin', $username, $hash, 'admin']);
  echo "OK. Admin dibuat: '{$username}'.\n";
}

echo "Login: {$username} / {$newPass}\n";
