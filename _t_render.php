<?php
$root = __DIR__;
$target = $_GET['p'] ?? 'admin/index.php';
$role = $_GET['role'] ?? 'admin';
unset($_GET['p'], $_GET['role']);
$_SERVER['REQUEST_METHOD'] = 'GET';
require_once $root . '/config/helpers.php';
$_SESSION['user'] = ['id'=>2,'nik'=>'0002','nama'=>'Karyawan Test','jabatan'=>'Staff','email'=>'k@test.local','role'=>$role];
$file = $root . '/' . $target;
chdir(dirname($file));
require $file;
