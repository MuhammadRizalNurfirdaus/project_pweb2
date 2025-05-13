<?php
// File: C:\xampp\htdocs\Cilengkrang-Web-Wisata\admin\pembayaran\proses_update_status_pembayaran.php

// LANGKAH 1: Muat Konfigurasi Utama dan Pemeriksaan Dasar
// config.php harus sudah memuat helpers.php (termasuk redirect() dan set_flash_message()),
// dan koneksi database ($conn).
if (!require_once __DIR__ . '/../../config/config.php') {
    // Jika config.php gagal dimuat, aplikasi tidak bisa berjalan.
    // Tampilkan pesan error sederhana dan hentikan eksekusi.
    http_response_code(503); // Service Unavailable
    error_log("FATAL ERROR di proses_update_status_pembayaran.php: Gagal memuat config.php.");
    exit("Kesalahan konfigurasi server. Aplikasi tidak dapat melanjutkan. Silakan hubungi administrator.");
}

// LANGKAH 2: Otentikasi Admin
// Pastikan fungsi require_admin() sudah terdefinisi dan berfungsi dengan benar.
// Fungsi ini biasanya akan melakukan redirect jika pengguna bukan admin.
try {
    require_admin();
} catch (Exception $e) {
    // Tangani jika require_admin() melempar exception (jarang terjadi jika dirancang untuk redirect)
    error_log("ERROR saat otentikasi admin di proses_update_status_pembayaran.php: " . $e->getMessage());
    set_flash_message('danger', 'Otentikasi gagal atau sesi tidak valid.');
    redirect('auth/login.php'); // Redirect ke halaman login jika otentikasi gagal
    exit; // Pastikan skrip berhenti setelah redirect
}

// LANGKAH 3: Muat Controller yang Diperlukan
// Pastikan path ke controller benar dan file controller tidak memiliki error.
if (!file_exists(__DIR__ . '/../../controllers/PembayaranController.php')) {
    error_log("FATAL ERROR di proses_update_status_pembayaran.php: File PembayaranController.php tidak ditemukan.");
    set_flash_message('danger', 'Kesalahan sistem: Komponen pembayaran tidak ditemukan.');
    redirect('admin/dashboard.php'); // Redirect ke dashboard jika komponen penting hilang
    exit;
}
require_once __DIR__ . '/../../controllers/PembayaranController.php';

// Definisikan path redirect default jika terjadi error atau proses selesai
// Ini adalah path RELATIF dari BASE_URL
$default_redirect_path = 'admin/pembayaran/kelola_pembayaran.php';
// Path redirect jika update berhasil dan kita ingin kembali ke detail pembayaran (jika ada ID)
$detail_redirect_path_template = 'admin/pembayaran/detail_pembayaran.php?id=';


// LANGKAH 4: Proses Data POST
if (!is_post()) {
    // Jika bukan metode POST, ini adalah akses yang tidak sah atau salah.
    set_flash_message('warning', 'Akses tidak diizinkan. Metode request harus POST.');
    redirect($default_redirect_path);
    exit;
}

if (!isset($_POST['update_status_pembayaran_submit'])) {
    // Jika tombol submit spesifik tidak ada, kemungkinan form tidak dikirim dengan benar.
    set_flash_message('warning', 'Permintaan tidak valid atau form tidak lengkap.');
    redirect($default_redirect_path);
    exit;
}

// Ambil dan validasi input dari form
// Pastikan nama input di form (name="id_pembayaran", name="new_status") sesuai.
$id_pembayaran = isset($_POST['id_pembayaran']) ? filter_var($_POST['id_pembayaran'], FILTER_VALIDATE_INT) : null;
$new_status = isset($_POST['new_status']) ? trim($_POST['new_status']) : null;
// Anda mungkin juga ingin mengambil ID Pemesanan Tiket jika ada dan relevan untuk redirect
// $id_pemesanan_tiket_terkait = isset($_POST['id_pemesanan_tiket']) ? filter_var($_POST['id_pemesanan_tiket'], FILTER_VALIDATE_INT) : null;

// Validasi data yang diterima
if ($id_pembayaran === null || $id_pembayaran === false || $id_pembayaran <= 0) {
    set_flash_message('danger', 'ID Pembayaran tidak valid atau tidak disertakan.');
    redirect($default_redirect_path);
    exit;
}

