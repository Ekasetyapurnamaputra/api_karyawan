<?php
require_once __DIR__ . '/../config/helpers.php';
require_once __DIR__ . '/../config/database.php';
require_role('karyawan');

$success = get_flash('success');
$error = get_flash('error');
$warning = get_flash('warning');
$user = current_user();
$userId = (int) ($user['id'] ?? 0);
$timezone = new DateTimeZone('Asia/Jakarta');
$now = new DateTimeImmutable('now', $timezone);
$today = $now->format('Y-m-d');
$profile = null;
$todayAttendance = null;
$history = [];
$pageError = null;

try {
    $pdo = getPDO();

    // Ambil data terbaru dari database, bukan hanya mengandalkan nama session.
    $profileStmt = $pdo->prepare(
        "SELECT id, nik, nama, jabatan, no_hp
         FROM karyawan
         WHERE id = :id AND role = 'karyawan'
         LIMIT 1"
    );
    $profileStmt->execute([':id' => $userId]);
    $profile = $profileStmt->fetch();

    if (!$profile) {
        set_flash('error', 'Akun karyawan tidak ditemukan atau sudah tidak aktif. Silakan login kembali.');
        header('Location: ' . app_url('auth/login.php'));
        exit;
    }

    $todayStmt = $pdo->prepare(
        'SELECT id, id_karyawan, tanggal, jam_masuk, jam_pulang, status, created_at
         FROM absensi
         WHERE id_karyawan = :id_karyawan AND tanggal = :tanggal
         LIMIT 1'
    );
    $todayStmt->execute([
        ':id_karyawan' => $userId,
        ':tanggal' => $today,
    ]);
    $todayAttendance = $todayStmt->fetch() ?: null;

    $historyStmt = $pdo->prepare(
        'SELECT tanggal, jam_masuk, jam_pulang, status
         FROM absensi
         WHERE id_karyawan = :id_karyawan
         ORDER BY tanggal DESC, id DESC
         LIMIT 30'
    );
    $historyStmt->execute([':id_karyawan' => $userId]);
    $history = $historyStmt->fetchAll();
} catch (PDOException $e) {
    error_log('Halaman absensi gagal memuat data: ' . $e->getMessage());
    $pageError = 'Data absensi belum dapat dimuat. Periksa koneksi database atau log PHP.';
}

$canCheckIn = !$pageError && !$todayAttendance;
$canCheckOut = !$pageError
    && is_array($todayAttendance)
    && !empty($todayAttendance['jam_masuk'])
    && empty($todayAttendance['jam_pulang']);

