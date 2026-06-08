<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Auth;
use App\Database;
use App\Response;
use App\Utils\Validator;

class OfficeController
{
    public function get(): void
    {
        $companyId = Auth::getCompanyId();
        $db = Database::getInstance();

        $stmt = $db->prepare('SELECT * FROM offices WHERE company_id = :cid ORDER BY id');
        $stmt->execute([':cid' => $companyId]);
        $offices = $stmt->fetchAll();

        Response::success($offices);
    }

    public function save(array $body): void
    {
        $v = new Validator();
        $v->required('name', $body['name'] ?? null, 'Nama Kantor')
          ->required('latitude', $body['latitude'] ?? null, 'Latitude')
          ->required('longitude', $body['longitude'] ?? null, 'Longitude')
          ->numeric('latitude', $body['latitude'] ?? null)
          ->numeric('longitude', $body['longitude'] ?? null)
          ->validate();

        $companyId = Auth::getCompanyId();
        $db = Database::getInstance();

        // Check if office exists (update) or insert
        $stmt = $db->prepare('SELECT id FROM offices WHERE company_id = :cid AND id = :id');
        $stmt->execute([':cid' => $companyId, ':id' => (int) ($body['id'] ?? 0)]);
        $exists = $stmt->fetch();

        if ($exists) {
            $stmt = $db->prepare(
                'UPDATE offices SET name = :name, latitude = :lat, longitude = :lng,
                 radius_meters = :radius, work_start = :wstart, work_end = :wend,
                 enforce_geofence = :gf WHERE id = :id AND company_id = :cid'
            );
            $stmt->execute([
                ':name' => $body['name'],
                ':lat' => (float) $body['latitude'],
                ':lng' => (float) $body['longitude'],
                ':radius' => (int) ($body['radius_meters'] ?? 200),
                ':wstart' => $body['work_start'] ?? '08:00',
                ':wend' => $body['work_end'] ?? '17:00',
                ':gf' => ($body['enforce_geofence'] ?? true) ? 1 : 0,
                ':id' => (int) $body['id'],
                ':cid' => $companyId,
            ]);
        } else {
            $stmt = $db->prepare(
                'INSERT INTO offices (company_id, name, latitude, longitude, radius_meters, work_start, work_end, enforce_geofence)
                 VALUES (:cid, :name, :lat, :lng, :radius, :wstart, :wend, :gf)'
            );
            $stmt->execute([
                ':cid' => $companyId,
                ':name' => $body['name'],
                ':lat' => (float) $body['latitude'],
                ':lng' => (float) $body['longitude'],
                ':radius' => (int) ($body['radius_meters'] ?? 200),
                ':wstart' => $body['work_start'] ?? '08:00',
                ':wend' => $body['work_end'] ?? '17:00',
                ':gf' => ($body['enforce_geofence'] ?? true) ? 1 : 0,
            ]);
        }

        Response::success(null, 'Konfigurasi kantor disimpan');
    }
}
