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
$nik = '';
$nama = '';
$jabatan = '';
$noHp = '';
$email = '';

try {
    $pdo = getPDO();
    $find = $pdo->prepare("SELECT id, nik, nama, jabatan, no_hp, email FROM karyawan WHERE id = :id AND role = 'karyawan' LIMIT 1");
    $find->execute([':id' => $id]);
    $employee = $find->fetch();
} catch (PDOException $e) {
    error_log('Cari karyawan untuk edit error: ' . $e->getMessage());
    http_response_code(500);
    exit('Data belum dapat dimuat. Periksa koneksi database atau log PHP.');
}

if (!$employee) {
    http_response_code(404);
    exit('Data karyawan tidak ditemukan.');
}

$nik = (string) $employee['nik'];
$nama = (string) $employee['nama'];
$jabatan = (string) $employee['jabatan'];
$noHp = (string) $employee['no_hp'];
$email = (string) $employee['email'];

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $nik = post_string('nik');
    $nama = post_string('nama');
    $jabatan = post_string('jabatan');
    $noHp = post_string('no_hp');
    $email = strtolower(post_string('email'));

    if (!csrf_valid()) {
        $errors[] = 'Sesi formulir tidak valid. Muat ulang halaman lalu coba kembali.';
    }
    if ($nik === '' || strlen($nik) > 30) {
        $errors[] = 'NIK wajib diisi dan maksimal 30 karakter.';
    }
    if ($nama === '' || strlen($nama) > 100) {
        $errors[] = 'Nama wajib diisi dan maksimal 100 karakter.';
    }
    if ($jabatan === '' || strlen($jabatan) > 100) {
        $errors[] = 'Jabatan wajib diisi dan maksimal 100 karakter.';
    }
    if ($noHp === '' || strlen($noHp) > 20 || !preg_match('/^\+?[0-9][0-9\s().-]{6,18}[0-9]$/', $noHp)) {
        $errors[] = 'Nomor WhatsApp wajib diisi dengan format nomor yang valid.';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 150) {
        $errors[] = 'Masukkan alamat email yang valid (maksimal 150 karakter).';
    }

    if (!$errors) {
        try {
            $check = $pdo->prepare(
                'SELECT id FROM karyawan WHERE (nik = :nik OR email = :email) AND id <> :id LIMIT 1'
            );
            $check->execute([':nik' => $nik, ':email' => $email, ':id' => $id]);

            if ($check->fetch()) {
                $errors[] = 'NIK atau email sudah digunakan oleh karyawan lain.';
            } else {
                $update = $pdo->prepare(
                    "UPDATE karyawan
                     SET nik = :nik, nama = :nama, jabatan = :jabatan,
                         no_hp = :no_hp, email = :email
                     WHERE id = :id AND role = 'karyawan'"
                );
                $update->execute([
                    ':nik' => $nik,
                    ':nama' => $nama,
                    ':jabatan' => $jabatan,
                    ':no_hp' => $noHp,
                    ':email' => $email,
                    ':id' => $id,
                ]);

                // Update sudah tersimpan; kegagalan notifikasi tidak membatalkan perubahan.
                $notificationOk = false;
                try {
                    require_once __DIR__ . '/../api/fonnte.php';
                    $when = (new DateTimeImmutable('now', new DateTimeZone('Asia/Jakarta')))->format('d-m-Y H:i:s');
                    $message = "PEMBARUAN DATA KARYAWAN\n"
                        . "Halo {$nama}, data karyawan Anda telah diperbarui oleh admin.\n"
                        . "NIK: {$nik}\n"
                        . "Jabatan: {$jabatan}\n"
                        . "Email: {$email}\n"
                        . "Waktu pembaruan: {$when}\n"
                        . "Jika ada data yang tidak sesuai, silakan hubungi admin.";
                    fonnte_send_message($noHp, $message);
                    $notificationOk = true;
                } catch (Throwable $e) {
                    error_log('Edit karyawan WhatsApp notification failed: ' . $e->getMessage());
                }

                if ($notificationOk) {
                    set_flash('success', 'Data karyawan berhasil diperbarui. Permintaan notifikasi WhatsApp diterima Fonnte; periksa ponsel tujuan untuk memastikan pesan diterima.');
                } else {
                    set_flash('warning', 'Data berhasil diperbarui, tetapi notifikasi WhatsApp tidak berhasil diproses. Periksa nomor, konfigurasi Fonnte, dan log PHP. Jangan mengulang edit hanya untuk mengirim notifikasi.');
                }
                header('Location: ' . app_url('karyawan/index.php'));
                exit;
            }
        } catch (PDOException $e) {
            error_log('Update karyawan error: ' . $e->getMessage());
            if ($e->getCode() === '23000') {
                $errors[] = 'NIK atau email sudah digunakan. Periksa kembali data.';
            } else {
                $errors[] = 'Perubahan belum dapat disimpan. Periksa koneksi database atau log PHP.';
            }
        }
    }
}
$pageTitle = 'Edit Karyawan';
$activePage = 'karyawan';
require_once __DIR__ . '/../layout/header.php';
?>

<div class="panel" style="max-width:680px;margin:0 auto;">
    <div style="margin-bottom:18px;">
        <h2>Edit Data Karyawan</h2>
        <p class="muted">Perbarui data karyawan. Notifikasi perubahan akan dicoba dikirim via WhatsApp.</p>
    </div>

    <?php if ($errors): ?>
        <div class="alert error"><strong>Periksa kembali:</strong><ul><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul></div>
    <?php endif; ?>

    <form method="post" action="<?= e(app_url('karyawan/edit.php?id=' . (int) $id)) ?>" autocomplete="on">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="id" value="<?= (int) $id ?>">
        
        <div>
            <label for="nik">NIK</label>
            <input id="nik" name="nik" maxlength="30" required value="<?= e($nik) ?>" autocomplete="off">
        </div>

        <div>
            <label for="nama">Nama Lengkap</label>
            <input id="nama" name="nama" maxlength="100" required value="<?= e($nama) ?>" autocomplete="name">
        </div>

        <div>
            <label for="jabatan">Jabatan</label>
            <input id="jabatan" name="jabatan" maxlength="100" required value="<?= e($jabatan) ?>">
        </div>

        <div>
            <label for="no_hp">Nomor WhatsApp</label>
            <input id="no_hp" name="no_hp" type="tel" maxlength="20" required value="<?= e($noHp) ?>" autocomplete="tel">
        </div>

        <div>
            <label for="email">Email</label>
            <input id="email" name="email" type="email" maxlength="150" required value="<?= e($email) ?>" autocomplete="email">
        </div>

        <div style="display:flex;gap:10px;margin-top:12px;">
            <button type="submit" class="button" style="flex:1;">Simpan Perubahan</button>
            <a class="button secondary" href="<?= e(app_url('karyawan/index.php')) ?>">Batal</a>
        </div>
    </form>
</div>

<?php
require_once __DIR__ . '/../layout/footer.php';
