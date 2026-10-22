<?php
require_once __DIR__ . '/config/database.php';
$pdo = getPDO();
$pdo->exec("DELETE FROM absensi WHERE id_karyawan IN (SELECT id FROM karyawan WHERE nik='999999999')");
$pdo->exec("DELETE FROM karyawan WHERE nik='999999999'");
echo "karyawan rows: " . $pdo->query("SELECT COUNT(*) FROM karyawan")->fetchColumn() . "\n";
echo "absensi rows: " . $pdo->query("SELECT COUNT(*) FROM absensi")->fetchColumn() . "\n";
