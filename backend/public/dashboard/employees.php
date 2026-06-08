<?php
$title = 'Karyawan';
require_once 'includes/header.php';

// Post handler — no duplicate validation code
$msg = ''; $err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_check()) {
    $action = $_POST['action'] ?? '';
    if ($action === 'add') {
        $res = api('POST', '/admin/employees', [
            'nip'       => safe_str($_POST['nip'] ?? ''),
            'full_name' => safe_str($_POST['full_name'] ?? ''),
            'email'     => safe_email($_POST['email'] ?? ''),
            'password'  => $_POST['password'] ?? '',
            'role'      => safe_str($_POST['role'] ?? ''),
            'office_id' => safe_int($_POST['office_id'] ?? 0) ?: null,
        ]);
        $msg = ($res['success'] ?? false) ? 'Karyawan berhasil ditambahkan' : ($res['error'] ?? 'Gagal');
    } elseif ($action === 'edit') {
        $res = api('PUT', '/admin/employees/' . safe_int($_POST['id']), [
            'full_name' => safe_str($_POST['full_name'] ?? ''),
            'nip'       => safe_str($_POST['nip'] ?? ''),
            'role'      => safe_str($_POST['role'] ?? ''),
        ]);
        $msg = ($res['success'] ?? false) ? 'Data diperbarui' : ($res['error'] ?? 'Gagal');
    }
}

$page     = get_param('page', '1', 'int') ?: 1;
$search   = get_param('search', '', 'string');
$employees = api('GET', '/admin/employees?page=' . $page . '&limit=100')['data'] ?? [];
$offices  = api('GET', '/office/config')['data'] ?? [];

// Client-side filter (small datasets) or API-side filter later
if ($search) {
    $employees = array_filter($employees, fn($e) =>
        stripos($e['full_name'] ?? '', $search) !== false ||
        stripos($e['nip'] ?? '', $search) !== false
    );
}
?>

<h1 class="page-title">👥 Kelola Karyawan</h1>
<?php if ($msg): ?><div class="alert <?= str_contains($msg, 'berhasil') || str_contains($msg, 'diperbarui') ? 'alert-success' : 'alert-error' ?>"><?= safe_str($msg) ?></div><?php endif; ?>

<div class="flex-between" style="margin-bottom:16px">
  <span style="color:#6b7280"><?= count($employees) ?> karyawan</span>
  <div class="flex">
    <input type="text" id="searchEmp" placeholder="Cari nama / NIP..." value="<?= safe_str($search) ?>" style="padding:8px 12px;border:2px solid #e5e7eb;border-radius:8px;font-size:14px;width:220px" data-debounce>
    <button class="btn btn-primary" onclick="document.getElementById('addForm').style.display='block'">+ Tambah Karyawan</button>
  </div>
</div>

<!-- Add Form -->
<div id="addForm" class="card" style="display:none">
  <div class="card-title">Tambah Karyawan Baru</div>
  <form method="POST"><?= csrf_field() ?><input type="hidden" name="action" value="add">
    <div class="grid-2">
      <div class="form-group"><label>NIP</label><input name="nip" required maxlength="30" pattern="[0-9]+" title="Hanya angka"></div>
      <div class="form-group"><label>Nama Lengkap</label><input name="full_name" required maxlength="100"></div>
      <div class="form-group"><label>Email</label><input name="email" type="email" required maxlength="100"></div>
      <div class="form-group"><label>Password (min 6 karakter)</label><input name="password" type="password" required minlength="6" maxlength="72" autocomplete="new-password"></div>
      <div class="form-group"><label>Role</label><select name="role">
        <?php foreach (['karyawan'=>'Karyawan','sales'=>'Sales','admin'=>'Admin'] as $k=>$v): ?>
          <option value="<?= $k ?>"><?= $v ?></option>
        <?php endforeach; ?>
        <?php if ($isOwner): ?><option value="owner">Owner</option><?php endif; ?>
      </select></div>
      <div class="form-group"><label>Kantor</label><select name="office_id">
        <option value="">-- Pilih --</option>
        <?php foreach ($offices as $o): ?>
          <option value="<?= $o['id'] ?>"><?= safe_str($o['name']) ?></option>
        <?php endforeach; ?>
      </select></div>
    </div>
    <div class="flex" style="margin-top:12px">
      <button type="submit" class="btn btn-primary">💾 Simpan</button>
      <button type="button" class="btn" style="background:#e5e7eb;color:#374151" onclick="document.getElementById('addForm').style.display='none'">Batal</button>
    </div>
  </form>
