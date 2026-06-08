<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Auth;
use App\Database;
use App\Response;
use App\Utils\Validator;

class AuthController
{
    public function register(array $body): void
    {
        $v = new Validator();
        $v->required('company_id', $body['company_id'] ?? null, 'ID Perusahaan')
          ->required('nip', $body['nip'] ?? null, 'NIP')
          ->required('full_name', $body['full_name'] ?? null, 'Nama Lengkap')
          ->required('email', $body['email'] ?? null, 'Email')
          ->required('password', $body['password'] ?? null, 'Password')
          ->inArray('role', $body['role'] ?? 'karyawan', ['admin', 'sales', 'karyawan'])
          ->email('email', $body['email'] ?? null)
          ->minLength('password', $body['password'] ?? '', 6, 'Password')
          ->validate();

        $db = Database::getInstance();

        // Check email exists
        $stmt = $db->prepare('SELECT id FROM users WHERE email = :email');
        $stmt->execute([':email' => $body['email']]);
        if ($stmt->fetch()) {
            Response::error('Email sudah terdaftar');
        }

        $hash = Auth::hashPassword($body['password']);

        $stmt = $db->prepare(
            'INSERT INTO users (company_id, office_id, role, nip, full_name, email, password_hash)
             VALUES (:cid, :oid, :role, :nip, :name, :email, :hash)'
        );
        $stmt->execute([
            ':cid' => (int) $body['company_id'],
            ':oid' => isset($body['office_id']) ? (int) $body['office_id'] : null,
            ':role' => $body['role'] ?? 'karyawan',
            ':nip' => $body['nip'],
            ':name' => $body['full_name'],
            ':email' => $body['email'],
            ':hash' => $hash,
        ]);

        $userId = (int) $db->lastInsertId();

        $user = $db->prepare('SELECT * FROM users WHERE id = :id');
        $user->execute([':id' => $userId]);
        $userData = $user->fetch();

        $token = Auth::generateToken($userData);

        Response::success([
            'token' => $token,
            'user' => $this->sanitizeUser($userData),
        ], 'Registrasi berhasil', 201);
    }

    public function login(array $body): void
    {
        $v = new Validator();
        $v->required('email', $body['email'] ?? null, 'Email')
          ->required('password', $body['password'] ?? null, 'Password')
          ->validate();

        $db = Database::getInstance();
        $stmt = $db->prepare('SELECT * FROM users WHERE email = :email AND is_active = 1');
        $stmt->execute([':email' => $body['email']]);
        $user = $stmt->fetch();

        if (!$user || !Auth::verifyPassword($body['password'], $user['password_hash'])) {
            Response::error('Email atau password salah', 401);
        }

        $token = Auth::generateToken($user);

        Response::success([
            'token' => $token,
            'user' => $this->sanitizeUser($user),
        ], 'Login berhasil');
    }

    public function forgotPassword(array $body): void
    {
        $v = new Validator();
        $v->required('email', $body['email'] ?? null, 'Email')->validate();

        $db = Database::getInstance();
        $stmt = $db->prepare('SELECT id FROM users WHERE email = :email');
        $stmt->execute([':email' => $body['email']]);
        $user = $stmt->fetch();

        if (!$user) {
            // Return success anyway (security: don't reveal if email exists)
            Response::success(null, 'Jika email terdaftar, link reset akan dikirim');
        }

        // TODO: send actual email with reset token
        // For now: placeholder
        Response::success(null, 'Jika email terdaftar, link reset akan dikirim');
    }

    public function profile(): void
    {
        $db = Database::getInstance();
        $stmt = $db->prepare('SELECT * FROM users WHERE id = :id');
        $stmt->execute([':id' => Auth::getUserId()]);
        $user = $stmt->fetch();

        if (!$user) Response::error('User tidak ditemukan', 404);

        Response::success($this->sanitizeUser($user));
    }

    public function updateProfile(array $body): void
    {
        $db = Database::getInstance();
        $fields = [];
        $params = [':id' => Auth::getUserId()];

        if (isset($body['full_name'])) {
            $fields[] = 'full_name = :name';
            $params[':name'] = $body['full_name'];
        }
        if (isset($body['phone'])) {
            $fields[] = 'phone = :phone';
            $params[':phone'] = $body['phone'];
        }
        if (isset($body['avatar_url'])) {
            $fields[] = 'avatar_url = :avatar';
            $params[':avatar'] = $body['avatar_url'];
        }

        if (count($fields) > 0) {
            $stmt = $db->prepare('UPDATE users SET ' . implode(', ', $fields) . ' WHERE id = :id');
            $stmt->execute($params);
        }

        Response::success(null, 'Profil diperbarui');
    }

    private function sanitizeUser(array $user): array
    {
        unset($user['password_hash'], $user['fcm_token']);
        return $user;
    }
}
