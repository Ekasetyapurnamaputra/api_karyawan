<?php
require_once __DIR__ . '/config/helpers.php';
$cases = [
  "=cmd|'/c calc'!A1" => true,
  "+123456" => true,
  "-1+2" => true,
  "@SUM(A1)" => true,
  "  =SUM(A1)" => true,
  "Eka" => false,
  "12735123713" => false,
  "" => false,
  "=safe" => true,
];
foreach ($cases as $v=>$should) {
  $r = csv_safe_value($v);
  $ok = (str_starts_with($r, "'") === $should);
  printf("%-24s => %-26s expected_prefix=%s %s\n", $v, $r, $should?'yes':'no', $ok?'OK':'MISMATCH');
}
echo "--- date validation ---\n";
foreach (['2026-10-22','2026-02-30','2026-13-01','2024-02-29','2026-1-1','abc',''] as $d) {
  printf("%-12s => %s\n", "'$d'", is_valid_date_string($d) ? 'VALID' : 'INVALID');
}
