<?php
// File: C:\xampp\htdocs\Cilengkrang-Web-Wisata\admin\pemesanan_tiket\proses_pemesanan.php
// Berfungsi sebagai handler untuk update status pemesanan dan pembayaran dari halaman detail_pemesanan.php

// LANGKAH 1: Muat Konfigurasi Utama dan Pemeriksaan Dasar
if (!require_once __DIR__ . '/../../config/config.php') {
    http_response_code(503); // Service Unavailable
    error_log("FATAL ERROR di proses_pemesanan.php: Gagal memuat config.php.");
    $errorMessage = (defined('IS_DEVELOPMENT') && IS_DEVELOPMENT) ? "Gagal memuat config.php." : "Kesalahan konfigurasi server.";
    exit("Kesalahan konfigurasi server. Aplikasi tidak dapat melanjutkan. " . $errorMessage);
}

// LANGKAH 2: Otentikasi Admin - SANGAT PENTING!
try {
    require_admin(); // Harus aktif untuk keamanan!
} catch (Exception $e) {
    error_log("ERROR saat otentikasi admin di proses_pemesanan.php: " . $e->getMessage());
    set_flash_message('danger', 'Otentikasi gagal atau sesi tidak valid.');
    redirect(AUTH_URL . '/login.php'); // Menggunakan AUTH_URL dari config
    exit;
}

// LANGKAH 3: Muat Controller yang Diperlukan
$controllerPemesananPath = CONTROLLERS_PATH . '/PemesananTiketController.php';
$controllerPembayaranPath = CONTROLLERS_PATH . '/PembayaranController.php'; // Pastikan ini adalah path yang benar

if (!file_exists($controllerPemesananPath)) {
    error_log("FATAL ERROR di proses_pemesanan.php: File PemesananTiketController.php tidak ditemukan di " . $controllerPemesananPath);
    set_flash_message('danger', 'Kesalahan sistem: Komponen Pemesanan Tiket tidak ditemukan.');
    redirect(ADMIN_URL . '/dashboard.php');
    exit;
}
if (!file_exists($controllerPembayaranPath)) {
    error_log("FATAL ERROR di proses_pemesanan.php: File PembayaranController.php tidak ditemukan di " . $controllerPembayaranPath);
    set_flash_message('danger', 'Kesalahan sistem: Komponen Pembayaran tidak ditemukan.');
    redirect(ADMIN_URL . '/dashboard.php');
    exit;
}
require_once $controllerPemesananPath;
require_once $controllerPembayaranPath;

// LANGKAH 4: Validasi Metode Request & CSRF Token
if (!is_post()) {
    set_flash_message('warning', 'Metode request tidak valid. Harus POST.');
    redirect($_SERVER['HTTP_REFERER'] ?? ADMIN_URL . '/dashboard.php');
    exit;
}

if (!function_exists('verify_csrf_token')) {
    function verify_csrf_token() {
        if (!isset($_POST['csrf_token'], $_SESSION['csrf_token'])) {
            return false;
        }
        return hash_equals($_SESSION['csrf_token'], $_POST['csrf_token']);
    }
}

if (!verify_csrf_token()) {
    set_flash_message('danger', 'Permintaan tidak valid atau sesi telah berakhir (CSRF token mismatch). Silakan coba lagi.');
    redirect($_SERVER['HTTP_REFERER'] ?? ADMIN_URL . '/dashboard.php');
    exit;
}

// LANGKAH 5: Ambil dan Validasi Input Dasar
$action = isset($_POST['action']) ? trim($_POST['action']) : '';

// ID Pemesanan bisa datang dari field yang berbeda tergantung aksi
$pemesanan_id_from_order_update = isset($_POST['pemesanan_id']) ? filter_var($_POST['pemesanan_id'], FILTER_VALIDATE_INT) : null;
$pemesanan_id_from_payment_create = isset($_POST['pemesanan_id_for_create_payment']) ? filter_var($_POST['pemesanan_id_for_create_payment'], FILTER_VALIDATE_INT) : null;

$pemesanan_id = null;
if ($action === 'create_manual_payment_entry') { // Ini adalah nama action dari detail_pemesanan.php yang direvisi
    $pemesanan_id = $pemesanan_id_from_payment_create;
} else {
    $pemesanan_id = $pemesanan_id_from_order_update;
}

if (!$pemesanan_id || $pemesanan_id <= 0) {
    set_flash_message('danger', 'ID Pemesanan tidak valid atau tidak disertakan untuk aksi yang diminta.');
    redirect(ADMIN_URL . '/pemesanan_tiket/kelola_pemesanan.php');
    exit;
}

