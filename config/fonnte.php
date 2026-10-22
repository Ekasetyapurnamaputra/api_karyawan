<?php

// Membaca token dari file lokal yang tidak boleh diunggah ke GitHub.
$localConfigPath = __DIR__ . '/fonnte.local.php';

if (!is_file($localConfigPath)) {
    throw new RuntimeException(
        'Konfigurasi Fonnte belum dibuat. Salin fonnte.local.example.php menjadi fonnte.local.php.'
    );
}

$localConfig = require $localConfigPath;

if (!is_array($localConfig)) {
    throw new RuntimeException('Format konfigurasi Fonnte tidak valid.');
}

$token = $localConfig['token'] ?? '';

if (!is_string($token) || trim($token) === '' || $token === 'GANTI_DENGAN_TOKEN_FONNTE') {
    throw new RuntimeException('Token Fonnte belum diisi dengan benar.');
}

$adminPhone = $localConfig['admin_phone'] ?? '';
if (!is_string($adminPhone)) {
    $adminPhone = '';
}

return [
    'token' => trim($token),
    'admin_phone' => trim($adminPhone),
];
