<?php
// File: C:\xampp\htdocs\Cilengkrang-Web-Wisata\admin\pemesanan_tiket\hapus_pemesanan.php

require_once __DIR__ . '/../../config/config.php'; // Memuat konfigurasi dan helper
require_once __DIR__ . '/../../controllers/PemesananTiketController.php'; // Memuat controller

// Pastikan admin yang login (bisa ditambahkan di config.php atau header_admin.php,
// atau dicek secara eksplisit di sini jika perlu)
// require_admin(); // Jika Anda memiliki helper untuk ini

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    set_flash_message('danger', 'ID Pemesanan tidak valid atau tidak ditemukan.');
    redirect('admin/pemesanan_tiket/kelola_pemesanan.php');
}

$id = (int)$_GET['id'];

// Memanggil method delete dari PemesananTiketController
if (PemesananTiketController::delete($id)) {
    set_flash_message('success', 'Pemesanan tiket berhasil dihapus.');
} else {
    // Pesan error spesifik mungkin sudah di-log oleh Controller atau Model
    set_flash_message('danger', 'Gagal menghapus pemesanan tiket. Coba lagi atau periksa log server.');
}

// Redirect kembali ke halaman kelola pemesanan tiket
redirect('admin/pemesanan_tiket/kelola_pemesanan.php');
