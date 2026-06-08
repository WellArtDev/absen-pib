<?php
/**
 * AbsenPIB — Single Entry Point for cPanel
 *
 * ALL requests route here via .htaccess RewriteRule ^ index.php
 * No namespace/composer magic — plain requires.
 */

define('ABSPATH', __DIR__);

// ─── Parse request ─────────────────────────────────
$method = $_SERVER['REQUEST_METHOD'];
$reqUri = $_SERVER['REQUEST_URI'] ?? '/';
$uri = strtok($reqUri, '?');
$path = '/' . ltrim($uri, '/');

// ─── Passthrough test files ────────────────────────
$passthrough = ['x-test.php', 'debug.php', 'debug2.php', 'debug3.php', 'debug4.php', 'debug5.php', 'composer-dump.php'];
if (in_array(ltrim($path, '/'), $passthrough) && file_exists(ABSPATH . '/' . ltrim($path, '/'))) {
    require ABSPATH . '/' . ltrim($path, '/');
    exit;
}

// ─── Serve uploaded files ──────────────────────────
if (str_starts_with($path, '/uploads/')) {
    $f = ABSPATH . $path;
    if (file_exists($f)) {
        header('Content-Type: ' . mime_content_type($f));
        header('Cache-Control: public, max-age=31536000');
        readfile($f);
        exit;
    }
}

// ─── OK, it's API/dashboard — load the real app ────
require ABSPATH . '/public/index.php';