$pageTitle = 'Portal Absensi';
$activePage = 'absensi';
$pageSection = 'PORTAL KARYAWAN';
$extraCss = ['assets/absensi.css'];
require_once __DIR__ . '/../layout/header.php';
?>

        <?php if ($pageError): ?>
            <div class="alert error"><?= e($pageError) ?></div>
        <?php endif; ?>

        <?php if (!$pageError && $profile): ?>
            <div class="panel">
                <div class="attendance-header" style="margin-bottom:20px;">
                    <div>
                        <h2>Presensi Harian</h2>
                        <p class="muted">Halo, <strong><?= e($profile['nama']) ?></strong> (NIK: <?= e($profile['nik']) ?> &middot; Jabatan: <?= e($profile['jabatan']) ?>)</p>
                        <p class="muted">Waktu Server: <strong><?= e($now->format('d-m-Y')) ?></strong> &middot; Zona Waktu: Asia/Jakarta (WIB)</p>
                    </div>
                </div>

                <div class="attendance-summary">
                    <div class="attendance-card">
                        <span class="attendance-card-label">Jam Masuk</span>
                        <strong><?= e($todayAttendance['jam_masuk'] ?? '—') ?></strong>
                    </div>
                    <div class="attendance-card">
                        <span class="attendance-card-label">Jam Pulang</span>
                        <strong><?= e($todayAttendance['jam_pulang'] ?? '—') ?></strong>
                    </div>
                    <div class="attendance-card">
                        <span class="attendance-card-label">Status Hari Ini</span>
                        <strong>
                            <?php if ($todayAttendance): ?>
                                <span class="status-pill" data-status="<?= e($todayAttendance['status']) ?>"><?= e($todayAttendance['status']) ?></span>
                            <?php else: ?>
                                <span class="status-pill">Belum Absen</span>
                            <?php endif; ?>
                        </strong>
                    </div>
                </div>

                <section class="attendance-actions" aria-labelledby="action-title" style="margin-top:24px;">
                    <h3 id="action-title" style="margin-bottom:8px;">Aksi Kehadiran Hari Ini</h3>
                    <?php if ($canCheckIn): ?>
                        <p class="muted">Kamu belum melakukan absensi masuk hari ini. Klik tombol di bawah untuk mencatat jam kehadiran.</p>
                        <form method="post" action="<?= e(app_url('absensi/masuk.php')) ?>" onsubmit="return confirm('Catat absensi masuk sekarang?');">
                            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                            <button type="submit" class="button attendance-button">ABSEN MASUK</button>
                        </form>
                    <?php elseif ($canCheckOut): ?>
                        <p class="muted">Absensi masuk tercatat pukul <strong><?= e($todayAttendance['jam_masuk']) ?></strong>. Klik tombol di bawah untuk mencatat jam pulang.</p>
                        <form method="post" action="<?= e(app_url('absensi/pulang.php')) ?>" onsubmit="return confirm('Catat absensi pulang sekarang?');">
                            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                            <button type="submit" class="button attendance-button">ABSEN PULANG</button>
                        </form>
                    <?php elseif ($todayAttendance && !empty($todayAttendance['jam_pulang'])): ?>
                        <div class="attendance-complete">
                            <strong>Absensi Hari Ini Lengkap</strong>
                            <p style="margin:4px 0 0;font-size:0.9rem;">Jam masuk: <?= e($todayAttendance['jam_masuk']) ?> &middot; Jam pulang: <?= e($todayAttendance['jam_pulang']) ?>.</p>
                        </div>
                    <?php elseif ($todayAttendance && empty($todayAttendance['jam_masuk'])): ?>
                        <p class="muted">Sudah ada catatan absensi untuk hari ini dengan status <?= e($todayAttendance['status']) ?>. Aksi masuk/pulang tidak tersedia.</p>
                    <?php else: ?>
                        <p class="muted">Aksi absensi belum dapat ditampilkan. Muat ulang halaman atau periksa pesan error.</p>
                    <?php endif; ?>
                </section>

                <section class="attendance-history" aria-labelledby="history-title" style="margin-top:28px;">
                    <div class="history-heading" style="display:flex;align-items:baseline;justify-content:space-between;flex-wrap:wrap;gap:8px;margin-bottom:12px;">
                        <h3 id="history-title" style="margin:0;">Riwayat Absensi</h3>
                        <span class="muted" style="font-size:0.85rem;">Maksimal 30 catatan terakhir</span>
                    </div>
                    <div class="table-wrap">
                        <table class="data-table">
                            <thead>
                            <tr>
                                <th>No.</th>
                                <th>Tanggal</th>
                                <th>Masuk</th>
                                <th>Pulang</th>
                                <th>Status</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php if (!$history): ?>
                                <tr><td colspan="5" class="empty-state">Belum ada riwayat absensi.</td></tr>
                            <?php else: ?>
                                <?php foreach ($history as $i => $row): ?>
                                    <tr>
                                        <td><?= $i + 1 ?></td>
                                        <td><?= e((new DateTimeImmutable($row['tanggal'], $timezone))->format('d-m-Y')) ?></td>
                                        <td><?= e($row['jam_masuk'] ?: '—') ?></td>
                                        <td><?= e($row['jam_pulang'] ?: '—') ?></td>
                                        <td><span class="status-pill" data-status="<?= e($row['status']) ?>"><?= e($row['status']) ?></span></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </section>
            </div>
        <?php endif; ?>

<?php
require_once __DIR__ . '/../layout/footer.php';
