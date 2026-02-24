<?php
declare(strict_types=1);

define('APP_NAME', 'Jurnal Kegiatan Ramadan 1447 H');
define('APP_BRAND_COLOR', '#198754');      // Bootstrap green
define('APP_BRAND_SOFT', '#d1e7dd');       // Soft green
define('APP_TIMEZONE', 'Asia/Jakarta');

date_default_timezone_set(APP_TIMEZONE);

// Ramadan date range (admin can later make this configurable)
// Default: 30 days from 2026-03-01 (placeholder). Change as needed.
define('RAMADAN_START', getenv('RAMADAN_START') ?: '2026-02-19');
define('RAMADAN_DAYS', intval(getenv('RAMADAN_DAYS') ?: '30'));

// Tahun Hijriyah Ramadan untuk label (Hari 1 Ramadan XXXX H)
define('RAMADAN_HIJRI_YEAR', '1447');
