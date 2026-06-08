<?php
/**
 * AbsenPIB public/index.php
 * No composer PSR-4 needed. All manual requires.
 */
define('ROOT', dirname(__DIR__));

// ─── Manual autoload ──────────────────────────────
$SRC = ROOT . '/src';
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

// .env
$envFile = ROOT . '/.env';
if (file_exists($envFile)) {
    foreach (file($envFile) as $line) {
        $line = trim($line);
        if ($line && !str_starts_with($line, '#') && str_contains($line, '=')) {
            [$k, $v] = explode('=', $line, 2);
            $_ENV[trim($k)] = trim($v);
            putenv(trim($k) . '=' . trim($v));
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
$rawUri = $_SERVER['REQUEST_URI'] ?? '/';
$uri = strtok($rawUri, '?');
$path = '/' . trim($uri, '/');
$body = json_decode(file_get_contents('php://input'), true) ?? [];
$query = $_GET;

// ─── Root → dashboard login page ───────────────────
if ($path === '/') {
    serveDashboard('index.php');
    exit;
}

// ─── Dashboard HTML pages ──────────────────────────
if (str_starts_with($path, '/dashboard')) {
    if ($path === '/dashboard') { header('Location: /dashboard/'); exit; }
    $seg = ltrim(substr($path, 10), '/') ?: 'index.php';
    serveDashboard($seg);
    exit;
}

// ─── API: /api prefix required ─────────────────────
if (!str_starts_with($path, '/api/')) {
    jsonResponse(404, ['success' => false, 'error' => 'Not found. Use /api/auth/login, /api/attendance/check-in, etc.']);
}

$apiPath = '/' . trim(substr($path, 4), '/') ?: '/';

// ─── JSON API ──────────────────────────────────────
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($method === 'OPTIONS') { http_response_code(200); exit; }

$router = new App\Router();
require_once $SRC . '/routes.php';
$router->dispatch($method, $apiPath, $body, $query);

// ─── Helper functions ──────────────────────────────
function serveDashboard(string $file): void {
    $f = __DIR__ . '/dashboard/' . basename($file);
    if (!is_file($f) || pathinfo($f, PATHINFO_EXTENSION) !== 'php') {
        $f = __DIR__ . '/dashboard/index.php';
    }
    header('Content-Type: text/html; charset=utf-8');
    require $f;
}

function jsonResponse(int $code, array $data): void {
    header('Content-Type: application/json; charset=utf-8');
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}
