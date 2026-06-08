<?php
declare(strict_types=1);

namespace App;

class Response
{
    public static function json(mixed $data, int $status = 200): never
    {
        http_response_code($status);
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    public static function success(mixed $data = null, string $message = 'OK', int $status = 200): never
    {
        self::json(['success' => true, 'message' => $message, 'data' => $data], $status);
    }

    public static function error(string $message, int $status = 400, mixed $errors = null): never
    {
        $payload = ['success' => false, 'error' => $message];
        if ($errors !== null) $payload['errors'] = $errors;
        self::json($payload, $status);
    }

    public static function paginated(array $data, int $total, int $page, int $limit): never
    {
        self::json([
            'success' => true,
            'data' => $data,
            'meta' => ['total' => $total, 'page' => $page, 'limit' => $limit]
        ]);
    }
}
