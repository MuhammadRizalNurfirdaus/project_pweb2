<?php
require_once __DIR__ . '/../config/config.php';

// Pastikan semua controller dan model yang dibutuhkan sudah dimuat
if (!class_exists('PembayaranController') || !class_exists('Pembayaran') || !class_exists('PemesananTiket')) {
    error_log("KRITIS proses_konfirmasi_pembayaran.php: Komponen yang dibutuhkan tidak ditemukan.");
    set_flash_message('danger', 'Kesalahan sistem: Tidak dapat memproses konfirmasi pembayaran.');
    redirect('user/riwayat_pemesanan.php');
    exit;
}

require_login();

if (is_post()) {
    if (!verify_csrf_token()) {
        set_flash_message('danger', 'Permintaan tidak valid atau sesi telah berakhir.');
        redirect_to_previous_or_default('user/riwayat_pemesanan.php'); // Fungsi helper jika ada
        exit;
    }

    $kode_pemesanan = input('kode_pemesanan');
    $pembayaran_id_str = input('pembayaran_id'); // ID record pembayaran yang akan diupdate
    $metode_pembayaran_input = input('metode_pembayaran');
    $catatan_user = input('catatan_user'); // Opsional
    $file_bukti = $_FILES['bukti_pembayaran'] ?? null;

    $pembayaran_id = filter_var($pembayaran_id_str, FILTER_VALIDATE_INT);

    // Validasi dasar
    if (empty($kode_pemesanan) || !$pembayaran_id || $pembayaran_id <= 0) {
        set_flash_message('danger', 'Data konfirmasi tidak lengkap atau tidak valid.');
        redirect_to_previous_or_default('user/detail_pemesanan.php?kode=' . e($kode_pemesanan));
        exit;
    }
    if (empty($metode_pembayaran_input)) {
        set_flash_message('danger', 'Metode pembayaran wajib dipilih.');
        redirect('user/detail_pemesanan.php?kode=' . e($kode_pemesanan));
        exit;
    }
    if (!$file_bukti || $file_bukti['error'] !== UPLOAD_ERR_OK || $file_bukti['size'] == 0) {
        set_flash_message('danger', 'Bukti pembayaran wajib diunggah.');
        redirect('user/detail_pemesanan.php?kode=' . e($kode_pemesanan));
        exit;
    }

    // Validasi file (tipe dan ukuran)
    $allowed_mime_types = ['image/jpeg', 'image/png', 'application/pdf'];
    $max_file_size = 2 * 1024 * 1024; // 2MB

    $file_mime_type = mime_content_type($file_bukti['tmp_name']);
    if (!in_array($file_mime_type, $allowed_mime_types)) {
        set_flash_message('danger', 'Format file bukti pembayaran tidak diizinkan (hanya JPG, PNG, PDF).');
        redirect('user/detail_pemesanan.php?kode=' . e($kode_pemesanan));
        exit;
    }
    if ($file_bukti['size'] > $max_file_size) {
        set_flash_message('danger', 'Ukuran file bukti pembayaran melebihi batas maksimal (2MB).');
        redirect('user/detail_pemesanan.php?kode=' . e($kode_pemesanan));
        exit;
    }

    // Generate nama file unik
    $file_extension = strtolower(pathinfo($file_bukti['name'], PATHINFO_EXTENSION));
    $new_filename = "bukti_" . $kode_pemesanan . "_" . time() . "." . $file_extension;
    $upload_path = UPLOADS_BUKTI_PEMBAYARAN_PATH . '/' . $new_filename;

    if (move_uploaded_file($file_bukti['tmp_name'], $upload_path)) {
        // File berhasil diunggah, update record pembayaran
        $details_pembayaran = [
            'metode_pembayaran' => $metode_pembayaran_input,
            'bukti_pembayaran' => $new_filename,
            'waktu_pembayaran' => date('Y-m-d H:i:s'), // Waktu saat konfirmasi
            // Catatan user bisa disimpan di catatan_admin jika tidak ada field khusus, atau buat field baru
            // 'catatan_admin' => ($pembayaran_lama['catatan_admin'] ?? '') . "\n[User Note]: " . $catatan_user 
        ];

        // Jika ada catatan user dan ingin disimpan terpisah, tambahkan ke PembayaranController atau modelnya
        // Untuk sekarang, kita bisa gabungkan ke catatan admin atau buat field baru di tabel pembayaran jika sering dipakai.

        // Panggil controller untuk update status dan detail
        // Status diubah menjadi 'awaiting_confirmation'
        if (PembayaranController::updateStatusPembayaranDanPemesananTerkait($pembayaran_id, 'awaiting_confirmation', $details_pembayaran)) {
            set_flash_message('success', 'Konfirmasi pembayaran Anda telah berhasil dikirim. Mohon tunggu verifikasi dari admin.');
        } else {
            set_flash_message('danger', 'Gagal menyimpan konfirmasi pembayaran. Silakan coba lagi atau hubungi admin.');
            // Hapus file yang sudah diupload jika update DB gagal
            if (file_exists($upload_path)) {
                unlink($upload_path);
            }
        }
    } else {
        set_flash_message('danger', 'Gagal mengunggah file bukti pembayaran.');
        error_log("Proses Konfirmasi Pembayaran: Gagal move_uploaded_file untuk " . $kode_pemesanan);
    }
} else {
    set_flash_message('danger', 'Metode permintaan tidak valid.');
}
redirect('user/detail_pemesanan.php?kode=' . e($kode_pemesanan));
exit;
