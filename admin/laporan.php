<?php
require_once __DIR__ . '/../config/helpers.php';
require_once __DIR__ . '/../config/database.php';
require_role('admin');

date_default_timezone_set('Asia/Jakarta');
$timezone = new DateTimeZone('Asia/Jakarta');
$today = (new DateTimeImmutable('now', $timezone))->format('Y-m-d');
$firstOfMonth = (new DateTimeImmutable($today, $timezone))->modify('first day of this month')->format('Y-m-d');

// Filter rentang tanggal.
$dateFrom = isset($_GET['dari']) && is_string($_GET['dari']) ? trim($_GET['dari']) : $firstOfMonth;
$dateTo = isset($_GET['sampai']) && is_string($_GET['sampai']) ? trim($_GET['sampai']) : $today;
$dateError = null;
if (!is_valid_date_string($dateFrom) || !is_valid_date_string($dateTo)) {
    $dateError = 'Tanggal tidak valid. Gunakan rentang tanggal yang benar.';
} elseif ($dateFrom > $dateTo) {
    $dateError = 'Tanggal dari tidak boleh lebih besar daripada tanggal sampai.';
}

// Filter status.
$statusOptions = ['Hadir', 'Terlambat', 'Izin', 'Sakit'];
$status = isset($_GET['status']) && is_string($_GET['status']) ? trim($_GET['status']) : '';
if ($status !== '' && !in_array($status, $statusOptions, true)) {
    $status = '';
}

$rows = [];
$summary = [
    'total' => 0,
    'karyawan_terlibat' => 0,
    'per_status' => array_fill_keys($statusOptions, 0),
    'per_hari' => [],
];
$pageError = null;

if ($dateError === null) {
    try {
        $pdo = getPDO();
        $stmt = $pdo->prepare(
            "SELECT k.nama, k.nik, a.tanggal, a.jam_masuk, a.jam_pulang, a.status
             FROM absensi a
             INNER JOIN karyawan k ON k.id = a.id_karyawan
             WHERE k.role = 'karyawan'
               AND a.tanggal >= :tanggal_dari
               AND a.tanggal <= :tanggal_sampai
             ORDER BY a.tanggal DESC, k.nama ASC"
        );
        $stmt->execute([
            ':tanggal_dari' => $dateFrom,
            ':tanggal_sampai' => $dateTo,
        ]);
        $rows = $stmt->fetchAll();

        $summary['total'] = count($rows);
        $employees = [];
        $days = [];
        foreach ($rows as $row) {
            $employees[(string) $row['nik']] = true;
            $rowStatus = (string) $row['status'];
            if (array_key_exists($rowStatus, $summary['per_status'])) {
                $summary['per_status'][$rowStatus]++;
            }
            $rowDate = (string) $row['tanggal'];
            if (!isset($days[$rowDate])) {
                $days[$rowDate] = ['total' => 0, 'hadir' => 0];
            }
            $days[$rowDate]['total']++;
            if ($rowStatus === 'Hadir' || $rowStatus === 'Terlambat') {
                $days[$rowDate]['hadir']++;
            }
        }
        $summary['karyawan_terlibat'] = count($employees);
        // Urutkan kronologis agar grafik per hari membaca dari tanggal terlama ke terbaru.
        ksort($days);
        $summary['per_hari'] = $days;
    } catch (PDOException $e) {
        error_log('Laporan absensi gagal dimuat: ' . $e->getMessage());
        $pageError = 'Laporan belum dapat dimuat. Periksa koneksi database atau log PHP.';
        $rows = [];
    }
}

$filteredRows = $status === ''
    ? $rows
    : array_values(array_filter($rows, static function ($row) use ($status) {
        return (string) $row['status'] === $status;
    }));

