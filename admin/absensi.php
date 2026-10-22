<?php
require_once __DIR__ . '/../config/helpers.php';
require_once __DIR__ . '/../config/database.php';
require_role('admin');

date_default_timezone_set('Asia/Jakarta');
$timezone = new DateTimeZone('Asia/Jakarta');
$today = (new DateTimeImmutable('now', $timezone))->format('Y-m-d');

// Filter tanggal (default: hari ini) dan filter status.
$date = isset($_GET['tanggal']) && is_string($_GET['tanggal']) ? trim($_GET['tanggal']) : $today;
$isDateValid = is_valid_date_string($date);
if (!$isDateValid) {
    $date = $today;
}

$statusOptions = ['Hadir', 'Terlambat', 'Izin', 'Sakit'];
$status = isset($_GET['status']) && is_string($_GET['status']) ? trim($_GET['status']) : '';
if ($status !== '' && !in_array($status, $statusOptions, true)) {
    $status = '';
}

$pageTitle = 'Data Absensi';
$activePage = 'absensi';
$extraCss = ['assets/admin.css'];

$rows = [];
$summary = ['total' => 0, 'sudah_pulang' => 0, 'per_status' => array_fill_keys($statusOptions, 0)];
$pageError = null;

try {
    $pdo = getPDO();

    $stmt = $pdo->prepare(
        "SELECT k.nama, k.nik, a.tanggal, a.jam_masuk, a.jam_pulang, a.status
         FROM absensi a
         INNER JOIN karyawan k ON k.id = a.id_karyawan
         WHERE a.tanggal = :tanggal AND k.role = 'karyawan'
         ORDER BY k.nama ASC"
    );
    $stmt->execute([':tanggal' => $date]);
    $rows = $stmt->fetchAll();

    $summary['total'] = count($rows);
    foreach ($rows as $row) {
        if (!empty($row['jam_pulang'])) {
            $summary['sudah_pulang']++;
        }
        $rowStatus = (string) $row['status'];
        if (array_key_exists($rowStatus, $summary['per_status'])) {
            $summary['per_status'][$rowStatus]++;
        }
    }
} catch (PDOException $e) {
    error_log('Daftar absensi admin gagal: ' . $e->getMessage());
    $pageError = 'Data absensi belum dapat dimuat. Periksa koneksi database atau log PHP.';
    $rows = [];
}

$dateLabel = (new DateTimeImmutable($date, $timezone))->format('d-m-Y');
$isToday = ($date === $today);
$filteredRows = $status === ''
    ? $rows
    : array_values(array_filter($rows, static function ($row) use ($status) {
        return (string) $row['status'] === $status;
    }));

// Ekspor CSV untuk tanggal terpilih.
$format = isset($_GET['format']) && is_string($_GET['format']) ? $_GET['format'] : '';
if ($format === 'csv') {
    if ($pageError !== null) {
        http_response_code(500);
        header('Content-Type: text/plain; charset=UTF-8');
        echo $pageError;
        exit;
    }

    $filename = 'absensi-' . $date . ($status !== '' ? '-' . strtolower(str_replace(' ', '-', $status)) : '') . '.csv';
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('X-Content-Type-Options: nosniff');
    echo "\xEF\xBB\xBF"; // BOM agar karakter UTF-8 lebih mudah dibaca Excel.
    $output = fopen('php://output', 'w');
    fputcsv($output, ['No', 'Nama', 'NIK', 'Tanggal', 'Masuk', 'Pulang', 'Status']);
    foreach ($filteredRows as $index => $row) {
        fputcsv($output, [
            $index + 1,
            csv_safe_value($row['nama']),
            csv_safe_value($row['nik']),
            csv_safe_value($row['tanggal']),
            csv_safe_value($row['jam_masuk']),
            csv_safe_value($row['jam_pulang']),
            csv_safe_value($row['status']),
        ]);
    }
    fclose($output);
    exit;
}

