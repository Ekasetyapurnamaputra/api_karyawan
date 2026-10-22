<?php
require_once __DIR__ . '/../config/helpers.php';
require_once __DIR__ . '/../config/database.php';

if (current_user() !== null) {
    $existingUser = current_user();
    if (($existingUser['role'] ?? '') === 'admin') {
        header('Location: ' . app_url('admin/index.php'));
    } else {
        header('Location: ' . app_url('absensi/index.php'));
    }
    exit;
}

$errors = [];
$nik = '';
$nama = '';
$jabatan = '';
$no_hp = '';
$email = '';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $nik = post_string('nik');
    $nama = post_string('nama');
    $jabatan = post_string('jabatan');
    $no_hp = post_string('no_hp');
    $email = strtolower(post_string('email'));
    $password = isset($_POST['password']) && is_string($_POST['password']) ? $_POST['password'] : '';
    $konfirmasi = isset($_POST['konfirmasi_password']) && is_string($_POST['konfirmasi_password']) ? $_POST['konfirmasi_password'] : '';

    if (!csrf_valid()) {
        $errors[] = 'Sesi formulir telah berakhir atau tidak valid. Muat ulang halaman lalu coba kembali.';
    }

    if ($nik === '' || strlen($nik) > 30) {
        $errors[] = 'NIK wajib diisi (maksimal 30 karakter).';
    }
    if ($nama === '' || strlen($nama) > 100) {
        $errors[] = 'Nama lengkap wajib diisi (maksimal 100 karakter).';
    }
    if ($jabatan === '' || strlen($jabatan) > 100) {
        $errors[] = 'Jabatan wajib diisi (maksimal 100 karakter).';
    }
    if ($no_hp === '' || strlen($no_hp) > 20 || !preg_match('/^\+?[0-9][0-9\s().-]{6,18}[0-9]$/', $no_hp)) {
        $errors[] = 'Nomor WhatsApp wajib diisi dengan format nomor yang valid (contoh: 081234567890).';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 150) {
        $errors[] = 'Alamat email tidak valid (maksimal 150 karakter).';
    }
    if (strlen($password) < 8) {
        $errors[] = 'Password minimal terdiri dari 8 karakter.';
    }
    if ($password !== $konfirmasi) {
        $errors[] = 'Konfirmasi password tidak sesuai dengan password yang dimasukkan.';
    }

    if (!$errors) {
        try {
            $pdo = getPDO();

            $check = $pdo->prepare(
                'SELECT id FROM karyawan WHERE nik = :nik OR email = :email LIMIT 1'
            );
            $check->execute([
                ':nik' => $nik,
                ':email' => $email,
            ]);

            if ($check->fetch()) {
                $errors[] = 'NIK atau email sudah terdaftar di sistem. Silakan periksa kembali atau gunakan data lain.';
            } else {
                $passwordHash = password_hash($password, PASSWORD_DEFAULT);

                // Role akun baru selalu otomatis 'karyawan', tidak dapat memilih role admin
                $insert = $pdo->prepare(
                    "INSERT INTO karyawan (nik, nama, jabatan, no_hp, email, password, role)
                     VALUES (:nik, :nama, :jabatan, :no_hp, :email, :password, 'karyawan')"
                );
                $insert->execute([
                    ':nik' => $nik,
                    ':nama' => $nama,
                    ':jabatan' => $jabatan,
                    ':no_hp' => $no_hp,
                    ':email' => $email,
                    ':password' => $passwordHash,
                ]);

                // Data tersimpan di database. Notifikasi WhatsApp diproses terpisah.
                $notificationFailures = [];
                $safeNama = str_replace(["\r", "\n"], ' ', $nama);
                $safeNik = str_replace(["\r", "\n"], ' ', $nik);
                $safeJabatan = str_replace(["\r", "\n"], ' ', $jabatan);
                $safeNoHp = str_replace(["\r", "\n"], ' ', $no_hp);
                $safeEmail = str_replace(["\r", "\n"], ' ', $email);
                $waktuRegistrasi = (new DateTimeImmutable(
                    'now',
                    new DateTimeZone('Asia/Jakarta')
                ))->format('d-m-Y H:i:s');

                $pesanKaryawan = "REGISTRASI BERHASIL\n"
                    . "Halo {$safeNama}, akun karyawan Anda telah berhasil terdaftar.\n"
                    . "NIK: {$safeNik}\n"
                    . "Jabatan: {$safeJabatan}\n"
                    . "Waktu registrasi: {$waktuRegistrasi} WIB";

                $pesanAdmin = "KARYAWAN BARU TERDAFTAR\n"
                    . "Nama: {$safeNama}\n"
                    . "NIK: {$safeNik}\n"
                    . "Jabatan: {$safeJabatan}\n"
                    . "Nomor WhatsApp: {$safeNoHp}\n"
                    . "Email: {$safeEmail}\n"
                    . "Waktu registrasi: {$waktuRegistrasi} WIB";

                try {
                    require_once __DIR__ . '/../api/fonnte.php';

                    try {
                        fonnte_send_message($no_hp, $pesanKaryawan);
                    } catch (Throwable $e) {
                        error_log('Register WhatsApp to employee failed: ' . $e->getMessage());
                        $notificationFailures[] = 'karyawan';
                    }

                    try {
                        $fonnteConfig = require __DIR__ . '/../config/fonnte.php';
                        $adminPhone = $fonnteConfig['admin_phone'] ?? '';

                        if (!is_string($adminPhone) || trim($adminPhone) === '') {
                            throw new RuntimeException(
                                'Nomor WhatsApp admin belum diatur pada config/fonnte.local.php.'
                            );
                        }

                        fonnte_send_message($adminPhone, $pesanAdmin);
                    } catch (Throwable $e) {
                        error_log('Register WhatsApp to admin failed: ' . $e->getMessage());
                        $notificationFailures[] = 'admin';
                    }
                } catch (Throwable $e) {
                    error_log('Register WhatsApp integration setup failed: ' . $e->getMessage());
                    $notificationFailures[] = 'integrasi WhatsApp';
                }

                if ($notificationFailures === []) {
                    $flashMessage = 'Pendaftaran berhasil. Permintaan notifikasi WhatsApp ke nomor Anda dan admin telah diterima Fonnte. Silakan masuk.';
                } else {
                    $failedTargets = implode(' dan ', array_unique($notificationFailures));
                    $flashMessage = 'Pendaftaran berhasil dan akun Anda telah tersimpan. Namun, notifikasi WhatsApp belum berhasil dikirim ke: '
                        . $failedTargets
                        . '. Akun tetap dapat digunakan. Silakan login.';
                }

                set_flash('success', $flashMessage);
                header('Location: ' . app_url('auth/login.php'));
                exit;
            }
        } catch (PDOException $e) {
            error_log('Register database error: ' . $e->getMessage());
            if ($e->getCode() === '23000') {
                $errors[] = 'NIK atau email sudah terdaftar. Silakan gunakan data yang berbeda.';
            } else {
                $errors[] = 'Pendaftaran belum dapat diproses. Terjadi gangguan pada koneksi data internal.';
            }
        }
    }
}

