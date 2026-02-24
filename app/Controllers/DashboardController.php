<?php
namespace App\Controllers;

use App\Core\BaseController;
use App\Core\Auth;

class DashboardController extends BaseController {
  public function index(): void {
    Auth::requireLogin();

    if (Auth::hasRole('admin')) {
      $this->redirect('/admin');
    }
    if (Auth::hasRole('student')) {
      $this->redirect('/student/journal');
    }
    if (Auth::hasRole('principal')) {
      $this->redirect('/principal/class');
    }
    if (Auth::hasRole('homeroom')) {
      $this->redirect('/homeroom/class');
    }
    if (Auth::hasRole('religion_teacher')) {
      $this->redirect('/religion/class');
    }

    $this->flash('error', 'Role tidak dikenali.');
    $this->redirect('/login');
  }
}