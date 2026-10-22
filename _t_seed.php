<?php
require_once __DIR__ . '/config/database.php';
$pdo = getPDO();
$pdo->exec("DELETE FROM absensi WHERE id_karyawan IN (SELECT id FROM karyawan WHERE role='karyawan') AND tanggal < CURDATE()");
echo "after cleanup absensi rows: " . $pdo->query("SELECT COUNT(*) FROM absensi")->fetchColumn() . "\n";
$karyawan = $pdo->query("SELECT id FROM karyawan WHERE role='karyawan'")->fetchAll(PDO::FETCH_COLUMN);
$statuses = ['Hadir','Terlambat','Izin','Sakit'];
$n = 0;
for ($d = 12; $d >= 1; $d--) {
    $date = date('Y-m-d', strtotime("-$d days"));
    foreach ($karyawan as $idx => $kid) {
        if (($d + $idx) % 3 === 0) { continue; }
        $st = $statuses[($d + $idx) % 4];
        $pdo->prepare("INSERT INTO absensi (id_karyawan, tanggal, jam_masuk, jam_pulang, status) VALUES (?,?,?,?,?)")
            ->execute([$kid, $date, '08:0'.$idx.':00', '17:0'.$idx.':00', $st]);
        $n++;
    }
}
echo "seeded rows=$n\n";
echo "total absensi: " . $pdo->query("SELECT COUNT(*) FROM absensi")->fetchColumn() . "\n";