$csvQuery = ['tanggal' => $date];
if ($status !== '') {
    $csvQuery['status'] = $status;
}
$csvUrl = app_url('admin/absensi.php') . '?' . http_build_query($csvQuery + ['format' => 'csv']);

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
                    <span class="adm-eyebrow">Kehadiran Harian</span>
                    <h2>Catatan Absensi</h2>
                    <p class="muted">
                        Data kehadiran tanggal <strong><?= e($dateLabel) ?></strong>
                        (<?= $isToday ? 'Hari ini' : 'Riwayat' ?>) &middot; <?= number_format($summary['total'], 0, ',', '.') ?> catatan ditemukan.
                    </p>
                </div>
                <div class="adm-hero-actions">
                    <a class="button secondary" href="<?= e(app_url('admin/laporan.php')) ?>">Buka Laporan</a>
                    <a class="button" href="<?= e($csvUrl) ?>">Unduh CSV</a>
                </div>
            </div>

            <form class="adm-filter" method="get" action="<?= e(app_url('admin/absensi.php')) ?>">
                <div class="filter-field is-wide">
                    <label for="tanggal">Tanggal Absensi</label>
                    <input id="tanggal" type="date" name="tanggal" value="<?= e($date) ?>" max="<?= e($today) ?>" required>
                </div>
                <div class="filter-field">
                    <label for="status">Status</label>
                    <select id="status" name="status">
                        <option value="">Semua status</option>
                        <?php foreach ($statusOptions as $statusOption): ?>
                            <option value="<?= e($statusOption) ?>"<?= $status === $statusOption ? ' selected' : '' ?>>
                                <?= e($statusOption) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="filter-field">
                    <label for="filter-terapkan">&nbsp;</label>
                    <button class="button" type="submit" id="filter-terapkan">Tampilkan</button>
                </div>
            </form>

            <div class="adm-chips">
                <?php foreach ($quickDateLinks as $quickLink): ?>
                    <?php
                    $quickQuery = ['tanggal' => $quickLink['tanggal']];
                    if ($status !== '') {
                        $quickQuery['status'] = $status;
                    }
                    ?>
                    <a class="adm-chip<?= $quickLink['tanggal'] === $date ? ' is-active' : '' ?>"
                       href="<?= e(app_url('admin/absensi.php') . '?' . http_build_query($quickQuery)) ?>">
                        <?= e($quickLink['label']) ?>
                    </a>
                <?php endforeach; ?>
                <?php if (!$isToday): ?>
                    <a class="adm-chip" href="<?= e(app_url('admin/absensi.php')) ?>">Kembali ke hari ini</a>
                <?php endif; ?>
            </div>

            <div class="stat-grid">
                <article class="stat-card">
                    <div class="stat-card-head">
                        <span class="stat-label">Total Catatan</span>
                        <span class="stat-icon">
                            <svg viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/><path d="m9 16 2 2 4-4"/></svg>
                        </span>
                    </div>
                    <strong><?= number_format($summary['total'], 0, ',', '.') ?></strong>
                    <span class="stat-note">Seluruh catatan pada tanggal terpilih</span>
                </article>

                <article class="stat-card tone-violet">
                    <div class="stat-card-head">
                        <span class="stat-label">Sudah Pulang</span>
                        <span class="stat-icon">
                            <svg viewBox="0 0 24 24"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
                        </span>
                    </div>
                    <strong><?= number_format($summary['sudah_pulang'], 0, ',', '.') ?></strong>
                    <span class="stat-note">Jam pulang sudah tercatat</span>
                </article>

                <?php foreach (array_slice($statusOptions, 0, 2) as $statusOption): ?>
                    <?php
                    $statusColors = [
                        'Hadir' => ['tone-green', 'var(--adm-green)'],
                        'Terlambat' => ['tone-amber', 'var(--adm-amber)'],
                        'Izin' => ['tone-violet', 'var(--adm-violet)'],
                        'Sakit' => ['tone-red', 'var(--adm-red)'],
                    ];
                    [$statusTone] = $statusColors[$statusOption];
                    ?>
                    <article class="stat-card <?= $statusTone ?>">
                        <div class="stat-card-head">
                            <span class="stat-label"><?= e($statusOption) ?></span>
                            <span class="stat-icon">
                                <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                            </span>
                        </div>
                        <strong><?= number_format($summary['per_status'][$statusOption], 0, ',', '.') ?></strong>
                        <span class="stat-note">Karyawan berstatus <?= e(strtolower($statusOption)) ?></span>
                    </article>
                <?php endforeach; ?>
            </div>
            <section class="adm-subpanel">
                <div class="adm-table-meta">
                    <p>
                        <strong>Daftar Absensi</strong> &mdash; tanggal <?= e($dateLabel) ?>
                        <?php if ($status !== ''): ?>
                            &middot; status <strong><?= e($status) ?></strong>
                        <?php endif; ?>
                        &middot; <?= number_format(count($filteredRows), 0, ',', '.') ?> baris ditampilkan.
                    </p>
                    <a href="<?= e($csvUrl) ?>">Unduh CSV &rarr;</a>
                </div>

                <div class="adm-table-wrap">
                    <table class="adm-table">
                        <thead>
                            <tr>
                                <th class="adm-index">No.</th>
                                <th>Nama</th>
                                <th>NIK</th>
                                <th>Tanggal</th>
                                <th>Masuk</th>
                                <th>Pulang</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if (!$filteredRows): ?>
                            <tr class="adm-empty-row">
                                <td colspan="7">Belum ada catatan absensi untuk filter yang dipilih.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($filteredRows as $i => $row): ?>
                                <tr>
                                    <td class="adm-index adm-cell-index" data-label="No."><?= $i + 1 ?></td>
                                    <td class="adm-name adm-cell-name" data-label="Nama"><?= e($row['nama']) ?></td>
                                    <td class="adm-cell-nik" data-label="NIK"><span class="adm-nik"><?= e($row['nik']) ?></span></td>
                                    <td class="adm-cell-tanggal" data-label="Tanggal"><?= e((new DateTimeImmutable($row['tanggal'], $timezone))->format('d-m-Y')) ?></td>
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

                <p class="adm-note">
                    Halaman ini menampilkan catatan yang tersimpan di database. Karyawan yang tidak memiliki catatan
                    pada tanggal tersebut tidak ditampilkan sebagai baris absensi.
                </p>
            </section>
        <?php endif; ?>

<?php
require_once __DIR__ . '/../layout/footer.php';
