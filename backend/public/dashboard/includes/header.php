<?php require_once __DIR__ . '/core.php';
// Auth guard: redirect to login if not authenticated
if (!$isLoggedIn) {
    header('Location: index.php');
    exit;
}
?><!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>AbsenPIB — <?= $title ?? 'Dashboard' ?></title>
  <meta name="robots" content="noindex">
  <style>
    *{margin:0;padding:0;box-sizing:border-box}
    body{font-family:system-ui,-apple-system,sans-serif;background:#f0f2f5;min-height:100vh;display:flex}
    .sidebar{width:260px;background:#fff;border-right:1px solid #e5e7eb;padding:20px;display:flex;flex-direction:column;position:fixed;height:100vh;overflow-y:auto}
    .sidebar .logo{font-size:22px;font-weight:800;color:#1a56db;margin-bottom:4px}
    .sidebar .logo-sub{font-size:12px;color:#9ca3af;margin-bottom:24px}
    .sidebar .user-info{display:flex;align-items:center;gap:10px;padding:12px;background:#f9fafb;border-radius:10px;margin-bottom:24px}
    .sidebar .user-avatar{width:36px;height:36px;border-radius:50%;background:#1a56db;color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:14px;flex-shrink:0}
    .sidebar .user-name{font-size:13px;font-weight:600;color:#111827}
    .sidebar .user-role{font-size:11px;color:#6b7280}
    .sidebar nav a{display:flex;align-items:center;gap:10px;padding:10px 12px;border-radius:8px;text-decoration:none;color:#374151;font-size:14px;font-weight:500;margin-bottom:4px;transition:background .15s}
    .sidebar nav a:hover,.sidebar nav a.active{background:#eff6ff;color:#1a56db;font-weight:600}
    .sidebar nav a .icon{font-size:18px;width:24px;text-align:center}
    .sidebar .logout{margin-top:auto;padding:10px 12px;border-radius:8px;background:#fef2f2;color:#dc2626;text-decoration:none;font-size:14px;font-weight:600;text-align:center}
    .main{margin-left:260px;flex:1;padding:24px 32px;min-height:100vh}
    .page-title{font-size:24px;font-weight:800;color:#111827;margin-bottom:24px}
    .stats-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:16px;margin-bottom:24px}
    .stat-card{background:#fff;border-radius:12px;padding:20px;border:1px solid #e5e7eb}
    .stat-card .stat-num{font-size:32px;font-weight:800;color:#111827}
    .stat-card .stat-label{font-size:13px;color:#6b7280;margin-top:4px}
    .card{background:#fff;border-radius:12px;border:1px solid #e5e7eb;padding:20px;margin-bottom:16px}
    .card-title{font-size:16px;font-weight:700;color:#111827;margin-bottom:16px}
    table{width:100%;border-collapse:collapse}
    th{text-align:left;padding:10px 12px;font-size:12px;font-weight:600;color:#6b7280;text-transform:uppercase;border-bottom:2px solid #e5e7eb}
    td{padding:10px 12px;font-size:14px;border-bottom:1px solid #f3f4f6}
    .btn{display:inline-block;padding:8px 16px;border-radius:8px;font-size:13px;font-weight:600;cursor:pointer;text-decoration:none;border:none;transition:all .15s;font-family:inherit}
    .btn-primary{background:#1a56db;color:#fff}.btn-primary:hover{background:#1e40af}
    .btn-success{background:#059669;color:#fff}.btn-success:hover{background:#047857}
    .btn-danger{background:#dc2626;color:#fff}.btn-danger:hover{background:#b91c1c}
    .btn-sm{padding:4px 10px;font-size:12px}
    .form-group{margin-bottom:16px}
    .form-group label{display:block;font-size:13px;font-weight:600;color:#374151;margin-bottom:6px}
    .form-group input,.form-group select,.form-group textarea{width:100%;padding:10px 14px;border:2px solid #e5e7eb;border-radius:8px;font-size:14px;font-family:inherit}
    .form-group input:focus,.form-group select:focus,.form-group textarea:focus{outline:none;border-color:#1a56db;box-shadow:0 0 0 3px #1a56db20}
    .alert{padding:12px 16px;border-radius:8px;font-size:13px;margin-bottom:16px}
    .alert-success{background:#d1fae5;color:#065f46}
    .alert-error{background:#fef2f2;color:#dc2626}
    .flex{display:flex;gap:12px;align-items:center}
    .flex-between{display:flex;justify-content:space-between;align-items:center}
    .grid-2{display:grid;grid-template-columns:1fr 1fr;gap:16px}
    .empty{text-align:center;padding:48px 20px;color:#9ca3af}
    .empty .empty-icon{font-size:48px}
    .empty .empty-text{font-size:15px;margin-top:8px}
    .tag{display:inline-block;padding:2px 8px;border-radius:6px;font-size:11px;font-weight:600}
    .tag-suspect{background:#fef3c7;color:#92400e}
    .tag-late{background:#fef2f2;color:#dc2626}
    .tag-ontime{background:#d1fae5;color:#065f46}
    @media(max-width:768px){.sidebar{display:none}.main{margin-left:0}}
  </style>
</head>
<body>
<aside class="sidebar">
  <div class="logo">🕐 AbsenPIB</div>
  <div class="logo-sub">Dashboard v1.0</div>
  <div class="user-info">
    <div class="user-avatar"><?= safe_str(mb_substr($user['full_name'] ?? '?', 0, 1)) ?></div>
    <div>
      <div class="user-name"><?= safe_str($user['full_name'] ?? '-') ?></div>
      <div class="user-role"><?= roleBadge($role) ?></div>
    </div>
  </div>
  <nav>
    <a href="home.php" class="<?= $currentPage==='home.php'?'active':'' ?>"><span class="icon">📊</span> Dashboard</a>
    <a href="live-location.php" class="<?= $currentPage==='live-location.php'?'active':'' ?>"><span class="icon">📍</span> Live Location</a>
    <a href="employees.php" class="<?= $currentPage==='employees.php'?'active':'' ?>"><span class="icon">👥</span> Karyawan</a>
    <a href="attendance.php" class="<?= $currentPage==='attendance.php'?'active':'' ?>"><span class="icon">📋</span> Absensi</a>
    <?php if ($isAdmin): ?>
    <a href="approvals.php" class="<?= $currentPage==='approvals.php'?'active':'' ?>"><span class="icon">✅</span> Approval</a>
    <?php endif; ?>
    <a href="reports.php" class="<?= $currentPage==='reports.php'?'active':'' ?>"><span class="icon">📥</span> Laporan</a>
    <?php if ($isOwner): ?>
    <a href="office.php" class="<?= $currentPage==='office.php'?'active':'' ?>"><span class="icon">🏢</span> Kantor</a>
    <?php endif; ?>
    <?php if ($isSuperadmin): ?>
    <a href="companies.php" class="<?= $currentPage==='companies.php'?'active':'' ?>"><span class="icon">🏭</span> Perusahaan</a>
    <?php endif; ?>
    <a href="profile.php" class="<?= $currentPage==='profile.php'?'active':'' ?>"><span class="icon">👤</span> Profil</a>
  </nav>
  <a href="logout.php" class="logout">🚪 Keluar</a>
</aside>
<main class="main">