// Tentukan URL redirect default (kembali ke halaman detail)
$redirect_url = ADMIN_URL . '/pemesanan_tiket/detail_pemesanan.php?id=' . $pemesanan_id;
// Ambil redirect_url dari form jika ada, ini lebih fleksibel
if (isset($_POST['redirect_url']) && !empty(trim($_POST['redirect_url']))) {
    // Lakukan validasi sederhana pada redirect_url untuk keamanan
    $posted_redirect_url = trim($_POST['redirect_url']);
    if (filter_var($posted_redirect_url, FILTER_VALIDATE_URL) && strpos($posted_redirect_url, BASE_URL) === 0) {
        // Hanya izinkan redirect ke URL di dalam BASE_URL
        $redirect_url = $posted_redirect_url;
    } elseif (strpos($posted_redirect_url, '/') === 0 || !preg_match('#^https?://#i', $posted_redirect_url)) {
        // Jika path relatif, gabungkan dengan BASE_URL
        $redirect_url = rtrim(BASE_URL, '/') . '/' . ltrim($posted_redirect_url, '/');
    } else {
        // Jika URL eksternal atau format aneh, fallback ke default
        error_log("Peringatan: Percobaan redirect ke URL tidak valid: " . $posted_redirect_url);
    }
}


// LANGKAH 6: Proses Berdasarkan Aksi yang Diminta

$admin_user_id = $_SESSION['user_id'] ?? 'UNKNOWN_ADMIN'; // Asumsi admin ID ada di user_id

