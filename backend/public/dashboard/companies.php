<?php $title = 'Perusahaan'; require_once 'includes/header.php';
if (!$isSuperadmin) { echo '<div class="alert alert-error">Akses ditolak. Superadmin saja.</div>'; require_once 'includes/footer.php'; exit; }

$msg = '';
$companies = api('GET', '/companies')['data'] ?? [];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_check()) {
    $res = api('POST', '/companies', [
        'name'             => safe_str($_POST['name'] ?? ''),
        'code'             => safe_str($_POST['code'] ?? ''),
        'address'          => safe_str($_POST['address'] ?? ''),
        'owner_email'      => safe_email($_POST['owner_email'] ?? ''),
        'owner_nip'        => safe_str($_POST['owner_nip'] ?? ''),
        'owner_full_name'  => safe_str($_POST['owner_full_name'] ?? ''),
        'owner_password'   => $_POST['owner_password'] ?? '',
    ]);
    if ($res['success'] ?? false) { $msg = 'Perusahaan berhasil dibuat!'; $companies = api('GET', '/companies')['data'] ?? []; }
    else $msg = $res['error'] ?? 'Gagal';
}
?>

<h1 class="page-title">🏭 Kelola Perusahaan</h1>
<?php if ($msg): ?><div class="alert <?= str_contains($msg,'berhasil') ? 'alert-success' : 'alert-error' ?>"><?= safe_str($msg) ?></div><?php endif; ?>

<button class="btn btn-primary" style="margin-bottom:16px" onclick="document.getElementById('addForm').style.display='block'">+ Perusahaan Baru</button>

<div id="addForm" class="card" style="display:none">
  <div class="card-title">Tambah Perusahaan + Owner</div>
  <form method="POST"><?= csrf_field() ?>
    <div class="grid-2">
      <div class="form-group"><label>Nama Perusahaan</label><input name="name" required maxlength="100"></div>
      <div class="form-group"><label>Kode Unik</label><input name="code" required maxlength="20" style="text-transform:uppercase"></div>
      <div class="form-group"><label>Alamat</label><input name="address" maxlength="255"></div>
      <div class="form-group"><label>Email Owner</label><input name="owner_email" type="email" required maxlength="100"></div>
      <div class="form-group"><label>NIP Owner</label><input name="owner_nip" required maxlength="30"></div>
      <div class="form-group"><label>Nama Lengkap Owner</label><input name="owner_full_name" required maxlength="100"></div>
      <div class="form-group"><label>Password Owner (min 6)</label><input name="owner_password" type="password" required minlength="6" maxlength="72" autocomplete="new-password"></div>
    </div>
    <div class="flex" style="gap:8px">
      <button type="submit" class="btn btn-success">Buat Perusahaan</button>
      <button type="button" class="btn" style="background:#e5e7eb;color:#374151" onclick="document.getElementById('addForm').style.display='none'">Batal</button>
    </div>
  </form>
</div>

<div class="card">
  <table><thead><tr><th>ID</th><th>Kode</th><th>Nama</th><th>Karyawan</th><th>Dibuat</th></tr></thead><tbody>
  <?php foreach ($companies as $c): ?>
    <tr><td><?= $c['id'] ?></td><td><strong><?= safe_str($c['code'] ?? '') ?></strong></td>
      <td><?= safe_str($c['name'] ?? '') ?></td><td><?= $c['employee_count'] ?? 0 ?></td>
      <td><?= formatDate($c['created_at'], 'd M Y') ?></td></tr>
  <?php endforeach; ?>
  <?php if (empty($companies)): ?>
    <tr><td colspan="5"><div class="empty"><div class="empty-text">Belum ada perusahaan</div></div></td></tr>
  <?php endif; ?>
  </tbody></table>
</div>
<?php require_once 'includes/footer.php'; ?>
