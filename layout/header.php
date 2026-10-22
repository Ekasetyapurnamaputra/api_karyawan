<?php
require_once __DIR__ . '/../config/helpers.php';

$layoutUser = current_user() ?? [];
$layoutRole = $layoutUser['role'] ?? 'karyawan';
$layoutIsAdmin = ($layoutRole === 'admin');

$layoutTitle = isset($pageTitle) && is_string($pageTitle) ? $pageTitle : 'Karyawan-WA';
$layoutActivePage = isset($activePage) && is_string($activePage) ? $activePage : 'dashboard';
$layoutSection = isset($pageSection) && is_string($pageSection) 
    ? $pageSection 
    : ($layoutIsAdmin ? 'ADMINISTRATOR' : 'PORTAL KARYAWAN');

// Generate initials for avatar (max 2 characters)
$layoutName = trim($layoutUser['nama'] ?? ($layoutIsAdmin ? 'Admin' : 'Karyawan'));
$layoutInitials = '';
$nameParts = preg_split('/\s+/', $layoutName);
if (!empty($nameParts[0])) {
    $layoutInitials .= mb_strtoupper(mb_substr($nameParts[0], 0, 1, 'UTF-8'), 'UTF-8');
}
if (count($nameParts) > 1 && !empty($nameParts[count($nameParts) - 1])) {
    $layoutInitials .= mb_strtoupper(mb_substr($nameParts[count($nameParts) - 1], 0, 1, 'UTF-8'), 'UTF-8');
}
if ($layoutInitials === '') {
    $layoutInitials = $layoutIsAdmin ? 'AD' : 'KR';
}

// Current date formatted in WIB
try {
    $layoutDateWib = (new DateTimeImmutable('now', new DateTimeZone('Asia/Jakarta')))->format('d M Y');
} catch (Throwable $e) {
    $layoutDateWib = date('d M Y');
}

// Flash messages
$layoutFlashSuccess = get_flash('success');
$layoutFlashError = get_flash('error');
$layoutFlashWarning = get_flash('warning');

// Navigation item definitions based on role
$layoutNavItems = [];
if ($layoutIsAdmin) {
    $layoutNavItems = [
        [
            'key' => 'dashboard',
            'label' => 'Dashboard',
            'url' => app_url('admin/index.php'),
            'icon' => '<svg viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="9" rx="1"/><rect x="14" y="3" width="7" height="5" rx="1"/><rect x="14" y="12" width="7" height="9" rx="1"/><rect x="3" y="16" width="7" height="5" rx="1"/></svg>',
        ],
        [
            'key' => 'karyawan',
            'label' => 'Data Karyawan',
            'url' => app_url('karyawan/index.php'),
            'icon' => '<svg viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>',
        ],
        [
            'key' => 'absensi',
            'label' => 'Catatan Absensi',
            'url' => app_url('admin/absensi.php'),
            'icon' => '<svg viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/><path d="m9 16 2 2 4-4"/></svg>',
        ],
        [
            'key' => 'laporan',
            'label' => 'Laporan & Rekap',
            'url' => app_url('admin/laporan.php'),
            'icon' => '<svg viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>',
        ],
    ];
} else {
    $layoutNavItems = [
        [
            'key' => 'absensi',
            'label' => 'Portal Absensi',
            'url' => app_url('absensi/index.php'),
            'icon' => '<svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>',
        ],
        [
            'key' => 'home',
            'label' => 'Beranda & Info',
            'url' => app_url('index.php'),
            'icon' => '<svg viewBox="0 0 24 24"><path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>',
        ],
    ];
}
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($layoutTitle) ?> — Karyawan-WA</title>
    <link rel="stylesheet" href="<?= e(app_url('assets/style.css')) ?>">
    <link rel="stylesheet" href="<?= e(app_url('assets/layout.css')) ?>">
    <?php if (isset($extraCss) && is_array($extraCss)): ?>
        <?php foreach ($extraCss as $cssPath): ?>
            <link rel="stylesheet" href="<?= e(app_url($cssPath)) ?>">
        <?php endforeach; ?>
    <?php endif; ?>
