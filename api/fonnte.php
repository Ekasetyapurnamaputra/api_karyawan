<?php

/**
 * Normalisasi nomor WhatsApp Indonesia ke format internasional 62xxxxxxxxxx.
 */
function fonnte_normalize_indonesia_phone(string $phone): string
{
    $digits = preg_replace('/\D+/', '', trim($phone));

    if (!is_string($digits) || $digits === '') {
        throw new InvalidArgumentException('Nomor WhatsApp wajib diisi.');
    }

    if (str_starts_with($digits, '0')) {
        $digits = '62' . substr($digits, 1);
    } elseif (str_starts_with($digits, '8')) {
        $digits = '62' . $digits;
    }

    // Untuk contoh proyek sekolah ini, terima nomor seluler Indonesia.
    if (!preg_match('/^628[0-9]{7,11}$/', $digits)) {
        throw new InvalidArgumentException(
            'Nomor tidak valid. Gunakan nomor WhatsApp Indonesia, misalnya 081234567890 atau 6281234567890.'
        );
    }

    return $digits;
}

/**
 * Mengirim pesan teks melalui API Fonnte.
 * Mengembalikan ringkasan respons ketika permintaan masuk ke antrean Fonnte.
 * Status antrean bukan bukti bahwa pesan sudah diterima di ponsel tujuan.
 *
 * @return array{target:string, detail:string, http_code:int}
 */
function fonnte_send_message(string $target, string $message): array
{
    if (!function_exists('curl_init')) {
        throw new RuntimeException(
            'Ekstensi cURL PHP belum aktif. Aktifkan extension=curl pada php.ini XAMPP lalu restart Apache.'
        );
    }

    $target = fonnte_normalize_indonesia_phone($target);
    $message = trim($message);

    if ($message === '') {
        throw new InvalidArgumentException('Pesan tidak boleh kosong.');
    }

    $config = require __DIR__ . '/../config/fonnte.php';
    $token = $config['token'] ?? '';

    if (!is_string($token) || trim($token) === '') {
        throw new RuntimeException('Token Fonnte tidak tersedia.');
    }

    $curl = curl_init();
    if ($curl === false) {
        throw new RuntimeException('Tidak dapat memulai cURL.');
    }

    curl_setopt_array($curl, [
        CURLOPT_URL => 'https://api.fonnte.com/send',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => [
            'target' => $target,
            'message' => $message,
            // Nomor sudah dinormalisasi ke 62, jadi Fonnte tidak perlu mengubah awalan lagi.
            'countryCode' => '0',
        ],
        CURLOPT_HTTPHEADER => [
            'Authorization: ' . $token,
        ],
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
    ]);

    $responseBody = curl_exec($curl);
    $curlError = curl_error($curl);
    $httpCode = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);

    if ($responseBody === false) {
        throw new RuntimeException('Permintaan ke Fonnte gagal: ' . ($curlError ?: 'kesalahan jaringan tidak diketahui.'));
    }

    $response = json_decode($responseBody, true);
    if (!is_array($response)) {
        throw new RuntimeException('Fonnte mengembalikan respons yang tidak dapat dibaca. HTTP ' . $httpCode . '.');
    }

    $status = $response['status'] ?? $response['Status'] ?? false;
    if ($httpCode < 200 || $httpCode >= 300 || $status !== true) {
        $reason = $response['reason'] ?? $response['detail'] ?? 'Permintaan ditolak atau gagal.';
        if (!is_string($reason)) {
            $reason = 'Permintaan ditolak atau gagal.';
        }
        throw new RuntimeException('Fonnte gagal (HTTP ' . $httpCode . '): ' . $reason);
    }

    $detail = $response['detail'] ?? 'Permintaan diterima Fonnte.';
    if (!is_string($detail)) {
        $detail = 'Permintaan diterima Fonnte.';
    }

    return [
        'target' => $target,
        'detail' => $detail,
        'http_code' => $httpCode,
    ];
}