// ======================================================
// AKSI: UPDATE STATUS PEMESANAN UTAMA
// ======================================================
if ($action === 'update_status_pemesanan' && isset($_POST['submit_update_order_status'])) {
    $status_pemesanan_baru = isset($_POST['new_order_status']) ? trim(strtolower($_POST['new_order_status'])) : null;
    $allowed_order_statuses = ['pending', 'waiting_payment', 'paid', 'confirmed', 'completed', 'cancelled', 'expired', 'refunded'];

    if (empty($status_pemesanan_baru) || !in_array($status_pemesanan_baru, $allowed_order_statuses)) {
        set_flash_message('danger', 'Status pemesanan baru tidak valid: "' . e($status_pemesanan_baru) . '"');
    } else {
        if (class_exists('PemesananTiketController') && method_exists('PemesananTiketController', 'updateStatusPemesanan')) { // Ganti nama method jika perlu
            $updateBerhasil = false;
            try {
                $updateBerhasil = PemesananTiketController::updateStatusPemesanan($pemesanan_id, $status_pemesanan_baru);
                if ($updateBerhasil) {
                    set_flash_message('success', 'Status pemesanan tiket berhasil diperbarui menjadi "' . e(ucfirst(str_replace('_', ' ', $status_pemesanan_baru))) . '".');
                    error_log("ADMIN ACTION: Status pemesanan ID {$pemesanan_id} diubah menjadi {$status_pemesanan_baru} oleh admin ID {$admin_user_id}");
                } else {
                    // Cek apakah controller sudah set flash message
                    if (!isset($_SESSION['flash_message'])) {
                        set_flash_message('danger', 'Gagal memperbarui status pemesanan tiket. Operasi tidak berhasil.');
                    }
                }
            } catch (Exception $e) {
                error_log("EXCEPTION saat update status pemesanan (ID: {$pemesanan_id}): " . $e->getMessage());
                set_flash_message('danger', 'Terjadi kesalahan teknis saat update status pemesanan: ' . e($e->getMessage()));
            }
        } else {
            set_flash_message('danger', 'Kesalahan sistem: Fungsi update status pemesanan tidak tersedia.');
            error_log("FATAL: PemesananTiketController atau method 'updateStatusPemesanan' tidak ditemukan.");
        }
    }
    redirect($redirect_url);
    exit;

    // ======================================================
    // AKSI: UPDATE STATUS PEMBAYARAN (Sudah dipindahkan ke PembayaranController)
    // File ini seharusnya tidak menangani update_status_pembayaran secara langsung jika sudah ada proses_update_status_pembayaran.php
    // Namun, jika Anda ingin tetap di sini untuk suatu alasan:
    // ======================================================
} elseif ($action === 'update_status_pembayaran_via_pemesanan' && isset($_POST['submit_update_payment_status_from_order'])) { // Ganti nama submit jika perlu
    // CATATAN: Sebaiknya aksi ini diarahkan ke controller/handler pembayaran yang spesifik.
    // Jika tetap di sini, pastikan logikanya sama dengan yang ada di admin/pembayaran/proses_update_status_pembayaran.php
    $pembayaran_id = isset($_POST['pembayaran_id']) ? filter_var($_POST['pembayaran_id'], FILTER_VALIDATE_INT) : null;
    $status_pembayaran_baru = isset($_POST['new_payment_status']) ? trim(strtolower($_POST['new_payment_status'])) : null;
    $allowed_payment_statuses = ['pending', 'awaiting_confirmation', 'success', 'paid', 'confirmed', 'failed', 'expired', 'refunded', 'cancelled'];

    if (!$pembayaran_id || $pembayaran_id <= 0) {
        set_flash_message('warning', 'ID Pembayaran tidak valid atau tidak ada untuk diupdate.');
        redirect($redirect_url);
        exit;
    }

    if (empty($status_pembayaran_baru) || !in_array($status_pembayaran_baru, $allowed_payment_statuses)) {
        set_flash_message('danger', 'Status pembayaran baru tidak valid: "' . e($status_pembayaran_baru) . '"');
    } else {
        // Ganti PembayaranController::updateStatusPembayaranDanTiket dengan method yang sesuai jika berbeda
        if (class_exists('PembayaranController') && method_exists('PembayaranController', 'updateStatusPembayaranDanPemesananTerkait')) {
            $updateBerhasil = false;
            try {
                // Pastikan method ini ada dan melakukan apa yang diharapkan
                $updateBerhasil = PembayaranController::updateStatusPembayaranDanPemesananTerkait($pembayaran_id, $status_pembayaran_baru);
                if ($updateBerhasil) {
                    set_flash_message('success', 'Status pembayaran berhasil diperbarui menjadi "' . e(ucfirst(str_replace('_', ' ', $status_pembayaran_baru))) . '". Status pesanan terkait mungkin juga telah diperbarui.');
                    error_log("ADMIN ACTION: Status pembayaran ID {$pembayaran_id} (Pemesanan ID {$pemesanan_id}) diubah menjadi {$status_pembayaran_baru} oleh admin ID {$admin_user_id}");
                } else {
                    if (!isset($_SESSION['flash_message'])) {
                        set_flash_message('danger', 'Gagal memperbarui status pembayaran. Operasi tidak berhasil.');
                    }
                }
            } catch (Exception $e) {
                error_log("EXCEPTION saat update status pembayaran (ID: {$pembayaran_id}): " . $e->getMessage());
                set_flash_message('danger', 'Terjadi kesalahan teknis saat update status pembayaran: ' . e($e->getMessage()));
            }
        } else {
            set_flash_message('danger', 'Kesalahan sistem: Fungsi update status pembayaran tidak tersedia.');
            error_log("FATAL: PembayaranController atau method 'updateStatusPembayaranDanPemesananTerkait' tidak ditemukan.");
        }
    }
    redirect($redirect_url);
    exit;

    // ======================================================
    // AKSI: BUAT ENTRI PEMBAYARAN MANUAL
    // ======================================================
} elseif ($action === 'create_manual_payment_entry' && isset($_POST['submit_create_manual_payment'])) { // Nama action disesuaikan dengan form
    if (class_exists('PembayaranController') && method_exists('PembayaranController', 'createManualPaymentEntryForPemesanan')) { // Ganti nama method jika perlu
        $buatBerhasil = false;
        try {
            // Method ini sebaiknya mengembalikan ID pembayaran baru atau true/false
            $new_pembayaran_id_or_status = PembayaranController::createManualPaymentEntryForPemesanan($pemesanan_id);
            $buatBerhasil = ($new_pembayaran_id_or_status !== false && $new_pembayaran_id_or_status !== null);

            if ($buatBerhasil) {
                $info_pembayaran_baru = is_numeric($new_pembayaran_id_or_status) ? " ID Pembayaran Baru: " . $new_pembayaran_id_or_status : "";
                set_flash_message('success', 'Entri pembayaran manual berhasil dibuat dengan status default.' . $info_pembayaran_baru);
                error_log("ADMIN ACTION: Entri pembayaran manual dibuat untuk pemesanan ID {$pemesanan_id} oleh admin ID {$admin_user_id}." . $info_pembayaran_baru);
            } else {
                if (!isset($_SESSION['flash_message'])) {
                    set_flash_message('danger', 'Gagal membuat entri pembayaran manual. Operasi tidak berhasil.');
                }
            }
        } catch (Exception $e) {
            error_log("EXCEPTION saat create manual payment (Pemesanan ID: {$pemesanan_id}): " . $e->getMessage());
            set_flash_message('danger', 'Terjadi kesalahan teknis saat membuat entri pembayaran: ' . e($e->getMessage()));
        }
    } else {
        set_flash_message('warning', 'Fungsionalitas pembuatan pembayaran manual belum tersedia.');
        error_log("PERINGATAN: PembayaranController atau method 'createManualPaymentEntryForPemesanan' tidak ditemukan.");
    }
    redirect($redirect_url);
    exit;

    // ======================================================
    // AKSI TIDAK DIKENALI ATAU FORM TIDAK DI-SUBMIT DENGAN BENAR
    // ======================================================
} else {
    error_log("PERINGATAN di proses_pemesanan.php: Aksi tidak dikenali ('" . e($action) . "') atau tombol submit tidak terdeteksi untuk pemesanan ID " . e($pemesanan_id) . ".");
    set_flash_message('warning', 'Aksi yang diminta tidak valid atau tidak dikenali.');
    redirect($redirect_url); // Kembali ke detail pemesanan
    exit;
}
