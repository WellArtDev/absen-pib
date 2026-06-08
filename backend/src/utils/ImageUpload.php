<?php
declare(strict_types=1);

namespace App\Utils;

class ImageUpload
{
    private string $uploadDir;

    public function __construct()
    {
        $this->uploadDir = $_ENV['UPLOAD_DIR'] ?? __DIR__ . '/../../uploads';
    }

    /**
     * Upload & compress image. Returns public URL.
     */
    public function upload(array $file, string $subfolder, int $maxWidth = 1080, int $quality = 70): string
    {
        if ($file['error'] !== UPLOAD_ERR_OK) {
            \App\Response::error('Gagal upload foto: ' . $file['error']);
        }

        $tmpPath = $file['tmp_name'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'webp'];
        if (!in_array($ext, $allowed, true)) {
            \App\Response::error('Format foto tidak diizinkan: ' . implode(', ', $allowed));
        }

        $destDir = rtrim($this->uploadDir, '/') . '/' . trim($subfolder, '/');
        if (!is_dir($destDir)) {
            mkdir($destDir, 0755, true);
        }

        $filename = date('Ymd_His') . '_' . bin2hex(random_bytes(8)) . '.jpg';
        $destPath = $destDir . '/' . $filename;

        // Create image from source
        $src = match ($ext) {
            'png' => imagecreatefrompng($tmpPath),
            'webp' => imagecreatefromwebp($tmpPath),
            default => imagecreatefromjpeg($tmpPath),
        };

        if (!$src) {
            \App\Response::error('Gagal membaca file gambar');
        }

        // Resize if wider than maxWidth
        $origW = imagesx($src);
        $origH = imagesy($src);

        if ($origW > $maxWidth) {
            $ratio = $maxWidth / $origW;
            $newW = $maxWidth;
            $newH = (int) ($origH * $ratio);
            $resized = imagecreatetruecolor($newW, $newH);
            imagecopyresampled($resized, $src, 0, 0, 0, 0, $newW, $newH, $origW, $origH);
            imagedestroy($src);
            $src = $resized;
        }

        // Save as JPEG with compression
        imagejpeg($src, $destPath, $quality);
        imagedestroy($src);

        $appUrl = rtrim($_ENV['APP_URL'] ?? '', '/');
        return $appUrl . '/uploads/' . $subfolder . '/' . $filename;
    }

    /**
     * Accept base64 encoded image from mobile app
     */
    public function uploadBase64(string $base64, string $subfolder, int $maxWidth = 1080, int $quality = 70): string
    {
        // Remove data URI prefix if present
        if (str_contains($base64, 'base64,')) {
            $base64 = explode('base64,', $base64)[1];
        }

        $decoded = base64_decode($base64, true);
        if (!$decoded) {
            \App\Response::error('Data gambar tidak valid');
        }

        $destDir = rtrim($this->uploadDir, '/') . '/' . trim($subfolder, '/');
        if (!is_dir($destDir)) {
            mkdir($destDir, 0755, true);
        }

        $filename = date('Ymd_His') . '_' . bin2hex(random_bytes(8)) . '.jpg';
        $destPath = $destDir . '/' . $filename;

        $src = imagecreatefromstring($decoded);
        if (!$src) {
            \App\Response::error('Gagal decode gambar');
        }

        $origW = imagesx($src);
        $origH = imagesy($src);
        if ($origW > $maxWidth) {
            $ratio = $maxWidth / $origW;
            $resized = imagecreatetruecolor($maxWidth, (int) ($origH * $ratio));
            imagecopyresampled($resized, $src, 0, 0, 0, 0, $maxWidth, (int) ($origH * $ratio), $origW, $origH);
            imagedestroy($src);
            $src = $resized;
        }

        imagejpeg($src, $destPath, $quality);
        imagedestroy($src);

        $appUrl = rtrim($_ENV['APP_URL'] ?? '', '/');
        return $appUrl . '/uploads/' . $subfolder . '/' . $filename;
    }
}
