<?php
namespace App\Controllers;

use App\Core\BaseController;
use App\Core\Auth;

class AuthController extends BaseController {
  public function loginForm(): void {
    if (Auth::check()) $this->redirect('/dashboard');
    $this->view('auth/login', [
      'error' => $this->getFlash('error'),
      'info'  => $this->getFlash('info'),
    ]);
  }

  public function login(): void {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    if ($username === '' || $password === '') {
      $this->flash('error', 'Username dan password wajib diisi.');
      $this->redirect('/login');
    }
    if (!Auth::attempt($username, $password)) {
      $this->flash('error', 'Login gagal. Cek username/password.');
      $this->redirect('/login');
    }
    $this->redirect('/dashboard');
  }

  public function logout(): void {
    Auth::logout();
    $this->flash('info', 'Anda sudah logout.');
    $this->redirect('/login');
  }
}
