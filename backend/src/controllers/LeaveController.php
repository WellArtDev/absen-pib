<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Auth;
use App\Database;
use App\Response;
use App\Utils\Validator;

class LeaveController
{
    public function submit(array $body): void
    {
        $v = new Validator();
        $v->required('leave_type', $body['leave_type'] ?? null, 'Jenis Cuti')
          ->required('start_date', $body['start_date'] ?? null, 'Tanggal Mulai')
          ->required('end_date', $body['end_date'] ?? null, 'Tanggal Selesai')
          ->required('reason', $body['reason'] ?? null, 'Alasan')
          ->inArray('leave_type', $body['leave_type'] ?? null, ['tahunan', 'sakit', 'darurat', 'lainnya'])
          ->validate();

        $userId = Auth::getUserId();
        $companyId = Auth::getCompanyId();
        $db = Database::getInstance();

        $start = new \DateTime($body['start_date']);
        $end = new \DateTime($body['end_date']);
        if ($end < $start) Response::error('Tanggal selesai tidak boleh sebelum tanggal mulai');

        $interval = $start->diff($end);
        $totalDays = $interval->days + 1;

        // Check quota for tahunan
        if ($body['leave_type'] === 'tahunan') {
            $stmt = $db->prepare('SELECT leave_quota_total, leave_quota_used FROM users WHERE id = :id');
            $stmt->execute([':id' => $userId]);
            $user = $stmt->fetch();

            $remaining = $user['leave_quota_total'] - $user['leave_quota_used'];
            if ($totalDays > $remaining) {
                Response::error("Kuota cuti tidak cukup. Sisa: {$remaining} hari");
            }
        }

        $stmt = $db->prepare(
            'INSERT INTO leaves (user_id, company_id, leave_type, start_date, end_date, total_days, reason)
             VALUES (:uid, :cid, :type, :start, :end, :days, :reason)'
        );
        $stmt->execute([
            ':uid' => $userId,
            ':cid' => $companyId,
            ':type' => $body['leave_type'],
            ':start' => $body['start_date'],
            ':end' => $body['end_date'],
            ':days' => $totalDays,
            ':reason' => $body['reason'],
        ]);

        $id = (int) $db->lastInsertId();
        $stmt = $db->prepare('SELECT * FROM leaves WHERE id = :id');
        $stmt->execute([':id' => $id]);
        $record = $stmt->fetch();

        Response::success($record, 'Pengajuan cuti dikirim, menunggu approval', 201);
    }

    public function history(): void
    {
        $userId = Auth::getUserId();
        $db = Database::getInstance();

        $stmt = $db->prepare('SELECT * FROM leaves WHERE user_id = :uid ORDER BY created_at DESC');
        $stmt->execute([':uid' => $userId]);

        Response::success($stmt->fetchAll());
    }

    public function pending(): void
    {
        $companyId = Auth::getCompanyId();
        $db = Database::getInstance();

        $stmt = $db->prepare(
            'SELECT l.*, u.full_name, u.nip, u.leave_quota_total, u.leave_quota_used
             FROM leaves l JOIN users u ON l.user_id = u.id
             WHERE l.company_id = :cid AND l.status = "pending"
             ORDER BY l.created_at DESC'
        );
        $stmt->execute([':cid' => $companyId]);

        Response::success($stmt->fetchAll());
    }

    public function quota(): void
    {
        $userId = Auth::getUserId();
        $db = Database::getInstance();

        $stmt = $db->prepare('SELECT leave_quota_total, leave_quota_used FROM users WHERE id = :id');
        $stmt->execute([':id' => $userId]);
        $user = $stmt->fetch();

        Response::success([
            'total' => (int) $user['leave_quota_total'],
            'used' => (int) $user['leave_quota_used'],
            'remaining' => (int) ($user['leave_quota_total'] - $user['leave_quota_used']),
        ]);
    }

    public function detail(int $id): void
    {
        $db = Database::getInstance();
        $stmt = $db->prepare('SELECT l.*, u.full_name, u.nip FROM leaves l JOIN users u ON l.user_id = u.id WHERE l.id = :id');
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

        $stmt = $db->prepare('SELECT * FROM leaves WHERE id = :id AND company_id = :cid');
        $stmt->execute([':id' => $id, ':cid' => $companyId]);
        $leave = $stmt->fetch();

        if (!$leave) Response::error('Cuti tidak ditemukan', 404);
        if ($leave['status'] !== 'pending') Response::error('Cuti sudah ' . $leave['status']);

        // Deduct quota if tahunan
        if ($leave['leave_type'] === 'tahunan' && !$leave['quota_deducted']) {
            $stmt = $db->prepare('UPDATE users SET leave_quota_used = leave_quota_used + :days WHERE id = :id');
            $stmt->execute([':days' => $leave['total_days'], ':id' => $leave['user_id']]);
        }

        $stmt = $db->prepare(
            'UPDATE leaves SET status = "approved", approved_by = :aid, approved_at = NOW(),
             quota_deducted = 1 WHERE id = :id'
        );
        $stmt->execute([':aid' => $approvedBy, ':id' => $id]);

        Response::success(null, 'Cuti disetujui');
    }

    public function reject(int $id, array $body): void
    {
        $v = new Validator();
        $v->required('reason', $body['reason'] ?? null, 'Alasan')->validate();

        $companyId = Auth::getCompanyId();
        $approvedBy = Auth::getUserId();
        $db = Database::getInstance();

        $stmt = $db->prepare('SELECT * FROM leaves WHERE id = :id AND company_id = :cid');
        $stmt->execute([':id' => $id, ':cid' => $companyId]);
        $leave = $stmt->fetch();

        if (!$leave) Response::error('Cuti tidak ditemukan', 404);
        if ($leave['status'] !== 'pending') Response::error('Cuti sudah ' . $leave['status']);

        $stmt = $db->prepare(
            'UPDATE leaves SET status = "rejected", approved_by = :aid, approved_at = NOW(),
             rejection_reason = :reason WHERE id = :id'
        );
        $stmt->execute([':aid' => $approvedBy, ':reason' => $body['reason'], ':id' => $id]);

        Response::success(null, 'Cuti ditolak');
    }
}
