<?php
declare(strict_types=1);

session_start();

define('APP_ROOT', dirname(__DIR__));
require APP_ROOT . '/app/config/app.php';
require APP_ROOT . '/app/config/db.php';

// Basic autoload (no composer required for app/)
spl_autoload_register(function ($class) {
  $prefix = 'App\\';
  if (str_starts_with($class, $prefix)) {
    $rel = substr($class, strlen($prefix));
    $rel = str_replace('\\', DIRECTORY_SEPARATOR, $rel);
    $file = APP_ROOT . '/app/' . $rel . '.php';
    if (file_exists($file)) require $file;
  }
});

// Optional composer autoload for Dompdf/PhpSpreadsheet if user runs composer install
$composerAutoload = APP_ROOT . '/vendor/autoload.php';
if (file_exists($composerAutoload)) {
  require $composerAutoload;
}

use App\Core\Router;

// ✅ Base URL otomatis (aman walau nama folder berubah & bisa hide /public)
$basePath = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
// Jika server mengarah ke project root dan rewrite ke /public, hilangkan "/public" dari base url
if ($basePath === '/public') $basePath = '';
if (str_ends_with($basePath, '/public')) $basePath = substr($basePath, 0, -strlen('/public'));
define('BASE_URL', $basePath === '' ? '' : $basePath);



$router = new Router();

// Auth
$router->get('/', 'App\\Controllers\\AuthController@loginForm');
$router->get('/login', 'App\\Controllers\\AuthController@loginForm');
$router->post('/login', 'App\\Controllers\\AuthController@login');
$router->get('/logout', 'App\\Controllers\\AuthController@logout');

// Dashboards
$router->get('/dashboard', 'App\\Controllers\\DashboardController@index');

// Student
$router->get('/student/journal', 'App\\Controllers\\StudentController@journal');
$router->post('/student/journal/save', 'App\\Controllers\\StudentController@saveJournal');
$router->get('/student/material', 'App\\Controllers\\StudentController@material');
$router->get('/student/report', 'App\\Controllers\\ReportController@studentReport');
$router->get('/student/report/xls', 'App\\Controllers\\ReportController@studentXls');
// (PDF removed) legacy path -> XLS
$router->get('/student/report/pdf', 'App\\Controllers\\ReportController@studentXls');

// Homeroom
$router->get('/homeroom/class', 'App\\Controllers\\HomeroomController@classReport');
$router->get('/homeroom/student', 'App\\Controllers\\HomeroomController@studentDetail');
$router->get('/homeroom/report/xls', 'App\\Controllers\\ReportController@classXls');
// legacy path -> XLS
$router->get('/homeroom/report/pdf', 'App\\Controllers\\ReportController@classXls');

// Religion Teacher
$router->get('/religion/class', 'App\\Controllers\\ReligionTeacherController@classReport');
$router->get('/religion/student', 'App\\Controllers\\ReligionTeacherController@studentDetail');
$router->get('/religion/report/xls', 'App\\Controllers\\ReportController@classXls');

// Admin
$router->get('/admin', 'App\\Controllers\\AdminController@index');
$router->get('/admin/import', 'App\\Controllers\\AdminController@importForm');
$router->post('/admin/import', 'App\\Controllers\\AdminController@importProcess');
$router->get('/admin/classes', 'App\\Controllers\\AdminController@classes');
$router->post('/admin/classes/create', 'App\\Controllers\\AdminController@createClass');

// Admin CRUD
$router->get('/admin/students', 'App\\Controllers\\AdminController@students');
$router->get('/admin/students/edit', 'App\\Controllers\\AdminController@editStudent');
$router->post('/admin/students/update', 'App\\Controllers\\AdminController@updateStudent');
$router->post('/admin/students/deactivate', 'App\\Controllers\\AdminController@deactivateStudent');
$router->post('/admin/students/activate', 'App\\Controllers\\AdminController@activateStudent');
$router->post('/admin/students/delete', 'App\\Controllers\\AdminController@deleteStudent');


$router->get('/admin/teachers', 'App\\Controllers\\AdminController@teachers');
$router->get('/admin/teachers/edit', 'App\\Controllers\\AdminController@editTeacher');
$router->post('/admin/teachers/update', 'App\\Controllers\\AdminController@updateTeacher');
$router->post('/admin/classes/delete', 'App\\Controllers\\AdminController@deleteClass');
$router->get('/admin/mapping-homeroom', 'App\\Controllers\\AdminController@mappingHomeroom');
$router->post('/admin/mapping-homeroom/save', 'App\\Controllers\\AdminController@saveMappingHomeroom');
$router->post('/admin/mapping-homeroom/delete', 'App\\Controllers\\AdminController@deleteMappingHomeroom');
$router->get('/admin/mapping-religion', 'App\\Controllers\\AdminController@mappingReligion');
$router->post('/admin/mapping-religion/save', 'App\\Controllers\\AdminController@saveMappingReligion');
$router->post('/admin/mapping-religion/delete', 'App\\Controllers\\AdminController@deleteMappingReligion');
// Principal (Kepala Sekolah)
$router->get('/principal/class', 'App\\Controllers\\PrincipalController@classReport');
$router->get('/principal/student', 'App\\Controllers\\PrincipalController@studentDetail');



// Public Dashboard (tanpa login)
$router->get('/public/report', 'App\\Controllers\\PublicReportController@index');
$router->get('/public/report/data', 'App\\Controllers\\PublicReportController@data');             // harian
$router->get('/public/report/analytics', 'App\\Controllers\\PublicReportController@analytics');  // lanjutan
$router->get('/public/report/advanced', 'App\\Controllers\\PublicReportController@advanced');
$router->get('/public/report/table', 'App\\Controllers\\PublicReportController@table');
$router->get('/public/report/rekap', 'App\\Controllers\\PublicReportController@rekap');

// Public - cari NISN/NIS siswa (tanpa login)
$router->get('/public/student-lookup', 'App\\Controllers\\PublicStudentLookupController@index');

$router->get('/material', 'App\\Controllers\\StudentController@material');

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';

// Jika app diakses via subfolder, buang prefix BASE_URL dari path
if (BASE_URL !== '' && BASE_URL !== '/' && str_starts_with($path, BASE_URL)) {
  $path = substr($path, strlen(BASE_URL));
}
if ($path === '') $path = '/';

$router->dispatch($_SERVER['REQUEST_METHOD'], $path);
