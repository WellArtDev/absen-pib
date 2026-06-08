<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Auth;
use App\Database;
use App\Response;
use App\Utils\Validator;

class AdminController
{
    public function dashboard(array $query): void
    {
        $companyId = Auth::getCompanyId();
        $db = Database::getInstance();
        $today = date('Y-m-d');

        // Stats
        $stmt = $db->prepare('SELECT COUNT(*) as total FROM users WHERE company_id = :cid AND is_active = 1');
        $stmt->execute([':cid' => $companyId]);
        $totalEmployees = (int) $stmt->fetch()['total'];

        $stmt = $db->prepare(
            'SELECT COUNT(DISTINCT user_id) as cnt FROM attendances
             WHERE company_id = :cid AND type = "check_in" AND DATE(server_timestamp) = :today'
        );
        $stmt->execute([':cid' => $companyId, ':today' => $today]);
        $presentToday = (int) $stmt->fetch()['cnt'];

        $stmt = $db->prepare(
            'SELECT COUNT(*) as cnt FROM attendances
             WHERE company_id = :cid AND type = "check_in" AND DATE(server_timestamp) = :today AND is_late = 1'
        );
        $stmt->execute([':cid' => $companyId, ':today' => $today]);
        $lateToday = (int) $stmt->fetch()['cnt'];

        $absentToday = max(0, $totalEmployees - $presentToday);

        // Pending overtime
        $stmt = $db->prepare('SELECT COUNT(*) as cnt FROM overtimes WHERE company_id = :cid AND status = "pending"');
        $stmt->execute([':cid' => $companyId]);
        $pendingOvertime = (int) $stmt->fetch()['cnt'];

        // Pending leaves
        $stmt = $db->prepare('SELECT COUNT(*) as cnt FROM leaves WHERE company_id = :cid AND status = "pending"');
        $stmt->execute([':cid' => $companyId]);
        $pendingLeaves = (int) $stmt->fetch()['cnt'];

        Response::success([
            'total_employees' => $totalEmployees,
            'present_today' => $presentToday,
            'late_today' => $lateToday,
            'absent_today' => $absentToday,
            'pending_overtime' => $pendingOvertime,
            'pending_leaves' => $pendingLeaves,
        ]);
    }

    public function employees(array $query): void
    {
        $companyId = Auth::getCompanyId();
        $db = Database::getInstance();

        $page = max(1, (int) ($query['page'] ?? 1));
        $limit = min(100, max(1, (int) ($query['limit'] ?? 20)));
        $offset = ($page - 1) * $limit;

        $stmt = $db->prepare('SELECT COUNT(*) as total FROM users WHERE company_id = :cid AND is_active = 1');
        $stmt->execute([':cid' => $companyId]);
        $total = (int) $stmt->fetch()['total'];

        $stmt = $db->prepare(
            'SELECT id, company_id, office_id, role, nip, full_name, email, avatar_url, phone,
                    leave_quota_total, leave_quota_used, is_active, created_at
             FROM users WHERE company_id = :cid AND is_active = 1
             ORDER BY created_at DESC LIMIT :limit OFFSET :offset'
        );
        $stmt->bindValue(':cid', $companyId, \PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, \PDO::PARAM_INT);
        $stmt->execute();
        $employees = $stmt->fetchAll();

        Response::paginated($employees, $total, $page, $limit);
    }

    public function createEmployee(array $body): void
    {
        $v = new Validator();
        $v->required('nip', $body['nip'] ?? null, 'NIP')
          ->required('full_name', $body['full_name'] ?? null, 'Nama Lengkap')
          ->required('email', $body['email'] ?? null, 'Email')
          ->required('password', $body['password'] ?? null, 'Password')
          ->inArray('role', $body['role'] ?? 'karyawan', ['admin', 'sales', 'karyawan'])
          ->email('email', $body['email'] ?? null)
          ->validate();

        $companyId = Auth::getCompanyId();
        $db = Database::getInstance();

        $hash = Auth::hashPassword($body['password']);

        $stmt = $db->prepare(
            'INSERT INTO users (company_id, office_id, role, nip, full_name, email, password_hash)
             VALUES (:cid, :oid, :role, :nip, :name, :email, :hash)'
        );
        $stmt->execute([
            ':cid' => $companyId,
            ':oid' => isset($body['office_id']) ? (int) $body['office_id'] : null,
            ':role' => $body['role'] ?? 'karyawan',
            ':nip' => $body['nip'],
            ':name' => $body['full_name'],
            ':email' => $body['email'],
            ':hash' => $hash,
        ]);

        $id = (int) $db->lastInsertId();
        $stmt = $db->prepare('SELECT * FROM users WHERE id = :id');
        $stmt->execute([':id' => $id]);
        $user = $stmt->fetch();
        unset($user['password_hash']);

        Response::success($user, 'Karyawan berhasil ditambahkan', 201);
    }

    public function employeeDetail(int $id): void
    {
        $companyId = Auth::getCompanyId();
        $db = Database::getInstance();

        $stmt = $db->prepare('SELECT * FROM users WHERE id = :id AND company_id = :cid');
        $stmt->execute([':id' => $id, ':cid' => $companyId]);
        $user = $stmt->fetch();

        if (!$user) Response::error('Karyawan tidak ditemukan', 404);
        unset($user['password_hash'], $user['fcm_token']);

        // Get recent attendances
        $stmt = $db->prepare(
            'SELECT * FROM attendances WHERE user_id = :uid ORDER BY server_timestamp DESC LIMIT 30'
        );
        $stmt->execute([':uid' => $id]);
        $attendances = $stmt->fetchAll();

        Response::success([
            'profile' => $user,
            'attendances' => $attendances,
        ]);
    }

    public function updateEmployee(int $id, array $body): void
    {
        $companyId = Auth::getCompanyId();
        $db = Database::getInstance();

        $fields = [];
        $params = [':id' => $id, ':cid' => $companyId];

        if (isset($body['full_name'])) {
            $fields[] = 'full_name = :name';
            $params[':name'] = $body['full_name'];
        }
        if (isset($body['nip'])) {
            $fields[] = 'nip = :nip';
            $params[':nip'] = $body['nip'];
        }
        if (isset($body['office_id'])) {
            $fields[] = 'office_id = :oid';
            $params[':oid'] = (int) $body['office_id'];
        }
        if (isset($body['role'])) {
            $fields[] = 'role = :role';
            $params[':role'] = $body['role'];
        }
        if (isset($body['is_active'])) {
            $fields[] = 'is_active = :active';
            $params[':active'] = $body['is_active'] ? 1 : 0;
        }

        if (count($fields) > 0) {
            $stmt = $db->prepare(
                'UPDATE users SET ' . implode(', ', $fields) . ' WHERE id = :id AND company_id = :cid'
            );
            $stmt->execute($params);
        }

        Response::success(null, 'Data karyawan diperbarui');
    }
}
