<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Auth;
use App\Database;
use App\Response;
use App\Utils\ImageUpload;
use App\Utils\AntiFakeGps;
use App\Utils\Validator;

class AttendanceController
{
    public function checkIn(array $body): void
    {
        $v = new Validator();
        $v->required('photo', $body['photo'] ?? null, 'Foto')
          ->required('latitude', $body['latitude'] ?? null, 'Latitude')
          ->required('longitude', $body['longitude'] ?? null, 'Longitude')
          ->numeric('latitude', $body['latitude'] ?? null)
          ->numeric('longitude', $body['longitude'] ?? null)
          ->validate();

        $db = Database::getInstance();
        $userId = Auth::getUserId();
        $companyId = Auth::getCompanyId();

        // Upload photo
        $uploader = new ImageUpload();
        $photoUrl = $uploader->uploadBase64($body['photo'], 'attendance-photos');

        // Anti-fake check
        $antiFake = AntiFakeGps::check([
            'user_id' => $userId,
            'latitude' => (float) $body['latitude'],
            'longitude' => (float) $body['longitude'],
            'gps_timestamp' => $body['gps_timestamp'] ?? null,
        ]);

        // Check if late
        $isLate = false;
        // Get office config
        $stmt = $db->prepare('SELECT * FROM offices WHERE id = :oid');
        $stmt->execute([':oid' => Auth::getRole() === 'sales' ? null : $body['office_id'] ?? null]);
        $office = $stmt->fetch();
        if ($office) {
            $workStart = $office['work_start'];
            $now = date('H:i:s');
            $isLate = $now > $workStart;
        }

        $stmt = $db->prepare(
            'INSERT INTO attendances (user_id, company_id, office_id, type, photo_url, latitude, longitude,
             altitude, gps_accuracy, address, device_info, gps_providers, gps_timestamp, is_late,
             suspicion_score, suspicion_flags, is_suspect)
             VALUES (:uid, :cid, :oid, "check_in", :photo, :lat, :lng, :alt, :acc, :addr, :dev, :prov, :gtime, :late,
             :score, :flags, :suspect)'
        );
        $stmt->execute([
            ':uid' => $userId,
            ':cid' => $companyId,
            ':oid' => isset($body['office_id']) ? (int) $body['office_id'] : null,
            ':photo' => $photoUrl,
            ':lat' => (float) $body['latitude'],
            ':lng' => (float) $body['longitude'],
            ':alt' => $body['altitude'] ?? null,
            ':acc' => $body['gps_accuracy'] ?? null,
            ':addr' => $body['address'] ?? null,
            ':dev' => isset($body['device_info']) ? json_encode($body['device_info']) : null,
            ':prov' => isset($body['gps_providers']) ? json_encode($body['gps_providers']) : null,
            ':gtime' => !empty($body['gps_timestamp']) ? date('Y-m-d H:i:s', strtotime($body['gps_timestamp'])) : null,
            ':late' => $isLate ? 1 : 0,
            ':score' => $antiFake['score'],
            ':flags' => json_encode($antiFake['flags']),
            ':suspect' => $antiFake['is_suspect'] ? 1 : 0,
        ]);

        $id = (int) $db->lastInsertId();
        $stmt = $db->prepare('SELECT * FROM attendances WHERE id = :id');
        $stmt->execute([':id' => $id]);
        $record = $stmt->fetch();

