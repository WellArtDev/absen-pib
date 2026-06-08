<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Auth;
use App\Database;
use App\Utils\CsvExport;

class ReportController
{
    public function export(array $query): void
    {
        $type = $query['type'] ?? 'attendance';
        $start = $query['start'] ?? date('Y-m-01');
        $end = $query['end'] ?? date('Y-m-t');
        $companyId = Auth::getCompanyId();
        $db = Database::getInstance();

        match ($type) {
            'attendance' => $this->exportAttendance($db, $companyId, $start, $end),
            'overtime' => $this->exportOvertime($db, $companyId, $start, $end),
            'leave' => $this->exportLeave($db, $companyId, $start, $end),
            default => \App\Response::error('Tipe export tidak valid: attendance, overtime, leave'),
        };
    }

    private function exportAttendance(\PDO $db, int $companyId, string $start, string $end): void
    {
        $stmt = $db->prepare(
            'SELECT u.nip, u.full_name, a.type, a.server_timestamp, a.address,
                    a.is_late, a.suspicion_score, a.is_suspect
             FROM attendances a JOIN users u ON a.user_id = u.id
             WHERE a.company_id = :cid
               AND a.server_timestamp BETWEEN :start AND :end
             ORDER BY u.full_name, a.server_timestamp'
        );
        $stmt->execute([':cid' => $companyId, ':start' => $start . ' 00:00:00', ':end' => $end . ' 23:59:59']);
        $rows = $stmt->fetchAll();

        $headers = ['nip' => 'NIP', 'full_name' => 'Nama', 'type' => 'Tipe', 'server_timestamp' => 'Waktu',
                     'address' => 'Lokasi', 'is_late' => 'Terlambat', 'suspicion_score' => 'Score Anti-Fake',
                     'is_suspect' => 'Mencurigakan'];

        $csv = CsvExport::generate($rows, $headers);
        CsvExport::download($csv, "absensi_{$companyId}_{$start}_{$end}.csv");
    }

    private function exportOvertime(\PDO $db, int $companyId, string $start, string $end): void
    {
        $stmt = $db->prepare(
            'SELECT u.nip, u.full_name, o.check_in_at, o.check_out_at, o.duration_minutes,
                    o.status, o.check_in_address
             FROM overtimes o JOIN users u ON o.user_id = u.id
             WHERE o.company_id = :cid
               AND DATE(o.check_in_at) BETWEEN :start AND :end
             ORDER BY u.full_name, o.check_in_at'
        );
        $stmt->execute([':cid' => $companyId, ':start' => $start, ':end' => $end]);
        $rows = $stmt->fetchAll();

        $headers = ['nip' => 'NIP', 'full_name' => 'Nama', 'check_in_at' => 'Mulai', 'check_out_at' => 'Selesai',
                     'duration_minutes' => 'Durasi (menit)', 'status' => 'Status', 'check_in_address' => 'Lokasi'];

        $csv = CsvExport::generate($rows, $headers);
        CsvExport::download($csv, "lembur_{$companyId}_{$start}_{$end}.csv");
    }

    private function exportLeave(\PDO $db, int $companyId, string $start, string $end): void
    {
        $stmt = $db->prepare(
            'SELECT u.nip, u.full_name, l.leave_type, l.start_date, l.end_date, l.total_days,
                    l.status, l.reason
             FROM leaves l JOIN users u ON l.user_id = u.id
             WHERE l.company_id = :cid
               AND l.start_date <= :end AND l.end_date >= :start
             ORDER BY u.full_name, l.start_date'
        );
        $stmt->execute([':cid' => $companyId, ':start' => $start, ':end' => $end]);
        $rows = $stmt->fetchAll();

        $headers = ['nip' => 'NIP', 'full_name' => 'Nama', 'leave_type' => 'Jenis Cuti', 'start_date' => 'Mulai',
                     'end_date' => 'Selesai', 'total_days' => 'Total Hari', 'status' => 'Status', 'reason' => 'Alasan'];

        $csv = CsvExport::generate($rows, $headers);
        CsvExport::download($csv, "cuti_{$companyId}_{$start}_{$end}.csv");
    }
}
