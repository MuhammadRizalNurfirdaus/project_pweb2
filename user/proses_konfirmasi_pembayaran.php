<?php
// File: C:\xampp\htdocs\Cilengkrang-Web-Wisata\user\proses_konfirmasi_pembayaran.php

require_once __DIR__ . '/../config/config.php';

// Pastikan semua komponen yang dibutuhkan sudah dimuat oleh config.php
if (!class_exists('PembayaranController')) {
    error_log("KRITIS proses_konfirmasi_pembayaran.php: PembayaranController tidak ditemukan.");
    set_flash_message('danger', 'Kesalahan sistem: Tidak dapat memproses konfirmasi (PCNF).');
    redirect_to_previous_or_default('user/dashboard.php');
    exit;
}
if (!class_exists('Pembayaran')) {
    error_log("KRITIS proses_konfirmasi_pembayaran.php: Model Pembayaran tidak ditemukan.");
    set_flash_message('danger', 'Kesalahan sistem: Tidak dapat memproses konfirmasi (MPNF).');
    redirect_to_previous_or_default('user/dashboard.php');
    exit;
}
// PemesananTiket mungkin juga dibutuhkan jika PembayaranController berinteraksi dengannya
if (!class_exists('PemesananTiket')) {
    error_log("PERINGATAN proses_konfirmasi_pembayaran.php: Model PemesananTiket tidak ditemukan.");
}


require_login();
$redirect_url_default = 'user/dashboard.php'; // Default redirect jika kode pemesanan tidak ada

