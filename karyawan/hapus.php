<?php
require_once __DIR__ . '/../config/helpers.php';
require_once __DIR__ . '/../config/database.php';
require_role('admin');

$idRaw = $_GET['id'] ?? $_POST['id'] ?? '';
$id = filter_var($idRaw, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if ($id === false || $id === null) {
    set_flash('error', 'ID karyawan tidak valid.');
    header('Location: ' . app_url('karyawan/index.php'));
    exit;
}

$errors = [];
$employee = null;

try {
    $pdo = getPDO();
    $find = $pdo->prepare(
        "SELECT id, nik, nama, jabatan, no_hp, email
         FROM karyawan WHERE id = :id AND role = 'karyawan' LIMIT 1"
    );
    $find->execute([':id' => $id]);
    $employee = $find->fetch();
} catch (PDOException $e) {
    error_log('Cari karyawan untuk hapus error: ' . $e->getMessage());
    http_response_code(500);
    exit('Data belum dapat dimuat. Periksa koneksi database atau log PHP.');
}

if (!$employee) {
    http_response_code(404);
    exit('Data karyawan tidak ditemukan atau akun tersebut bukan akun karyawan.');
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    if (!csrf_valid()) {
        $errors[] = 'Sesi formulir tidak valid. Muat ulang halaman lalu coba kembali.';
    } else {
        try {
            // Ambil ulang data sebelum mengirim notifikasi dan sebelum DELETE.
            $findAgain = $pdo->prepare(
                "SELECT id, nik, nama, jabatan, no_hp, email
                 FROM karyawan WHERE id = :id AND role = 'karyawan' LIMIT 1"
            );
            $findAgain->execute([':id' => $id]);
            $employee = $findAgain->fetch();

            if (!$employee) {
                $errors[] = 'Data karyawan tidak lagi tersedia. Tidak ada data yang dihapus.';
            } else {
                // Jobsheet mensyaratkan notifikasi sebelum penghapusan.
                // Jika Fonnte menolak permintaan, data tetap dipertahankan.
                require_once __DIR__ . '/../api/fonnte.php';
                $when = (new DateTimeImmutable('now', new DateTimeZone('Asia/Jakarta')))->format('d-m-Y H:i:s');
                $name = str_replace(["\r", "\n"], ' ', (string) $employee['nama']);
                $nik = str_replace(["\r", "\n"], ' ', (string) $employee['nik']);
                $jabatan = str_replace(["\r", "\n"], ' ', (string) $employee['jabatan']);
                $message = "PEMBERITAHUAN PENGHAPUSAN DATA\n"
                    . "Halo {$name}, admin akan menghapus data karyawan Anda dari sistem.\n"
                    . "NIK: {$nik}\n"
                    . "Jabatan: {$jabatan}\n"
                    . "Waktu pemberitahuan: {$when}\n"
                    . "Jika ada pertanyaan, silakan hubungi admin.";

                // Fungsi ini melempar exception jika Fonnte menolak request.
                fonnte_send_message((string) $employee['no_hp'], $message);

                $delete = $pdo->prepare("DELETE FROM karyawan WHERE id = :id AND role = 'karyawan'");
                $delete->execute([':id' => $id]);

                if ($delete->rowCount() !== 1) {
                    set_flash('warning', 'Permintaan notifikasi diterima Fonnte, tetapi data tidak terhapus. Periksa database sebelum mengulangi tindakan.');
                } else {
                    set_flash('success', 'Notifikasi penghapusan diterima Fonnte dan data karyawan berhasil dihapus. Riwayat absensi terkait juga terhapus karena ON DELETE CASCADE.');
                }
                header('Location: ' . app_url('karyawan/index.php'));
                exit;
            }
        } catch (Throwable $e) {
            error_log('Hapus karyawan atau notifikasi WhatsApp gagal: ' . $e->getMessage());
            $errors[] = 'Notifikasi WhatsApp gagal diproses, sehingga data tidak dihapus. Periksa nomor, konfigurasi Fonnte, dan log PHP sebelum mencoba kembali.';
        }
    }
}
$pageTitle = 'Konfirmasi Hapus Karyawan';
$activePage = 'karyawan';
require_once __DIR__ . '/../layout/header.php';
?>

<div class="panel" style="max-width:680px;margin:0 auto;">
    <div style="margin-bottom:18px;">
        <h2>Konfirmasi Hapus Karyawan</h2>
        <p class="muted">Tindakan ini permanen. Sistem akan mengirim pemberitahuan WhatsApp sebelum menghapus akun.</p>
    </div>

    <div class="alert warning">
        <strong>Perhatian Penting:</strong> Sebelum data dihapus, aplikasi akan mencoba mengirim pemberitahuan WhatsApp kepada karyawan. Jika pengiriman ditolak Fonnte, penghapusan dibatalkan demi keamanan audit.
    </div>

    <?php if ($errors): ?>
        <div class="alert error"><ul><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul></div>
    <?php endif; ?>

    <div style="background:var(--slate-50);border:1px solid var(--line);border-radius:var(--radius-md);padding:16px;margin:16px 0;">
        <dl style="display:grid;grid-template-columns:120px 1fr;gap:10px 14px;margin:0;">
            <dt style="color:var(--ink-muted);font-weight:600;">NIK</dt>
            <dd style="margin:0;font-weight:700;"><?= e($employee['nik']) ?></dd>

            <dt style="color:var(--ink-muted);font-weight:600;">Nama Lengkap</dt>
            <dd style="margin:0;font-weight:700;"><?= e($employee['nama']) ?></dd>

            <dt style="color:var(--ink-muted);font-weight:600;">Jabatan</dt>
            <dd style="margin:0;"><?= e($employee['jabatan']) ?></dd>

            <dt style="color:var(--ink-muted);font-weight:600;">WhatsApp</dt>
            <dd style="margin:0;"><?= e($employee['no_hp']) ?></dd>

            <dt style="color:var(--ink-muted);font-weight:600;">Email</dt>
            <dd style="margin:0;"><?= e($employee['email']) ?></dd>
        </dl>
    </div>

    <p style="color:var(--danger-text);font-size:0.88rem;margin-bottom:18px;">
        <strong>Peringatan Relasi Database:</strong> Seluruh riwayat absensi karyawan ini juga akan ikut terhapus secara otomatis (CASCADE DELETE).
    </p>

    <form method="post" action="<?= e(app_url('karyawan/hapus.php?id=' . (int) $id)) ?>">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="id" value="<?= (int) $id ?>">
        <div style="display:flex;gap:10px;">
            <button type="submit" class="button danger" style="flex:1;" onclick="return confirm('Yakin melanjutkan? Sistem akan mengirim WhatsApp sebelum menghapus data.');">Kirim Pemberitahuan dan Hapus</button>
            <a class="button secondary" href="<?= e(app_url('karyawan/index.php')) ?>">Batal</a>
        </div>
    </form>
</div>

<?php
require_once __DIR__ . '/../layout/footer.php';
