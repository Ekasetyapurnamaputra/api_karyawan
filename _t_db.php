<?php
require_once __DIR__ . '/config/database.php';
$pdo = getPDO();
foreach (['karyawan','absensi'] as $tbl) {
  $st = $pdo->query("SHOW TABLES LIKE '$tbl'")->fetchAll();
  echo "table $tbl exists: " . (count($st) ? 'YES' : 'NO') . "\n";
}
echo "karyawan rows: " . $pdo->query("SELECT COUNT(*) FROM karyawan")->fetchColumn() . "\n";
echo "absensi rows: " . $pdo->query("SELECT COUNT(*) FROM absensi")->fetchColumn() . "\n";
echo "roles: "; foreach ($pdo->query("SELECT role, COUNT(*) c FROM karyawan GROUP BY role") as $r) { echo "{$r['role']}={$r['c']} "; } echo "\n";
echo "status: "; foreach ($pdo->query("SELECT status, COUNT(*) c FROM absensi GROUP BY status") as $r) { echo "[{$r['status']}]={$r['c']} "; } echo "\n";
echo "dates: "; foreach ($pdo->query("SELECT tanggal, COUNT(*) c FROM absensi GROUP BY tanggal ORDER BY tanggal DESC LIMIT 10") as $r) { echo "{$r['tanggal']}={$r['c']} "; } echo "\n";
$cols = $pdo->query("SHOW COLUMNS FROM absensi")->fetchAll(PDO::FETCH_ASSOC);
echo "absensi cols: "; foreach ($cols as $c) echo "{$c['Field']}({$c['Type']}) "; echo "\n";
echo "distinct karyawan in absensi: " . $pdo->query("SELECT COUNT(DISTINCT idk) FROM absensi")->fetchColumn() . "\n";
