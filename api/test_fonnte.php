<?php
require_once __DIR__ . '/../config/helpers.php';
require_once __DIR__ . '/fonnte.php';

// Halaman uji hanya dapat digunakan oleh admin yang sudah login.
require_role('admin');

$errors = [];
$success = null;
$target = '';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $target = post_string('target');

    if (!csrf_valid()) {
        $errors[] = 'Sesi formulir tidak valid. Muat ulang halaman dan coba kembali.';
    }

    if ($target === '') {
        $errors[] = 'Nomor WhatsApp tujuan wajib diisi.';
    }

    if (!$errors) {
        $namaPenguji = current_user()['nama'] ?? 'Admin';
        $pesan = "Tes integrasi Fonnte berhasil dipanggil dari aplikasi Karyawan-WA.\n"
            . 'Penguji: ' . (string) $namaPenguji . "\n"
            . 'Waktu: ' . date('d-m-Y H:i:s');

        try {
            $result = fonnte_send_message($target, $pesan);
            $success = 'Permintaan diterima Fonnte. Detail: ' . $result['detail']
                . ' (HTTP ' . $result['http_code'] . ', tujuan ' . $result['target'] . ').';
        } catch (Throwable $e) {
            error_log('Fonnte test error: ' . $e->getMessage());
            $errors[] = $e->getMessage();
        }
    }
}
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Uji Fonnte</title>
    <link rel="stylesheet" href="<?= e(app_url('assets/style.css')) ?>">
</head>
<body>
<main class="container narrow">
    <section class="panel">
        <p class="eyebrow">KARYAWAN-WA / TAHAP 4</p>
        <h1>Uji WhatsApp Fonnte</h1>
        <p class="muted">Gunakan nomor WhatsApp milik sendiri atau nomor yang pemiliknya sudah memberi izin untuk menerima pesan tes.</p>

        <?php if ($success !== null): ?>
            <div class="alert success">
                <?= e($success) ?><br>
                Status ini menunjukkan permintaan diterima/masuk antrean Fonnte, bukan bukti bahwa pesan sudah diterima di ponsel tujuan.
            </div>
        <?php endif; ?>

        <?php if ($errors): ?>
            <div class="alert error"><strong>Pengujian gagal:</strong><ul>
                <?php foreach ($errors as $error): ?>
                    <li><?= e($error) ?></li>
                <?php endforeach; ?>
            </ul></div>
        <?php endif; ?>

        <form method="post" action="">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <label for="target">Nomor WhatsApp tujuan</label>
            <input id="target" name="target" type="tel" required maxlength="25"
                   placeholder="081234567890 atau 6281234567890"
                   value="<?= e($target) ?>">
            <p class="muted">Aplikasi akan menormalkan nomor Indonesia ke format 62 sebelum mengirimnya.</p>
            <button type="submit">Kirim pesan tes</button>
        </form>

        <p><a href="<?= e(app_url('index.php')) ?>">Kembali ke halaman utama</a></p>
    </section>
</main>
</body>
</html>
