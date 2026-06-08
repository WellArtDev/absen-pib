<?php $title = 'Live Location'; require_once 'includes/header.php';

$today = get_param('date', date('Y-m-d'), 'date');
$officeId = get_param('office', '', 'int');

// Get all today's attendances
$all = api('GET', "/attendance/all?start={$today}&end={$today}")['data'] ?? [];
$offices = api('GET', '/office/config')['data'] ?? [];

// Group by user — latest position only
$locations = [];
foreach ($all as $a) {
    if (empty($a['latitude']) || empty($a['longitude'])) continue;
    $uid = $a['user_id'];
    if (!isset($locations[$uid]) || strtotime($a['server_timestamp']) > strtotime($locations[$uid]['server_timestamp'] ?? '')) {
        $a['color'] = ($a['type'] === 'check_in') ? '#059669' : '#dc2626';
        $locations[$uid] = $a;
    }
}
?>

<h1 class="page-title">📍 Live Location</h1>

<div class="card">
  <form method="GET" class="flex" style="flex-wrap:wrap;gap:8px;align-items:end">
    <div class="form-group" style="margin:0"><label>Tanggal</label><input type="date" name="date" value="<?= $today ?>" style="width:160px"></div>
    <div class="form-group" style="margin:0"><label>Kantor</label><select name="office" style="width:160px"><option value="">Semua</option>
      <?php foreach ($offices as $o): ?><option value="<?= $o['id'] ?>" <?= $officeId==$o['id']?'selected':'' ?>><?= safe_str($o['name']) ?></option><?php endforeach; ?>
    </select></div>
    <button type="submit" class="btn btn-primary">Filter</button>
    <span style="margin-left:8px;font-size:13px;color:#6b7280"><?= count($locations) ?> pin lokasi</span>
  </form>
</div>

<!-- Map container -->
<div class="card" style="padding:0;overflow:hidden;height:500px;position:relative">
  <div id="map" style="width:100%;height:100%"></div>
</div>

<!-- Employee list with pins -->
<div class="card">
  <div class="card-title">📍 Karyawan Hari Ini (<?= count($locations) ?>)</div>
  <table>
    <thead><tr><th>Nama</th><th>NIP</th><th>Tipe</th><th>Waktu</th><th>Lokasi</th><th>Koordinat</th><th>Status</th></tr></thead>
    <tbody>
    <?php foreach ($locations as $loc): ?>
      <tr>
        <td><strong><?= safe_str($loc['full_name'] ?? '') ?></strong></td>
        <td><?= safe_str((string)($loc['nip'] ?? '')) ?></td>
        <td><span style="display:inline-block;width:10px;height:10px;border-radius:50%;background:<?= $loc['color'] ?>;margin-right:6px"></span> <?= ($loc['type']==='check_in')?'Masuk':'Keluar' ?></td>
        <td><?= formatDate($loc['server_timestamp'], 'H:i:s') ?></td>
        <td style="font-size:12px;max-width:200px" title="<?= safe_str($loc['address'] ?? '') ?>"><?= truncate($loc['address'] ?? '-') ?></td>
        <td style="font-family:monospace;font-size:12px"><?= round($loc['latitude'], 6) ?>, <?= round($loc['longitude'], 6) ?></td>
        <td>
          <?php if ($loc['is_suspect']): ?><span class="tag tag-suspect">⚠ Suspect</span><?php endif; ?>
          <?php if ($loc['is_late']): ?><span class="tag tag-late">Terlambat</span><?php endif; ?>
          <?php if (!$loc['is_suspect'] && !$loc['is_late']): ?>✅<?php endif; ?>
        </td>
      </tr>
    <?php endforeach; ?>
    <?php if (empty($locations)): ?>
      <tr><td colspan="7"><div class="empty"><div class="empty-text">Belum ada absensi hari ini</div></div></td></tr>
    <?php endif; ?>
    </tbody>
  </table>
</div>

<!-- Leaflet Map (free, no API key) -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
  const map = L.map('map').setView([-6.2088, 106.8456], 13);
  L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> | AbsenPIB',
    maxZoom: 19
  }).addTo(map);

  const locations = <?= json_encode(array_values($locations), JSON_UNESCAPED_UNICODE) ?>;
  const markers = [];
  const bounds = [];

  locations.forEach(loc => {
    const lat = parseFloat(loc.latitude);
    const lng = parseFloat(loc.longitude);
    bounds.push([lat, lng]);

    const icon = L.divIcon({
      className: 'custom-marker',
      html: `<div style="background:${loc.color};width:28px;height:28px;border-radius:50%;border:3px solid #fff;box-shadow:0 2px 8px rgba(0,0,0,.3);display:flex;align-items:center;justify-content:center;font-size:14px">${loc.type==='check_in'?'📍':'🏁'}</div>`,
      iconSize: [28, 28],
      iconAnchor: [14, 14]
    });

    const popup = `
      <strong>${loc.full_name || 'Unknown'}</strong><br>
      <small>${loc.nip || ''}</small><br>
      ${loc.type === 'check_in' ? '🟢 Check In' : '🔴 Check Out'} — ${new Date(loc.server_timestamp).toLocaleTimeString('id-ID')}<br>
      <em>${loc.address || 'No address'}</em>
      ${loc.is_suspect ? '<br><span style="color:#dc2626">⚠ Mencurigakan (Score: '+loc.suspicion_score+'/5)</span>' : ''}
    `;

    const marker = L.marker([lat, lng], { icon }).addTo(map).bindPopup(popup);
    markers.push(marker);
  });

  if (bounds.length > 0) {
    map.fitBounds(bounds, { padding: [50, 50] });
  }

  // Office radius circles
  const offices = <?= json_encode($offices, JSON_UNESCAPED_UNICODE) ?>;
  offices.filter(o => o.enforce_geofence == 1).forEach(o => {
    L.circle([parseFloat(o.latitude), parseFloat(o.longitude)], {
      radius: parseInt(o.radius_meters) || 200,
      color: '#1a56db',
      fillColor: '#1a56db',
      fillOpacity: 0.08,
      weight: 1,
      dashArray: '4 4'
    }).addTo(map).bindPopup(`<strong>🏢 ${o.name}</strong><br>Radius: ${o.radius_meters || 200}m`);
  });
});
</script>
<style>
  .leaflet-container { font-family: system-ui, sans-serif; }
</style>

<?php require_once 'includes/footer.php'; ?>
