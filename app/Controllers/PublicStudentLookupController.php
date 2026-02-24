<?php
namespace App\Controllers;

use App\Core\BaseController;

class PublicStudentLookupController extends BaseController
{
  public function index(): void
  {
    // Halaman publik: tidak perlu login
    $q = trim((string)($_GET['q'] ?? ''));

    // Default: tidak tampil hasil jika belum mengetik
    $rows = [];
    $searched = false;
    $error = '';

    if ($q !== '') {
      $searched = true;

      // Minimal 2 karakter supaya tidak berat
      if (mb_strlen($q) < 2) {
        $error = 'Minimal ketik 2 huruf.';
      } else {
        // Cari berdasarkan nama (aktif saja, role student saja)
        // Catatan: menampilkan NIS (student_profiles.nis) sebagai NISN/NIS sesuai data sekolah
        $sql = "
          SELECT u.name, sp.nis
          FROM users u
          JOIN student_profiles sp ON sp.user_id = u.id
          WHERE FIND_IN_SET('student', u.roles)
            AND u.is_active = 1
            AND u.name LIKE ?
          ORDER BY u.name ASC
          LIMIT 50
        ";
        $stmt = db()->prepare($sql);
        $stmt->execute(['%' . $q . '%']);
        $rows = $stmt->fetchAll() ?: [];
      }
    }

    $this->view('public/student_lookup', [
      'q' => $q,
      'rows' => $rows,
      'searched' => $searched,
      'error' => $error,
    ]);
  }
}