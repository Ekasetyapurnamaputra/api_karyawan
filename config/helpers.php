<?php

// Helper umum aplikasi. File ini juga menginisialisasi session secara aman.
if (session_status() !== PHP_SESSION_ACTIVE) {
    $isHttps = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';

    ini_set('session.use_only_cookies', '1');
    ini_set('session.use_strict_mode', '1');

    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => $isHttps,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);

    session_start();
}

// Sesuaikan jika nama folder project berbeda dari karyawan-wa.
const APP_BASE_URL = '/karyawan-wa';

function app_url(string $path = ''): string
{
    return APP_BASE_URL . '/' . ltrim($path, '/');
}

function e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function post_string(string $key): string
{
    $value = $_POST[$key] ?? '';
    return is_string($value) ? trim($value) : '';
}

function csrf_token(): string
{
    if (!isset($_SESSION['csrf_token']) || !is_string($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function csrf_valid(): bool
{
    $posted = $_POST['csrf_token'] ?? '';
    $stored = $_SESSION['csrf_token'] ?? '';

    return is_string($posted)
        && is_string($stored)
        && $posted !== ''
        && hash_equals($stored, $posted);
}

function set_flash(string $key, string $message): void
{
    $_SESSION['_flash'][$key] = $message;
}

function get_flash(string $key): ?string
{
    if (!isset($_SESSION['_flash'][$key])) {
        return null;
    }

    $message = $_SESSION['_flash'][$key];
    unset($_SESSION['_flash'][$key]);

    return is_string($message) ? $message : null;
}

function current_user(): ?array
{
    return isset($_SESSION['user']) && is_array($_SESSION['user'])
        ? $_SESSION['user']
        : null;
}

function require_login(): void
{
    if (current_user() === null) {
        set_flash('error', 'Silakan login terlebih dahulu.');
        header('Location: ' . app_url('auth/login.php'));
        exit;
    }
}

function require_role(string $role): void
{
    require_login();

    $user = current_user();
    if (($user['role'] ?? '') !== $role) {
        http_response_code(403);
        exit('Akses ditolak.');
    }
}

/**
 * Memvalidasi format tanggal Y-m-d sekaligus memastikan tanggalnya benar-benar ada di kalender.
 */
function is_valid_date_string(string $date): bool
{
    if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $date, $parts) !== 1) {
        return false;
    }

    return checkdate((int) $parts[2], (int) $parts[3], (int) $parts[1]);
}

/**
 * Mengubah nilai apa pun menjadi teks yang aman untuk diekspor ke CSV/spreadsheet.
 * Nilai yang diawali =, +, -, atau @ diberi tanda kutip agar tidak dieksekusi
 * sebagai formula oleh Excel / Google Sheets.
 */
function csv_safe_value($value): string
{
    $value = (string) ($value ?? '');

    if ($value !== '' && preg_match('/^[\s\x00-\x1F]*[=+\-@]/u', $value) === 1) {
        return "'" . $value;
    }

    return $value;
}

