<?php
// File: C:\xampp\htdocs\Cilengkrang-Web-Wisata\admin\pemesanan_tiket\proses_pemesanan.php
// Berfungsi sebagai handler untuk update status dari halaman detail_pemesanan.php

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../controllers/PemesananTiketController.php';
require_once __DIR__ . '/../../controllers/PembayaranController.php'; // Diperlukan untuk update status pembayaran

// require_admin(); // Pastikan hanya admin yang bisa akses

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Ambil ID pemesanan tiket utama
    $pemesanan_id = isset($_POST['pemesanan_id']) ? filter_var($_POST['pemesanan_id'], FILTER_VALIDATE_INT) : null;
    $action = isset($_POST['action']) ? trim($_POST['action']) : '';

    if (!$pemesanan_id || $pemesanan_id <= 0) {
        set_flash_message('danger', 'ID Pemesanan tidak valid.');
        redirect('admin/pemesanan_tiket/kelola_pemesanan.php');
    }

    $redirect_url = 'admin/pemesanan_tiket/detail_pemesanan.php?id=' . $pemesanan_id; // Default redirect kembali ke detail

    if ($action === 'update_status_pemesanan') {
        // Proses update status pemesanan utama
        $status_pemesanan_baru = isset($_POST['status_pemesanan_baru']) ? trim($_POST['status_pemesanan_baru']) : null;

        // Validasi status baru (sesuaikan dengan ENUM di tabel pemesanan_tiket)
        $allowed_pemesanan_statuses = ['pending', 'waiting_payment', 'paid', 'confirmed', 'completed', 'cancelled', 'expired'];
        if (empty($status_pemesanan_baru) || !in_array($status_pemesanan_baru, $allowed_pemesanan_statuses)) {
            set_flash_message('danger', 'Status pemesanan baru tidak valid.');
        } else {
            if (PemesananTiketController::updateStatus($pemesanan_id, $status_pemesanan_baru)) {
                set_flash_message('success', 'Status pemesanan tiket berhasil diperbarui menjadi "' . e(ucfirst(str_replace('_', ' ', $status_pemesanan_baru))) . '".');
            } else {
                set_flash_message('danger', 'Gagal memperbarui status pemesanan tiket.');
            }
        }
        redirect($redirect_url);
    } elseif ($action === 'update_status_pembayaran') {
        // Proses update status pembayaran
        $pembayaran_id = isset($_POST['pembayaran_id']) ? filter_var($_POST['pembayaran_id'], FILTER_VALIDATE_INT) : null; // ID dari tabel pembayaran
        $status_pembayaran_baru = isset($_POST['status_pembayaran_baru']) ? trim($_POST['status_pembayaran_baru']) : null;

        // Validasi status pembayaran baru (sesuaikan dengan ENUM di tabel pembayaran)
        $allowed_pembayaran_statuses = ['pending', 'success', 'failed', 'expired', 'refunded', 'awaiting_confirmation'];
        if (empty($status_pembayaran_baru) || !in_array($status_pembayaran_baru, $allowed_pembayaran_statuses)) {
            set_flash_message('danger', 'Status pembayaran baru tidak valid.');
        } elseif (!$pembayaran_id || $pembayaran_id <= 0) {
            // Jika belum ada entri pembayaran, mungkin perlu dibuat dulu atau beri error
            // Untuk saat ini, asumsikan admin hanya bisa update jika sudah ada entri pembayaran
            // atau PembayaranController::updateStatus akan membuat entri baru jika $pembayaran_id null/0 dan pemesanan_id ada.
            set_flash_message('warning', 'Tidak ada ID pembayaran yang valid untuk diupdate. Entri pembayaran mungkin belum ada.');
        } else {
            // Panggil method di PembayaranController untuk update status
            // Anda perlu membuat PembayaranController dan method updateStatusPembayaran
            if (class_exists('PembayaranController') && method_exists('PembayaranController', 'updateStatusPembayaran')) {
                if (PembayaranController::updateStatusPembayaran($pembayaran_id, $status_pembayaran_baru, $pemesanan_id)) {
                    set_flash_message('success', 'Status pembayaran berhasil diperbarui menjadi "' . e(ucfirst($status_pembayaran_baru)) . '".');

                    // Jika pembayaran sukses, mungkin update juga status pemesanan tiket utama menjadi 'paid' atau 'confirmed'
                    if ($status_pembayaran_baru === 'success') {
                        // Pilih status yang sesuai untuk pemesanan tiket
                        PemesananTiketController::updateStatus($pemesanan_id, 'paid'); // atau 'confirmed'
                    }
                } else {
                    set_flash_message('danger', 'Gagal memperbarui status pembayaran.');
                }
            } else {
                set_flash_message('warning', 'Fungsionalitas update status pembayaran belum tersedia (Controller/Method tidak ditemukan).');
                error_log("PembayaranController atau method updateStatusPembayaran tidak ditemukan.");
            }
        }
        redirect($redirect_url);
    } else {
        set_flash_message('warning', 'Aksi tidak dikenali.');
        redirect($redirect_url);
    }
} else {
    // Jika bukan POST, redirect
    set_flash_message('info', 'Metode request tidak valid.');
    redirect('admin/pemesanan_tiket/kelola_pemesanan.php');
}
