<?php
// File: C:\xampp\htdocs\Cilengkrang-Web-Wisata\admin\alat_sewa\hapus_alat.php

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../controllers/SewaAlatController.php'; // Menggunakan SewaAlatController

// require_admin(); // Pastikan admin sudah login

// Validasi ID dari parameter GET
if (isset($_GET['id']) && filter_var($_GET['id'], FILTER_VALIDATE_INT) && (int)$_GET['id'] > 0) {
    $id_alat = (int)$_GET['id'];

    // Ambil nama item untuk pesan flash sebelum dihapus (opsional, tapi baik untuk feedback)
    $alat_sewa_info = SewaAlatController::getById($id_alat);
    $nama_item_display = $alat_sewa_info ? $alat_sewa_info['nama_item'] : "ID: " . $id_alat;

    // Panggil method delete dari SewaAlatController
    // Controller akan memanggil Model, yang di dalamnya ada logika pengecekan foreign key
    // dan penghapusan file gambar terkait.
    if (SewaAlatController::delete($id_alat)) {
        set_flash_message('success', 'Alat sewa "' . e($nama_item_display) . '" berhasil dihapus.');
    } else {
        // Pesan error spesifik (misalnya, karena masih ada pemesanan terkait atau ID tidak ditemukan)
        // kemungkinan sudah di-set oleh Controller atau Model (jika Model return false dan Controller set flash).
        // Jika tidak ada flash message dari Controller/Model, set pesan umum.
        if (!isset($_SESSION['flash_message'])) {
            set_flash_message('danger', 'Gagal menghapus alat sewa "' . e($nama_item_display) . '". Alat mungkin masih terpakai dalam pemesanan atau terjadi kesalahan.');
        }
        error_log("Gagal menghapus alat sewa ID: " . $id_alat . " dari admin/alat_sewa/hapus_alat.php");
    }
} else {
    // Jika ID tidak ada atau tidak valid
    set_flash_message('warning', 'Permintaan tidak valid atau ID alat sewa tidak ditemukan/tidak valid.');
    error_log("Permintaan hapus alat sewa tidak valid. ID: " . ($_GET['id'] ?? 'Tidak ada ID'));
}

// Redirect kembali ke halaman kelola alat sewa
redirect('admin/alat_sewa/kelola_alat.php');