</head>
<body>

<div class="app-layout">

    <!-- DESKTOP SIDEBAR -->
    <aside class="app-sidebar" aria-label="Sidebar Navigasi Utama">
        <div>
            <div class="app-sidebar-header">
                <div class="app-brand-icon" aria-hidden="true">K</div>
                <div class="app-brand-text">
                    <span class="app-brand-title">Karyawan-WA</span>
                    <span class="app-brand-subtitle">Enterprise Gateway</span>
                </div>
            </div>

            <nav class="app-nav" aria-label="Menu Utama Desktop">
                <span class="app-nav-heading">Menu Utama</span>
                <?php foreach ($layoutNavItems as $item): ?>
                    <?php $isActive = ($layoutActivePage === $item['key']); ?>
                    <a class="app-nav-link<?= $isActive ? ' is-active' : '' ?>" 
                       href="<?= e($item['url']) ?>" 
                       <?= $isActive ? 'aria-current="page"' : '' ?>>
                        <?= $item['icon'] ?>
                        <span><?= e($item['label']) ?></span>
                    </a>
                <?php endforeach; ?>
            </nav>
        </div>

        <div class="app-sidebar-footer">
            <div class="app-user-card">
                <div class="app-user-avatar" aria-hidden="true"><?= e($layoutInitials) ?></div>
                <div class="app-user-info">
                    <span class="app-user-name"><?= e($layoutName) ?></span>
                    <span class="app-user-role">
                        <span class="badge-role <?= $layoutIsAdmin ? 'admin' : 'karyawan' ?>"><?= e(strtoupper($layoutRole)) ?></span>
                    </span>
                </div>
            </div>

            <form method="post" action="<?= e(app_url('auth/logout.php')) ?>" class="app-sidebar-logout-form">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <button type="submit" class="app-sidebar-logout-btn">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                        <polyline points="16 17 21 12 16 7"/>
                        <line x1="21" y1="12" x2="9" y2="12"/>
                    </svg>
                    <span>Logout</span>
                </button>
            </form>
        </div>
    </aside>

    <!-- COMPACT MOBILE HEADER (< 1024px) -->
    <header class="app-mobile-header" aria-label="Header Navigasi Mobile">
        <button id="appMobileMenuBtn" class="app-mobile-menu-btn" type="button" aria-label="Buka Menu Navigasi" aria-expanded="false" aria-controls="appDrawer">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round">
                <line x1="3" y1="12" x2="21" y2="12"/>
                <line x1="3" y1="6" x2="21" y2="6"/>
                <line x1="3" y1="18" x2="21" y2="18"/>
            </svg>
        </button>

        <a class="app-mobile-brand" href="<?= e(app_url($layoutIsAdmin ? 'admin/index.php' : 'absensi/index.php')) ?>">
            <div class="app-brand-icon" style="width:30px;height:30px;font-size:0.9rem;" aria-hidden="true">K</div>
            <span class="app-brand-title">Karyawan-WA</span>
        </a>

        <div class="app-user-avatar" style="width:32px;height:32px;font-size:0.75rem;" aria-hidden="true">
            <?= e($layoutInitials) ?>
        </div>
    </header>

    <!-- MOBILE OFF-CANVAS DRAWER -->
    <div id="appDrawerBackdrop" class="app-drawer-backdrop" aria-hidden="true"></div>

    <aside id="appDrawer" class="app-drawer" aria-label="Drawer Navigasi Mobile">
        <div>
            <div class="app-drawer-header">
                <div style="display:flex;align-items:center;gap:10px;">
                    <div class="app-brand-icon" style="width:32px;height:32px;font-size:0.95rem;" aria-hidden="true">K</div>
                    <span class="app-brand-title">Karyawan-WA</span>
                </div>
                <button id="appDrawerCloseBtn" class="app-drawer-close-btn" type="button" aria-label="Tutup Menu">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="18" y1="6" x2="6" y2="18"/>
                        <line x1="6" y1="6" x2="18" y2="18"/>
                    </svg>
                </button>
            </div>

            <div class="app-drawer-user">
                <div class="app-user-avatar"><?= e($layoutInitials) ?></div>
                <div class="app-user-info">
                    <span class="app-user-name"><?= e($layoutName) ?></span>
                    <span class="app-user-role">
                        <span class="badge-role <?= $layoutIsAdmin ? 'admin' : 'karyawan' ?>"><?= e(strtoupper($layoutRole)) ?></span>
                    </span>
                </div>
            </div>

            <nav class="app-drawer-nav" aria-label="Menu Mobile">
                <span class="app-nav-heading">Menu Navigasi</span>
                <?php foreach ($layoutNavItems as $item): ?>
                    <?php $isActive = ($layoutActivePage === $item['key']); ?>
                    <a class="app-nav-link<?= $isActive ? ' is-active' : '' ?>" 
                       href="<?= e($item['url']) ?>" 
                       <?= $isActive ? 'aria-current="page"' : '' ?>>
                        <?= $item['icon'] ?>
                        <span><?= e($item['label']) ?></span>
                    </a>
                <?php endforeach; ?>
            </nav>
        </div>

        <div class="app-drawer-footer">
            <form method="post" action="<?= e(app_url('auth/logout.php')) ?>" class="app-sidebar-logout-form">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <button type="submit" class="app-sidebar-logout-btn">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                        <polyline points="16 17 21 12 16 7"/>
                        <line x1="21" y1="12" x2="9" y2="12"/>
                    </svg>
                    <span>Logout</span>
                </button>
            </form>
        </div>
    </aside>

    <!-- FIXED MOBILE BOTTOM NAVIGATION (< 768px) -->
    <nav class="app-bottom-nav" aria-label="Navigasi Cepat Bawah">
        <?php foreach ($layoutNavItems as $item): ?>
            <?php $isActive = ($layoutActivePage === $item['key']); ?>
            <a class="app-bottom-nav-item<?= $isActive ? ' is-active' : '' ?>" 
               href="<?= e($item['url']) ?>" 
               <?= $isActive ? 'aria-current="page"' : '' ?>>
                <?= $item['icon'] ?>
                <span><?= e($item['label']) ?></span>
            </a>
        <?php endforeach; ?>
    </nav>

    <!-- MAIN CONTENT WRAPPER -->
    <main class="app-main">
        <!-- TOPBAR (DESKTOP) -->
        <header class="app-topbar" aria-label="Topbar Halaman">
            <div class="app-topbar-breadcrumb">
                <span class="app-topbar-context"><?= e($layoutSection) ?></span>
                <h1 class="app-topbar-title"><?= e($layoutTitle) ?></h1>
            </div>

            <div class="app-topbar-actions">
                <div class="app-date-pill">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                        <line x1="16" y1="2" x2="16" y2="6"/>
                        <line x1="8" y1="2" x2="8" y2="6"/>
                        <line x1="3" y1="10" x2="21" y2="10"/>
                    </svg>
                    <span><?= e($layoutDateWib) ?></span>
                </div>
            </div>
        </header>

        <!-- CONTENT BODY WITH AUTOMATIC FLASH RENDERING -->
        <div class="app-content-body">
            <?php if ($layoutFlashSuccess): ?>
                <div class="alert success" role="alert"><?= e($layoutFlashSuccess) ?></div>
            <?php endif; ?>
            <?php if ($layoutFlashWarning): ?>
                <div class="alert warning" role="alert"><?= e($layoutFlashWarning) ?></div>
            <?php endif; ?>
            <?php if ($layoutFlashError): ?>
                <div class="alert error" role="alert"><?= e($layoutFlashError) ?></div>
            <?php endif; ?>

