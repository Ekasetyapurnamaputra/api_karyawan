<?php
require_once __DIR__ . '/../config/helpers.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    header('Location: ' . app_url('index.php'));
    exit;
}

if (!csrf_valid()) {
    set_flash('error', 'Permintaan logout tidak valid. Silakan coba kembali.');
    header('Location: ' . app_url('index.php'));
    exit;
}

$_SESSION = [];

if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', [
        'expires' => time() - 42000,
        'path' => $params['path'],
        'domain' => $params['domain'],
        'secure' => $params['secure'],
        'httponly' => $params['httponly'],
        'samesite' => $params['samesite'] ?? 'Lax',
    ]);
}

session_destroy();
header('Location: ' . app_url('auth/login.php?logout=1'));
exit;