$format = isset($_GET['format']) && is_string($_GET['format']) ? $_GET['format'] : '';
if ($format === 'csv') {
    if ($dateError !== null || $pageError !== null) {
        http_response_code(400);
        header('Content-Type: text/plain; charset=UTF-8');
        echo $dateError ?? $pageError;
        exit;
    }
    $filename = 'laporan-absensi-' . $dateFrom . '-sampai-' . $dateTo
        . ($status !== '' ? '-' . strtolower(str_replace(' ', '-', $status)) : '') . '.csv';
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

$pageTitle = 'Laporan Absensi';
$activePage = 'laporan';
$extraCss = ['assets/admin.css'];

$csvQuery = [
    'dari' => $dateFrom,
    'sampai' => $dateTo,
];
if ($status !== '') {
    $csvQuery['status'] = $status;
}
$csvUrl = app_url('admin/laporan.php') . '?' . http_build_query($csvQuery + ['format' => 'csv']);

$rangeDays = 0;
if ($dateError === null) {
    $rangeDays = (int) ((new DateTimeImmutable($dateTo, $timezone))
        ->diff(new DateTimeImmutable($dateFrom, $timezone))->days) + 1;
}
$avgPerDay = $rangeDays > 0 ? (int) round($summary['total'] / $rangeDays) : 0;
$attendanceRate = $summary['karyawan_terlibat'] > 0
    ? (int) round(($summary['per_status']['Hadir'] / $summary['karyawan_terlibat']) * 100)
    : 0;

require_once __DIR__ . '/../layout/header.php';
?>

        <?php if ($dateError): ?>
            <div class="alert error"><?= e($dateError) ?></div>
        <?php elseif ($pageError): ?>
            <div class="alert error"><?= e($pageError) ?></div>
        <?php else: ?>
            <div class="adm-hero">
                <div class="adm-hero-text">
                    <span class="adm-eyebrow">Rekap Kehadiran</span>
                    <h2>Laporan Absensi</h2>
                    <p class="muted">
                        Rentang <strong><?= e((new DateTimeImmutable($dateFrom, $timezone))->format('d-m-Y')) ?></strong>
                        sampai <strong><?= e((new DateTimeImmutable($dateTo, $timezone))->format('d-m-Y')) ?></strong>
                        &middot; <?= number_format($rangeDays, 0, ',', '.') ?> hari
                    </p>
                </div>
                <div class="adm-hero-actions">
                    <a class="button secondary" href="<?= e(app_url('admin/absensi.php?tanggal=' . urlencode($dateTo))) ?>">Catatan Harian</a>
                    <a class="button" href="<?= e($csvUrl) ?>">Unduh CSV</a>
                </div>
            </div>

            <form class="adm-filter" method="get" action="<?= e(app_url('admin/laporan.php')) ?>">
                <div class="filter-field">
                    <label for="dari">Dari Tanggal</label>
                    <input id="dari" type="date" name="dari" value="<?= e($dateFrom) ?>" max="<?= e($today) ?>" required>
                </div>
                <div class="filter-field">
                    <label for="sampai">Sampai Tanggal</label>
                    <input id="sampai" type="date" name="sampai" value="<?= e($dateTo) ?>" max="<?= e($today) ?>" required>
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
                    <label for="filter-laporan">&nbsp;</label>
                    <button class="button" type="submit" id="filter-laporan">Tampilkan Laporan</button>
                </div>
            </form>

            <div class="adm-report-actions">
                <div class="adm-report-summary">
                    <div>
                        <span>Total Catatan</span>
                        <strong><?= number_format($summary['total'], 0, ',', '.') ?></strong>
                    </div>
                    <div>
                        <span>Karyawan Terlibat</span>
                        <strong><?= number_format($summary['karyawan_terlibat'], 0, ',', '.') ?></strong>
                    </div>
                    <div>
                        <span>Rata-rata / Hari</span>
                        <strong><?= number_format($avgPerDay, 0, ',', '.') ?></strong>
                    </div>
                    <div>
                        <span>Tingkat Hadir</span>
                        <strong><?= number_format($attendanceRate, 0, ',', '.') ?>%</strong>
                    </div>
                    <?php if ($status !== ''): ?>
                        <div>
                            <span>Filter Status</span>
                            <strong><?= e($status) ?></strong>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="adm-split">
                <section class="adm-subpanel">
                    <h3>Rekap per Status</h3>
                    <p class="muted">Distribusi status pada rentang terpilih.</p>
                    <?php if ($summary['total'] === 0): ?>
                        <div class="adm-empty">Tidak ada catatan pada rentang ini.</div>
                    <?php else: ?>
                        <div class="adm-breakdown">
                            <?php
                            $statusColors = [
                                'Hadir' => 'var(--adm-green)',
                                'Terlambat' => 'var(--adm-amber)',
                                'Izin' => 'var(--adm-violet)',
                                'Sakit' => 'var(--adm-red)',
                            ];
                            ?>
                            <?php foreach ($summary['per_status'] as $statusName => $statusTotal): ?>
                                <?php
                                $statusPercent = $summary['total'] > 0 ? (int) round(($statusTotal / $summary['total']) * 100) : 0;
                                $statusColor = $statusColors[$statusName] ?? 'var(--adm-blue)';
                                ?>
                                <div class="adm-breakdown-row">
                                    <div class="adm-breakdown-head">
                                        <span class="adm-breakdown-name">
                                            <span class="adm-breakdown-dot" style="background: <?= $statusColor ?>;"></span>
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
                    <?php endif; ?>
                </section>

                <section class="adm-subpanel adm-span-full">
                    <h3>Rekap per Hari</h3>
                    <p class="muted">Jumlah catatan pada setiap tanggal.</p>
                    <?php if (!$summary['per_hari']): ?>
                        <div class="adm-empty">Belum ada data harian.</div>
                    <?php else: ?>
                        <?php $dayMax = max(array_map(static fn($d) => $d['total'], $summary['per_hari'])); ?>
                        <div class="adm-trend-scroll">
                            <div class="adm-trend adm-trend-daily">
                                <?php foreach ($summary['per_hari'] as $dayDate => $dayData): ?>
                                    <?php
                                    $dayPercent = $dayMax > 0 ? max(6, (int) round(($dayData['total'] / $dayMax) * 100)) : 0;
                                    $dayLabel = (new DateTimeImmutable($dayDate, $timezone))->format('d/m');
                                    ?>
                                    <div class="adm-trend-col" title="<?= e($dayLabel) ?>: <?= (int) $dayData['total'] ?> catatan">
                                        <span class="adm-trend-value"><?= (int) $dayData['total'] ?></span>
                                        <span class="adm-trend-bar" style="height: <?= $dayPercent ?>%"></span>
                                        <span class="adm-trend-label"><?= e($dayLabel) ?></span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <div class="adm-legend">
                            <span>Hari tercatat: <strong><?= number_format(count($summary['per_hari']), 0, ',', '.') ?></strong></span>
                            <span>Total: <strong><?= number_format($summary['total'], 0, ',', '.') ?></strong> catatan</span>
                        </div>
                    <?php endif; ?>
                </section>
            </div>
            <section class="adm-subpanel">
                <div class="adm-table-meta">
                    <p>
                        <strong>Detail Catatan</strong> &mdash;
                        <?= e((new DateTimeImmutable($dateFrom, $timezone))->format('d-m-Y')) ?> sampai
                        <?= e((new DateTimeImmutable($dateTo, $timezone))->format('d-m-Y')) ?>
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
                                <td colspan="7">Tidak ada catatan absensi untuk filter yang dipilih.</td>
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
                    Laporan menampilkan catatan yang benar-benar tersimpan di tabel absensi. Karyawan tanpa catatan
                    pada suatu tanggal tidak otomatis dibuatkan baris &ldquo;Tidak Hadir&rdquo;.
                </p>
            </section>
        <?php endif; ?>

<?php
require_once __DIR__ . '/../layout/footer.php';
