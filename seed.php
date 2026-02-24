<?php
// Run in terminal: php seed.php
require __DIR__ . '/app/config/app.php';
require __DIR__ . '/app/config/db.php';

$adminUser = 'admin';
$adminPass = 'admin123';

$hash = password_hash($adminPass, PASSWORD_BCRYPT);

$stmt = db()->prepare("SELECT id FROM users WHERE username=?");
$stmt->execute([$adminUser]);
$exists = $stmt->fetch();

if ($exists) {
  $stmt2 = db()->prepare("UPDATE users SET password_hash=?, roles='admin', is_active=1, name='Admin' WHERE username=?");
  $stmt2->execute([$hash, $adminUser]);
  echo "Admin updated: {$adminUser} / {$adminPass}\n";
} else {
  $stmt2 = db()->prepare("INSERT INTO users(name, username, password_hash, roles, is_active) VALUES (?,?,?,?,1)");
  $stmt2->execute(['Admin', $adminUser, $hash, 'admin']);
  echo "Admin created: {$adminUser} / {$adminPass}\n";
}

echo "Done.\n";
