<?php
/**
 * AbsenPIB public/index.php
 * Manual requires — no composer PSR-4 needed. Works on cPanel.
 */
define('ABSPATH', dirname(__DIR__));

// ─── Manual autoload ALL source files ──────────────
$SRC = ABSPATH . '/src';
require_once $SRC . '/Database.php';
require_once $SRC . '/Response.php';
require_once $SRC . '/Auth.php';
require_once $SRC . '/Middleware.php';
require_once $SRC . '/Router.php';
require_once $SRC . '/utils/Validator.php';
require_once $SRC . '/utils/ImageUpload.php';
require_once $SRC . '/utils/AntiFakeGps.php';
require_once $SRC . '/utils/CsvExport.php';
require_once $SRC . '/utils/Notification.php';
require_once $SRC . '/controllers/AuthController.php';
require_once $SRC . '/controllers/AttendanceController.php';
require_once $SRC . '/controllers/OvertimeController.php';
require_once $SRC . '/controllers/LeaveController.php';
require_once $SRC . '/controllers/AdminController.php';
require_once $SRC . '/controllers/CompanyController.php';
require_once $SRC . '/controllers/OfficeController.php';
require_once $SRC . '/controllers/ReportController.php';
require_once $SRC . '/controllers/NotificationController.php';

// Vendor (dotenv only)
$vendor = ABSPATH . '/vendor/autoload.php';
if (file_exists($vendor)) require_once $vendor;

// Load .env manually (fallback if dotenv fails)
if (file_exists(ABSPATH . '/.env')) {
    foreach (file(ABSPATH . '/.env') as $line) {
        $line = trim($line);
        if ($line && !str_starts_with($line, '#') && str_contains($line, '=')) {
            [$k, $v] = explode('=', $line, 2);
            $val = trim($v);
            $_ENV[trim($k)] = $val;
            putenv(trim($k) . '=' . $val);
        }
    }
}

// Error handling
error_reporting(0);
ini_set('display_errors', '0');
set_exception_handler(function (\Throwable $e) {
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => false, 'error' => 'Internal Server Error']);
});

// ─── Parse request ─────────────────────────────────
$method = $_SERVER['REQUEST_METHOD'];
$uri = strtok($_SERVER['REQUEST_URI'] ?? '/', '?');
$path = '/' . trim($uri, '/');
$body = json_decode(file_get_contents('php://input'), true) ?? [];
$query = $_GET;

// ─── Root → login page ─────────────────────────────
if ($path === '/') {
    header('Content-Type: text/html; charset=utf-8');
    require __DIR__ . '/dashboard/index.php';
    exit;
}

// ─── Dashboard HTML pages ──────────────────────────
if (str_starts_with($path, '/dashboard')) {
    if ($path === '/dashboard') { header('Location: /dashboard/'); exit; }
    $seg = basename($path) ?: 'index.php';
    $file = __DIR__ . '/dashboard/' . $seg;
    if (is_file($file) && pathinfo($file, PATHINFO_EXTENSION) === 'php') {
        header('Content-Type: text/html; charset=utf-8');
        require $file;
        exit;
    }
    header('Content-Type: text/html; charset=utf-8');
    require __DIR__ . '/dashboard/index.php';
    exit;
}

// ─── API: /api prefix required ─────────────────────
if (!str_starts_with($path, '/api/')) {
    header('Content-Type: application/json; charset=utf-8');
    http_response_code(404);
    echo json_encode([
        'success' => false,
        'error' => 'Not found.',
        'docs' => ['api' => '/api/auth/login', 'dashboard' => '/dashboard/']
    ]);
    exit;
}

$apiPath = '/' . trim(substr($path, 4), '/') ?: '/';

// ─── JSON API response ─────────────────────────────
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($method === 'OPTIONS') { http_response_code(200); exit; }

$router = new App\Router();
require_once $SRC . '/routes.php';
$router->dispatch($method, $apiPath, $body, $query);
