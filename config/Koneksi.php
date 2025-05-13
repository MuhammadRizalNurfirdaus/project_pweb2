<?php
// File: C:\xampp\htdocs\Cilengkrang-Web-Wisata\config\Koneksi.php

/**
 * File ini bertanggung jawab untuk membuat koneksi ke database
 * dan mendefinisikan variabel global $conn.
 * 
 * Error handling koneksi utama akan dilakukan di config.php setelah file ini di-include.
 */

$host = 'localhost';
$db_user = 'root';
$db_pass = ''; // Ganti dengan password database Anda jika ada
$db_name = 'cilengkrang_web_wisata'; // Pastikan nama database ini benar

// Nonaktifkan pelaporan error mysqli internal agar bisa ditangani secara manual jika perlu
// mysqli_report(MYSQLI_REPORT_OFF); // Atau MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT untuk throw exception

// Membuat koneksi
// @ untuk menekan warning default PHP jika koneksi gagal, karena akan ditangani di config.php
$conn = @mysqli_connect($host, $db_user, $db_pass, $db_name);

// Pengecekan koneksi akan dilakukan di config.php setelah file ini di-include.
// Jika $conn adalah false, config.php akan mendeteksinya.

// Jika koneksi berhasil, set character set
if ($conn) {
    if (!mysqli_set_charset($conn, "utf8mb4")) {
        // Log error jika gagal mengatur charset, tapi jangan hentikan eksekusi di sini.
        // Aplikasi mungkin masih bisa berjalan dengan charset default, meskipun tidak ideal.
        // config.php mungkin tidak akan tahu error ini kecuali jika Anda set $conn = false;
        error_log("Peringatan di Koneksi.php: Gagal mengatur character set utf8mb4: " . mysqli_error($conn));
    }
}
// Tidak ada 'die()' atau 'exit()' di sini. Biarkan config.php yang menangani.
// Variabel $conn akan tersedia secara global untuk config.php.
