<?php
// File: C:\xampp\htdocs\Cilengkrang-Web-Wisata\controllers\PembayaranController.php

/**
 * PembayaranController
 * Bertanggung jawab untuk mengelola logika bisnis terkait pembayaran.
 * Berinteraksi dengan Model Pembayaran dan PemesananTiket.
 */

// Diasumsikan model-model ini sudah dimuat oleh config.php atau autoloader.
// Untuk memastikan, bisa ditambahkan pengecekan atau require di sini jika perlu.
// Contoh:
// if (!class_exists('Pembayaran')) {
//     if (defined('MODELS_PATH') && file_exists(MODELS_PATH . '/Pembayaran.php')) {
//         require_once MODELS_PATH . '/Pembayaran.php';
//     }
// }
// if (!class_exists('PemesananTiket')) {
//    if (defined('MODELS_PATH') && file_exists(MODELS_PATH . '/PemesananTiket.php')) {
//        require_once MODELS_PATH . '/PemesananTiket.php';
//    }
//}
// if (!class_exists('PemesananTiketController')) {
//    if (defined('CONTROLLERS_PATH') && file_exists(CONTROLLERS_PATH . '/PemesananTiketController.php')) {
//        require_once CONTROLLERS_PATH . '/PemesananTiketController.php';
//    }
//}