        Response::success($record, 'Check-in berhasil', 201);
    }

    public function checkOut(array $body): void
    {
        $v = new Validator();
        $v->required('photo', $body['photo'] ?? null, 'Foto')
          ->required('latitude', $body['latitude'] ?? null, 'Latitude')
          ->required('longitude', $body['longitude'] ?? null, 'Longitude')
          ->validate();

        $userId = Auth::getUserId();
        $companyId = Auth::getCompanyId();
        $db = Database::getInstance();

        $uploader = new ImageUpload();
        $photoUrl = $uploader->uploadBase64($body['photo'], 'attendance-photos');

        $antiFake = AntiFakeGps::check([
            'user_id' => $userId,
            'latitude' => (float) $body['latitude'],
            'longitude' => (float) $body['longitude'],
            'gps_timestamp' => $body['gps_timestamp'] ?? null,
        ]);

        $stmt = $db->prepare(
            'INSERT INTO attendances (user_id, company_id, type, photo_url, latitude, longitude,
             altitude, gps_accuracy, address, gps_providers, gps_timestamp,
             suspicion_score, suspicion_flags, is_suspect)
             VALUES (:uid, :cid, "check_out", :photo, :lat, :lng, :alt, :acc, :addr, :prov, :gtime,
             :score, :flags, :suspect)'
        );
        $stmt->execute([
            ':uid' => $userId,
            ':cid' => $companyId,
            ':photo' => $photoUrl,
            ':lat' => (float) $body['latitude'],
            ':lng' => (float) $body['longitude'],
            ':alt' => $body['altitude'] ?? null,
            ':acc' => $body['gps_accuracy'] ?? null,
            ':addr' => $body['address'] ?? null,
            ':prov' => isset($body['gps_providers']) ? json_encode($body['gps_providers']) : null,
            ':gtime' => !empty($body['gps_timestamp']) ? date('Y-m-d H:i:s', strtotime($body['gps_timestamp'])) : null,
            ':score' => $antiFake['score'],
            ':flags' => json_encode($antiFake['flags']),
            ':suspect' => $antiFake['is_suspect'] ? 1 : 0,
        ]);

        $id = (int) $db->lastInsertId();
        $stmt = $db->prepare('SELECT * FROM attendances WHERE id = :id');
        $stmt->execute([':id' => $id]);
        $record = $stmt->fetch();

        Response::success($record, 'Check-out berhasil', 201);
    }

    public function today(): void
    {
        $userId = Auth::getUserId();
        $db = Database::getInstance();
        $today = date('Y-m-d');

        $stmt = $db->prepare(
            'SELECT * FROM attendances WHERE user_id = :uid AND DATE(server_timestamp) = :today ORDER BY server_timestamp'
        );
        $stmt->execute([':uid' => $userId, ':today' => $today]);
        $records = $stmt->fetchAll();

        $checkIn = null;
        $checkOut = null;
        foreach ($records as $r) {
            if ($r['type'] === 'check_in') $checkIn = $r;
            if ($r['type'] === 'check_out') $checkOut = $r;
        }

        Response::success(['check_in' => $checkIn, 'check_out' => $checkOut]);
    }

    public function history(array $query): void
    {
        $userId = Auth::getUserId();
        $db = Database::getInstance();

        $start = $query['start'] ?? date('Y-m-01');
        $end = $query['end'] ?? date('Y-m-t');
        $page = max(1, (int) ($query['page'] ?? 1));
        $limit = min(100, max(1, (int) ($query['limit'] ?? 20)));
        $offset = ($page - 1) * $limit;

        $stmt = $db->prepare(
            'SELECT * FROM attendances WHERE user_id = :uid
             AND server_timestamp BETWEEN :start AND :end
             ORDER BY server_timestamp DESC LIMIT :limit OFFSET :offset'
        );
        $stmt->bindValue(':uid', $userId, \PDO::PARAM_INT);
        $stmt->bindValue(':start', $start . ' 00:00:00');
        $stmt->bindValue(':end', $end . ' 23:59:59');
        $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, \PDO::PARAM_INT);
        $stmt->execute();
        $records = $stmt->fetchAll();

        Response::success($records);
    }

    public function all(array $query): void
    {
        $companyId = Auth::getCompanyId();
        $db = Database::getInstance();

        $start = $query['start'] ?? date('Y-m-01');
        $end = $query['end'] ?? date('Y-m-t');

        $stmt = $db->prepare(
            'SELECT a.*, u.full_name, u.nip
             FROM attendances a JOIN users u ON a.user_id = u.id
             WHERE a.company_id = :cid
               AND a.server_timestamp BETWEEN :start AND :end
             ORDER BY a.server_timestamp DESC'
        );
        $stmt->execute([
            ':cid' => $companyId,
            ':start' => $start . ' 00:00:00',
            ':end' => $end . ' 23:59:59',
        ]);
        $records = $stmt->fetchAll();

        Response::success($records);
    }

    public function detail(int $id): void
    {
        $db = Database::getInstance();
        $stmt = $db->prepare('SELECT * FROM attendances WHERE id = :id');
        $stmt->execute([':id' => $id]);
        $record = $stmt->fetch();

        if (!$record) Response::error('Data tidak ditemukan', 404);

        Response::success($record);
    }

    public function servePhoto(string $filename): void
    {
        $path = __DIR__ . '/../../uploads/attendance-photos/' . basename($filename);
        if (!file_exists($path)) Response::error('File tidak ditemukan', 404);

        header('Content-Type: image/jpeg');
        header('Cache-Control: public, max-age=31536000');
        readfile($path);
        exit;
    }

    public function serveAttachment(string $filename): void
    {
        $path = __DIR__ . '/../../uploads/leave-attachments/' . basename($filename);
        if (!file_exists($path)) Response::error('File tidak ditemukan', 404);

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $path);
        finfo_close($finfo);

        header('Content-Type: ' . $mime);
        header('Cache-Control: public, max-age=31536000');
        readfile($path);
        exit;
    }
}
