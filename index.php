<?php
require_once __DIR__ . '/config/helpers.php';
$user = current_user();
$flashError = get_flash('error');
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Aplikasi Karyawan-WA</title>
    <link rel="stylesheet" href="<?= e(app_url('assets/style.css')) ?>">
</head>
<body>
<main class="container">
    <section class="panel">
        <p class="eyebrow">PROJECT PEMROGRAMAN WEB API</p>
        <h1>Aplikasi Karyawan-WA</h1>
        <p class="muted">Register, autentikasi, pengelolaan karyawan, absensi, dan integrasi WhatsApp Gateway.</p>

        <?php if ($flashError): ?><div class="alert error"><?= e($flashError) ?></div><?php endif; ?>

        <?php if ($user): ?>
            <div class="welcome">
                <h2>Selamat datang, <?= e($user['nama'] ?? 'Pengguna') ?>.</h2>
                <p>Login aktif sebagai <strong><?= e(strtoupper($user['role'] ?? 'karyawan')) ?></strong>.</p>
                
                <div style="margin:20px 0;display:flex;gap:12px;flex-wrap:wrap;">
                    <?php if (($user['role'] ?? '') === 'admin'): ?>
                        <a class="button" href="<?= e(app_url('admin/index.php')) ?>">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right:4px;">
                                <rect x="3" y="3" width="7" height="9" rx="1"/><rect x="14" y="3" width="7" height="5" rx="1"/><rect x="14" y="12" width="7" height="9" rx="1"/><rect x="3" y="16" width="7" height="5" rx="1"/>
                            </svg>
                            Buka Dashboard Admin
                        </a>
                        <a class="button secondary" href="<?= e(app_url('karyawan/index.php')) ?>">Kelola Karyawan</a>
                    <?php else: ?>
                        <a class="button" href="<?= e(app_url('absensi/index.php')) ?>">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right:4px;">
                                <circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>
                            </svg>
                            Buka Portal Absensi
                        </a>
                    <?php endif; ?>
                </div>

                <dl class="details" style="display:grid;grid-template-columns:100px 1fr;gap:8px 16px;padding:16px;border:1px solid var(--line);border-radius:12px;background:var(--slate-50);margin:16px 0;">
                    <dt style="color:var(--ink-muted);font-weight:600;">NIK</dt><dd style="margin:0;font-weight:700;"><?= e($user['nik'] ?? '-') ?></dd>
                    <dt style="color:var(--ink-muted);font-weight:600;">Email</dt><dd style="margin:0;"><?= e($user['email'] ?? '-') ?></dd>
                    <dt style="color:var(--ink-muted);font-weight:600;">Jabatan</dt><dd style="margin:0;"><?= e($user['jabatan'] ?? '-') ?></dd>
                    <dt style="color:var(--ink-muted);font-weight:600;">Role</dt><dd style="margin:0;"><span class="badge-role <?= ($user['role'] ?? '') === 'admin' ? 'admin' : 'karyawan' ?>"><?= e(strtoupper($user['role'] ?? '-')) ?></span></dd>
                </dl>

                <form method="post" action="<?= e(app_url('auth/logout.php')) ?>" style="margin-top:16px;">
                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                    <button type="submit" class="button secondary">Logout</button>
                </form>
            </div>
        <?php else: ?>
            <p>Mulai dengan membuat akun karyawan atau login ke akun yang sudah ada.</p>
            <div class="actions">
                <a class="button" href="<?= e(app_url('auth/login.php')) ?>">Login</a>
                <a class="button secondary" href="<?= e(app_url('auth/register.php')) ?>">Register karyawan</a>
            </div>
        <?php endif; ?>
    </section>
</main>
</body>
</html>
