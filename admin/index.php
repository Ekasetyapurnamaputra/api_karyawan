<?php
require_once __DIR__ . '/../config/helpers.php';
require_once __DIR__ . '/../config/database.php';
require_role('admin');

date_default_timezone_set('Asia/Jakarta');
$timezone = new DateTimeZone('Asia/Jakarta');
$today = (new DateTimeImmutable('now', $timezone))->format('Y-m-d');

// Filter tanggal dashboard (default: hari ini).
$selectedDate = isset($_GET['tanggal']) && is_string($_GET['tanggal'])
    ? trim($_GET['tanggal'])
    : $today;
$dateValid = is_valid_date_string($selectedDate);
if (!$dateValid) {
    $selectedDate = $today;
}

$pageTitle = 'Dashboard Admin';
$activePage = 'dashboard';
$extraCss = ['assets/admin.css'];

$pageError = null;
$counts = [
    'total_karyawan' => 0,
    'sudah_absen' => 0,
    'belum_absen' => 0,
    'sudah_pulang' => 0,
    'total_catatan' => 0,
];
$statusBreakdown = [
    'Hadir' => 0,
    'Terlambat' => 0,
    'Izin' => 0,
    'Sakit' => 0,
];
$recentAttendance = [];
$trend = [];

try {
    $pdo = getPDO();

    // Total karyawan aktif (role karyawan, bukan akun admin).
    $stmt = $pdo->query("SELECT COUNT(*) FROM karyawan WHERE role = 'karyawan'");
    $counts['total_karyawan'] = (int) $stmt->fetchColumn();

    // Rekap status absensi pada tanggal terpilih.
    $stmt = $pdo->prepare(
        "SELECT a.status, COUNT(*) AS total
         FROM absensi a
         INNER JOIN karyawan k ON k.id = a.id_karyawan
         WHERE a.tanggal = :tanggal AND k.role = 'karyawan'
         GROUP BY a.status"
    );
    $stmt->execute([':tanggal' => $selectedDate]);
    $totalCatatan = 0;
    foreach ($stmt->fetchAll() as $row) {
        $total = (int) $row['total'];
        $totalCatatan += $total;
        if (array_key_exists($row['status'], $statusBreakdown)) {
            $statusBreakdown[$row['status']] = $total;
        }
    }
    $counts['total_catatan'] = $totalCatatan;

    // Karyawan unik yang sudah tercatat pada tanggal terpilih.
    $stmt = $pdo->prepare(
        "SELECT COUNT(DISTINCT a.id_karyawan)
         FROM absensi a
         INNER JOIN karyawan k ON k.id = a.id_karyawan
         WHERE a.tanggal = :tanggal AND k.role = 'karyawan'"
    );
    $stmt->execute([':tanggal' => $selectedDate]);
    $counts['sudah_absen'] = (int) $stmt->fetchColumn();
    $counts['belum_absen'] = max(0, $counts['total_karyawan'] - $counts['sudah_absen']);

    // Karyawan yang sudah tercatat jam pulang (absensi lengkap).
    $stmt = $pdo->prepare(
        "SELECT COUNT(DISTINCT a.id_karyawan)
         FROM absensi a
         INNER JOIN karyawan k ON k.id = a.id_karyawan
         WHERE a.tanggal = :tanggal
           AND a.jam_pulang IS NOT NULL
           AND k.role = 'karyawan'"
    );
    $stmt->execute([':tanggal' => $selectedDate]);
    $counts['sudah_pulang'] = (int) $stmt->fetchColumn();

    // Catatan absensi terbaru pada tanggal terpilih.
    $stmt = $pdo->prepare(
        "SELECT k.nama, k.nik, a.tanggal, a.jam_masuk, a.jam_pulang, a.status
         FROM absensi a
         INNER JOIN karyawan k ON k.id = a.id_karyawan
         WHERE a.tanggal = :tanggal AND k.role = 'karyawan'
         ORDER BY a.jam_masuk DESC, k.nama ASC
         LIMIT 8"
    );
    $stmt->execute([':tanggal' => $selectedDate]);
    $recentAttendance = $stmt->fetchAll();

    // Tren 7 hari terakhir (termasuk tanggal terpilih).
    $trendStart = (new DateTimeImmutable($selectedDate, $timezone))->modify('-6 days');
    $stmt = $pdo->prepare(
        "SELECT a.tanggal, COUNT(DISTINCT a.id_karyawan) AS total
         FROM absensi a
         INNER JOIN karyawan k ON k.id = a.id_karyawan
         WHERE k.role = 'karyawan'
           AND a.tanggal >= :tanggal_mulai
           AND a.tanggal <= :tanggal_akhir
         GROUP BY a.tanggal"
    );
    $stmt->execute([
        ':tanggal_mulai' => $trendStart->format('Y-m-d'),
        ':tanggal_akhir' => $selectedDate,
    ]);
    $trendMap = [];
    foreach ($stmt->fetchAll() as $row) {
        $trendMap[$row['tanggal']] = (int) $row['total'];
    }
    for ($i = 0; $i < 7; $i++) {
        $day = $trendStart->modify('+' . $i . ' days');
        $key = $day->format('Y-m-d');
        $trend[] = [
            'tanggal' => $key,
            'label' => $day->format('D'),
            'tanggal_label' => $day->format('d/m'),
            'total' => $trendMap[$key] ?? 0,
        ];
    }
} catch (PDOException $e) {
    error_log('Dashboard admin gagal memuat data: ' . $e->getMessage());
    $pageError = 'Data dashboard belum dapat dimuat. Periksa koneksi database atau log PHP.';
    $trend = [];
}

