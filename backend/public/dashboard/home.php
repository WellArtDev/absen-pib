<?php $title = 'Dashboard'; require_once 'includes/header.php'; ?>

<h1 class="page-title">Dashboard</h1>

<div class="stats-grid">
  <div class="stat-card"><div class="stat-num"><?= $stats['present_today'] ?? 0 ?></div><div class="stat-label">Hadir Hari Ini</div></div>
  <div class="stat-card"><div class="stat-num"><?= $stats['late_today'] ?? 0 ?></div><div class="stat-label">Terlambat</div></div>
  <div class="stat-card"><div class="stat-num"><?= $stats['absent_today'] ?? 0 ?></div><div class="stat-label">Belum Absen</div></div>
  <div class="stat-card"><div class="stat-num"><?= $stats['total_employees'] ?? 0 ?></div><div class="stat-label">Total Karyawan</div></div>
  <div class="stat-card"><div class="stat-num"><?= $stats['pending_overtime'] ?? 0 ?></div><div class="stat-label">Lembur Pending</div></div>
  <div class="stat-card"><div class="stat-num"><?= $stats['pending_leaves'] ?? 0 ?></div><div class="stat-label">Cuti Pending</div></div>
</div>

<div class="grid-2">
  <div class="card">
    <div class="card-title">📋 Absensi Hari Ini</div>
    <?php $today = date('Y-m-d');
    $todayAtt = api('GET', "/attendance/all?start={$today}&end={$today}")['data'] ?? [];
    if ($todayAtt): ?><table>
      <thead><tr><th>Nama</th><th>Tipe</th><th>Waktu</th><th>Status</th></tr></thead>
      <tbody>
      <?php foreach (array_slice($todayAtt, 0, 10) as $a): ?>
        <tr>
          <td><?= safe_str($a['full_name'] ?? '') ?></td>
          <td><?= ($a['type'] === 'check_in') ? '🟢 Masuk' : '🔴 Keluar' ?></td>
          <td><?= formatDate($a['server_timestamp'], 'H:i') ?></td>
          <td>
            <?php if ($a['is_late']): ?><span class="tag tag-late">Terlambat</span><?php endif; ?>
            <?php if ($a['is_suspect']): ?><span class="tag tag-suspect">⚠</span><?php endif; ?>
            <?php if (!$a['is_late'] && !$a['is_suspect']): ?>✅<?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table><?php else: ?><div class="empty"><div class="empty-text">Belum ada absensi hari ini</div></div><?php endif; ?>
  </div>

  <div class="card">
    <div class="card-title">⏰ Pending Review</div>
    <?php
    $pendOv = api('GET', '/overtime/pending')['data'] ?? [];
    $pendLv = api('GET', '/leave/pending')['data'] ?? [];
    $reviews = [...array_map(fn($o) => ['_type'=>'Lembur','_date'=>formatDate($o['check_in_at'],'d M'),'_name'=>$o['full_name'],'link'=>'approvals.php'], $pendOv),
                   ...array_map(fn($l) => ['_type'=>'Cuti '.$l['leave_type'],'_date'=>formatDate($l['start_date'],'d M'),'_name'=>$l['full_name'],'link'=>'approvals.php'], $pendLv)];
    if ($reviews): ?><table>
      <thead><tr><th>Tipe</th><th>Karyawan</th><th>Tanggal</th><th></th></tr></thead>
      <tbody>
      <?php foreach (array_slice($reviews, 0, 8) as $r): ?>
        <tr><td><?= safe_str($r['_type']) ?></td><td><?= safe_str($r['_name']) ?></td><td><?= $r['_date'] ?></td>
          <td><a href="<?= $r['link'] ?>" class="btn btn-sm btn-primary">Review</a></td></tr>
      <?php endforeach; ?>
      </tbody>
    </table><?php else: ?><div class="empty"><div class="empty-text">✅ Tidak ada pending</div></div><?php endif; ?>
  </div>
</div>

<div class="card">
  <div class="card-title">🔍 Cari Karyawan</div>
  <div class="flex">
    <input type="text" id="quickSearch" placeholder="Ketik nama atau NIP..." style="flex:1;padding:10px 14px;border:2px solid #e5e7eb;border-radius:8px;font-size:14px" data-debounce>
  </div>
  <div id="searchResults" style="margin-top:12px"></div>
</div>

<script>
let debounceTimer;
document.getElementById('quickSearch').addEventListener('input', function() {
  clearTimeout(debounceTimer);
  const q = this.value.trim();
  if (!q) { document.getElementById('searchResults').innerHTML = ''; return; }
  debounceTimer = setTimeout(async () => {
    const res = await fetch('<?= $API_URL ?>/admin/employees?limit=100', {headers:{'Authorization':'Bearer <?= $_COOKIE['absen_token'] ?>'}});
    const data = await res.json();
    const filtered = (data.data||[]).filter(e => e.full_name.toLowerCase().includes(q.toLowerCase()) || e.nip.includes(q));
    if (!filtered.length) { document.getElementById('searchResults').innerHTML='<p style="color:#9ca3af;padding:12px">Tidak ditemukan</p>'; return; }
    document.getElementById('searchResults').innerHTML = `<table><thead><tr><th>NIP</th><th>Nama</th><th>Email</th><th>Role</th></tr></thead><tbody>
      ${filtered.slice(0,10).map(e => `<tr><td>${e.nip}</td><td><strong>${e.full_name}</strong></td><td>${e.email}</td><td>${e.role}</td></tr>`).join('')}
    </tbody></table>`;
  }, 350);
});
</script>

<?php require_once 'includes/footer.php'; ?>
