<?php
// Login page — standalone, no core.php include
// Cookie sudah diset di sini, baru redirect ke halaman dashboard
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Email tidak valid';
    } elseif (strlen($password) < 6) {
        $error = 'Password minimal 6 karakter';
    } else {
        // Panggil API login via localhost
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $scheme = 'http';
        $apiUrl = "{$scheme}://{$host}/auth/login";

        $ch = curl_init($apiUrl);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode(['email' => $email, 'password' => $password]),
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 10,
        ]);
        $response = json_decode(curl_exec($ch), true);
        curl_close($ch);

        if ($response['success'] ?? false) {
            $token = $response['data']['token'];
            $user  = $response['data']['user'];

            $userJson = json_encode($user, JSON_UNESCAPED_UNICODE);
            header('Set-Cookie: absen_token=' . $token . '; Path=/; Max-Age=86400; HttpOnly', false);
            header('Set-Cookie: absen_user=' . $userJson . '; Path=/; Max-Age=86400; HttpOnly', false);
            header('Location: home.php');
            exit;
        }
        $error = $response['error'] ?? 'Login gagal. Periksa email dan password.';
    }
}
?><!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>AbsenPIB — Masuk</title>
  <style>
    *{margin:0;padding:0;box-sizing:border-box}
    body{font-family:system-ui,-apple-system,sans-serif;background:linear-gradient(135deg,#1a56db20,#1a56db08);min-height:100vh;display:flex;align-items:center;justify-content:center}
    .login-box{background:#fff;border-radius:16px;padding:40px;width:100%;max-width:420px;box-shadow:0 4px 32px rgba(0,0,0,.08);margin:20px}
    .logo{text-align:center;margin-bottom:32px}
    .logo h1{font-size:32px;color:#1a56db;font-weight:800}
    .logo p{color:#6b7280;font-size:14px;margin-top:4px}
    .field{margin-bottom:16px}
    .field label{display:block;font-size:13px;font-weight:600;color:#374151;margin-bottom:6px}
    .field input{width:100%;padding:12px 16px;border:2px solid #e5e7eb;border-radius:10px;font-size:15px;transition:border-color .2s;font-family:inherit}
    .field input:focus{outline:none;border-color:#1a56db;box-shadow:0 0 0 3px #1a56db20}
    .btn{width:100%;padding:14px;background:#1a56db;color:#fff;border:none;border-radius:10px;font-size:16px;font-weight:700;cursor:pointer;transition:background .2s;font-family:inherit}
    .btn:hover{background:#1e40af}
    .error{background:#fef2f2;color:#dc2626;padding:10px 16px;border-radius:8px;font-size:13px;margin-bottom:16px;text-align:center}
    .footer{text-align:center;margin-top:20px;font-size:12px;color:#9ca3af}
  </style>
</head>
<body>
<div class="login-box">
  <div class="logo">
    <h1>🕐 AbsenPIB</h1>
    <p>Dashboard Administrasi</p>
  </div>
  <?php if ($error): ?><div class="error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
  <form method="POST" autocomplete="on">
    <div class="field"><label>Email</label><input type="email" name="email" value="<?= htmlspecialchars($email ?? '') ?>" placeholder="email@perusahaan.com" required autofocus></div>
    <div class="field"><label>Password</label><input type="password" name="password" placeholder="••••••••" required minlength="6"></div>
    <button type="submit" class="btn">Masuk</button>
  </form>
  <div class="footer">&copy; <?= date('Y') ?> AbsenPIB v1.0 · PHP Native · Multi-tenant RBAC</div>
</div>
</body>
</html>
