<?php
// File: C:\xampp\htdocs\Cilengkrang-Web-Wisata\admin\pemesanan_tiket\proses_pemesanan.php
// Berfungsi sebagai handler untuk update status pemesanan dan pembuatan entri pembayaran manual dari halaman detail_pemesanan.php

// LANGKAH 1: Muat Konfigurasi Utama dan Pemeriksaan Dasar
if (!require_once __DIR__ . '/../../config/config.php') {
    http_response_code(503); // Service Unavailable
    error_log("FATAL ERROR di proses_pemesanan.php: Gagal memuat config.php.");
    $errorMessage = (defined('IS_DEVELOPMENT') && IS_DEVELOPMENT) ? "Gagal memuat config.php." : "Kesalahan konfigurasi server.";
    exit("Kesalahan konfigurasi server. Aplikasi tidak dapat melanjutkan. " . $errorMessage);
}

// LANGKAH 2: Otentikasi Admin - SANGAT PENTING!
try {
    require_admin(); // Fungsi dari auth_helpers.php (dimuat oleh config.php)
} catch (Exception $e) { // Tangkap jika require_admin() melempar exception
    error_log("ERROR saat otentikasi admin di proses_pemesanan.php: " . $e->getMessage());
    set_flash_message('danger', 'Otentikasi gagal atau sesi tidak valid. Silakan login kembali.');
    redirect(AUTH_URL . '/login.php'); // Menggunakan AUTH_URL dari config
    exit;
}

// LANGKAH 3: Muat Controller yang Diperlukan
// Controller PemesananTiketController dan PembayaranController sudah dimuat oleh config.php
if (!class_exists('PemesananTiketController')) {
    error_log("FATAL ERROR di proses_pemesanan.php: Class PemesananTiketController tidak ditemukan.");
    set_flash_message('danger', 'Kesalahan sistem: Komponen Pemesanan Tiket tidak ditemukan.');
    redirect(ADMIN_URL . '/dashboard.php');
    exit;
}
if (!class_exists('PembayaranController')) {
    error_log("FATAL ERROR di proses_pemesanan.php: Class PembayaranController tidak ditemukan.");
    set_flash_message('danger', 'Kesalahan sistem: Komponen Pembayaran tidak ditemukan.');
    redirect(ADMIN_URL . '/dashboard.php');
    exit;
}

// LANGKAH 4: Validasi Metode Request & CSRF Token
if (!is_post()) { // is_post() adalah helper yang mungkin ada di helpers.php
    set_flash_message('warning', 'Metode request tidak valid. Harus POST.');
    redirect($_SERVER['HTTP_REFERER'] ?? ADMIN_URL . '/dashboard.php');
    exit;
}

// Asumsikan verify_csrf_token() ada di helpers.php atau auth_helpers.php
if (!function_exists('verify_csrf_token')) {
    // Definisikan jika belum ada (sebaiknya ada di file helper terpusat)
    function verify_csrf_token()
    {
        if (!isset($_POST['csrf_token'], $_SESSION['csrf_token'])) {
            return false;
        }
        return hash_equals($_SESSION['csrf_token'], $_POST['csrf_token']);
    }
}

if (!verify_csrf_token()) {
    set_flash_message('danger', 'Permintaan tidak valid atau sesi telah berakhir (CSRF token mismatch). Silakan coba lagi.');
    // Hapus CSRF token lama agar tidak menyebabkan masalah berulang jika user refresh
    unset($_SESSION['csrf_token']);
    redirect($_SERVER['HTTP_REFERER'] ?? ADMIN_URL . '/dashboard.php');
    exit;
}
// Setelah verifikasi, CSRF token bisa dihapus atau di-regenerate untuk request berikutnya
unset($_SESSION['csrf_token']);


// LANGKAH 5: Ambil dan Validasi Input Dasar
$action = isset($_POST['action']) ? trim($_POST['action']) : '';

// ID Pemesanan bisa datang dari field yang berbeda tergantung aksi
$pemesanan_id = null;
if (isset($_POST['pemesanan_id'])) { // Umumnya untuk update status pesanan
    $pemesanan_id = filter_var($_POST['pemesanan_id'], FILTER_VALIDATE_INT);
} elseif (isset($_POST['pemesanan_id_for_create_payment'])) { // Khusus untuk buat entri pembayaran
    $pemesanan_id = filter_var($_POST['pemesanan_id_for_create_payment'], FILTER_VALIDATE_INT);
}


if (!$pemesanan_id || $pemesanan_id <= 0) {
    set_flash_message('danger', 'ID Pemesanan tidak valid atau tidak disertakan untuk aksi yang diminta.');
    redirect(ADMIN_URL . '/pemesanan_tiket/kelola_pemesanan.php');
    exit;
}

// Tentukan URL redirect default (kembali ke halaman detail)
$default_redirect_url = ADMIN_URL . '/pemesanan_tiket/detail_pemesanan.php?id=' . $pemesanan_id;
$redirect_url = $default_redirect_url; // Default