$success = get_flash('success');
$flashError = get_flash('error');
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Registrasi Karyawan — Karyawan-WA</title>
    <link rel="stylesheet" href="<?= e(app_url('assets/style.css')) ?>">
    <link rel="stylesheet" href="<?= e(app_url('assets/auth.css')) ?>">
</head>
<body>
<main class="auth-wrapper">
    <section class="auth-card register-card" aria-labelledby="register-title">
        <div class="auth-brand">
            <div class="auth-brand-icon" aria-hidden="true">K</div>
            <h1 id="register-title" class="auth-title">Pendaftaran Karyawan</h1>
            <p class="auth-subtitle">Buat akun presensi baru. Akun otomatis memiliki hak akses sebagai karyawan.</p>
        </div>

        <?php if ($success): ?>
            <div class="alert success" role="alert"><?= e($success) ?></div>
        <?php endif; ?>

        <?php if ($flashError): ?>
            <div class="alert error" role="alert"><?= e($flashError) ?></div>
        <?php endif; ?>

        <?php if ($errors): ?>
            <div class="alert error" role="alert">
                <strong>Mohon periksa kembali formulir Anda:</strong>
                <ul>
                    <?php foreach ($errors as $err): ?>
                        <li><?= e($err) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="post" action="<?= e(app_url('auth/register.php')) ?>" class="auth-form" autocomplete="on">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">

            <div class="form-row">
                <div class="form-group">
                    <label for="nik">NIK (Nomor Induk Karyawan)</label>
                    <input id="nik" 
                           name="nik" 
                           type="text" 
                           maxlength="30" 
                           required 
                           value="<?= e($nik) ?>" 
                           autocomplete="off" 
                           inputmode="numeric"
                           placeholder="Contoh: 3201012345670001">
                </div>

                <div class="form-group">
                    <label for="nama">Nama Lengkap</label>
                    <input id="nama" 
                           name="nama" 
                           type="text" 
                           maxlength="100" 
                           required 
                           value="<?= e($nama) ?>" 
                           autocomplete="name" 
                           placeholder="Nama lengkap Anda">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="jabatan">Jabatan / Posisi</label>
                    <input id="jabatan" 
                           name="jabatan" 
                           type="text" 
                           maxlength="100" 
                           required 
                           value="<?= e($jabatan) ?>" 
                           placeholder="Contoh: Staff IT, Operator, dll.">
                </div>

                <div class="form-group">
                    <label for="no_hp">Nomor WhatsApp</label>
                    <input id="no_hp" 
                           name="no_hp" 
                           type="tel" 
                           maxlength="20" 
                           required 
                           value="<?= e($no_hp) ?>" 
                           placeholder="081234567890" 
                           autocomplete="tel" 
                           inputmode="tel">
                    <span class="form-hint">Format Indonesia (awalan 08 atau 62).</span>
                </div>
            </div>

            <div class="form-group">
                <label for="email">Alamat Email</label>
                <input id="email" 
                       name="email" 
                       type="email" 
                       maxlength="150" 
                       required 
                       value="<?= e($email) ?>" 
                       autocomplete="email" 
                       inputmode="email"
                       placeholder="nama@perusahaan.com">
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="password">Password (minimal 8 karakter)</label>
                    <div class="password-input-wrapper">
                        <input id="password" 
                               name="password" 
                               type="password" 
                               minlength="8" 
                               required 
                               autocomplete="new-password" 
                               placeholder="Minimal 8 karakter">
                        <button type="button" 
                                class="password-toggle-btn" 
                                data-target="password" 
                                aria-label="Tampilkan password" 
                                title="Tampilkan password">
                            <svg class="icon-eye" viewBox="0 0 24 24" aria-hidden="true">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                                <circle cx="12" cy="12" r="3"/>
                            </svg>
                            <svg class="icon-eye-off" viewBox="0 0 24 24" aria-hidden="true" style="display:none;">
                                <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/>
                                <line x1="1" y1="1" x2="23" y2="23"/>
                            </svg>
                        </button>
                    </div>
                </div>

                <div class="form-group">
                    <label for="konfirmasi_password">Konfirmasi Password</label>
                    <div class="password-input-wrapper">
                        <input id="konfirmasi_password" 
                               name="konfirmasi_password" 
                               type="password" 
                               minlength="8" 
                               required 
                               autocomplete="new-password" 
                               placeholder="Ulangi password">
                        <button type="button" 
                                class="password-toggle-btn" 
                                data-target="konfirmasi_password" 
                                aria-label="Tampilkan konfirmasi password" 
                                title="Tampilkan konfirmasi password">
                            <svg class="icon-eye" viewBox="0 0 24 24" aria-hidden="true">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                                <circle cx="12" cy="12" r="3"/>
                            </svg>
                            <svg class="icon-eye-off" viewBox="0 0 24 24" aria-hidden="true" style="display:none;">
                                <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/>
                                <line x1="1" y1="1" x2="23" y2="23"/>
                            </svg>
                        </button>
                    </div>
                </div>
            </div>

            <button type="submit" class="button auth-submit-btn">
                <span>Daftar Akun Karyawan</span>
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <line x1="5" y1="12" x2="19" y2="12"/>
                    <polyline points="12 5 19 12 12 19"/>
                </svg>
            </button>
        </form>

        <div class="auth-footer">
            Sudah memiliki akun? <a href="<?= e(app_url('auth/login.php')) ?>">Masuk di sini</a>
        </div>
    </section>

    <div class="auth-brand-bottom">
        &copy; <?= date('Y') ?> Karyawan-WA Enterprise &middot; Keamanan Data Terjamin
    </div>
</main>

<script src="<?= e(app_url('assets/auth.js')) ?>"></script>
</body>
</html>
