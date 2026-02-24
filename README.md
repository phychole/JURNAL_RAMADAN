# Buku Kegiatan Ramadan (PHP Native + Bootstrap)

## Jalankan di Laragon (Apache)
1. Buat database `ramadhan_book`
2. Import `database.sql`
3. Jalankan seed admin:
   - Buka terminal di folder project
   - `php seed.php`
4. (Opsional tapi disarankan) Install dependency untuk PDF & XLSX:
   - `composer install`
5. Arahkan Document Root ke folder `public/`
   - Laragon: klik kanan project -> www -> pilih folder project
   - Pastikan URL mengarah ke `.../public`

## Akun default
- admin / admin123 (dibuat oleh seed.php)

## PENTING INI YA...
- Untuk mengganti password admin silahkan edit script seed.php atau reset_password.php
- Pastikan hapus file seed.php dan reset_password.php agar tidak diakses oleh orang lain saat dipublish untuk publik

## Import Data
- Admin -> Import
- XLSX butuh `composer install` (PhpSpreadsheet)
- Alternatif: simpan file Excel sebagai CSV

di bagian import bisa mengimport untuk data dari kelas, siswa dan guru

## Report
- Siswa punya report pribadi + chart
- Wali kelas & guru agama punya rekap kelas + chart.

## Konfigurasi Ramadan
Edit:
- `app/config/app.php` -> `RAMADAN_START`, `RAMADAN_DAYS`
Atau set env var:
- `RAMADAN_START=2026-02-19`
- `RAMADAN_DAYS=30`
## Maping Wali Kelas dan Guru Agama
menu maping wali kelas dan guru agama

## Informasi dari kami
Aplikasi ini dibuat secara sederhana dan boleh dikembangkan sesuai dengan kebutuhan. Mohon maaf jika masih banyak kekurangan karena memang dibuat untuk sesederhana mungkin. Saya menerima saran dan masukan untuk kita kembangkan bersama
