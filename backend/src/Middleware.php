<?php
declare(strict_types=1);

namespace App;

class Middleware
{
    /**
     * Require JWT authentication
     */
    public static function auth(): void
    {
        $header = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        $token = str_replace('Bearer ', '', $header);

        if (!$token) {
            Response::error('Token tidak ditemukan', 401);
        }

        $payload = Auth::verifyToken($token);
        if (!$payload) {
            Response::error('Token tidak valid atau kadaluarsa', 401);
        }

        $GLOBALS['_auth_payload'] = $payload;
    }

    /**
     * Require specific role(s)
     * @param string[] $roles
     */
    public static function role(array $roles): void
    {
        self::auth();
        $userRole = Auth::getRole();
        if (!in_array($userRole, $roles, true)) {
            Response::error('Akses ditolak: role tidak diizinkan', 403);
        }
    }

    /**
     * Require admin or above (admin, owner, superadmin)
     */
    public static function admin(): void
    {
        self::role(['admin', 'owner', 'superadmin']);
    }

    /**
     * Require owner or superadmin
     */
    public static function owner(): void
    {
        self::role(['owner', 'superadmin']);
    }

    /**
     * Require superadmin only
     */
    public static function superadmin(): void
    {
        self::role(['superadmin']);
    }

    /**
     * Tenant isolation: ensure user only accesses own company data
     */
    public static function tenantIsolation(int $requestedCompanyId): void
    {
        self::auth();
        $role = Auth::getRole();
        $userCompanyId = Auth::getCompanyId();

        // superadmin bypasses tenant isolation
        if ($role === 'superadmin') return;

        if ($userCompanyId !== $requestedCompanyId) {
            Response::error('Akses ditolak: data di luar perusahaan Anda', 403);
        }
    }
}
