<?php $title = 'Absensi'; require_once 'includes/header.php';
$start = get_param('start', date('Y-m-01'), 'date');
$end   = get_param('end', date('Y-m-t'), 'date');
$search = get_param('search', '', 'string');

$all = api('GET', "/attendance/all?start={$start}&end={$end}")['data'] ?? [];
// Client-side filter
if ($search) {
    $all = array_filter($all, fn($r) =>
        stripos($r['full_name'] ?? '', $search) !== false ||
        stripos($r['nip'] ?? '', $search) !== false
    );
}
?>
<h1 class="page-title">📋 Absensi</h1>

<div class="card">
  <form method="GET" class="flex" style="flex-wrap:wrap;gap:8px;align-items:end">
    <div class="form-group" style="margin:0"><label>Dari</label><input type="date" name="start" value="<?= $start ?>" style="width:160px"></div>
    <div class="form-group" style="margin:0"><label>Sampai</label><input type="date" name="end" value="<?= $end ?>" style="width:160px"></div>
    <input type="text" name="search" placeholder="Cari nama / NIP..." value="<?= safe_str($search) ?>" style="padding:8px 12px;border:2px solid #e5e7eb;border-radius:8px;font-size:14px;width:200px" data-debounce data-target="form">
    <button type="submit" class="btn btn-primary">Filter</button>
    <a href="reports.php?type=attendance&start=<?= $start ?>&end=<?= $end ?>" class="btn btn-success">📥 Export CSV</a>
  </form>

  <table>
    <thead><tr><th>NIP</th><th>Nama</th><th>Tipe</th><th>Tanggal</th><th>Lokasi</th><th>Score</th><th>Status</th></tr></thead>
    <tbody id="attTable">
    <?php foreach ($all as $a): ?>
      <tr>
        <td><?= safe_str($a['nip'] ?? '') ?></td>
        <td><strong><?= safe_str($a['full_name'] ?? '') ?></strong></td>
        <td><?= ($a['type'] === 'check_in') ? '🟢 Masuk' : '🔴 Keluar' ?></td>
        <td><?= formatDate($a['server_timestamp']) ?></td>
        <td style="font-size:12px;max-width:180px" title="<?= safe_str($a['address'] ?? '') ?>"><?= truncate($a['address'] ?? '-') ?></td>
        <td><?= $a['suspicion_score'] ?>/5 <?= $a['is_suspect'] ? '⚠' : '' ?></td>
        <td>
          <?php if ($a['is_late']): ?><span class="tag tag-late">Terlambat</span><?php endif; ?>
          <?php if ($a['is_suspect']): ?><span class="tag tag-suspect">⚠ Mencurigakan</span><?php endif; ?>
          <?php if (!$a['is_late'] && !$a['is_suspect']): ?><span class="tag tag-ontime">OK</span><?php endif; ?>
        </td>
      </tr>
    <?php endforeach; ?>
    <?php if (empty($all)): ?>
      <tr><td colspan="7"><div class="empty"><div class="empty-text">Tidak ada data absensi</div></div></td></tr>
    <?php endif; ?>
    </tbody>
  </table>
</div>

<script>
// Debounced search on all inputs with data-debounce
document.querySelectorAll('[data-debounce]').forEach(el => {
  let t;
  el.addEventListener('input', function() {
    clearTimeout(t);
    const v = this.value;
    t = setTimeout(() => {
      const form = el.dataset.target === 'form' ? el.closest('form') : null;
      if (form) { form.submit(); } else { window.location.search = v ? 'search=' + encodeURIComponent(v) : ''; }
    }, 350);
  });
});
</script>
<?php require_once 'includes/footer.php'; ?>
