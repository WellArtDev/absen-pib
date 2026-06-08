<?php $title = 'Konfigurasi Kantor'; require_once 'includes/header.php';
if (!$isOwner) { echo '<div class="alert alert-error">Akses ditolak. Owner/Superadmin saja.</div>'; require_once 'includes/footer.php'; exit; }

$msg = '';
$offices = api('GET', '/office/config')['data'] ?? [];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_check()) {
    $res = api('POST', '/office/config', [
        'id'                => safe_int($_POST['id'] ?? 0) ?: null,
        'name'              => safe_str($_POST['name'] ?? ''),
        'latitude'          => safe_float($_POST['latitude'] ?? 0),
        'longitude'         => safe_float($_POST['longitude'] ?? 0),
        'radius_meters'     => safe_int($_POST['radius_meters'] ?? 200),
        'work_start'        => safe_str($_POST['work_start'] ?? '08:00'),
        'work_end'          => safe_str($_POST['work_end'] ?? '17:00'),
        'enforce_geofence'  => isset($_POST['enforce_geofence']),
    ]);
    $msg = ($res['success'] ?? false) ? 'Konfigurasi disimpan.' : ($res['error'] ?? 'Gagal');
    $offices = api('GET', '/office/config')['data'] ?? [];
}
?>

<h1 class="page-title">🏢 Konfigurasi Kantor</h1>
<?php if ($msg): ?><div class="alert alert-success"><?= safe_str($msg) ?></div><?php endif; ?>

<?php foreach ($offices as $o): ?>
<div class="card">
  <div class="card-title">📍 <?= safe_str($o['name']) ?></div>
  <form method="POST"><?= csrf_field() ?><input type="hidden" name="id" value="<?= $o['id'] ?>">
    <div class="grid-2">
      <div class="form-group"><label>Nama Kantor</label><input name="name" value="<?= safe_str($o['name']) ?>" required maxlength="100"></div>
      <div class="form-group"><label>Radius Geofence (meter)</label><input name="radius_meters" type="number" value="<?= $o['radius_meters'] ?? 200 ?>" required min="10" max="10000"></div>
      <div class="form-group"><label>Latitude</label><input name="latitude" value="<?= $o['latitude'] ?>" required step="any" placeholder="-6.2088"></div>
      <div class="form-group"><label>Longitude</label><input name="longitude" value="<?= $o['longitude'] ?>" required step="any" placeholder="106.8456"></div>
      <div class="form-group"><label>Jam Masuk</label><input name="work_start" type="time" value="<?= $o['work_start'] ?? '08:00' ?>" required></div>
      <div class="form-group"><label>Jam Pulang</label><input name="work_end" type="time" value="<?= $o['work_end'] ?? '17:00' ?>" required></div>
    </div>
    <div class="form-group"><label><input type="checkbox" name="enforce_geofence" <?= ($o['enforce_geofence']??1)?'checked':'' ?> style="width:auto"> Wajib Geofence (karyawan kantor harus absen dalam radius)</label></div>
    <p style="margin-bottom:12px;font-size:12px;color:#6b7280">📍 <a href="https://www.openstreetmap.org/?mlat=<?= $o['latitude'] ?>&mlon=<?= $o['longitude'] ?>&zoom=18" target="_blank" rel="noopener" style="color:#1a56db">Lihat di OpenStreetMap →</a></p>
    <button type="submit" class="btn btn-primary">💾 Simpan</button>
  </form>
</div>
<?php endforeach; ?>

<div class="card">
  <div class="card-title">➕ Tambah Kantor Cabang</div>
  <form method="POST"><?= csrf_field() ?>
    <div class="grid-2">
      <div class="form-group"><label>Nama Kantor</label><input name="name" required maxlength="100" placeholder="Kantor Cabang"></div>
      <div class="form-group"><label>Radius (m)</label><input name="radius_meters" type="number" value="200" required min="10"></div>
      <div class="form-group"><label>Latitude</label><input name="latitude" required step="any" placeholder="-6.2088"></div>
      <div class="form-group"><label>Longitude</label><input name="longitude" required step="any" placeholder="106.8456"></div>
      <div class="form-group"><label>Jam Masuk</label><input name="work_start" type="time" value="08:00" required></div>
      <div class="form-group"><label>Jam Pulang</label><input name="work_end" type="time" value="17:00" required></div>
    </div>
    <button type="submit" class="btn btn-success">➕ Tambah Kantor</button>
  </form>
</div>
<?php require_once 'includes/footer.php'; ?>