class PembayaranController
{
    private const FALLBACK_ALLOWED_PAYMENT_STATUSES = [
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
    private const FALLBACK_SUCCESSFUL_PAYMENT_STATUSES = ['success', 'paid', 'confirmed'];

    private static function getAllowedPaymentStatuses()
    {
        if (class_exists('Pembayaran') && defined('Pembayaran::ALLOWED_STATUSES')) {
            return Pembayaran::ALLOWED_STATUSES;
        }
        return self::FALLBACK_ALLOWED_PAYMENT_STATUSES;
    }

    private static function getSuccessfulPaymentStatuses()
    {
        if (class_exists('Pembayaran') && defined('Pembayaran::SUCCESSFUL_PAYMENT_STATUSES')) {
            return Pembayaran::SUCCESSFUL_PAYMENT_STATUSES;
        }
        return self::FALLBACK_SUCCESSFUL_PAYMENT_STATUSES;
    }

    private static function checkRequiredModelsAndMethods(array $models_with_methods)
    {
        foreach ($models_with_methods as $model_name => $methods) {
            if (!class_exists($model_name)) {
                $error_msg = get_called_class() . " Fatal Error: Model {$model_name} tidak ditemukan atau tidak dimuat.";
                error_log($error_msg);
                throw new RuntimeException($error_msg);
            }
            if (is_array($methods)) {
                foreach ($methods as $method_name) {
                    if (!method_exists($model_name, $method_name)) {
                        $error_msg = get_called_class() . " Fatal Error: Metode {$model_name}::{$method_name} tidak ditemukan.";
                        error_log($error_msg);
                        throw new RuntimeException($error_msg);
                    }
                }
            }
        }
    }

    public static function getAllPembayaranForAdmin($orderBy = 'p.created_at DESC')
    {
        try {
            self::checkRequiredModelsAndMethods(['Pembayaran' => ['findAllWithKodePemesanan']]);
            $data = Pembayaran::findAllWithKodePemesanan($orderBy);
            return $data ?: [];
        } catch (Exception $e) {
            error_log(get_called_class() . "::getAllPembayaranForAdmin() - Exception: " . $e->getMessage());
            return [];
        }
    }

    public static function getDetailPembayaranLengkap($pembayaran_id)
    {
        $id_val = filter_var($pembayaran_id, FILTER_VALIDATE_INT);
        if (!$id_val || $id_val <= 0) {
            error_log(get_called_class() . "::getDetailPembayaranLengkap - ID Pembayaran tidak valid: " . htmlspecialchars((string)$pembayaran_id));
            return null;
        }

        try {
            self::checkRequiredModelsAndMethods([
                'Pembayaran' => ['findById'],
                'PemesananTiketController' => ['getDetailPemesananLengkap']
            ]);

            $data_pembayaran = Pembayaran::findById($id_val);

            if (!$data_pembayaran) {
                error_log(get_called_class() . "::getDetailPembayaranLengkap - Pembayaran ID {$id_val} tidak ditemukan.");
                return null;
            }

            $detail_lengkap_pemesanan = null;
            if (!empty($data_pembayaran['pemesanan_tiket_id'])) {
                $detail_lengkap_pemesanan = PemesananTiketController::getDetailPemesananLengkap((int)$data_pembayaran['pemesanan_tiket_id']);
            }

            return [
                'pembayaran' => $data_pembayaran,
                'pemesanan_detail' => $detail_lengkap_pemesanan
            ];
        } catch (Exception $e) {
            error_log(get_called_class() . "::getDetailPembayaranLengkap() - Exception untuk ID Pembayaran {$id_val}: " . $e->getMessage());
            return null;
        }
    }

    public static function updateStatusPembayaranDanPemesananTerkait($id_pembayaran, $new_status_pembayaran, $details = [])
    {
        global $conn;

        $id_pembayaran_val = filter_var($id_pembayaran, FILTER_VALIDATE_INT);
        if ($id_pembayaran_val === false || $id_pembayaran_val <= 0) {
            if (function_exists('set_flash_message')) set_flash_message('danger', 'ID Pembayaran tidak valid untuk proses update.');
            error_log(get_called_class() . "::updateStatusPembayaranDanPemesananTerkait() - ID Pembayaran tidak valid: " . print_r($id_pembayaran, true));
            return false;
        }

        $clean_new_status = strtolower(trim($new_status_pembayaran));
        $allowed_statuses = self::getAllowedPaymentStatuses();

        if (empty($clean_new_status) || !in_array($clean_new_status, $allowed_statuses)) {
            if (function_exists('set_flash_message')) set_flash_message('danger', 'Status Pembayaran (' . e($new_status_pembayaran) . ') tidak valid.');
            error_log(get_called_class() . "::updateStatusPembayaranDanPemesananTerkait() - Status Pembayaran tidak valid: " . $new_status_pembayaran);
            return false;
        }

        try {
            self::checkRequiredModelsAndMethods([
                'Pembayaran' => ['updateStatusAndDetails', 'findById', 'getLastError'],
                'PemesananTiket' => ['updateStatusPemesanan', 'getLastError', 'findById'] // Tambahkan findById jika belum ada
            ]);

            if (!$conn || ($conn instanceof mysqli && $conn->connect_error)) {
                throw new RuntimeException("Koneksi database tidak tersedia untuk transaksi.");
            }
            mysqli_begin_transaction($conn);

            $pembayaranDataSaatIni = Pembayaran::findById($id_pembayaran_val);
            if (!$pembayaranDataSaatIni) {
                throw new Exception("Data pembayaran dengan ID {$id_pembayaran_val} tidak ditemukan.");
            }

            if (isset($details['waktu_pembayaran']) && !empty($details['waktu_pembayaran'])) {
                $dtWaktu = DateTime::createFromFormat('Y-m-d H:i:s', $details['waktu_pembayaran']) ?: (DateTime::createFromFormat('Y-m-d\TH:i:s', $details['waktu_pembayaran']) ?: DateTime::createFromFormat('Y-m-d\TH:i', $details['waktu_pembayaran']));
                if (!$dtWaktu) {
                    throw new Exception("Format waktu pembayaran di detail tidak valid: " . e($details['waktu_pembayaran']));
                }
                $details['waktu_pembayaran'] = $dtWaktu->format('Y-m-d H:i:s');
            }

            $updatePembayaranBerhasil = Pembayaran::updateStatusAndDetails($id_pembayaran_val, $clean_new_status, $details);

            if (!$updatePembayaranBerhasil) {
                throw new Exception('Gagal memperbarui status pembayaran di database. ' . Pembayaran::getLastError());
            }
            error_log("INFO PembayaranController: Status pembayaran ID {$id_pembayaran_val} berhasil diupdate menjadi '{$clean_new_status}'.");

            $successful_statuses = self::getSuccessfulPaymentStatuses();
            if (in_array($clean_new_status, $successful_statuses)) {
                if (!empty($pembayaranDataSaatIni['pemesanan_tiket_id'])) {
                    $pemesanan_tiket_id_terkait = (int)$pembayaranDataSaatIni['pemesanan_tiket_id'];
                    $status_tiket_baru = ($clean_new_status === 'confirmed') ? 'confirmed' : 'paid';

                    if (!PemesananTiket::updateStatusPemesanan($pemesanan_tiket_id_terkait, $status_tiket_baru)) {
                        error_log("PERINGATAN PembayaranController: Gagal update status Pemesanan Tiket ID {$pemesanan_tiket_id_terkait} ke '{$status_tiket_baru}'. Error Model: " . PemesananTiket::getLastError());
                        $current_flash = $_SESSION['flash_message'] ?? null;
                        $warning_message = 'Status tiket terkait gagal diupdate otomatis. Harap periksa manual.';
                        $new_message = ($current_flash && $current_flash['type'] === 'success' ? $current_flash['message'] . "<br>" : "") . $warning_message;
                        if (function_exists('set_flash_message')) set_flash_message('warning', $new_message);
                    } else {
                        error_log("INFO PembayaranController: Sukses update Pemesanan Tiket ID {$pemesanan_tiket_id_terkait} ke '{$status_tiket_baru}'.");
                    }
                } else {
                    error_log("PERINGATAN PembayaranController: Tidak ada pemesanan_tiket_id terkait untuk pembayaran sukses ID {$id_pembayaran_val}.");
                }
            }

            mysqli_commit($conn);
            return true;
        } catch (Exception $e) {
            // Penanganan rollback disempurnakan
            if (isset($conn) && $conn->thread_id && mysqli_errno($conn) === 0) { // Cek koneksi masih valid
                if ($conn && $conn->thread_id) { // Cek koneksi masih valid
                    mysqli_rollback($conn);
                }
            }
            error_log(get_called_class() . "::updateStatusPembayaranDanPemesananTerkait() - Exception ID {$id_pembayaran_val}: " . $e->getMessage());
            if (!isset($_SESSION['flash_message'])) {
                if (function_exists('set_flash_message')) set_flash_message('danger', 'Terjadi kesalahan teknis: ' . e($e->getMessage()));
            }
            return false;
        }
    }

    public static function createManualPaymentEntryForPemesanan($pemesanan_id)
    {
        $pemesanan_id_val = filter_var($pemesanan_id, FILTER_VALIDATE_INT);
        if ($pemesanan_id_val === false || $pemesanan_id_val <= 0) {
            if (function_exists('set_flash_message')) set_flash_message('danger', 'ID Pemesanan tidak valid.');
            return false;
        }

        try {
            self::checkRequiredModelsAndMethods([
                'PemesananTiket' => ['findById', 'updateStatusPemesanan', 'getLastError'],
                'Pembayaran' => ['findByPemesananIdAndStatus', 'create', 'getLastError']
            ]);

            $pemesananData = PemesananTiket::findById($pemesanan_id_val);
            if (!$pemesananData || !isset($pemesananData['total_harga_akhir'])) {
                throw new Exception('Data pemesanan tidak ditemukan atau tidak lengkap untuk ID: ' . $pemesanan_id_val . ". Error Model: " . PemesananTiket::getLastError());
            }

            $allowed_statuses = self::getAllowedPaymentStatuses();
            $nonFinalStatus = array_diff($allowed_statuses, ['failed', 'cancelled', 'expired', 'refunded']);

            $existingActivePayment = Pembayaran::findByPemesananIdAndStatus($pemesanan_id_val, $nonFinalStatus);
            if ($existingActivePayment) {
                if (function_exists('set_flash_message')) set_flash_message('info', 'Pemesanan ini sudah memiliki entri pembayaran aktif (ID Pembayaran: ' . $existingActivePayment['id'] . ').');
                return $existingActivePayment['id'];
            }

            $data_pembayaran_baru = [
                'pemesanan_tiket_id' => $pemesanan_id_val,
                'kode_pemesanan' => $pemesananData['kode_pemesanan'] ?? ('INV-' . date('Ymd') . '-' . $pemesanan_id_val),
                'jumlah_dibayar' => 0,
                'metode_pembayaran' => 'Manual (Admin)',
                'status_pembayaran' => 'pending',
                'catatan_admin' => 'Pembayaran dibuat manual oleh admin pada ' . date('Y-m-d H:i:s') . '.'
            ];

            $new_payment_id = Pembayaran::create($data_pembayaran_baru);
            if ($new_payment_id) {
                error_log("INFO: Entri pembayaran manual dibuat. ID: {$new_payment_id} untuk Pemesanan ID: {$pemesanan_id_val}.");
                if (strtolower($pemesananData['status']) === 'pending') {
                    PemesananTiket::updateStatusPemesanan($pemesanan_id_val, 'waiting_payment');
                }
                if (function_exists('set_flash_message')) set_flash_message('success', 'Entri pembayaran manual berhasil dibuat.');
                return $new_payment_id;
            } else {
                throw new Exception('Gagal menyimpan data pembayaran manual. ' . Pembayaran::getLastError());
            }
        } catch (Exception $e) {
            error_log(get_called_class() . "::createManualPaymentEntryForPemesanan() - Exception: " . $e->getMessage());
            if (!isset($_SESSION['flash_message'])) {
                if (function_exists('set_flash_message')) set_flash_message('danger', 'Gagal membuat entri pembayaran: ' . e($e->getMessage()));
            }
            return false;
        }
    }

    public static function getTotalRevenue($statusArray = null)
    {
        try {
            self::checkRequiredModelsAndMethods(['Pembayaran' => ['getTotalRevenue']]);
            $successful_statuses = self::getSuccessfulPaymentStatuses();
            $validStatusArray = $statusArray ?? $successful_statuses;
            return Pembayaran::getTotalRevenue($validStatusArray);
        } catch (Exception $e) {
            error_log(get_called_class() . "::getTotalRevenue() - Exception: " . $e->getMessage());
            return 0.0;
        }
    }

    public static function countByStatus($status)
    {
        try {
            self::checkRequiredModelsAndMethods(['Pembayaran' => ['countByStatus']]);
            // Pastikan metode countByStatus ada di Model Pembayaran
            if (!method_exists('Pembayaran', 'countByStatus')) {
                error_log(get_called_class() . "::countByStatus() - Metode Pembayaran::countByStatus tidak ditemukan.");
                return 0;
            }
            return Pembayaran::countByStatus($status);
        } catch (Exception $e) {
            error_log(get_called_class() . "::countByStatus() - Exception: " . $e->getMessage());
            return 0;
        }
    }
}
