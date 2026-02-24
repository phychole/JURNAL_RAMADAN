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

## Import Siswa
- Admin -> Import
- XLSX butuh `composer install` (PhpSpreadsheet)
- Alternatif: simpan file Excel sebagai CSV

Kolom yang dibaca:
`nis, nama, username, password, kelas, tahun, gender, phone, address`

## Report
- Siswa punya report pribadi + chart
- Wali kelas & guru agama punya rekap kelas + chart
- Download PDF aktif jika dompdf terpasang (composer install). Jika belum, akan tampil HTML printable.

## Konfigurasi Ramadan
Edit:
- `app/config/app.php` -> `RAMADAN_START`, `RAMADAN_DAYS`
Atau set env var:
- `RAMADAN_START=2026-02-19`
- `RAMADAN_DAYS=30`