// Ambil redirect_url dari form jika ada, ini lebih fleksibel
if (isset($_POST['redirect_url']) && !empty(trim($_POST['redirect_url']))) {
    $posted_redirect_url = trim($_POST['redirect_url']);
    // Validasi sederhana untuk redirect_url (mencegah open redirect)
    if (filter_var($posted_redirect_url, FILTER_VALIDATE_URL) && strpos($posted_redirect_url, BASE_URL) === 0) {
        $redirect_url = $posted_redirect_url; // Hanya izinkan redirect ke URL di dalam BASE_URL
    } elseif ((strpos($posted_redirect_url, '/') === 0 || strpos($posted_redirect_url, ADMIN_URL) === 0) && !preg_match('#^https?://#i', $posted_redirect_url)) {
        // Jika path relatif atau dimulai dengan ADMIN_URL, gabungkan dengan BASE_URL jika perlu
        if (strpos($posted_redirect_url, ADMIN_URL) === 0) {
            $redirect_url = $posted_redirect_url; // Sudah termasuk base admin url
        } else {
            $redirect_url = rtrim(BASE_URL, '/') . '/' . ltrim($posted_redirect_url, '/');
        }
    } else {
        error_log("Peringatan di proses_pemesanan.php: Percobaan redirect ke URL tidak valid atau eksternal: " . $posted_redirect_url . ". Menggunakan default: " . $default_redirect_url);
        // Fallback ke default jika URL tidak aman
    }
}


// LANGKAH 6: Proses Berdasarkan Aksi yang Diminta
$admin_user_id = $_SESSION['user_id'] ?? 'UNKNOWN_ADMIN'; // Asumsi admin ID ada di user_id session

// ======================================================
// AKSI: UPDATE STATUS PEMESANAN UTAMA
// ======================================================
if ($action === 'update_status_pemesanan' && isset($_POST['submit_update_order_status'])) {
    $status_pemesanan_baru = isset($_POST['new_order_status']) ? trim(strtolower($_POST['new_order_status'])) : null;
    // Daftar status yang diizinkan bisa didefinisikan sebagai konstanta atau diambil dari model jika ada
    $allowed_order_statuses = ['pending', 'waiting_payment', 'paid', 'confirmed', 'completed', 'cancelled', 'expired', 'refunded'];

    if (empty($status_pemesanan_baru) || !in_array($status_pemesanan_baru, $allowed_order_statuses)) {
        set_flash_message('danger', 'Status pemesanan baru tidak valid: "' . e($status_pemesanan_baru) . '"');
    } else {
        // Pastikan nama method controller sudah benar: updateStatusPemesanan atau updateStatus saja
        if (method_exists('PemesananTiketController', 'updateStatusPemesanan')) {
            $updateBerhasil = false;
            try {
                $updateBerhasil = PemesananTiketController::updateStatusPemesanan($pemesanan_id, $status_pemesanan_baru);
                if ($updateBerhasil) {
                    set_flash_message('success', 'Status pemesanan tiket (ID: ' . e($pemesanan_id) . ') berhasil diperbarui menjadi "' . e(ucfirst(str_replace('_', ' ', $status_pemesanan_baru))) . '".');
                    error_log("ADMIN ACTION: Status pemesanan ID {$pemesanan_id} diubah menjadi {$status_pemesanan_baru} oleh admin ID {$admin_user_id}");
                } else {
                    if (!isset($_SESSION['flash_message'])) { // Cek jika controller belum set pesan
                        set_flash_message('danger', 'Gagal memperbarui status pemesanan tiket. Operasi tidak berhasil.');
                    }
                }
            } catch (Exception $e) {
                error_log("EXCEPTION saat update status pemesanan (ID: {$pemesanan_id}, Status: {$status_pemesanan_baru}): " . $e->getMessage());
                set_flash_message('danger', 'Terjadi kesalahan teknis saat update status pemesanan: ' . e($e->getMessage()));
            }
        } else {
            set_flash_message('danger', 'Kesalahan sistem: Fungsi update status pemesanan tidak tersedia.');
            error_log("FATAL ERROR di proses_pemesanan.php: Method 'updateStatusPemesanan' tidak ditemukan di PemesananTiketController.");
        }
    }
    redirect($redirect_url);
    exit;

    // ======================================================
    // AKSI: BUAT ENTRI PEMBAYARAN MANUAL
    // ======================================================
} elseif ($action === 'create_manual_payment_entry' && isset($_POST['submit_create_manual_payment'])) {
    // Pastikan nama method controller sudah benar
    if (method_exists('PembayaranController', 'createManualPaymentEntryForPemesanan')) {
        $buatBerhasil = false;
        try {
            // Method ini sebaiknya mengembalikan ID pembayaran baru, true, atau false
            $new_pembayaran_id_or_status = PembayaranController::createManualPaymentEntryForPemesanan($pemesanan_id);
            $buatBerhasil = ($new_pembayaran_id_or_status !== false && $new_pembayaran_id_or_status !== null);

            if ($buatBerhasil) {
                $info_pembayaran_baru = is_numeric($new_pembayaran_id_or_status) ? " ID Pembayaran Baru: " . $new_pembayaran_id_or_status : "";
                set_flash_message('success', 'Entri pembayaran manual berhasil dibuat untuk pemesanan ID ' . e($pemesanan_id) . ' dengan status default.' . $info_pembayaran_baru);
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
        error_log("PERINGATAN di proses_pemesanan.php: Method 'createManualPaymentEntryForPesanan' tidak ditemukan di PembayaranController.");
    }
    redirect($redirect_url);
    exit;

    // ======================================================
    // AKSI TIDAK DIKENALI ATAU FORM TIDAK DI-SUBMIT DENGAN BENAR
    // ======================================================
} else {
    error_log("PERINGATAN di proses_pemesanan.php: Aksi tidak dikenali ('" . e($action) . "') atau tombol submit tidak terdeteksi untuk pemesanan ID " . e($pemesanan_id) . ". Data POST: " . print_r($_POST, true));
    set_flash_message('warning', 'Aksi yang diminta tidak valid atau tidak dikenali.');
    redirect($redirect_url); // Kembali ke detail pemesanan atau halaman relevan
    exit;
}
