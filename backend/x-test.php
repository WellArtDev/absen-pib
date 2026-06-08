<?php
/**
 * Minimal API test — no composer autoload, all manual requires
 */
error_reporting(E_ALL);
ini_set('display_errors', '1');

try {
    // Manual requires
    $SRC = __DIR__ . '/src';
    require_once $SRC . '/Database.php';
    require_once $SRC . '/Response.php';
    require_once $SRC . '/Auth.php';
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

    // Load .env manually
    if (file_exists(__DIR__ . '/.env')) {
        foreach (file(__DIR__ . '/.env') as $line) {
            $line = trim($line);
            if ($line && !str_starts_with($line, '#') && str_contains($line, '=')) {
                [$k, $v] = explode('=', $line, 2);
                $_ENV[trim($k)] = trim($v);
            }
        }
    }

    echo "<h2>1. Files loaded</h2>✅ All manual requires OK<br>";

    // Test AuthController
    $ctrl = new App\Controllers\AuthController();
    echo "<h2>2. AuthController instance</h2>✅ " . get_class($ctrl) . "<br>";

    // Test Router
    $router = new App\Router();
    require_once $SRC . '/routes.php';
    echo "<h2>3. Routes loaded</h2>✅<br>";

    // Test login via router
    ob_start();
    $router->dispatch('POST', '/auth/login',
        ['email' => 'superadmin@absenpib.com', 'password' => 'admin123'],
        []
    );
    $output = ob_get_clean();
    echo "<h2>4. POST /auth/login response:</h2>";
    $resp = json_decode($output, true);
    if ($resp) {
        echo ($resp['success'] ?? false) ? '✅ SUCCESS' : '❌ ' . ($resp['error'] ?? '');
        echo "<pre>" . substr($output, 0, 200) . "</pre>";
    } else {
        echo "RAW: <pre>" . htmlspecialchars(substr($output, 0, 500)) . "</pre>";
    }

} catch (Throwable $e) {
    echo "<h1>💥 " . get_class($e) . ": " . $e->getMessage() . "</h1>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
