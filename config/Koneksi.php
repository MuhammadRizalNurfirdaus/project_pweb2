<?php
$host = "localhost";
$user = "root";
$pass = "";
$dbname = "cilengkrang_web_wisata";

$conn = mysqli_connect($host, $user, $pass, $dbname);

// Cek koneksi
if (!$conn) {
    die("Koneksi gagal: " . mysqli_connect_error());
} else {
    echo "";
}