$selectedDateLabel = (new DateTimeImmutable($selectedDate, $timezone))->format('d-m-Y');
$isToday = ($selectedDate === $today);

$presenceRate = $counts['total_karyawan'] > 0
    ? (int) round(($counts['sudah_absen'] / $counts['total_karyawan']) * 100)
    : 0;
$completionRate = $counts['sudah_absen'] > 0
    ? (int) round(($counts['sudah_pulang'] / $counts['sudah_absen']) * 100)
    : 0;
$absenceRate = $counts['total_karyawan'] > 0
    ? (int) round(($counts['belum_absen'] / $counts['total_karyawan']) * 100)
    : 0;

$trendMax = 0;
foreach ($trend as $trendRow) {
    $trendMax = max($trendMax, $trendRow['total']);
}

$quickDateLinks = [
    ['label' => 'Hari ini', 'tanggal' => $today],
    ['label' => 'Kemarin', 'tanggal' => (new DateTimeImmutable($today, $timezone))->modify('-1 day')->format('Y-m-d')],
    ['label' => '7 hari lalu', 'tanggal' => (new DateTimeImmutable($today, $timezone))->modify('-7 days')->format('Y-m-d')],
];

require_once __DIR__ . '/../layout/header.php';
?>


        <?php if ($pageError): ?>
            <div class="alert error"><?= e($pageError) ?></div>
        <?php else: ?>
            <div class="adm-hero">
                <div class="adm-hero-text">
                    <span class="adm-eyebrow">Ringkasan Kehadiran</span>
                    <h2>Dashboard Admin</h2>
                    <p class="muted">
                        Data kehadiran tanggal <strong><?= e($selectedDateLabel) ?></strong>
                        (<?= $isToday ? 'Hari ini' : 'Riwayat' ?>) &middot; Zona waktu WIB.
                    </p>
                </div>
                <div class="adm-hero-actions">
                    <a class="button secondary" href="<?= e(app_url('admin/absensi.php?tanggal=' . urlencode($selectedDate))) ?>">Catatan Absensi</a>
                    <a class="button" href="<?= e(app_url('admin/laporan.php')) ?>">Buka Laporan</a>
                </div>
            </div>

            <form class="adm-filter" method="get" action="<?= e(app_url('admin/index.php')) ?>">
                <div class="filter-field is-wide">
                    <label for="filter-tanggal">Tanggal Data</label>
                    <input type="date" id="filter-tanggal" name="tanggal" value="<?= e($selectedDate) ?>" max="<?= e($today) ?>">
                </div>
                <div class="filter-field">
                    <label for="filter-cek">&nbsp;</label>
                    <button class="button" type="submit" id="filter-cek">Terapkan Filter</button>
                </div>
            </form>

            <div class="adm-chips">
                <?php foreach ($quickDateLinks as $quickLink): ?>
                    <a class="adm-chip<?= $quickLink['tanggal'] === $selectedDate ? ' is-active' : '' ?>"
                       href="<?= e(app_url('admin/index.php?tanggal=' . urlencode($quickLink['tanggal']))) ?>">
                        <?= e($quickLink['label']) ?>
                    </a>
                <?php endforeach; ?>
                <?php if (!$isToday): ?>
                    <a class="adm-chip" href="<?= e(app_url('admin/index.php')) ?>">Kembali ke hari ini</a>
                <?php endif; ?>
            </div>

            <div class="stat-grid">
                <article class="stat-card">
                    <div class="stat-card-head">
                        <span class="stat-label">Jumlah Karyawan</span>
                        <span class="stat-icon">
                            <svg viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/></svg>
                        </span>
                    </div>
                    <strong><?= number_format($counts['total_karyawan'], 0, ',', '.') ?></strong>
                    <span class="stat-note">Akun terdaftar dengan role karyawan</span>
                </article>

                <article class="stat-card tone-green">
                    <div class="stat-card-head">
                        <span class="stat-label">Sudah Absen</span>
                        <span class="stat-icon">
                            <svg viewBox="0 0 24 24"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
                        </span>
                    </div>
                    <strong><?= number_format($counts['sudah_absen'], 0, ',', '.') ?></strong>
                    <span class="stat-note"><?= e(number_format($presenceRate, 0, ',', '.')) ?>% dari total karyawan</span>
                    <div class="stat-meter">
                        <div class="stat-meter-bar"><span style="width: <?= (int) $presenceRate ?>%"></span></div>
                    </div>
                </article>

                <article class="stat-card tone-red">
                    <div class="stat-card-head">
                        <span class="stat-label">Belum Absen</span>
                        <span class="stat-icon">
                            <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                        </span>
                    </div>
                    <strong><?= number_format($counts['belum_absen'], 0, ',', '.') ?></strong>
                    <span class="stat-note"><?= e(number_format($absenceRate, 0, ',', '.')) ?>% belum tercatat</span>
                    <div class="stat-meter">
                        <div class="stat-meter-bar"><span style="width: <?= (int) $absenceRate ?>%"></span></div>
                    </div>
                </article>

                <article class="stat-card tone-violet">
                    <div class="stat-card-head">
                        <span class="stat-label">Sudah Pulang</span>
                        <span class="stat-icon">
                            <svg viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                        </span>
                    </div>
                    <strong><?= number_format($counts['sudah_pulang'], 0, ',', '.') ?></strong>
                    <span class="stat-note"><?= e(number_format($completionRate, 0, ',', '.')) ?>% dari yang sudah absen</span>
                    <div class="stat-meter">
                        <div class="stat-meter-bar"><span style="width: <?= (int) $completionRate ?>%"></span></div>
                    </div>
                </article>
            </div>

            <div class="adm-split">
                <section class="adm-subpanel">
                    <h3>Rekap Status Absensi</h3>
                    <p class="muted">Distribusi status pada tanggal <?= e($selectedDateLabel) ?>.</p>
                    <?php if ($counts['total_catatan'] === 0): ?>
                        <div class="adm-empty">Belum ada catatan absensi pada tanggal ini.</div>
                    <?php else: ?>
                        <div class="adm-breakdown">
                            <?php foreach ($statusBreakdown as $statusName => $statusTotal): ?>
                                <?php
                                $statusPercent = $counts['total_catatan'] > 0 ? (int) round(($statusTotal / $counts['total_catatan']) * 100) : 0;
                                $statusColors = [
                                    'Hadir' => 'var(--adm-green)',
                                    'Terlambat' => 'var(--adm-amber)',
                                    'Izin' => 'var(--adm-violet)',
                                    'Sakit' => 'var(--adm-red)',
                                ];
                                $statusColor = $statusColors[$statusName] ?? 'var(--adm-blue)';
                                ?>
                                <div class="adm-breakdown-row">
                                    <div class="adm-breakdown-head">
                                        <span class="adm-breakdown-name">
                                            <span class="adm-breakdown-dot s-<?= e(str_replace(' ', '', $statusName)) ?>"></span>
                                            <?= e($statusName) ?>
                                        </span>
                                        <span class="adm-breakdown-count"><?= number_format($statusTotal, 0, ',', '.') ?> (<?= $statusPercent ?>%)</span>
                                    </div>
                                    <div class="adm-breakdown-bar">
                                        <span style="width: <?= $statusPercent ?>%; background: <?= $statusColor ?>;"></span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="adm-legend">
                            <span>Total catatan: <strong><?= number_format($counts['total_catatan'], 0, ',', '.') ?></strong></span>
                            <span>Total karyawan: <strong><?= number_format($counts['total_karyawan'], 0, ',', '.') ?></strong></span>
                        </div>
                    <?php endif; ?>
                </section>

                <section class="adm-subpanel adm-span-full">
                    <h3>Tren Kehadiran 7 Hari</h3>
                    <p class="muted">Jumlah karyawan tercatat per hari.</p>
                    <?php if (!$trend): ?>
                        <div class="adm-empty">Tren belum dapat dimuat.</div>
                    <?php else: ?>
                        <div class="adm-trend">
                            <?php foreach ($trend as $trendRow): ?>
                                <?php
                                $barPercent = $trendMax > 0 ? max(6, (int) round(($trendRow['total'] / $trendMax) * 100)) : 0;
                                $hasData = $trendRow['total'] > 0;
                                ?>
                                <div class="adm-trend-col" title="<?= e($trendRow['tanggal_label']) ?>: <?= (int) $trendRow['total'] ?> orang">
                                    <span class="adm-trend-value"><?= (int) $trendRow['total'] ?></span>
                                    <span class="adm-trend-bar<?= $hasData ? '' : ' is-empty' ?>" style="height: <?= $barPercent ?>%"></span>
                                    <span class="adm-trend-label"><?= e($trendRow['label']) ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="adm-legend">
                            <span><span class="adm-breakdown-dot"></span>Jumlah karyawan hadir per hari</span>
                            <span><span class="adm-breakdown-dot" style="background:#e2e8f0"></span>Belum ada data</span>
                        </div>
                    <?php endif; ?>
                </section>
            </div>

            <section class="adm-subpanel">
                <div class="adm-table-meta">
                    <p><strong>Absensi Terbaru</strong> &mdash; maksimal 8 catatan pada tanggal <?= e($selectedDateLabel) ?>.</p>
                    <a href="<?= e(app_url('admin/absensi.php?tanggal=' . urlencode($selectedDate))) ?>">Lihat semua absensi &rarr;</a>
                </div>
                <div class="adm-table-wrap">
                    <table class="adm-table">
                        <thead>
                            <tr>
                                <th class="adm-index">No.</th>
                                <th>Nama</th>
                                <th>NIK</th>
                                <th>Masuk</th>
                                <th>Pulang</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if (!$recentAttendance): ?>
                            <tr class="adm-empty-row">
                                <td colspan="6">Belum ada catatan absensi untuk tanggal ini.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($recentAttendance as $i => $row): ?>
                                <tr>
                                    <td class="adm-index adm-cell-index" data-label="No."><?= $i + 1 ?></td>
                                    <td class="adm-name adm-cell-name" data-label="Nama"><?= e($row['nama']) ?></td>
                                    <td class="adm-cell-nik" data-label="NIK"><span class="adm-nik"><?= e($row['nik']) ?></span></td>
                                    <td class="adm-time adm-cell-masuk" data-label="Jam Masuk"><?= e($row['jam_masuk'] ?: '—') ?></td>
                                    <td class="adm-time adm-cell-pulang<?= $row['jam_pulang'] ? '' : ' is-empty' ?>" data-label="Jam Pulang"><?= e($row['jam_pulang'] ?: '—') ?></td>
                                    <td class="adm-cell-status" data-label="Status">
                                        <span class="adm-status-pill" data-status="<?= e($row['status']) ?>"><?= e($row['status']) ?></span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>

            <div class="adm-quick-links">
                <a class="adm-quick-link" href="<?= e(app_url('karyawan/index.php')) ?>"><strong>Kelola karyawan</strong><span>Tambah, lihat, edit, atau hapus data karyawan.</span></a>
                <a class="adm-quick-link" href="<?= e(app_url('admin/absensi.php')) ?>"><strong>Data absensi</strong><span>Lihat catatan kehadiran berdasarkan tanggal.</span></a>
                <a class="adm-quick-link" href="<?= e(app_url('admin/laporan.php')) ?>"><strong>Laporan absensi</strong><span>Filter rentang tanggal dan unduh CSV untuk Excel.</span></a>
            </div>
        <?php endif; ?>

<?php
require_once __DIR__ . '/../layout/footer.php';
