<?php
require_once __DIR__ . '/../config/helpers.php';
require_once __DIR__ . '/../config/database.php';
require_role('admin');

$success = get_flash('success');
$error = get_flash('error');
$warning = get_flash('warning');
$employees = [];
$pageError = null;

try {
    $pdo = getPDO();
    // Halaman ini mengelola akun karyawan saja, bukan akun admin.
    $stmt = $pdo->query(
        "SELECT id, nik, nama, jabatan, no_hp, email, created_at, updated_at
         FROM karyawan
         WHERE role = 'karyawan'
         ORDER BY created_at DESC, id DESC"
    );
    $employees = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log('List karyawan error: ' . $e->getMessage());
    $pageError = 'Daftar karyawan belum dapat dimuat. Periksa koneksi database atau log PHP.';
}
$pageTitle = 'Data Karyawan';
$activePage = 'karyawan';
require_once __DIR__ . '/../layout/header.php';
?>

        <?php if ($pageError): ?>
            <div class="alert error"><?= e($pageError) ?></div>
        <?php else: ?>
            <div class="panel">
                <div class="page-heading" style="display:flex;align-items:flex-start;justify-content:space-between;flex-wrap:wrap;gap:16px;margin-bottom:18px;">
                    <div>
                        <h2>Daftar Karyawan</h2>
                        <p class="muted">Kelola akun karyawan. Password tidak ditampilkan dan akun admin tidak dikelola dari halaman ini.</p>
                    </div>
                    <div class="actions page-actions" style="margin-top:0;">
                        <a class="button" href="<?= e(app_url('karyawan/tambah.php')) ?>">+ Tambah Karyawan</a>
                    </div>
                </div>

                <p class="table-meta">Jumlah karyawan: <strong><?= count($employees) ?></strong></p>
                <div class="table-wrap">
                    <table class="data-table">
                        <thead>
                        <tr>
                            <th>No.</th><th>NIK</th><th>Nama</th><th>Jabatan</th><th>WhatsApp</th><th>Email</th><th>Aksi</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php if (!$employees): ?>
                            <tr><td colspan="7" class="empty-state">Belum ada data karyawan. Tambahkan karyawan atau gunakan halaman register.</td></tr>
                        <?php else: ?>
                            <?php foreach ($employees as $index => $employee): ?>
                                <tr>
                                    <td><?= $index + 1 ?></td>
                                    <td><?= e($employee['nik']) ?></td>
                                    <td><strong><?= e($employee['nama']) ?></strong></td>
                                    <td><?= e($employee['jabatan']) ?></td>
                                    <td><?= e($employee['no_hp']) ?></td>
                                    <td><?= e($employee['email']) ?></td>
                                    <td>
                                        <div class="row-actions">
                                            <a class="button small secondary" href="<?= e(app_url('karyawan/edit.php?id=' . (int) $employee['id'])) ?>">Edit</a>
                                            <a class="button small danger" href="<?= e(app_url('karyawan/hapus.php?id=' . (int) $employee['id'])) ?>">Hapus</a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <p class="muted small-note" style="margin-top:14px;font-size:0.85rem;">Catatan: penghapusan permanen juga menghapus riwayat absensi terkait karena relasi database saat ini menggunakan ON DELETE CASCADE.</p>
            </div>
        <?php endif; ?>

<?php
require_once __DIR__ . '/../layout/footer.php';
