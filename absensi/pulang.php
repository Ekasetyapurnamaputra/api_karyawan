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
$jamPulang = $now->format('H:i:s');

try {
    $pdo = getPDO();

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

    // Update atomik: hanya dapat berhasil jika absensi masuk sudah ada dan jam pulang masih kosong.
    $update = $pdo->prepare(
        'UPDATE absensi
         SET jam_pulang = :jam_pulang
         WHERE id_karyawan = :id_karyawan
           AND tanggal = :tanggal
           AND jam_masuk IS NOT NULL
           AND jam_pulang IS NULL'
    );
    $update->execute([
        ':jam_pulang' => $jamPulang,
        ':id_karyawan' => $userId,
        ':tanggal' => $tanggal,
    ]);

    if ($update->rowCount() !== 1) {
        $check = $pdo->prepare(
            'SELECT jam_masuk, jam_pulang
             FROM absensi
             WHERE id_karyawan = :id_karyawan AND tanggal = :tanggal
             LIMIT 1'
        );
        $check->execute([
            ':id_karyawan' => $userId,
            ':tanggal' => $tanggal,
        ]);
        $todayRecord = $check->fetch();

        if (!$todayRecord || empty($todayRecord['jam_masuk'])) {
            set_flash('error', 'Absensi pulang ditolak karena kamu belum melakukan absensi masuk hari ini.');
        } elseif (!empty($todayRecord['jam_pulang'])) {
            set_flash('error', 'Absensi pulang hari ini sudah tercatat pada pukul ' . $todayRecord['jam_pulang'] . '.');
        } else {
            set_flash('error', 'Absensi pulang belum dapat disimpan. Muat ulang halaman dan coba kembali.');
        }
        header('Location: ' . app_url('absensi/index.php'));
        exit;
    }

    $attendanceStmt = $pdo->prepare(
        'SELECT jam_masuk, jam_pulang, status
         FROM absensi
         WHERE id_karyawan = :id_karyawan AND tanggal = :tanggal
         LIMIT 1'
    );
    $attendanceStmt->execute([
        ':id_karyawan' => $userId,
        ':tanggal' => $tanggal,
    ]);
    $attendance = $attendanceStmt->fetch();
    if (!$attendance) {
        throw new RuntimeException('Data absensi tidak ditemukan setelah pembaruan.');
    }
} catch (PDOException $e) {
    error_log('Absensi pulang database error: ' . $e->getMessage());
    set_flash('error', 'Absensi pulang gagal diproses. Periksa koneksi database atau log PHP.');
    header('Location: ' . app_url('absensi/index.php'));
    exit;
}

// Jam pulang sudah tersimpan. Error Fonnte tidak membatalkan data absensi.
$safeNama = str_replace(["\r", "\n"], ' ', (string) $employee['nama']);
$safeNik = str_replace(["\r", "\n"], ' ', (string) $employee['nik']);
$tanggalPesan = $now->format('d-m-Y');
$pesan = "ABSENSI PULANG\n"
    . "Nama: {$safeNama}\n"
    . "NIK: {$safeNik}\n"
    . "Tanggal: {$tanggalPesan}\n"
    . "Jam Masuk: {$attendance['jam_masuk']}\n"
    . "Jam Pulang: {$attendance['jam_pulang']}\n"
    . "Status: PULANG";

try {
    require_once __DIR__ . '/../api/fonnte.php';
    fonnte_send_message((string) $employee['no_hp'], $pesan);
    set_flash('success', 'Absensi pulang pukul ' . $jamPulang . ' berhasil disimpan. Permintaan notifikasi WhatsApp telah diterima Fonnte.');
} catch (Throwable $e) {
    error_log('Notifikasi WhatsApp absensi pulang gagal: ' . $e->getMessage());
    set_flash('warning', 'Absensi pulang berhasil disimpan pukul ' . $jamPulang . ', tetapi notifikasi WhatsApp gagal diproses. Periksa konfigurasi Fonnte/log PHP.');
}

header('Location: ' . app_url('absensi/index.php'));
exit;
