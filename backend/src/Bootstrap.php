<?php
declare(strict_types=1);

namespace App;

class Bootstrap
{
    private static bool $loaded = false;

    public static function init(): void
    {
        if (self::$loaded) return;
        self::$loaded = true;

        // Load .env manually
        $envFile = dirname(__DIR__) . '/.env';
        if (file_exists($envFile)) {
            foreach (file($envFile) as $line) {
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
        ini_set('log_errors', '1');

        set_exception_handler(function (\Throwable $e) {
            http_response_code(500);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => false,
                'error' => 'Internal Server Error'
            ]);
        });
    }
}
