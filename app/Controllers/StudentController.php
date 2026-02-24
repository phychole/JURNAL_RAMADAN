<?php
namespace App\Controllers;

use App\Core\BaseController;
use App\Core\Auth;
use App\Models\StudentProfile;
use App\Models\Journal;
use App\Models\SermonNote;
use App\Models\GoodDeed;
use App\Models\ExtraActivity;

class StudentController extends BaseController {
  public function journal(): void {
  Auth::requireRole(['student']);
  $user = Auth::user();
  $profile = StudentProfile::findByUserId((int)$user['id']);

  $day = (int)($_GET['day'] ?? 1);
  if ($day < 1) $day = 1;
  if ($day > (int)RAMADAN_DAYS) $day = (int)RAMADAN_DAYS;

  // tanggal riil (untuk penyimpanan DB)
  $date = date('Y-m-d', strtotime(RAMADAN_START . ' +' . ($day - 1) . ' day'));

  $journal = Journal::getForStudentDate((int)$user['id'], $date);
  $sermon  = SermonNote::getForStudentDate((int)$user['id'], $date);
  $good    = GoodDeed::getForStudentDate((int)$user['id'], $date);
  $extra   = ExtraActivity::getForStudentDate((int)$user['id'], $date);

  $this->view('student/journal', [
    'user' => $user,
    'profile' => $profile,
    'day' => $day,
    'date' => $date,
    'journal' => $journal,
    'sermon' => $sermon,
    'good' => $good,
    'extra' => $extra,
    'success' => $this->getFlash('success'),
    'error' => $this->getFlash('error'),
  ]);
}


  public function saveJournal(): void {
  Auth::requireRole(['student']);
  $userId = (int)Auth::user()['id'];

  $day = (int)($_POST['day'] ?? 1);
  if ($day < 1) $day = 1;
  if ($day > (int)RAMADAN_DAYS) $day = (int)RAMADAN_DAYS;

  $date = date('Y-m-d', strtotime(RAMADAN_START . ' +' . ($day - 1) . ' day'));
Journal::upsert($userId, $date, $_POST);
    SermonNote::upsert($userId, $date, $_POST);
    GoodDeed::upsert($userId, $date, $_POST);
    ExtraActivity::upsert($userId, $date, $_POST);

    $this->flash('success', 'Data tersimpan.');
    $this->redirect('/student/journal?day=' . (int)$day);
  }

public function material(): void {
  Auth::requireLogin(); // bukan requireRole(['student'])
  $this->view('student/material', [
    'success' => $this->getFlash('success'),
    'error' => $this->getFlash('error'),
  ]);
}
}
