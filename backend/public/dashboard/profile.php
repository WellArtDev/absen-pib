<?php $title = 'Profil'; require_once 'includes/header.php';
$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_check()) {
    $res = api('PUT', '/profile', [
        'full_name' => safe_str($_POST['full_name'] ?? ''),
        'phone'     => safe_str($_POST['phone'] ?? ''),
    ]);
    $msg = ($res['success'] ?? false) ? 'Profil diperbarui' : ($res['error'] ?? 'Gagal');
}
$quota = api('GET', '/leave/quota')['data'] ?? [];
?>

<h1 class="page-title">👤 Profil Saya</h1>
<?php if ($msg): ?><div class="alert alert-success"><?= safe_str($msg) ?></div><?php endif; ?>

<div class="card" style="max-width:640px">
  <div class="card-title">Informasi Pribadi</div>
  <form method="POST"><?= csrf_field() ?>
    <div class="form-group"><label>Role</label><input value="<?= safe_str($user['role'] ?? '') ?>" disabled style="background:#f3f4f6"></div>
    <div class="form-group"><label>NIP</label><input value="<?= safe_str((string)($user['nip'] ?? '')) ?>" disabled style="background:#f3f4f6"></div>
    <div class="form-group"><label>Nama Lengkap</label><input name="full_name" value="<?= safe_str($user['full_name'] ?? '') ?>" required maxlength="100"></div>
    <div class="form-group"><label>Email</label><input value="<?= safe_str($user['email'] ?? '') ?>" disabled style="background:#f3f4f6"></div>
    <div class="form-group"><label>No. Telepon</label><input name="phone" value="<?= safe_str($user['phone'] ?? '') ?>" maxlength="30"></div>
    <button type="submit" class="btn btn-primary">💾 Simpan Perubahan</button>
  </form>
</div>

<div class="card" style="max-width:640px">
  <div class="card-title">Statistik Cuti</div>
  <?php $quota = api('GET', '/leave/quota')['data'] ?? []; ?>
  <div style="font-size:36px;font-weight:800;color:#1a56db"><?= $quota['remaining'] ?? 12 ?> <span style="font-size:16px;color:#6b7280">/ <?= $quota['total'] ?? 12 ?> hari tersisa</span></div>
  <div style="margin-top:8px;height:8px;background:#e5e7eb;border-radius:4px">
    <?php $pct = ($quota['total'] ?? 1) > 0 ? (($quota['used'] ?? 0) / ($quota['total'] ?? 1)) * 100 : 0; ?>
    <div style="height:100%;width:<?= min(100, $pct) ?>%;background:<?= $pct > 80 ? '#dc2626' : '#059669' ?>;border-radius:4px"></div>
  </div>
  <div style="margin-top:4px;font-size:12px;color:#6b7280">Terpakai: <?= $quota['used'] ?? 0 ?> hari</div>
</div>
<?php require_once 'includes/footer.php'; ?>
