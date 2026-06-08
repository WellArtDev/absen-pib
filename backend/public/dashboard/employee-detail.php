<?php require_once 'includes/core.php';
$id = (int)($_GET['id'] ?? 0);
$employee = api('GET', "/admin/employees/{$id}")['data'] ?? null;
if (!$employee) { echo 'Karyawan tidak ditemukan'; exit; }
$profile = $employee['profile'];
$attendances = $employee['attendances'] ?? [];
?>
<?php $title = 'Detail: ' . htmlspecialchars($profile['full_name']); require_once 'includes/header.php'; ?>

<h1 class="page-title">👤 <?= htmlspecialchars($profile['full_name']) ?></h1>

<div class="stats-grid">
  <div class="stat-card"><div class="stat-num"><?= count(array_filter($attendances, fn($a) => $a['type'] === 'check_in')) ?></div><div class="stat-label">Total Check-In (30 hari)</div></div>
  <div class="stat-card"><div class="stat-num"><?= count(array_filter($attendances, fn($a) => $a['is_late'] == 1)) ?></div><div class="stat-label">Terlambat</div></div>
  <div class="stat-card"><div class="stat-num"><?= $profile['leave_quota_total'] - $profile['leave_quota_used'] ?></div><div class="stat-label">Sisa Cuti</div></div>
  <div class="stat-card"><div class="stat-num"><?= count(array_filter($attendances, fn($a) => $a['is_suspect'] == 1)) ?></div><div class="stat-label">Mencurigakan</div></div>
</div>

<div class="flex" style="margin-bottom:16px; gap:8px">
  <div class="form-group" style="margin:0"><input value="NIP: <?= $profile['nip'] ?>" disabled style="background:#f3f4f6;width:auto"></div>
  <div class="form-group" style="margin:0"><input value="Email: <?= $profile['email'] ?>" disabled style="background:#f3f4f6;width:auto"></div>
  <div class="form-group" style="margin:0"><?= roleBadge($profile['role']) ?></div>
</div>

<div class="card">
  <div class="card-title">📋 Riwayat Absensi</div>
  <table>
    <thead><tr><th>Tipe</th><th>Waktu</th><th>Lokasi</th><th>Status</th></tr></thead>
    <tbody>
    <?php foreach (array_slice($attendances, 0, 30) as $a): ?>
      <tr>
        <td><?= $a['type'] === 'check_in' ? '🟢 Masuk' : '🔴 Keluar' ?></td>
        <td><?= date('d M Y H:i', strtotime($a['server_timestamp'])) ?></td>
        <td style="font-size:12px;max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?= htmlspecialchars($a['address'] ?? '-') ?></td>
        <td>
          <?php if ($a['is_late']): ?><span class="tag tag-late">Terlambat</span><br><?php endif; ?>
          <?php if ($a['is_suspect']): ?><span class="tag tag-suspect">⚠ Score: <?= $a['suspicion_score'] ?>/5</span><?php endif; ?>
          <?php if (!$a['is_late'] && !$a['is_suspect']): ?>✅<?php endif; ?>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>

<?php require_once 'includes/footer.php'; ?>
