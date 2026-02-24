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

  /** Hitung hari maksimal yang boleh diisi berdasarkan tanggal hari ini */
  private function maxDayAllowed(): int
  {
    $today = date('Y-m-d');
    $start = date('Y-m-d', strtotime(RAMADAN_START));
    $days  = (int)RAMADAN_DAYS;

    // Jika hari ini sebelum RAMADAN_START, batasi hanya hari 1
    if ($today < $start) return 1;

    $diffDays = (int)floor((strtotime($today) - strtotime($start)) / 86400) + 1;
    $max = max(1, min($days, $diffDays));
    return $max;
  }

  private function clampDay(int $day): int
  {
    if ($day < 1) return 1;
    $max = (int)RAMADAN_DAYS;
    if ($day > $max) return $max;
    return $day;
  }

  public function journal(): void {
    Auth::requireRole(['student']);

    $user = Auth::user();
    $profile = StudentProfile::findByUserId((int)$user['id']);

    $maxDayAllowed = $this->maxDayAllowed();

    // Default: buka hari yang boleh paling akhir (hari ini)
    $day = (int)($_GET['day'] ?? $maxDayAllowed);
    $day = $this->clampDay($day);

    // Jika user memilih hari masa depan, paksa ke maxDayAllowed
    if ($day > $maxDayAllowed) $day = $maxDayAllowed;

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
      'maxDayAllowed' => $maxDayAllowed, // <-- penting untuk disable dropdown
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

    $maxDayAllowed = $this->maxDayAllowed();

    $day = (int)($_POST['day'] ?? $maxDayAllowed);
    $day = $this->clampDay($day);

    // Tolak jika mencoba isi hari masa depan
    if ($day > $maxDayAllowed) {
      $this->flash('error', 'Belum bisa mengisi hari tersebut. Silakan isi sampai hari ini saja.');
      $this->redirect('/student/journal?day=' . $maxDayAllowed);
      return;
    }

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
