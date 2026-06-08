<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Auth;
use App\Database;
use App\Response;
use App\Utils\Validator;

class CompanyController
{
    public function list(): void
    {
        $db = Database::getInstance();
        $stmt = $db->query('SELECT c.*, COUNT(u.id) as employee_count FROM companies c LEFT JOIN users u ON c.id = u.company_id GROUP BY c.id ORDER BY c.created_at DESC');
        Response::success($stmt->fetchAll());
    }

    public function create(array $body): void
    {
        $v = new Validator();
        $v->required('name', $body['name'] ?? null, 'Nama Perusahaan')
          ->required('code', $body['code'] ?? null, 'Kode Perusahaan')
          ->required('owner_email', $body['owner_email'] ?? null, 'Email Owner')
          ->required('owner_nip', $body['owner_nip'] ?? null, 'NIP Owner')
          ->required('owner_password', $body['owner_password'] ?? null, 'Password Owner')
          ->validate();

        $db = Database::getInstance();

        // Create company
        $stmt = $db->prepare('INSERT INTO companies (name, code, address, phone) VALUES (:name, :code, :addr, :phone)');
        $stmt->execute([
            ':name' => $body['name'],
            ':code' => $body['code'],
            ':addr' => $body['address'] ?? null,
            ':phone' => $body['phone'] ?? null,
        ]);
        $companyId = (int) $db->lastInsertId();

        // Create default office
        $stmt = $db->prepare('INSERT INTO offices (company_id, name, latitude, longitude) VALUES (:cid, "Kantor Pusat", -6.2088, 106.8456)');
        $stmt->execute([':cid' => $companyId]);
        $officeId = (int) $db->lastInsertId();

        // Create owner
        $hash = Auth::hashPassword($body['owner_password']);
        $stmt = $db->prepare(
            'INSERT INTO users (company_id, office_id, role, nip, full_name, email, password_hash)
             VALUES (:cid, :oid, "owner", :nip, :name, :email, :hash)'
        );
        $stmt->execute([
            ':cid' => $companyId,
            ':oid' => $officeId,
            ':nip' => $body['owner_nip'],
            ':name' => $body['owner_full_name'] ?? 'Owner ' . $body['name'],
            ':email' => $body['owner_email'],
            ':hash' => $hash,
        ]);

        // Update company owner_id
        $db->prepare('UPDATE companies SET owner_id = :oid WHERE id = :cid')
           ->execute([':oid' => $db->lastInsertId(), ':cid' => $companyId]);

        Response::success([
            'company_id' => $companyId,
            'office_id' => $officeId,
        ], 'Perusahaan berhasil dibuat', 201);
    }

    public function update(int $id, array $body): void
    {
        $db = Database::getInstance();
        $fields = [];
        $params = [':id' => $id];

        if (isset($body['name'])) { $fields[] = 'name = :name'; $params[':name'] = $body['name']; }
        if (isset($body['address'])) { $fields[] = 'address = :addr'; $params[':addr'] = $body['address']; }
        if (isset($body['phone'])) { $fields[] = 'phone = :phone'; $params[':phone'] = $body['phone']; }

        if (count($fields) > 0) {
            $stmt = $db->prepare('UPDATE companies SET ' . implode(', ', $fields) . ' WHERE id = :id');
            $stmt->execute($params);
        }

        Response::success(null, 'Perusahaan diperbarui');
    }
}
