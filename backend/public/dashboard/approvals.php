<?php $title = 'Approval'; require_once 'includes/header.php';
if (!$isAdmin) { echo '<div class="alert alert-error">Akses ditolak. Hanya Admin/Owner/Superadmin.</div>'; require_once 'includes/footer.php'; exit; }

$msg = ''; $err = '';
// Handle approve/reject
if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_check()) {
    $action = safe_str($_POST['_action'] ?? '');
    $id     = safe_int($_POST['id'] ?? 0);
    $type   = safe_str($_POST['type'] ?? '');
    if ($type === 'overtime') {
        $res = ($action === 'approve')
            ? api('POST', "/overtime/{$id}/approve")
            : api('POST', "/overtime/{$id}/reject", ['reason' => safe_str($_POST['reason'] ?? '')]);
    } elseif ($type === 'leave') {
        $res = ($action === 'approve')
            ? api('POST', "/leave/{$id}/approve")
            : api('POST', "/leave/{$id}/reject", ['reason' => safe_str($_POST['reason'] ?? '')]);
    }
    $msg = ($res['success'] ?? false)
        ? ucfirst($type) . ' berhasil ' . ($action === 'approve' ? 'disetujui ✅' : 'ditolak ❌')
        : ($res['error'] ?? 'Gagal');
}

$pendingOvertime = api('GET', '/overtime/pending')['data'] ?? [];
$pendingLeaves   = api('GET', '/leave/pending')['data'] ?? [];
?>

<h1 class="page-title">✅ Approval</h1>
<?php if ($msg): ?><div class="alert <?= str_contains($msg,'berhasil') ? 'alert-success' : 'alert-error' ?>"><?= safe_str($msg) ?></div><?php endif; ?>

<!-- Overtime -->
<div class="card">
  <div class="card-title">⏰ Lembur Pending (<?= count($pendingOvertime) ?>)</div>
  <?php if ($pendingOvertime): ?><table>
    <thead><tr><th>Karyawan</th><th>Tanggal</th><th>Durasi</th><th>Lokasi</th><th>Aksi</th></tr></thead>
    <tbody>
    <?php foreach ($pendingOvertime as $o): ?>
      <tr>
        <td><strong><?= safe_str($o['full_name']) ?></strong><br><small style="color:#6b7280"><?= safe_str((string)($o['nip'] ?? '')) ?></small></td>
        <td><?= formatDate($o['check_in_at'], 'd M H:i') ?><br><?= $o['check_out_at'] ? 's/d ' . formatDate($o['check_out_at'], 'H:i') : '(aktif)' ?></td>
        <td><?= $o['duration_minutes'] ? round($o['duration_minutes']/60, 1) . ' jam' : '-' ?></td>
        <td style="font-size:12px;max-width:140px" title="<?= safe_str($o['check_in_address'] ?? '') ?>"><?= truncate($o['check_in_address'] ?? '-') ?></td>
        <td>
          <form method="POST" style="display:inline"><?= csrf_field() ?><input type="hidden" name="id" value="<?= $o['id'] ?>"><input type="hidden" name="type" value="overtime">
            <button type="submit" name="_action" value="approve" class="btn btn-sm btn-success">✅ Setujui</button>
          </form>
          <form method="POST" style="display:inline" onsubmit="var r=prompt('Alasan:');if(!r)return false;this.elements[3].value=r.trim()"><?= csrf_field() ?>
            <input type="hidden" name="id" value="<?= $o['id'] ?>"><input type="hidden" name="type" value="overtime"><input type="hidden" name="reason" value="">
            <button type="submit" name="_action" value="reject" class="btn btn-sm btn-danger">❌ Tolak</button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table><?php else: ?><div class="empty"><div class="empty-text">Tidak ada lembur pending</div></div><?php endif; ?>
</div>

<!-- Leave -->
<div class="card">
  <div class="card-title">🏖️ Cuti Pending (<?= count($pendingLeaves) ?>)</div>
  <?php if ($pendingLeaves): ?><table>
    <thead><tr><th>Karyawan</th><th>Jenis</th><th>Tanggal</th><th>Hari</th><th>Kuota</th><th>Alasan</th><th>Aksi</th></tr></thead>
    <tbody>
    <?php foreach ($pendingLeaves as $l): ?>
      <tr>
        <td><strong><?= safe_str($l['full_name']) ?></strong></td>
        <td><?= leaveTypeIcon($l['leave_type']) ?></td>
        <td><?= formatDate($l['start_date'], 'd M') ?> - <?= formatDate($l['end_date'], 'd M Y') ?></td>
        <td><?= $l['total_days'] ?> hari</td>
        <td><?= $l['leave_quota_used'] ?>/<?= $l['leave_quota_total'] ?></td>
        <td style="max-width:140px;font-size:12px"><?= truncate($l['reason'], 60) ?></td>
        <td>
          <form method="POST" style="display:inline"><?= csrf_field() ?><input type="hidden" name="id" value="<?= $l['id'] ?>"><input type="hidden" name="type" value="leave">
            <button type="submit" name="_action" value="approve" class="btn btn-sm btn-success">✅ Setujui</button>
          </form>
          <form method="POST" style="display:inline" onsubmit="var r=prompt('Alasan:');if(!r)return false;this.elements[3].value=r.trim()"><?= csrf_field() ?>
            <input type="hidden" name="id" value="<?= $l['id'] ?>"><input type="hidden" name="type" value="leave"><input type="hidden" name="reason" value="">
            <button type="submit" name="_action" value="reject" class="btn btn-sm btn-danger">❌ Tolak</button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table><?php else: ?><div class="empty"><div class="empty-text">Tidak ada cuti pending</div></div><?php endif; ?>
</div>
<?php require_once 'includes/footer.php'; ?>
