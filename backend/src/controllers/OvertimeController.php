<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Auth;
use App\Database;
use App\Response;
use App\Utils\ImageUpload;
use App\Utils\Validator;

class OvertimeController
{
    public function start(array $body): void
    {
        $v = new Validator();
        $v->required('photo', $body['photo'] ?? null, 'Foto')
          ->required('latitude', $body['latitude'] ?? null, 'Latitude')
          ->required('longitude', $body['longitude'] ?? null, 'Longitude')
          ->validate();

        $userId = Auth::getUserId();
        $companyId = Auth::getCompanyId();

        $uploader = new ImageUpload();
        $photoUrl = $uploader->uploadBase64($body['photo'], 'attendance-photos');

        $db = Database::getInstance();
        $stmt = $db->prepare(
            'INSERT INTO overtimes (user_id, company_id, check_in_photo_url, check_in_lat, check_in_lng,
             check_in_address, check_in_at)
             VALUES (:uid, :cid, :photo, :lat, :lng, :addr, NOW())'
        );
        $stmt->execute([
            ':uid' => $userId,
            ':cid' => $companyId,
            ':photo' => $photoUrl,
            ':lat' => (float) $body['latitude'],
            ':lng' => (float) $body['longitude'],
            ':addr' => $body['address'] ?? null,
        ]);

        $id = (int) $db->lastInsertId();
        $stmt = $db->prepare('SELECT * FROM overtimes WHERE id = :id');
        $stmt->execute([':id' => $id]);
        $record = $stmt->fetch();

        Response::success($record, 'Lembur dimulai', 201);
    }

    public function end(array $body): void
    {
        $v = new Validator();
        $v->required('overtime_id', $body['overtime_id'] ?? null, 'ID Lembur')
          ->required('photo', $body['photo'] ?? null, 'Foto')
          ->required('latitude', $body['latitude'] ?? null, 'Latitude')
          ->required('longitude', $body['longitude'] ?? null, 'Longitude')
          ->validate();

        $userId = Auth::getUserId();

        $uploader = new ImageUpload();
        $photoUrl = $uploader->uploadBase64($body['photo'], 'attendance-photos');

        $db = Database::getInstance();

        // Verify ownership
        $stmt = $db->prepare('SELECT * FROM overtimes WHERE id = :id AND user_id = :uid');
        $stmt->execute([':id' => (int) $body['overtime_id'], ':uid' => $userId]);
        $overtime = $stmt->fetch();

        if (!$overtime) Response::error('Lembur tidak ditemukan', 404);
        if ($overtime['check_out_at'] !== null) Response::error('Lembur sudah selesai');

        $checkInTime = strtotime($overtime['check_in_at']);
        $checkOutTime = time();
        $durationMinutes = max(0, (int) (($checkOutTime - $checkInTime) / 60));

        $stmt = $db->prepare(
            'UPDATE overtimes SET check_out_photo_url = :photo, check_out_lat = :lat, check_out_lng = :lng,
             check_out_address = :addr, check_out_at = NOW(), duration_minutes = :dur
             WHERE id = :id'
        );
        $stmt->execute([
            ':photo' => $photoUrl,
            ':lat' => (float) $body['latitude'],
            ':lng' => (float) $body['longitude'],
            ':addr' => $body['address'] ?? null,
            ':dur' => $durationMinutes,
            ':id' => (int) $body['overtime_id'],
        ]);

        $stmt = $db->prepare('SELECT * FROM overtimes WHERE id = :id');
        $stmt->execute([':id' => (int) $body['overtime_id']]);
        $record = $stmt->fetch();

        Response::success($record, 'Lembur selesai, menunggu approval');
    }

    public function history(): void
    {
        $userId = Auth::getUserId();
        $db = Database::getInstance();

        $stmt = $db->prepare('SELECT * FROM overtimes WHERE user_id = :uid ORDER BY created_at DESC');
        $stmt->execute([':uid' => $userId]);

        Response::success($stmt->fetchAll());
    }

    public function pending(): void
    {
        $companyId = Auth::getCompanyId();
        $db = Database::getInstance();

        $stmt = $db->prepare(
            'SELECT o.*, u.full_name, u.nip
             FROM overtimes o JOIN users u ON o.user_id = u.id
             WHERE o.company_id = :cid AND o.status = "pending"
             ORDER BY o.created_at DESC'
        );
        $stmt->execute([':cid' => $companyId]);

        Response::success($stmt->fetchAll());
    }

    public function detail(int $id): void
    {
        $db = Database::getInstance();
        $stmt = $db->prepare('SELECT o.*, u.full_name, u.nip FROM overtimes o JOIN users u ON o.user_id = u.id WHERE o.id = :id');
        $stmt->execute([':id' => $id]);
        $record = $stmt->fetch();

        if (!$record) Response::error('Data tidak ditemukan', 404);
        Response::success($record);
    }

    public function approve(int $id, array $body): void
    {
        $companyId = Auth::getCompanyId();
        $approvedBy = Auth::getUserId();
        $db = Database::getInstance();

        $stmt = $db->prepare('SELECT * FROM overtimes WHERE id = :id AND company_id = :cid');
        $stmt->execute([':id' => $id, ':cid' => $companyId]);
        $overtime = $stmt->fetch();

        if (!$overtime) Response::error('Lembur tidak ditemukan', 404);
        if ($overtime['status'] !== 'pending') Response::error('Lembur sudah ' . $overtime['status']);

        $stmt = $db->prepare(
            'UPDATE overtimes SET status = "approved", approved_by = :aid, approved_at = NOW() WHERE id = :id'
        );
        $stmt->execute([':aid' => $approvedBy, ':id' => $id]);

        Response::success(null, 'Lembur disetujui');
    }

    public function reject(int $id, array $body): void
    {
        $v = new Validator();
        $v->required('reason', $body['reason'] ?? null, 'Alasan')->validate();

        $companyId = Auth::getCompanyId();
        $approvedBy = Auth::getUserId();
        $db = Database::getInstance();

        $stmt = $db->prepare('SELECT * FROM overtimes WHERE id = :id AND company_id = :cid');
        $stmt->execute([':id' => $id, ':cid' => $companyId]);
        $overtime = $stmt->fetch();

        if (!$overtime) Response::error('Lembur tidak ditemukan', 404);
        if ($overtime['status'] !== 'pending') Response::error('Lembur sudah ' . $overtime['status']);

        $stmt = $db->prepare(
            'UPDATE overtimes SET status = "rejected", approved_by = :aid, approved_at = NOW(),
             rejection_reason = :reason WHERE id = :id'
        );
        $stmt->execute([':aid' => $approvedBy, ':reason' => $body['reason'], ':id' => $id]);

        Response::success(null, 'Lembur ditolak');
    }
}
