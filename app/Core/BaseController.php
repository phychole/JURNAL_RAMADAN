<?php
namespace App\Core;

class BaseController {
  protected function view(string $view, array $data = []): void {
    extract($data);
    require APP_ROOT . '/app/views/layout/header.php';
    require APP_ROOT . '/app/views/' . $view . '.php';
    require APP_ROOT . '/app/views/layout/footer.php';
  }

  protected function redirect(string $path): void {
    $path = '/' . ltrim($path, '/');
    $base = defined('BASE_URL') ? BASE_URL : '';
    header("Location: {$base}{$path}");
    exit;
  }

  protected function flash(string $key, string $message): void {
    $_SESSION['_flash'][$key] = $message;
  }

  protected function getFlash(string $key): ?string {
    $msg = $_SESSION['_flash'][$key] ?? null;
    if (isset($_SESSION['_flash'][$key])) unset($_SESSION['_flash'][$key]);
    return $msg;
  }
}
