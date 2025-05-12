<?php
// File: C:\xampp\htdocs\Cilengkrang-Web-Wisata\admin\pemesanan_sewa\proses_update_status_sewa.php

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/auth_helpers.php';
require_once __DIR__ . '/../../includes/flash_message.php';
require_once __DIR__ . '/../../controllers/PemesananSewaAlatController.php';
// Koneksi $conn diasumsikan tersedia

require_admin();

$redirect_url = ADMIN_URL . '/pemesanan_sewa/kelola_pemesanan_sewa.php'; // URL redirect

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['pemesanan_id']) || !isset($_POST['new_status']) || empty($_POST['new_status'])) {
        set_flash_message('danger', 'Data tidak lengkap untuk update status.');
        header('Location: ' . $redirect_url);
        exit;
    }

    $pemesanan_id = $_POST['pemesanan_id']; // Validasi INT ada di controller
    $new_status = trim($_POST['new_status']);

    if (PemesananSewaAlatController::updateStatusSewa($pemesanan_id, $new_status)) {
        // Jangan set flash success jika controller sudah set flash warning (misal gagal update stok)
        if (!isset($_SESSION['flash_message'])) {
            set_flash_message('success', 'Status pemesanan sewa berhasil diperbarui.');
        }
    } // Pesan error sudah di set di controller jika gagal

    header('Location: ' . $redirect_url);
    exit;
} else {
    set_flash_message('warning', 'Akses tidak sah.');
    header('Location: ' . $redirect_url);
    exit;
}