if (empty($new_status)) {
    set_flash_message('danger', 'Status baru pembayaran tidak boleh kosong.');
    // Redirect kembali ke halaman detail pembayaran jika memungkinkan, atau ke kelola.
    // Jika Anda memiliki $id_pembayaran yang valid, Anda bisa redirect ke detailnya lagi.
    redirect($detail_redirect_path_template . $id_pembayaran);
    exit;
}

// LANGKAH 5: Panggil Controller untuk Update Status
// Asumsi: PembayaranController::updateStatusPembayaranDanTiket() akan mengembalikan true jika sukses, false jika gagal.
//         Controller/Model juga diharapkan sudah mengatur flash message yang lebih spesifik jika terjadi error.
$update_berhasil = false;
try {
    // Anda bisa mengirimkan array $details jika ada informasi tambahan yang perlu disimpan
    // Contoh: $details = ['catatan_admin' => $_POST['catatan_admin'] ?? null];
    // Untuk saat ini, kita hanya mengirim ID dan status.
    $update_berhasil = PembayaranController::updateStatusPembayaranDanTiket($id_pembayaran, $new_status);
} catch (Exception $e) {
    // Tangani jika controller melempar exception (praktik baik untuk controller)
    error_log("EXCEPTION saat update status pembayaran (ID: {$id_pembayaran}): " . $e->getMessage());
    set_flash_message('danger', 'Terjadi kesalahan sistem saat memproses permintaan. Error: ' . $e->getMessage());
    // Tidak perlu set $update_berhasil = false; karena sudah defaultnya.
}


// LANGKAH 6: Atur Pesan Feedback dan Tentukan Path Redirect Final
$final_redirect_path = $default_redirect_path; // Default redirect ke halaman kelola

if ($update_berhasil) {
    // Jika controller tidak mengatur flash message sukses, kita bisa set di sini.
    if (!isset($_SESSION['flash_message'])) {
        set_flash_message('success', 'Status pembayaran untuk ID #' . htmlspecialchars($id_pembayaran) . ' berhasil diperbarui menjadi "' . htmlspecialchars(ucfirst($new_status)) . '".');
    }
    // Setelah sukses, mungkin lebih baik redirect kembali ke halaman detail pembayaran tersebut
    $final_redirect_path = $detail_redirect_path_template . $id_pembayaran;
} else {
    // Jika controller tidak mengatur flash message error, kita set di sini.
    if (!isset($_SESSION['flash_message'])) {
        set_flash_message('danger', 'Gagal memperbarui status pembayaran untuk ID #' . htmlspecialchars($id_pembayaran) . '. Silakan coba lagi atau hubungi administrator.');
    }
    // Jika gagal, bisa redirect ke detail atau tetap di kelola, tergantung preferensi
    $final_redirect_path = $detail_redirect_path_template . $id_pembayaran; // Atau $default_redirect_path
}


// LANGKAH 7: Lakukan Redirect
// Pastikan tidak ada output lain yang sudah terkirim ke browser sebelum header Location.
if (headers_sent($file, $line)) {
    error_log("PERINGATAN di proses_update_status_pembayaran.php: Headers sudah terkirim di file '{$file}' pada baris {$line}. Redirect otomatis ke '{$final_redirect_path}' mungkin gagal.");
    // Sediakan link manual sebagai fallback jika redirect otomatis gagal
    // Ini sangat berguna saat debugging.
    $fallback_url = BASE_URL . '/' . ltrim($final_redirect_path, '/');
    echo "Proses selesai. Jika Anda tidak diarahkan secara otomatis, silakan <a href=\"" . htmlspecialchars($fallback_url, ENT_QUOTES, 'UTF-8') . "\">klik di sini untuk melanjutkan</a>.";
    exit;
} else {
    // Lakukan redirect menggunakan path relatif yang sudah ditentukan.
    // Fungsi redirect() di helpers.php akan menggabungkannya dengan BASE_URL.
    error_log("INFO di proses_update_status_pembayaran.php: Akan melakukan redirect ke path relatif: " . $final_redirect_path);
    redirect($final_redirect_path);
    exit; // Pastikan skrip berhenti setelah memanggil redirect.
}