if (is_post()) {
    if (!verify_csrf_token()) {
        set_flash_message('danger', 'Permintaan tidak valid atau sesi Anda telah berakhir. Silakan coba lagi.');
        redirect($redirect_url_default);
        exit;
    }

    $kode_pemesanan = input('kode_pemesanan', null, 'POST');
    $pembayaran_id_str = input('pembayaran_id', null, 'POST');
    $pemesanan_tiket_id_str = input('pemesanan_tiket_id', null, 'POST'); // Ambil pemesanan_tiket_id
    $metode_pembayaran_input = input('metode_pembayaran', null, 'POST');
    $catatan_user_input = input('catatan_user', null, 'POST');
    $file_bukti = $_FILES['bukti_pembayaran'] ?? null;

    // Set URL redirect jika ada kode pemesanan (untuk kembali ke detail jika error)
    if (!empty($kode_pemesanan)) {
        $redirect_url_default = 'user/detail_pemesanan.php?kode=' . urlencode($kode_pemesanan);
    }

    $pembayaran_id = filter_var($pembayaran_id_str, FILTER_VALIDATE_INT);
    $pemesanan_tiket_id = filter_var($pemesanan_tiket_id_str, FILTER_VALIDATE_INT); // Validasi pemesanan_tiket_id

    // Validasi dasar
    if (empty($kode_pemesanan) || !$pembayaran_id || $pembayaran_id <= 0 || !$pemesanan_tiket_id || $pemesanan_tiket_id <= 0) {
        set_flash_message('danger', 'Data konfirmasi tidak lengkap atau tidak valid. Pastikan semua field terisi.');
        redirect($redirect_url_default);
        exit;
    }
    if (empty($metode_pembayaran_input)) {
        set_flash_message('danger', 'Metode pembayaran wajib dipilih.');
        redirect($redirect_url_default);
        exit;
    }
    if (!$file_bukti || $file_bukti['error'] !== UPLOAD_ERR_OK || $file_bukti['size'] == 0) {
        set_flash_message('danger', 'Bukti pembayaran wajib diunggah.');
        redirect($redirect_url_default);
        exit;
    }

    // Validasi file (tipe dan ukuran)
    $allowed_mime_types = ['image/jpeg', 'image/png', 'application/pdf'];
    $max_file_size = 2 * 1024 * 1024; // 2MB

    $file_mime_type = mime_content_type($file_bukti['tmp_name']);
    if (!in_array($file_mime_type, $allowed_mime_types)) {
        set_flash_message('danger', 'Format file bukti pembayaran tidak diizinkan (hanya JPG, PNG, PDF).');
        redirect($redirect_url_default);
        exit;
    }
    if ($file_bukti['size'] > $max_file_size) {
        set_flash_message('danger', 'Ukuran file bukti pembayaran melebihi batas maksimal (2MB).');
        redirect($redirect_url_default);
        exit;
    }

    // Generate nama file unik
    $file_extension = strtolower(pathinfo($file_bukti['name'], PATHINFO_EXTENSION));
    $new_filename = "bukti_" . preg_replace('/[^A-Za-z0-9\-]/', '', $kode_pemesanan) . "_" . time() . "." . $file_extension;

    // Pastikan konstanta UPLOADS_BUKTI_PEMBAYARAN_PATH terdefinisi dan path valid
    if (!defined('UPLOADS_BUKTI_PEMBAYARAN_PATH') || !is_dir(UPLOADS_BUKTI_PEMBAYARAN_PATH) || !is_writable(UPLOADS_BUKTI_PEMBAYARAN_PATH)) {
        error_log("KRITIS proses_konfirmasi_pembayaran.php: Path upload bukti pembayaran tidak valid atau tidak dapat ditulis: " . (defined('UPLOADS_BUKTI_PEMBAYARAN_PATH') ? UPLOADS_BUKTI_PEMBAYARAN_PATH : 'TIDAK TERDEFINISI'));
        set_flash_message('danger', 'Kesalahan sistem: Tidak dapat menyimpan file bukti pembayaran. Hubungi admin.');
        redirect($redirect_url_default);
        exit;
    }
    $upload_path = UPLOADS_BUKTI_PEMBAYARAN_PATH . DIRECTORY_SEPARATOR . $new_filename;


    // Sebelum memindahkan file, ambil data pembayaran lama untuk menggabungkan catatan admin
    $pembayaran_lama = Pembayaran::findById($pembayaran_id);
    $catatan_admin_lama = $pembayaran_lama['catatan_admin'] ?? '';
    $catatan_admin_baru = $catatan_admin_lama;
    if (!empty($catatan_user_input)) {
        $catatan_admin_baru .= (!empty($catatan_admin_lama) ? "\n" : "") . "[Catatan User " . date('d/m/Y H:i') . "]: " . $catatan_user_input;
    }


    if (move_uploaded_file($file_bukti['tmp_name'], $upload_path)) {
        $details_pembayaran_update = [
            'metode_pembayaran' => $metode_pembayaran_input,
            'bukti_pembayaran' => $new_filename,
            'waktu_pembayaran' => date('Y-m-d H:i:s'),
            'catatan_admin' => trim($catatan_admin_baru)
        ];

        // Panggil controller untuk update status pembayaran dan pemesanan terkait
        // Status diubah menjadi 'awaiting_confirmation'
        if (PembayaranController::updateStatusPembayaranDanPemesananTerkait($pembayaran_id, 'awaiting_confirmation', $details_pembayaran_update)) {
            set_flash_message('success', 'Konfirmasi pembayaran Anda telah berhasil dikirim. Mohon tunggu verifikasi dari admin.');
        } else {
            // Ambil error dari controller jika ada, atau gunakan pesan default
            $controller_error = $_SESSION['flash_message']['message'] ?? 'Gagal menyimpan konfirmasi pembayaran. Silakan coba lagi atau hubungi admin.';
            if (isset($_SESSION['flash_message'])) unset($_SESSION['flash_message']); // Bersihkan flash message dari controller

            set_flash_message('danger', $controller_error);
            error_log("Proses Konfirmasi Pembayaran: Gagal update status via PembayaranController untuk pembayaran ID {$pembayaran_id}. Error mungkin dari controller/model.");

            // Hapus file yang sudah diupload jika update DB gagal
            if (file_exists($upload_path)) {
                @unlink($upload_path);
            }
        }
    } else {
        set_flash_message('danger', 'Gagal mengunggah file bukti pembayaran. Pastikan folder uploads dapat ditulis.');
        error_log("Proses Konfirmasi Pembayaran: Gagal move_uploaded_file untuk " . e($kode_pemesanan) . " ke " . $upload_path . ". Error PHP: " . ($file_bukti['error'] ?? 'Unknown'));
    }
} else {
    set_flash_message('warning', 'Metode permintaan tidak valid.');
}
redirect($redirect_url_default); // Redirect kembali ke detail atau dashboard
exit;
