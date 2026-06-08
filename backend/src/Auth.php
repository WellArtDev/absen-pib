<?php
declare(strict_types=1);

namespace App;

/**
 * Simple JWT implementation — no external dependencies.
 * Uses HMAC-SHA256.
 */
class Auth
{
    private static string $algo = 'sha256';

    public static function base64urlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    public static function base64urlDecode(string $data): string
    {
        return base64_decode(strtr($data, '-_', '+/'));
    }

    public static function generateToken(array $user): string
    {
        $secret = $_ENV['JWT_SECRET'] ?? 'changeme';
        $expiry = (int) ($_ENV['JWT_EXPIRY'] ?? 86400);

        $header = self::base64urlEncode(json_encode(['alg' => 'HS256', 'typ' => 'JWT']));

        $payload = self::base64urlEncode(json_encode([
            'iss' => 'absen-pib',
            'iat' => time(),
            'exp' => time() + $expiry,
            'sub' => (int) $user['id'],
            'company_id' => (int) ($user['company_id'] ?? 0),
            'role' => $user['role'],
            'nip' => $user['nip'],
            'full_name' => $user['full_name'],
        ]));

        $signature = self::base64urlEncode(
            hash_hmac(self::$algo, "{$header}.{$payload}", $secret, true)
        );

        return "{$header}.{$payload}.{$signature}";
    }

    public static function verifyToken(string $token): ?object
    {
        try {
            $parts = explode('.', $token);
            if (count($parts) !== 3) return null;

            $secret = $_ENV['JWT_SECRET'] ?? 'changeme';
            $sig = self::base64urlEncode(
                hash_hmac(self::$algo, "{$parts[0]}.{$parts[1]}", $secret, true)
            );

            if (!hash_equals($sig, $parts[2])) return null;

            $payload = json_decode(self::base64urlDecode($parts[1]), true);
            if (!$payload) return null;

            // Check expiry
            if (isset($payload['exp']) && $payload['exp'] < time()) return null;

            return (object) $payload;
        } catch (\Throwable) {
            return null;
        }
    }

    public static function getUserId(): int
    {
        return (int) ($GLOBALS['_auth_payload']->sub ?? 0);
    }

    public static function getCompanyId(): int
    {
        return (int) ($GLOBALS['_auth_payload']->company_id ?? 0);
    }

    public static function getRole(): string
    {
        return $GLOBALS['_auth_payload']->role ?? '';
    }

    public static function hashPassword(string $password): string
    {
        return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
    }

    public static function verifyPassword(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }
}
