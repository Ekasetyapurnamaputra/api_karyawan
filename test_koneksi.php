<?php

require_once __DIR__ . '/config/database.php';

try {
    $pdo = getPDO();

    echo "Koneksi PDO BERHASIL.<br>";
    echo "Database aktif: "
        . htmlspecialchars(
            $pdo->query('SELECT DATABASE()')->fetchColumn(),
            ENT_QUOTES,
            'UTF-8'
        );

    echo "<br><br>Daftar tabel:<br>";

    $tables = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);

    if (empty($tables)) {
        echo "Koneksi berhasil, tetapi belum ada tabel.";
    } else {
        foreach ($tables as $table) {
            echo htmlspecialchars(
                $table,
                ENT_QUOTES,
                'UTF-8'
            ) . "<br>";
        }
    }
} catch (Throwable $e) {
    http_response_code(500);

    echo "<h3>Koneksi atau pengujian database gagal</h3>";
    echo "<pre>" . htmlspecialchars(
        $e->getMessage(),
        ENT_QUOTES,
        'UTF-8'
    ) . "</pre>";
}