</div>

<!-- Employee Table -->
<div class="card"><table>
  <thead><tr><th>NIP</th><th>Nama</th><th>Email</th><th>Role</th><th>Cuti</th><th>Aksi</th></tr></thead>
  <tbody id="empTable">
  <?php foreach ($employees as $e): ?>
    <tr>
      <td><?= safe_str($e['nip']) ?></td>
      <td><strong><?= safe_str($e['full_name']) ?></strong></td>
      <td><?= safe_str($e['email']) ?></td>
      <td><?= roleBadge($e['role']) ?></td>
      <td><?= (int)($e['leave_quota_used'] ?? 0) ?>/<?= (int)($e['leave_quota_total'] ?? 12) ?></td>
      <td>
        <a href="javascript:void(0)" onclick='editEmployee(<?= json_encode($e, JSON_HEX_APOS|JSON_UNESCAPED_UNICODE) ?>)' class="btn btn-sm btn-primary">Edit</a>
        <a href="employee-detail.php?id=<?= $e['id'] ?>" class="btn btn-sm" style="background:#e5e7eb;color:#374151">Detail</a>
      </td>
    </tr>
  <?php endforeach; ?>
  <?php if (empty($employees)): ?>
    <tr><td colspan="6"><div class="empty"><div class="empty-text">Tidak ada data</div></div></td></tr>
  <?php endif; ?>
  </tbody>
</table></div>

<!-- Edit Modal -->
<div id="editModal" style="display:none;position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,.5);z-index:1000;align-items:center;justify-content:center">
  <div class="card" style="max-width:480px;width:100%;margin:40px">
    <div class="card-title">Edit Karyawan</div>
    <form method="POST"><?= csrf_field() ?><input type="hidden" name="action" value="edit"><input type="hidden" name="id" id="editId">
      <div class="form-group"><label>NIP</label><input name="nip" id="editNip" required maxlength="30"></div>
      <div class="form-group"><label>Nama Lengkap</label><input name="full_name" id="editName" required maxlength="100"></div>
      <div class="form-group"><label>Role</label><select name="role" id="editRole">
        <?php foreach (['karyawan'=>'Karyawan','sales'=>'Sales','admin'=>'Admin','owner'=>'Owner'] as $k=>$v): ?>
          <option value="<?= $k ?>"><?= $v ?></option>
        <?php endforeach; ?>
      </select></div>
      <div class="flex" style="gap:8px">
        <button type="submit" class="btn btn-primary">💾 Simpan</button>
        <button type="button" class="btn" style="background:#e5e7eb;color:#374151" onclick="document.getElementById('editModal').style.display='none'">Batal</button>
      </div>
    </form>
  </div>
</div>

<script>
function editEmployee(e) {
  for (const k of ['id','nip','full_name','role']) document.getElementById('edit' + (k==='full_name'?'Name':k==='id'?'Id':k.charAt(0).toUpperCase()+k.slice(1))).value = e[k];
  document.getElementById('editModal').style.display = 'flex';
}
document.getElementById('editModal').addEventListener('click', function(ev) { if (ev.target === this) this.style.display = 'none'; });

// Debounced search
let debounceTimer;
document.getElementById('searchEmp').addEventListener('input', function() {
  clearTimeout(debounceTimer);
  const s = this.value;
  debounceTimer = setTimeout(() => { window.location.search = s ? '?search=' + encodeURIComponent(s) : ''; }, 350);
});
</script>

<?php require_once 'includes/footer.php'; ?>
