<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

$dbHost = 'localhost'; // atau 127.0.0.1
$dbUser = 'root';      // Ganti jika user Anda berbeda
$dbPass = '';          // Ganti jika password Anda berbeda
$dbName = 'cilengkrang_web_wisata';

$connTest = new mysqli($dbHost, $dbUser, $dbPass, $dbName);

if ($connTest->connect_error) {
    die("Koneksi test gagal: " . $connTest->connect_error);
}
echo "Koneksi test berhasil.<br>";

$kode_test = "TEST" . time();
$tanggal_test = date('Y-m-d');

// Coba query dengan nama kolom yang diketik manual dan backtick
$sqlTest = "INSERT INTO `pemesanan_tiket` (`kode_pemesanan`, `tanggal_kunjungan`, `total_harga_akhir`, `status`) VALUES (?, ?, 0.00, 'pending')";

$stmtTest = $connTest->prepare($sqlTest);

if (!$stmtTest) {
    die("Prepare statement test gagal: (" . $connTest->errno . ") " . $connTest->error . "<br>Query: " . $sqlTest);
}
echo "Prepare statement test berhasil.<br>";

$stmtTest->bind_param("ss", $kode_test, $tanggal_test);
echo "Bind param test berhasil.<br>";

if ($stmtTest->execute()) {
    echo "Execute statement test berhasil. ID baru: " . $stmtTest->insert_id;
} else {
    die("Execute statement test gagal: (" . $stmtTest->errno . ") " . $stmtTest->error);
}

$stmtTest->close();
$connTest->close();
