<?php
// File: C:\xampp\htdocs\Cilengkrang-Web-Wisata\admin\pemesanan_sewa\proses_update_status_sewa.php

if (!require_once __DIR__ . '/../../config/config.php') {
    exit("Config Error.");
}
require_admin();
if (!require_once __DIR__ . '/../../controllers/PemesananSewaAlatController.php') {
    set_flash_message('danger', 'Kesalahan sistem.');
    redirect('admin/pemesanan_sewa/kelola_pemesanan_sewa.php'); // Redirect ke halaman kelola sewa
}

// URL redirect yang BENAR setelah proses
$redirect_url = ADMIN_URL . '/pemesanan_sewa/kelola_pemesanan_sewa.php';

if (is_post() && isset($_POST['update_status_sewa_submit'])) { // Pastikan nama tombol submit sesuai
    $pemesanan_id = isset($_POST['pemesanan_id']) ? filter_var($_POST['pemesanan_id'], FILTER_VALIDATE_INT) : null;
    $new_status = isset($_POST['new_status']) ? trim($_POST['new_status']) : null;

    if (!$pemesanan_id || empty($new_status)) {
        set_flash_message('danger', 'Data tidak lengkap untuk update status sewa.');
    } else {
        // Panggil metode statis controller
        if (PemesananSewaAlatController::updateStatusSewa($pemesanan_id, $new_status)) {
            // Pesan sukses akan diset oleh controller jika berhasil, atau di sini
            if (!isset($_SESSION['flash_message'])) {
                set_flash_message('success', 'Status pemesanan sewa alat berhasil diperbarui.');
            }
        } else {
            // Pesan error kemungkinan sudah diset oleh controller atau model
            if (!isset($_SESSION['flash_message'])) {
                set_flash_message('danger', 'Gagal memperbarui status pemesanan sewa alat.');
            }
        }
    }
} else {
    set_flash_message('warning', 'Akses tidak sah atau metode request tidak valid.');
}

// Sebelum redirect, pastikan tidak ada output lain
if (headers_sent($file, $line)) {
    error_log("Peringatan di proses_update_status_sewa.php: Headers sudah terkirim di {$file} baris {$line}. Redirect ke '{$redirect_url}' mungkin gagal.");
    // Fallback jika redirect gagal
    echo "Proses selesai. <a href='" . e($redirect_url) . "'>Kembali ke Kelola Pemesanan Sewa</a>";
    exit;
} else {
    error_log("proses_update_status_sewa.php: Akan redirect ke: " . $redirect_url);
    redirect($redirect_url); // Fungsi redirect() dari helpers.php
}
