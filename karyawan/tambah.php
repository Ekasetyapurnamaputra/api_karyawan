<?php
require_once __DIR__ . '/../config/helpers.php';
require_once __DIR__ . '/../config/database.php';
require_role('admin');

$errors = [];
$nik = '';
$nama = '';
$jabatan = '';
$noHp = '';
$email = '';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $nik = post_string('nik');
    $nama = post_string('nama');
    $jabatan = post_string('jabatan');
    $noHp = post_string('no_hp');
    $email = strtolower(post_string('email'));
    $password = isset($_POST['password']) && is_string($_POST['password']) ? $_POST['password'] : '';
    $konfirmasi = isset($_POST['konfirmasi_password']) && is_string($_POST['konfirmasi_password']) ? $_POST['konfirmasi_password'] : '';

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
    if (strlen($password) < 8 || strlen($password) > 200) {
        $errors[] = 'Password harus terdiri dari 8–200 karakter.';
    }
    if ($password !== $konfirmasi) {
        $errors[] = 'Konfirmasi password tidak sama.';
    }

    if (!$errors) {
        try {
            $pdo = getPDO();
            $check = $pdo->prepare('SELECT id FROM karyawan WHERE nik = :nik OR email = :email LIMIT 1');
            $check->execute([':nik' => $nik, ':email' => $email]);

            if ($check->fetch()) {
                $errors[] = 'NIK atau email sudah digunakan oleh akun lain.';
            } else {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $insert = $pdo->prepare(
                    "INSERT INTO karyawan (nik, nama, jabatan, no_hp, email, password, role)
                     VALUES (:nik, :nama, :jabatan, :no_hp, :email, :password, 'karyawan')"
                );
                $insert->execute([
                    ':nik' => $nik,
                    ':nama' => $nama,
                    ':jabatan' => $jabatan,
                    ':no_hp' => $noHp,
                    ':email' => $email,
                    ':password' => $hash,
                ]);

                set_flash('success', 'Data karyawan berhasil ditambahkan. Sampaikan password awal melalui jalur terpisah yang aman; password tidak dikirim melalui WhatsApp oleh aplikasi.');
                header('Location: ' . app_url('karyawan/index.php'));
                exit;
            }
        } catch (PDOException $e) {
            error_log('Tambah karyawan error: ' . $e->getMessage());
            if ($e->getCode() === '23000') {
                $errors[] = 'NIK atau email sudah terdaftar. Periksa kembali data.';
            } else {
                $errors[] = 'Data belum dapat disimpan. Periksa koneksi database atau log PHP.';
            }
        }
    }
}
$pageTitle = 'Tambah Karyawan';
$activePage = 'karyawan';
require_once __DIR__ . '/../layout/header.php';
?>

<div class="panel" style="max-width:680px;margin:0 auto;">
    <div style="margin-bottom:18px;">
        <h2>Tambah Karyawan Baru</h2>
        <p class="muted">Akun baru dibuat dengan role <strong>karyawan</strong>. Akun admin tidak dapat dibuat dari menu ini.</p>
    </div>

    <?php if ($errors): ?>
        <div class="alert error"><strong>Periksa kembali data formulir:</strong><ul><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul></div>
    <?php endif; ?>

    <div class="alert warning">
        <strong>Pemberitahuan Keamanan:</strong> Password awal harus disampaikan secara terpisah melalui jalur aman internal. Jangan mengirim password lewat WhatsApp atau email.
    </div>

    <form method="post" action="<?= e(app_url('karyawan/tambah.php')) ?>" autocomplete="on">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        
        <div>
            <label for="nik">NIK (Nomor Induk Karyawan)</label>
            <input id="nik" name="nik" maxlength="30" required value="<?= e($nik) ?>" autocomplete="off" placeholder="Contoh: 3201012345670001">
        </div>

        <div>
            <label for="nama">Nama Lengkap</label>
            <input id="nama" name="nama" maxlength="100" required value="<?= e($nama) ?>" autocomplete="name" placeholder="Nama lengkap karyawan">
        </div>

        <div>
            <label for="jabatan">Jabatan</label>
            <input id="jabatan" name="jabatan" maxlength="100" required value="<?= e($jabatan) ?>" placeholder="Contoh: Staf IT, Operator, dll.">
        </div>

        <div>
            <label for="no_hp">Nomor WhatsApp</label>
            <input id="no_hp" name="no_hp" type="tel" maxlength="20" required value="<?= e($noHp) ?>" placeholder="Contoh: 081234567890" autocomplete="tel">
        </div>

        <div>
            <label for="email">Alamat Email</label>
            <input id="email" name="email" type="email" maxlength="150" required value="<?= e($email) ?>" autocomplete="email" placeholder="karyawan@perusahaan.com">
        </div>

        <div>
            <label for="password">Password Awal (8–200 karakter)</label>
            <input id="password" name="password" type="password" minlength="8" maxlength="200" required autocomplete="new-password">
        </div>

        <div>
            <label for="konfirmasi_password">Konfirmasi Password</label>
            <input id="konfirmasi_password" name="konfirmasi_password" type="password" minlength="8" maxlength="200" required autocomplete="new-password">
        </div>

        <div style="display:flex;gap:10px;margin-top:12px;">
            <button type="submit" class="button" style="flex:1;">Simpan Karyawan</button>
            <a class="button secondary" href="<?= e(app_url('karyawan/index.php')) ?>">Batal</a>
        </div>
    </form>
</div>

<?php
require_once __DIR__ . '/../layout/footer.php';
