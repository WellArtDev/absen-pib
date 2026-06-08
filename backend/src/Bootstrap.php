<?php
declare(strict_types=1);

namespace App;

use Dotenv\Dotenv;

class Bootstrap
{
    public static function init(): void
    {
        // Manual autoload fallback — cPanel often breaks composer PSR-4
        self::registerAutoloader();

        // Load .env
        $dotenv = Dotenv::createImmutable(__DIR__ . '/..');
        $dotenv->safeLoad();

        // Error handling
        error_reporting(E_ALL);
        ini_set('display_errors', '0');
        ini_set('log_errors', '1');

        set_exception_handler(function (\Throwable $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => $_ENV['NODE_ENV'] === 'development' ? $e->getMessage() : 'Internal Server Error'
            ]);
        });
    }

    /**
     * Load all PHP files from /src subdirs so we don't rely on composer autoload.
     */
    private static function registerAutoloader(): void
    {
        $srcDir = __DIR__;
        $dirs = ['', 'controllers', 'utils', 'models'];

        foreach ($dirs as $sub) {
            $path = $sub ? $srcDir . '/' . $sub : $srcDir;
            $pattern = $path . '/*.php';
            $files = glob($pattern) ?: [];

            foreach ($files as $file) {
                $class = 'App\\' . basename($file, '.php');
                if ($sub && $sub !== '') {
                    $class = 'App\\' . ucfirst($sub) . '\\' . basename($file, '.php');
                }
                // Only load if class not already defined
                if (!class_exists($class, false)) {
                    require_once $file;
                }
            }
        }
    }
}
