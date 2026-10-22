<?php
require_once __DIR__ . '/../config/helpers.php';
require_once __DIR__ . '/../config/database.php';
require_role('karyawan');

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    header('Location: ' . app_url('absensi/index.php'));
    exit;
}

if (!csrf_valid()) {
    set_flash('error', 'Sesi formulir tidak valid. Muat ulang halaman lalu coba kembali.');
    header('Location: ' . app_url('absensi/index.php'));
    exit;
}

$user = current_user();
$userId = (int) ($user['id'] ?? 0);
$timezone = new DateTimeZone('Asia/Jakarta');
$now = new DateTimeImmutable('now', $timezone);
$tanggal = $now->format('Y-m-d');
$jamMasuk = $now->format('H:i:s');

try {
    $pdo = getPDO();

    // Ambil data terbaru dan pastikan akun tetap berstatus karyawan.
    $employeeStmt = $pdo->prepare(
        "SELECT id, nik, nama, no_hp
         FROM karyawan
         WHERE id = :id AND role = 'karyawan'
         LIMIT 1"
    );
    $employeeStmt->execute([':id' => $userId]);
    $employee = $employeeStmt->fetch();

    if (!$employee) {
        set_flash('error', 'Akun karyawan tidak ditemukan. Silakan login kembali.');
        header('Location: ' . app_url('auth/login.php'));
        exit;
    }

    $check = $pdo->prepare(
        'SELECT id, jam_masuk FROM absensi
         WHERE id_karyawan = :id_karyawan AND tanggal = :tanggal
         LIMIT 1'
    );
    $check->execute([
        ':id_karyawan' => $userId,
        ':tanggal' => $tanggal,
    ]);

    if ($check->fetch()) {
        set_flash('error', 'Absensi hari ini sudah tercatat. Karyawan tidak boleh absen masuk dua kali pada tanggal yang sama.');
        header('Location: ' . app_url('absensi/index.php'));
        exit;
    }

    // Indeks unik (id_karyawan, tanggal) di database menjaga agar tidak terjadi absensi ganda
    // bahkan jika dua permintaan dikirim hampir bersamaan.
    $insert = $pdo->prepare(
        "INSERT INTO absensi (id_karyawan, tanggal, jam_masuk, status)
         VALUES (:id_karyawan, :tanggal, :jam_masuk, 'Hadir')"
    );
    $insert->execute([
        ':id_karyawan' => $userId,
        ':tanggal' => $tanggal,
        ':jam_masuk' => $jamMasuk,
    ]);
} catch (PDOException $e) {
    error_log('Absensi masuk database error: ' . $e->getMessage());

    // Menangani tabrakan dua request atau refresh yang mengirim POST dua kali.
    try {
        $pdo = getPDO();
        $existing = $pdo->prepare(
            'SELECT id FROM absensi WHERE id_karyawan = :id_karyawan AND tanggal = :tanggal LIMIT 1'
        );
        $existing->execute([
            ':id_karyawan' => $userId,
            ':tanggal' => $tanggal,
        ]);
        if ($existing->fetch()) {
            set_flash('error', 'Absensi hari ini sudah tercatat. Tidak ada absensi kedua yang dibuat.');
        } else {
            set_flash('error', 'Absensi masuk gagal disimpan. Periksa database atau log PHP.');
        }
    } catch (Throwable $lookupError) {
        error_log('Pemeriksaan ulang absensi masuk gagal: ' . $lookupError->getMessage());
        set_flash('error', 'Absensi masuk gagal disimpan. Periksa database atau log PHP.');
    }

    header('Location: ' . app_url('absensi/index.php'));
    exit;
}

// Data absensi sudah tersimpan. Kegagalan WhatsApp tidak membatalkan absensi.
$safeNama = str_replace(["\r", "\n"], ' ', (string) $employee['nama']);
$safeNik = str_replace(["\r", "\n"], ' ', (string) $employee['nik']);
$tanggalPesan = $now->format('d-m-Y');
$pesan = "ABSENSI MASUK\n"
    . "Nama: {$safeNama}\n"
    . "NIK: {$safeNik}\n"
    . "Tanggal: {$tanggalPesan}\n"
    . "Jam: {$jamMasuk}\n"
    . "Status: HADIR";

try {
    require_once __DIR__ . '/../api/fonnte.php';
    fonnte_send_message((string) $employee['no_hp'], $pesan);
    set_flash('success', 'Absensi masuk pukul ' . $jamMasuk . ' berhasil disimpan. Permintaan notifikasi WhatsApp telah diterima Fonnte.');
} catch (Throwable $e) {
    error_log('Notifikasi WhatsApp absensi masuk gagal: ' . $e->getMessage());
    set_flash('warning', 'Absensi masuk berhasil disimpan pukul ' . $jamMasuk . ', tetapi notifikasi WhatsApp gagal diproses. Periksa konfigurasi Fonnte/log PHP; jangan absen ulang.');
}

header('Location: ' . app_url('absensi/index.php'));
exit;
