<?php
// File: C:\xampp\htdocs\Cilengkrang-Web-Wisata\controllers\PembayaranController.php

// Diasumsikan models/Pembayaran.php menggunakan gaya statis dan global $conn
require_once __DIR__ . '/../models/Pembayaran.php';

class PembayaranController
{

    /**
     * Mengambil semua data pembayaran untuk admin (dipanggil dari halaman admin).
     * Memanggil metode statis dari model.
     * @return array Array data pembayaran atau array kosong jika gagal/tidak ada.
     */
    public static function getAllPembayaranForAdmin()
    {
        // Memanggil metode statis model yang melakukan join
        $data = Pembayaran::findAllWithKodePemesanan('p.created_at DESC'); // Menggunakan metode yang sudah ada di model
        return $data ?: []; // Kembalikan array kosong jika null/false
    }

    /**
     * Mengupdate status pembayaran (contoh, bisa dipanggil dari proses konfirmasi).
     * @param int $id ID pembayaran
     * @param string $status Status baru
     * @param array $details Detail tambahan (opsional)
     * @return bool True jika sukses, false jika gagal.
     */
    public static function updateStatus($id, $status, $details = [])
    {
        // Validasi dasar ID dan Status di sini jika perlu
        $id_val = filter_var($id, FILTER_VALIDATE_INT);
        $allowed_status = ['pending', 'success', 'failed', 'expired', 'refunded', 'awaiting_confirmation']; // Sesuaikan

        if (!$id_val || $id_val <= 0) {
            set_flash_message('danger', 'ID Pembayaran tidak valid.');
            return false;
        }
        if (empty($status) || !in_array($status, $allowed_status)) {
            set_flash_message('danger', 'Status Pembayaran tidak valid.');
            return false;
        }

        // Panggil metode statis model untuk update
        $success = Pembayaran::updateStatusAndDetails($id_val, $status, $details);

        if (!$success) {
            if (!isset($_SESSION['flash_message'])) { // Cek jika model belum set flash
                set_flash_message('danger', 'Gagal memperbarui status pembayaran.');
            }
            return false;
        }

        // PENTING: Jika status 'success', update status pemesanan tiket
        if ($success && in_array($status, ['success', 'paid', 'confirmed'])) {
            require_once __DIR__ . '/../models/PemesananTiket.php'; // Perlu model tiket
            $pembayaranData = Pembayaran::findById($id_val);
            if ($pembayaranData && isset($pembayaranData['pemesanan_tiket_id'])) {
                // Panggil metode statis di PemesananTiket untuk update status
                // Asumsi ada metode PemesananTiket::updateStatus($id, $newStatus)
                $updateTiketStatus = PemesananTiket::updateStatus($pembayaranData['pemesanan_tiket_id'], 'paid'); // atau 'confirmed'
                if (!$updateTiketStatus) {
                    error_log("Peringatan: Gagal update status pemesanan tiket ID " . $pembayaranData['pemesanan_tiket_id'] . " setelah pembayaran sukses.");
                    // Mungkin set flash message tambahan?
                }
            }
        }
        return true;
    }

    // Tambahkan metode statis lain jika diperlukan (misal: getByPemesananId, etc.)
}
