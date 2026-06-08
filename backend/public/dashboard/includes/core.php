<?php
/**
 * Dashboard Core — shared API helper, auth, input validation, utilities
 * No duplicate code — all pages use these functions.
 */

// Auto-detect API URL from current host (works with Laragon vhosts)
$HOST = $_SERVER['HTTP_HOST'] ?? 'localhost';
$API_URL = (isset($_SERVER['HTTPS']) ? 'https' : 'http') . "://{$HOST}";

// ─── Security: Input sanitization ──────────────────
function safe_str(string $str): string {
    return htmlspecialchars(strip_tags(trim($str)), ENT_QUOTES, 'UTF-8');
}
function safe_int(mixed $val): int {
    return (int) filter_var($val, FILTER_VALIDATE_INT);
}
function safe_float(mixed $val): float {
    return (float) filter_var($val, FILTER_VALIDATE_FLOAT);
}
function safe_date(string $val): string {
    $d = \DateTime::createFromFormat('Y-m-d', $val);
    return $d ? $d->format('Y-m-d') : date('Y-m-d');
}
function safe_email(string $val): string {
    $val = filter_var(trim($val), FILTER_VALIDATE_EMAIL);
    return $val ?: '';
}

// URL parameters — always filtered before use
function get_param(string $key, string $default = '', string $type = 'string'): mixed {
    $val = $_GET[$key] ?? $default;
    return match($type) {
        'int'    => safe_int($val),
        'float'  => safe_float($val),
        'date'   => safe_date((string)$val),
        'email'  => safe_email((string)$val),
        'string' => safe_str((string)$val),
        default  => safe_str((string)$val),
    };
}

// ─── API call ──────────────────────────────────────
function api(string $method, string $path, ?array $body = null): array {
    global $API_URL;
    $token = $_COOKIE['absen_token'] ?? '';
    $ch = curl_init($API_URL . $path);

    // Include cookie so Laragon Apache authenticates the internal request
    $cookieStr = 'absen_token=' . $token;
    if (!empty($_COOKIE['absen_user'])) {
        $cookieStr .= '; absen_user=' . $_COOKIE['absen_user'];
    }

    $headers = [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $token,
        'Cookie: ' . $cookieStr,
    ];
    curl_setopt_array($ch, [
        CURLOPT_CUSTOMREQUEST  => $method,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => $headers,
        CURLOPT_TIMEOUT        => 10,
        CURLOPT_CONNECTTIMEOUT => 3,
    ]);
    if ($body !== null) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body, JSON_UNESCAPED_UNICODE));
    }
    $response = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($code === 401) {
        header('Set-Cookie: absen_token=; Path=/; Max-Age=0; HttpOnly', false);
        header('Set-Cookie: absen_user=; Path=/; Max-Age=0; HttpOnly', false);
        header('Location: index.php?expired=1');
        exit;
    }
    return json_decode($response, true) ?? [];
}

// ─── Auth check (skip if not needed) ────────────────
$user = json_decode($_COOKIE['absen_user'] ?? 'null', true) ?: [];
$isLoggedIn = !empty($user) && !empty($_COOKIE['absen_token']);

// ─── Fetch stats once per request ───────────────────
$stats = [];
$role = $user['role'] ?? '';
$isSuperadmin = ($role === 'superadmin');
$isOwner = ($role === 'owner' || $role === 'superadmin');
$isAdmin = ($isOwner || $role === 'admin');

if ($isLoggedIn) {
    $stats = api('GET', '/admin/dashboard')['data'] ?? [];
}

// ─── Shared badge helpers (no duplicate HTML) ───────
function roleBadge(string $r): string {
    $map = [
        'superadmin' => ['#fef3c7','#92400e','Superadmin'],
        'owner'      => ['#dbeafe','#1e40af','Owner'],
        'admin'      => ['#d1fae5','#065f46','Admin'],
        'sales'      => ['#f3e8ff','#6b21a8','Sales'],
        'karyawan'   => ['#f3f4f6','#374151','Karyawan'],
    ];
    [$bg,$fg,$label] = $map[$r] ?? $map['karyawan'];
    return "<span style='background:{$bg};color:{$fg};padding:2px 8px;border-radius:6px;font-size:11px;font-weight:700'>{$label}</span>";
}

function statusBadge(string $s): string {
    return match($s) {
        'approved' => '<span style="color:#059669;font-weight:700">Disetujui</span>',
        'rejected' => '<span style="color:#dc2626;font-weight:700">Ditolak</span>',
        default    => '<span style="color:#d97706;font-weight:700">Pending</span>',
    };
}

function leaveTypeIcon(string $t): string {
    return match($t) {
        'tahunan' => '🗓 Tahunan',
        'sakit'   => '🤒 Sakit',
        'darurat' => '🚨 Darurat',
        default   => '📝 Lainnya',
    };
}

function formatDate(string $dt, string $fmt = 'd M Y H:i'): string {
    $ts = strtotime($dt);
    return $ts ? date($fmt, $ts) : '-';
}

function truncate(string $s, int $len = 40): string {
    return mb_strlen($s) > $len ? mb_substr($s, 0, $len) . '…' : $s;
}

// ─── CSRF token ─────────────────────────────────────
if (session_status() === PHP_SESSION_NONE) session_start();
if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
}
function csrf_field(): string {
    return '<input type="hidden" name="_csrf" value="' . $_SESSION['csrf'] . '">';
}
function csrf_check(): bool {
    return hash_equals($_SESSION['csrf'], $_POST['_csrf'] ?? '');
}

$currentPage = basename($_SERVER['PHP_SELF']);
?>