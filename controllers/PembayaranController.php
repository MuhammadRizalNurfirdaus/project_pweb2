<?php
// File: C:\xampp\htdocs\Cilengkrang-Web-Wisata\controllers\PembayaranController.php

/**
 * PembayaranController
 * Bertanggung jawab untuk mengelola logika bisnis terkait pembayaran.
 * Berinteraksi dengan Model Pembayaran dan PemesananTiket.
 */

// Diasumsikan model-model ini sudah dimuat oleh config.php atau autoloader.
// Jika tidak, uncomment baris berikut atau pastikan config.php memuatnya.
// require_once __DIR__ . '/../models/Pembayaran.php';
// require_once __DIR__ . '/../models/PemesananTiket.php';

class PembayaranController
{
    private const ALLOWED_PAYMENT_STATUSES = [
        'pending',
        'awaiting_confirmation',
        'success',
        'paid',
        'confirmed',
        'failed',
        'expired',
        'refunded',
        'cancelled'
    ];
    private const SUCCESSFUL_PAYMENT_STATUSES = ['success', 'paid', 'confirmed'];

    /**
     * Mengambil semua data pembayaran beserta kode pemesanan terkait.
     * @param string $orderBy Pengurutan hasil.
     * @return array Data pembayaran atau array kosong.
     */
    public static function getAllPembayaranForAdmin($orderBy = 'p.created_at DESC')
    {
        // Asumsi koneksi $conn tersedia secara global atau model Pembayaran menanganinya.
        // Jika Pembayaran::findAllWithKodePemesanan() butuh $conn, maka: global $conn;
        // dan panggil Pembayaran::findAllWithKodePemesanan($conn, $orderBy);

        if (!class_exists('Pembayaran') || !method_exists('Pembayaran', 'findAllWithKodePemesanan')) {
            error_log("PembayaranController::getAllPembayaranForAdmin() - Model Pembayaran atau metode findAllWithKodePemesanan tidak ada.");
            return [];
        }
        try {
            $data = Pembayaran::findAllWithKodePemesanan($orderBy);
            return $data ?: [];
        } catch (Exception $e) {
            error_log("PembayaranController::getAllPembayaranForAdmin() - Exception: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Mengambil detail satu pembayaran berdasarkan ID.
     * @param int $id_pembayaran ID pembayaran.
     * @return array|null Data pembayaran atau null.
     */
    public static function getPembayaranDetailById($id_pembayaran)
    {
        $id_val = filter_var($id_pembayaran, FILTER_VALIDATE_INT);
        if ($id_val === false || $id_val <= 0) {
            error_log("PembayaranController::getPembayaranDetailById() - ID Pembayaran tidak valid: " . print_r($id_pembayaran, true));
            if (function_exists('set_flash_message')) set_flash_message('danger', 'ID Pembayaran tidak valid.');
            return null;
        }

        if (!class_exists('Pembayaran') || !method_exists('Pembayaran', 'findById')) {
            error_log("PembayaranController::getPembayaranDetailById() - Model Pembayaran atau metode findById tidak ada.");
            if (function_exists('set_flash_message')) set_flash_message('danger', 'Kesalahan sistem: Komponen data pembayaran tidak ditemukan.');
            return null;
        }
        try {
            return Pembayaran::findById($id_val);
        } catch (Exception $e) {
            error_log("PembayaranController::getPembayaranDetailById() - Exception untuk ID {$id_val}: " . $e->getMessage());
            if (function_exists('set_flash_message')) set_flash_message('danger', 'Gagal mengambil detail pembayaran: Terjadi kesalahan server.');
            return null;
        }
    }

    /**
     * Mengupdate status pembayaran dan status pemesanan tiket terkait jika pembayaran sukses.
     * @param int $id_pembayaran ID pembayaran.
     * @param string $new_status_pembayaran Status baru.
     * @param array $details Detail tambahan opsional.
     * @return bool True jika update status pembayaran utama berhasil.
     */
    public static function updateStatusPembayaranDanPemesananTerkait($id_pembayaran, $new_status_pembayaran, $details = [])
    {
        $id_pembayaran_val = filter_var($id_pembayaran, FILTER_VALIDATE_INT);
        if ($id_pembayaran_val === false || $id_pembayaran_val <= 0) {
            if (function_exists('set_flash_message')) set_flash_message('danger', 'ID Pembayaran tidak valid untuk proses update.');
            error_log("PembayaranController::updateStatusPembayaranDanPemesananTerkait() - ID Pembayaran tidak valid: " . print_r($id_pembayaran, true));
            return false;
        }

        $clean_new_status = strtolower(trim($new_status_pembayaran));
        if (empty($clean_new_status) || !in_array($clean_new_status, self::ALLOWED_PAYMENT_STATUSES)) {
            if (function_exists('set_flash_message')) set_flash_message('danger', 'Status Pembayaran (' . e($new_status_pembayaran) . ') tidak valid.');
            error_log("PembayaranController::updateStatusPembayaranDanPemesananTerkait() - Status Pembayaran tidak valid: " . $new_status_pembayaran);
            return false;
        }

        if (!class_exists('Pembayaran') || !method_exists('Pembayaran', 'updateStatusAndDetails') || !method_exists('Pembayaran', 'findById')) {
            error_log("PembayaranController::updateStatusPembayaranDanPemesananTerkait() - Model Pembayaran atau metode yang dibutuhkan tidak ada.");
            if (function_exists('set_flash_message')) set_flash_message('danger', 'Kesalahan sistem: Komponen update pembayaran tidak lengkap.');
            return false;
        }

        try {
            $updatePembayaranBerhasil = Pembayaran::updateStatusAndDetails($id_pembayaran_val, $clean_new_status, $details);

            if (!$updatePembayaranBerhasil) {
                if (function_exists('set_flash_message') && !isset($_SESSION['flash_message'])) {
                    set_flash_message('danger', 'Gagal memperbarui status pembayaran di database.');
                }
                error_log("PembayaranController::updateStatusPembayaranDanPemesananTerkait() - Gagal update pembayaran ID: {$id_pembayaran_val} ke status: {$clean_new_status}.");
                return false;
            }

            // Jika update pembayaran berhasil DAN statusnya sukses
            if (in_array($clean_new_status, self::SUCCESSFUL_PAYMENT_STATUSES)) {
                $pembayaranData = Pembayaran::findById($id_pembayaran_val);

                if ($pembayaranData && !empty($pembayaranData['pemesanan_tiket_id'])) {
                    $pemesanan_tiket_id_terkait = (int)$pembayaranData['pemesanan_tiket_id'];
                    $status_tiket_baru = 'confirmed'; // Atau 'paid' jika 'confirmed' punya arti lain

                    if (class_exists('PemesananTiket') && method_exists('PemesananTiket', 'updateStatusPemesanan')) { // Sesuaikan nama method jika berbeda
                        $updateStatusTiketBerhasil = PemesananTiket::updateStatusPemesanan($pemesanan_tiket_id_terkait, $status_tiket_baru);
                        if (!$updateStatusTiketBerhasil) {
                            error_log("PERINGATAN PembayaranController: Gagal update Pemesanan Tiket ID {$pemesanan_tiket_id_terkait} ke '{$status_tiket_baru}' stlh pembayaran ID {$id_pembayaran_val} sukses.");
                            if (function_exists('set_flash_message')) {
                                // Tambahkan pesan, jangan timpa yang sukses
                                $current_flash = $_SESSION['flash_message'] ?? null;
                                set_flash_message('warning', ($current_flash ? $current_flash['message'] . "<br>" : "") . 'Status tiket terkait gagal diupdate otomatis. Harap periksa manual.');
                            }
                        } else {
                            error_log("INFO PembayaranController: Sukses update Pemesanan Tiket ID {$pemesanan_tiket_id_terkait} ke '{$status_tiket_baru}'.");
                        }
                    } else {
                        error_log("KESALAHAN SISTEM PembayaranController: Model PemesananTiket atau metode updateStatusPemesanan tidak ada.");
                        if (function_exists('set_flash_message')) {
                            $current_flash = $_SESSION['flash_message'] ?? null;
                            set_flash_message('warning', ($current_flash ? $current_flash['message'] . "<br>" : "") . 'Komponen update status tiket tidak tersedia. Harap periksa manual.');
                        }
                    }
                } else {
                    error_log("PERINGATAN PembayaranController: Tidak ada pemesanan_tiket_id untuk pembayaran ID {$id_pembayaran_val}.");
                }
            }
            // Pesan sukses utama akan diset oleh skrip pemanggil (proses_pemesanan.php)
            return true;
        } catch (Exception $e) {
            error_log("PembayaranController::updateStatusPembayaranDanPemesananTerkait() - Exception ID {$id_pembayaran_val}: " . $e->getMessage());
            if (function_exists('set_flash_message')) set_flash_message('danger', 'Terjadi kesalahan teknis saat memperbarui status pembayaran.');
            return false;
        }
    }

    /**
     * Membuat entri pembayaran manual baru untuk sebuah pemesanan tiket.
     * @param int $pemesanan_id ID pemesanan tiket.
     * @param string $metode_pembayaran Metode pembayaran.
     * @param string $status_default Status awal.
     * @param float|null $jumlah_dibayar Jumlah dibayar (null untuk mengambil dari total pemesanan).
     * @return int|false ID pembayaran baru atau false.
     */
    public static function createManualPaymentEntryForPemesanan($pemesanan_id, $metode_pembayaran = 'Manual (Admin)', $status_default = 'pending', $jumlah_dibayar = null)
    {
        $pemesanan_id_val = filter_var($pemesanan_id, FILTER_VALIDATE_INT);
        if ($pemesanan_id_val === false || $pemesanan_id_val <= 0) {
            error_log("PembayaranController::createManualPaymentEntryForPemesanan() - ID Pemesanan tidak valid: " . $pemesanan_id);
            if (function_exists('set_flash_message')) set_flash_message('danger', 'ID Pemesanan tidak valid.');
            return false;
        }

        if (
            !class_exists('PemesananTiket') || !method_exists('PemesananTiket', 'findById') ||
            !class_exists('Pembayaran') || !method_exists('Pembayaran', 'create')
        ) {
            error_log("PembayaranController::createManualPaymentEntryForPemesanan() - Model/Metode yang dibutuhkan tidak ada.");
            if (function_exists('set_flash_message')) set_flash_message('danger', 'Kesalahan sistem: Komponen tidak lengkap.');
            return false;
        }

        $pemesananData = PemesananTiket::findById($pemesanan_id_val);
        if (!$pemesananData || !isset($pemesananData['total_harga_akhir'])) {
            error_log("PembayaranController::createManualPaymentEntryForPemesanan() - Data pemesanan ID {$pemesanan_id_val} tidak ada / total harga tidak ada.");
            if (function_exists('set_flash_message')) set_flash_message('danger', 'Data pemesanan tidak ditemukan/lengkap.');
            return false;
        }

        // Cek apakah sudah ada pembayaran dengan status selain 'failed', 'cancelled', 'expired', 'refunded' untuk pemesanan ini
        $nonFinalStatus = array_diff(self::ALLOWED_PAYMENT_STATUSES, ['failed', 'cancelled', 'expired', 'refunded']);
        $existingActivePayment = Pembayaran::findByPemesananIdAndStatus($pemesanan_id_val, $nonFinalStatus);
        if ($existingActivePayment) {
            error_log("PembayaranController::createManualPaymentEntryForPemesanan() - Sudah ada pembayaran aktif untuk Pemesanan ID: {$pemesanan_id_val}.");
            if (function_exists('set_flash_message')) set_flash_message('warning', 'Pemesanan ini sudah memiliki entri pembayaran aktif. Tidak dapat membuat entri baru.');
            return false;
        }


        $data_pembayaran_baru = [
            'pemesanan_tiket_id' => $pemesanan_id_val,
            'kode_pemesanan' => $pemesananData['kode_pemesanan'] ?? ('INV-' . date('Ymd') . '-' . $pemesanan_id_val),
            'jumlah_dibayar' => ($jumlah_dibayar !== null && is_numeric($jumlah_dibayar)) ? (float)$jumlah_dibayar : (float)$pemesananData['total_harga_akhir'],
            'metode_pembayaran' => $metode_pembayaran,
            'status_pembayaran' => strtolower(trim($status_default)),
            'waktu_pembayaran' => (in_array(strtolower(trim($status_default)), self::SUCCESSFUL_PAYMENT_STATUSES)) ? date('Y-m-d H:i:s') : null,
            'catatan_admin' => 'Pembayaran dibuat manual oleh admin pada ' . date('Y-m-d H:i:s') . '.'
        ];

        try {
            $new_payment_id = Pembayaran::create($data_pembayaran_baru);
            if ($new_payment_id) {
                error_log("INFO: Entri pembayaran manual dibuat. ID: {$new_payment_id} untuk Pemesanan ID: {$pemesanan_id_val}.");
                if (in_array($data_pembayaran_baru['status_pembayaran'], self::SUCCESSFUL_PAYMENT_STATUSES)) {
                    if (class_exists('PemesananTiket') && method_exists('PemesananTiket', 'updateStatusPemesanan')) {
                        PemesananTiket::updateStatusPemesanan($pemesanan_id_val, 'confirmed'); // Atau 'paid'
                    }
                } elseif ($data_pembayaran_baru['status_pembayaran'] === 'pending' || $data_pembayaran_baru['status_pembayaran'] === 'awaiting_confirmation') {
                    if (class_exists('PemesananTiket') && method_exists('PemesananTiket', 'updateStatusPemesanan')) {
                        // Jika status pemesanan sebelumnya 'pending', ubah jadi 'waiting_payment'
                        if (strtolower($pemesananData['status']) === 'pending') {
                            PemesananTiket::updateStatusPemesanan($pemesanan_id_val, 'waiting_payment');
                        }
                    }
                }
                return $new_payment_id;
            } else {
                error_log("ERROR: Gagal buat entri pembayaran manual Pemesanan ID: {$pemesanan_id_val}. Pembayaran::create gagal.");
                if (function_exists('set_flash_message')) set_flash_message('danger', 'Gagal menyimpan data pembayaran manual.');
                return false;
            }
        } catch (Exception $e) {
            error_log("PembayaranController::createManualPaymentEntryForPemesanan() - Exception: " . $e->getMessage());
            if (function_exists('set_flash_message')) set_flash_message('danger', 'Kesalahan teknis saat membuat pembayaran manual.');
            return false;
        }
    }

    /**
     * Menghitung total pendapatan.
     * @param array $statusArray Status pembayaran yang dianggap pendapatan.
     * @return float|int Total pendapatan.
     */
    public static function getTotalRevenue($statusArray = null) // Diubah nama agar konsisten dengan pemanggilan di dashboard
    {
        // global $conn; // Hapus jika model menangani koneksi
        $validStatusArray = $statusArray ?? self::SUCCESSFUL_PAYMENT_STATUSES;
        if (!class_exists('Pembayaran') || !method_exists('Pembayaran', 'getTotalRevenue')) {
            error_log("PembayaranController::getTotalRevenue() - Model Pembayaran atau metode getTotalRevenue tidak ada.");
            return 0;
        }
        try {
            // Panggil Pembayaran::getTotalRevenue($conn, $validStatusArray) jika model memerlukan $conn
            return Pembayaran::getTotalRevenue($validStatusArray);
        } catch (Exception $e) {
            error_log("PembayaranController::getTotalRevenue() - Exception: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Menghitung jumlah pembayaran berdasarkan status.
     * @param string $status Status pembayaran.
     * @return int Jumlah pembayaran.
     */
    public static function countByStatus($status) // Diubah nama agar konsisten dengan pemanggilan di dashboard
    {
        // global $conn; // Hapus jika model menangani koneksi
        $clean_status = strtolower(trim($status));
        if (empty($clean_status)) return 0;

        if (!class_exists('Pembayaran') || !method_exists('Pembayaran', 'countByStatus')) {
            error_log("PembayaranController::countByStatus() - Model Pembayaran atau metode countByStatus tidak ada.");
            return 0;
        }
        try {
            // Panggil Pembayaran::countByStatus($conn, $clean_status) jika model memerlukan $conn
            return Pembayaran::countByStatus($clean_status);
        } catch (Exception $e) {
            error_log("PembayaranController::countByStatus() - Exception: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Mengupdate HANYA status dan detail tabel pembayaran.
     * @param int $id_pembayaran ID pembayaran.
     * @param string $new_status_pembayaran Status baru.
     * @param array $details Detail tambahan.
     * @return bool True jika berhasil.
     */
    public static function updateHanyaStatusPembayaran($id_pembayaran, $new_status_pembayaran, $details = [])
    {
        $id_pembayaran_val = filter_var($id_pembayaran, FILTER_VALIDATE_INT);
        if ($id_pembayaran_val === false || $id_pembayaran_val <= 0) {
            if (function_exists('set_flash_message')) set_flash_message('danger', 'ID Pembayaran tidak valid.');
            return false;
        }

        $clean_new_status = strtolower(trim($new_status_pembayaran));
        if (empty($clean_new_status) || !in_array($clean_new_status, self::ALLOWED_PAYMENT_STATUSES)) {
            if (function_exists('set_flash_message')) set_flash_message('danger', 'Status Pembayaran tidak valid.');
            return false;
        }

        if (!class_exists('Pembayaran') || !method_exists('Pembayaran', 'updateStatusAndDetails')) {
            error_log("PembayaranController::updateHanyaStatusPembayaran() - Model Pembayaran atau metode updateStatusAndDetails tidak ada.");
            if (function_exists('set_flash_message')) set_flash_message('danger', 'Kesalahan sistem: Komponen update pembayaran tidak ada.');
            return false;
        }
        try {
            $updateBerhasil = Pembayaran::updateStatusAndDetails($id_pembayaran_val, $clean_new_status, $details);
            if (!$updateBerhasil && function_exists('set_flash_message') && !isset($_SESSION['flash_message'])) {
                set_flash_message('danger', 'Gagal memperbarui status pembayaran.');
            }
            return $updateBerhasil;
        } catch (Exception $e) {
            error_log("PembayaranController::updateHanyaStatusPembayaran() - Exception ID {$id_pembayaran_val}: " . $e->getMessage());
            if (function_exists('set_flash_message')) set_flash_message('danger', 'Terjadi kesalahan teknis.');
            return false;
        }
    }
}
// End of PembayaranController.php