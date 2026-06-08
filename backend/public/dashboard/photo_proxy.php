<?php require_once 'includes/core.php';
// Serve photo from backend uploads
$photoFile = $_GET['file'] ?? '';
$path = __DIR__ . '/../uploads/attendance-photos/' . basename($photoFile);
if (file_exists($path)) {
    header('Content-Type: image/jpeg');
    readfile($path);
} else {
    http_response_code(404);
}
exit;
