<?php
$host = 'localhost';
$db_user = 'root';
$db_pass = ''; // Ganti dengan password database Anda jika ada
$db_name = 'cilengkrang_web_wisata'; // Pastikan nama database ini benar

$conn = mysqli_connect($host, $db_user, $db_pass, $db_name);

if (!$conn) {
    // Tampilkan pesan error yang lebih informatif dan hentikan eksekusi
    // Jangan tampilkan detail error koneksi di lingkungan produksi
    $error_message = "Koneksi database gagal. ";
    if (mysqli_connect_errno()) {
        $error_message .= "Error: " . mysqli_connect_error();
    } else {
        $error_message .= "Silakan periksa konfigurasi database Anda.";
    }
    // Untuk development, die() bisa digunakan. Di produksi, log error dan tampilkan pesan umum.
    die($error_message);
}

if (!mysqli_set_charset($conn, "utf8mb4")) {
    error_log("Gagal mengatur character set utf8mb4: " . mysqli_error($conn));
}
