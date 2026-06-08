<?php $title = 'Laporan'; require_once 'includes/header.php';
$type  = get_param('type', 'attendance', 'string');
$start = get_param('start', date('Y-m-01'), 'date');
$end   = get_param('end', date('Y-m-t'), 'date');
$exportUrl = "{$API_URL}/reports/export?type={$type}&start={$start}&end={$end}";
?>

<h1 class="page-title">📥 Export Laporan</h1>

<div class="card">
  <form method="GET" class="flex" style="flex-wrap:wrap;gap:10px;align-items:end">
    <div class="form-group" style="margin:0"><label>Tipe</label><select name="type" style="width:160px">
      <?php foreach (['attendance'=>'Absensi','overtime'=>'Lembur','leave'=>'Cuti'] as $k=>$v): ?>
        <option value="<?= $k ?>" <?= $type===$k?'selected':'' ?>><?= $v ?></option>
      <?php endforeach; ?>
    </select></div>
    <div class="form-group" style="margin:0"><label>Dari</label><input type="date" name="start" value="<?= $start ?>" style="width:160px"></div>
    <div class="form-group" style="margin:0"><label>Sampai</label><input type="date" name="end" value="<?= $end ?>" style="width:160px"></div>
    <button type="submit" class="btn btn-primary">Tampilkan</button>
    <a href="<?= $exportUrl ?>" class="btn btn-success" download>📥 Download CSV</a>
  </form>
</div>

<div class="card">
  <div class="card-title">📊 Preview: <?= ['attendance'=>'Absensi','overtime'=>'Lembur','leave'=>'Cuti'][$type] ?? $type ?></div>
  <?php
  if ($type === 'attendance'):
    $rows = api('GET', "/attendance/all?start={$start}&end={$end}")['data'] ?? [];
  ?>
    <div class="flex-between" style="margin-bottom:12px"><span><?= count($rows) ?> data</span></div>
    <table><thead><tr><th>NIP</th><th>Nama</th><th>Tipe</th><th>Waktu</th><th>Status</th><th>Score</th></tr></thead><tbody>
    <?php foreach (array_slice($rows, 0, 50) as $r): ?>
      <tr><td><?= safe_str($r['nip'] ?? '') ?></td><td><?= safe_str($r['full_name'] ?? '') ?></td>
        <td><?= ($r['type']==='check_in')?'Masuk':'Keluar' ?></td><td><?= formatDate($r['server_timestamp']) ?></td>
        <td><?= $r['is_late']?'⏰ Terlambat':'✅ Tepat' ?><?= $r['is_suspect']?' ⚠':'' ?></td><td><?= $r['suspicion_score'] ?>/5</td></tr>
    <?php endforeach; ?>
    </tbody></table>
  <?php elseif ($type === 'overtime'):
    $rows = api('GET', '/overtime/history')['data'] ?? [];
  ?>
    <table><thead><tr><th>Tanggal</th><th>Durasi</th><th>Status</th></tr></thead><tbody>
    <?php foreach ($rows as $r): ?>
      <tr><td><?= formatDate($r['check_in_at'], 'd M Y H:i') ?></td>
        <td><?= $r['duration_minutes'] ? round($r['duration_minutes']/60,1).' jam' : '-' ?></td>
        <td><?= statusBadge($r['status']) ?></td></tr>
    <?php endforeach; ?>
    </tbody></table>
  <?php else:
    $rows = api('GET', '/leave/history')['data'] ?? [];
  ?>
    <table><thead><tr><th>Tipe</th><th>Tanggal</th><th>Hari</th><th>Status</th></tr></thead><tbody>
    <?php foreach ($rows as $r): ?>
      <tr><td><?= leaveTypeIcon($r['leave_type']) ?></td>
        <td><?= formatDate($r['start_date'], 'd M') ?> - <?= formatDate($r['end_date'], 'd M Y') ?></td>
        <td><?= $r['total_days'] ?> hari</td><td><?= statusBadge($r['status']) ?></td></tr>
    <?php endforeach; ?>
    </tbody></table>
  <?php endif; ?>
</div>
<?php require_once 'includes/footer.php'; ?>
