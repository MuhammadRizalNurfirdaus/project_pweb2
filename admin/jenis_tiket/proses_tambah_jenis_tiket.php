<?php
// File: C:\xampp\htdocs\Cilengkrang-Web-Wisata\admin\jenis_tiket\proses_tambah_jenis_tiket.php

// 1. Sertakan config.php pertama kali
if (!require_once __DIR__ . '/../../config/config.php') {
    http_response_code(503);
    error_log("FATAL ERROR di proses_tambah_jenis_tiket.php: Gagal memuat config.php.");
    exit("Kesalahan konfigurasi server.");
}

// 2. Pastikan hanya admin yang bisa akses
require_admin();

// 3. Sertakan JenisTiketController
// Diasumsikan config.php sudah memuat Controller atau Anda menggunakan autoloader.
// Jika tidak, require_once di sini.
$controllerPath = CONTROLLERS_PATH . '/JenisTiketController.php';
if (!class_exists('JenisTiketController')) { // Cek dulu apakah sudah dimuat
    if (file_exists($controllerPath)) {
        require_once $controllerPath;
    } else {
        error_log("FATAL ERROR di proses_tambah_jenis_tiket.php: File JenisTiketController.php tidak ditemukan di " . $controllerPath);
        set_flash_message('danger', 'Kesalahan sistem: Komponen inti tidak dapat dimuat.');
        redirect(ADMIN_URL . '/dashboard.php');
        exit;
    }
}

// 4. Hanya proses jika metode POST
if (!is_post()) {
    set_flash_message('danger', 'Akses tidak sah: Metode permintaan tidak diizinkan.');
    redirect(ADMIN_URL . '/jenis_tiket/kelola_jenis_tiket.php'); // Redirect ke halaman kelola jika bukan POST
    exit;
}

// 5. Validasi CSRF Token
if (!function_exists('verify_csrf_token') || !verify_csrf_token(null, true)) { // verifikasi dan unset token
    set_flash_message('danger', 'Permintaan tidak valid atau token CSRF salah/kadaluarsa. Silakan coba lagi.');
    redirect(ADMIN_URL . '/jenis_tiket/tambah_jenis_tiket.php'); // Kembali ke form tambah
    exit;
}

// 6. Ambil data dari form menggunakan fungsi input()
$nama_layanan = input('nama_layanan_display', '', 'post');
$tipe_hari = input('tipe_hari', '', 'post');
$harga_input = input('harga', '', 'post'); // Ambil sebagai string untuk validasi
$deskripsi = input('deskripsi', null, 'post');
$wisata_id_input = input('wisata_id', null, 'post');
// Untuk checkbox, jika tidak dicentang, $_POST['aktif'] tidak akan dikirim.
// Hidden input dengan value "0" adalah fallback yang baik.
// Atau, cara lain:
$aktif = isset($_POST['aktif']) && $_POST['aktif'] == '1' ? 1 : 0;

// 7. Simpan data ke session untuk repopulasi jika ada error redirect
$session_form_data_key = 'flash_form_data_tambah_jenis_tiket';
$_SESSION[$session_form_data_key] = [
    'nama_layanan_display' => $nama_layanan,
    'tipe_hari' => $tipe_hari,
    'harga' => $harga_input,
    'deskripsi' => $deskripsi,
    'aktif' => $aktif,
    'wisata_id' => $wisata_id_input
];

// 8. Validasi Input Dasar di Sini (sebelum memanggil Controller)
if (empty($nama_layanan) || empty($tipe_hari) || $harga_input === '' || !is_numeric($harga_input) || (float)$harga_input < 0) {
    set_flash_message('danger', 'Nama layanan, tipe hari, dan harga (angka valid non-negatif) wajib diisi.');
    redirect(ADMIN_URL . '/jenis_tiket/tambah_jenis_tiket.php');
    exit;
}
// Anda bisa menambahkan validasi lain di sini jika perlu,
// misalnya untuk format tipe_hari jika tidak menggunakan select.

// 9. Siapkan data untuk dikirim ke Controller
$data_to_controller = [
    'nama_layanan_display' => $nama_layanan,
    'tipe_hari' => $tipe_hari,
    'harga' => (float)$harga_input, // Kirim sebagai float ke Controller
    'deskripsi' => $deskripsi,
    'aktif' => $aktif,
    'wisata_id' => !empty($wisata_id_input) ? (int)$wisata_id_input : null
];

// 10. Panggil metode create dari JenisTiketController
if (!method_exists('JenisTiketController', 'create')) {
    error_log("FATAL ERROR di proses_tambah_jenis_tiket.php: Metode JenisTiketController::create() tidak ditemukan.");
    set_flash_message('danger', 'Kesalahan sistem: Fungsi penambahan jenis tiket tidak tersedia.');
    redirect(ADMIN_URL . '/jenis_tiket/tambah_jenis_tiket.php');
    exit;
}

$result = JenisTiketController::create($data_to_controller);

if (is_numeric($result) && $result > 0) { // Jika Controller mengembalikan ID baru
    unset($_SESSION[$session_form_data_key]); // Hapus data form dari session jika berhasil
    set_flash_message('success', 'Jenis tiket "' . e($nama_layanan) . ' - ' . e($tipe_hari) . '" berhasil ditambahkan.');
    redirect(ADMIN_URL . '/jenis_tiket/kelola_jenis_tiket.php');
} elseif (is_string($result)) { // Jika Controller mengembalikan string kode error
    // Pesan flash kemungkinan sudah di-set oleh Controller jika ia mengembalikan string error
    // Jika tidak, Anda bisa memetakan kode error ke pesan di sini.
    if (!isset($_SESSION['flash_message'])) { // Hanya set jika controller belum
        $error_message = 'Gagal menambahkan jenis tiket: ';
        switch ($result) {
            case 'duplicate':
                $error_message .= 'Kombinasi nama layanan, tipe hari, dan destinasi sudah ada.';
                break;
            case 'invalid_input':
                $error_message .= 'Data yang dimasukkan tidak valid.';
                break;
            // Tambahkan kasus lain jika ada
            default:
                $error_message .= 'Terjadi kesalahan yang tidak diketahui.';
        }
        set_flash_message('danger', $error_message);
    }
    redirect(ADMIN_URL . '/jenis_tiket/tambah_jenis_tiket.php');
} else { // Jika Controller mengembalikan false (error umum/DB)
    // Pesan error kemungkinan sudah di-set oleh Controller atau Model
    if (!isset($_SESSION['flash_message'])) {
        set_flash_message('danger', 'Gagal menambahkan jenis tiket karena kesalahan internal. Silakan coba lagi.');
    }
    redirect(ADMIN_URL . '/jenis_tiket/tambah_jenis_tiket.php');
}
exit; // Pastikan exit setelah semua kemungkinan redirect
