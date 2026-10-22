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

$error = null;
$email = '';
$success = get_flash('success');
$flashError = get_flash('error');

if (isset($_GET['logout']) && $_GET['logout'] === '1') {
    $success = 'Anda telah berhasil keluar dari sistem.';
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $email = strtolower(post_string('email'));
    $password = isset($_POST['password']) && is_string($_POST['password']) ? $_POST['password'] : '';

    if (!csrf_valid()) {
        $error = 'Sesi formulir telah berakhir atau tidak valid. Muat ulang halaman lalu coba kembali.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL) || $password === '') {
        $error = 'Format email tidak valid atau password belum diisi.';
    } else {
        try {
            $pdo = getPDO();
            $stmt = $pdo->prepare(
                'SELECT id, nik, nama, jabatan, email, password, role
                 FROM karyawan WHERE email = :email LIMIT 1'
            );
            $stmt->execute([':email' => $email]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                // Ganti ID session setelah login untuk membantu mencegah session fixation.
                session_regenerate_id(true);
                $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
                $_SESSION['user'] = [
                    'id' => (int) $user['id'],
                    'nik' => $user['nik'],
                    'nama' => $user['nama'],
                    'jabatan' => $user['jabatan'],
                    'email' => $user['email'],
                    'role' => $user['role'],
                ];

                if ($user['role'] === 'admin') {
                    header('Location: ' . app_url('admin/index.php'));
                } else {
                    header('Location: ' . app_url('absensi/index.php'));
                }
                exit;
            }

            // Pesan umum agar tidak membocorkan apakah email terdaftar.
            $error = 'Email atau password yang Anda masukkan salah.';
        } catch (PDOException $e) {
            error_log('Login database error: ' . $e->getMessage());
            $error = 'Layanan login belum dapat memproses permintaan. Periksa koneksi basis data atau log sistem.';
        }
    }
}
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login Pengguna — Karyawan-WA</title>
    <link rel="stylesheet" href="<?= e(app_url('assets/style.css')) ?>">
    <link rel="stylesheet" href="<?= e(app_url('assets/auth.css')) ?>">
</head>
<body>
<main class="auth-wrapper">
    <section class="auth-card" aria-labelledby="login-title">
        <div class="auth-brand">
            <div class="auth-brand-icon" aria-hidden="true">K</div>
            <h1 id="login-title" class="auth-title">Karyawan-WA</h1>
            <p class="auth-subtitle">Portal Presensi &amp; Manajemen Karyawan</p>
        </div>

        <?php if ($success): ?>
            <div class="alert success" role="alert"><?= e($success) ?></div>
        <?php endif; ?>

        <?php if ($flashError): ?>
            <div class="alert error" role="alert"><?= e($flashError) ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert error" role="alert"><?= e($error) ?></div>
        <?php endif; ?>

        <form method="post" action="<?= e(app_url('auth/login.php')) ?>" class="auth-form" autocomplete="on">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">

            <div class="form-group">
                <label for="email">Alamat Email</label>
                <input id="email" 
                       name="email" 
                       type="email" 
                       maxlength="150" 
                       required 
                       value="<?= e($email) ?>" 
                       autocomplete="username" 
                       inputmode="email"
                       placeholder="nama@perusahaan.com"
                       autofocus>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <div class="password-input-wrapper">
                    <input id="password" 
                           name="password" 
                           type="password" 
                           required 
                           autocomplete="current-password" 
                           placeholder="Masukkan password Anda">
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

            <button type="submit" class="button auth-submit-btn">
                <span>Masuk ke Akun</span>
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <line x1="5" y1="12" x2="19" y2="12"/>
                    <polyline points="12 5 19 12 12 19"/>
                </svg>
            </button>
        </form>

        <div class="auth-footer">
            Belum memiliki akun? <a href="<?= e(app_url('auth/register.php')) ?>">Daftar sebagai karyawan</a>
        </div>
    </section>

    <div class="auth-brand-bottom">
        &copy; <?= date('Y') ?> Karyawan-WA Enterprise &middot; Sistem Berjalan Normal
    </div>
</main>

<script src="<?= e(app_url('assets/auth.js')) ?>"></script>
</body>
</html>
