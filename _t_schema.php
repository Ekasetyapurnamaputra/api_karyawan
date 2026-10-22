<?php
require_once __DIR__ . '/config/database.php';
$pdo = getPDO();
echo "mysql NOW: " . $pdo->query("SELECT NOW()")->fetchColumn() . "\n";
echo "php date_default: " . date_default_timezone_get() . " now=" . date('Y-m-d H:i:s') . "\n";
foreach ($pdo->query("SELECT id,nik,nama,role FROM karyawan") as $r) { echo "K {$r['id']} {$r['nik']} [{$r['nama']}] {$r['role']}\n"; }
foreach ($pdo->query("SELECT id,id_karyawan,tanggal,status FROM absensi") as $r) { echo "A {$r['id']} k{$r['id_karyawan']} {$r['tanggal']} {$r['status']}\n"; }
