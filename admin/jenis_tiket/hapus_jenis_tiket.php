<?php
// File: C:\xampp\htdocs\Cilengkrang-Web-Wisata\admin\jenis_tiket\hapus_jenis_tiket.php

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../controllers/JenisTiketController.php';

require_admin(); // Pastikan admin sudah login

if (isset($_GET['id']) && filter_var($_GET['id'], FILTER_VALIDATE_INT) && (int)$_GET['id'] > 0) {
    $id_jenis_tiket = (int)$_GET['id'];

    // Ambil nama untuk pesan flash sebelum dihapus (opsional)
    $jenis_tiket = JenisTiketController::getById($id_jenis_tiket);
    $nama_display = $jenis_tiket ? ($jenis_tiket['nama_layanan_display'] . ' - ' . $jenis_tiket['tipe_hari']) : "ID: " . $id_jenis_tiket;

    if (JenisTiketController::delete($id_jenis_tiket)) {
        set_flash_message('success', 'Jenis tiket "' . e($nama_display) . '" berhasil dihapus.');
    } else {
        // Pesan error bisa jadi karena foreign key constraint atau ID tidak ditemukan
        if (!isset($_SESSION['flash_message'])) {
            set_flash_message('danger', 'Gagal menghapus jenis tiket "' . e($nama_display) . '". Mungkin masih digunakan atau terjadi kesalahan.');
        }
    }
} else {
    set_flash_message('warning', 'Permintaan tidak valid atau ID jenis tiket tidak ditemukan/tidak valid.');
}

redirect('admin/jenis_tiket/kelola_jenis_tiket.php');
