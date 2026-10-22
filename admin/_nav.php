<?php
$adminNavTitle = isset($pageTitle) && is_string($pageTitle) ? $pageTitle : 'Dashboard Admin';
$adminActivePage = isset($activePage) && is_string($activePage) ? $activePage : 'dashboard';
$adminUser = current_user() ?? [];
?>
<header class="admin-header">
    <div class="admin-brand">
        <p class="eyebrow">KARYAWAN-WA / ADMIN</p>
        <h1><?= e($adminNavTitle) ?></h1>
        <p class="muted">Masuk sebagai <?= e($adminUser['nama'] ?? 'Admin') ?></p>
    </div>
    <nav class="admin-nav" aria-label="Navigasi admin">
        <a href="<?= e(app_url('admin/index.php')) ?>"<?= $adminActivePage === 'dashboard' ? ' aria-current="page"' : '' ?>>Dashboard</a>
        <a href="<?= e(app_url('karyawan/index.php')) ?>"<?= $adminActivePage === 'karyawan' ? ' aria-current="page"' : '' ?>>Karyawan</a>
        <a href="<?= e(app_url('admin/absensi.php')) ?>"<?= $adminActivePage === 'absensi' ? ' aria-current="page"' : '' ?>>Absensi</a>
        <a href="<?= e(app_url('admin/laporan.php')) ?>"<?= $adminActivePage === 'laporan' ? ' aria-current="page"' : '' ?>>Laporan</a>
        <form method="post" action="<?= e(app_url('auth/logout.php')) ?>" class="admin-logout-form">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <button class="button secondary" type="submit">Logout</button>
        </form>
    </nav>
</header>
