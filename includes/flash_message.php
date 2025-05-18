<?php
// File: C:\xampp\htdocs\Cilengkrang-Web-Wisata\includes\flash_message.php

/**
 * Mengatur pesan flash yang akan disimpan di session.
 * Pesan ini akan ditampilkan satu kali pada request berikutnya.
 *
 * PENTING: Diasumsikan config.php sudah memanggil session_start()
 *          sebelum fungsi ini digunakan.
 *          PESAN YANG DIKIRIM KE FUNGSI INI DIASUMSIKAN SUDAH AMAN UNTUK DITAMPILKAN
 *          (misalnya, sudah di-escape jika mengandung input pengguna yang tidak tepercaya,
 *           kecuali untuk karakter seperti '&' yang memang ingin ditampilkan apa adanya).
 *
 * @param string $type Tipe pesan (misalnya, 'success', 'danger', 'warning', 'info').
 *                     Ini akan digunakan sebagai bagian dari kelas CSS alert Bootstrap.
 * @param string $message Isi pesan yang akan ditampilkan.
 */
function set_flash_message($type, $message)
{
    if (session_status() == PHP_SESSION_NONE) {
        error_log("KRITIKAL di flash_message.php: Session belum dimulai saat set_flash_message() dipanggil. Pesan flash mungkin tidak tersimpan.");
        // Tidak mencoba session_start() di sini, biarkan config.php yang mengelola.
        return;
    }

    // Simpan pesan dan tipe mentah
    $_SESSION['flash_message'] = [
        'type' => strtolower(trim($type)),
        'message' => $message
    ];
}

/**
 * Menampilkan pesan flash jika ada, lalu menghapusnya dari session.
 * Fungsi ini mengembalikan string HTML untuk alert Bootstrap.
 * TIPE PESAN AKAN DI-ESCAPE untuk keamanan kelas CSS.
 * ISI PESAN (message) AKAN DITAMPILKAN APA ADANYA (RAW) - pastikan aman saat set_flash_message.
 *
 * @return string String HTML untuk alert, atau string kosong jika tidak ada pesan flash.
 */
function display_flash_message()
{
    if (session_status() == PHP_SESSION_NONE) {
        error_log("PERINGATAN di flash_message.php: Session belum dimulai saat display_flash_message() dipanggil. Pesan flash tidak dapat ditampilkan.");
        return ''; // Tidak bisa menampilkan jika session tidak ada
    }

    if (isset($_SESSION['flash_message'])) {
        $flash = $_SESSION['flash_message'];
        unset($_SESSION['flash_message']); // Hapus pesan setelah diambil agar hanya tampil sekali

        // Escape tipe pesan untuk digunakan dalam nama kelas CSS (keamanan)
        $type_for_class = function_exists('e') ? e($flash['type']) : htmlspecialchars((string)$flash['type'], ENT_QUOTES, 'UTF-8');

        // Pesan ($flash['message']) akan ditampilkan apa adanya.
        // Pastikan pesan ini sudah aman saat Anda memanggil set_flash_message().
        // Jika Anda ingin selalu aman dan membiarkan '&' menjadi '&', maka lakukan:
        // $message_to_display = function_exists('e') ? e($flash['message']) : htmlspecialchars((string)$flash['message'], ENT_QUOTES, 'UTF-8');
        // Namun, karena Anda ingin '&' tampil apa adanya, kita gunakan langsung:
        $message_to_display = $flash['message'];

        $icon_html = '';
        $alert_class = 'alert-' . $type_for_class; // Kelas alert default

        // Menentukan ikon berdasarkan tipe pesan asli
        switch ($flash['type']) {
            case 'success':
                $icon_html = '<i class="fas fa-check-circle me-2"></i>';
                break;
            case 'danger':
            case 'error':
                $icon_html = '<i class="fas fa-times-circle me-2"></i>';
                $alert_class = 'alert-danger';
                break;
            case 'warning':
                $icon_html = '<i class="fas fa-exclamation-triangle me-2"></i>';
                break;
            case 'info':
            case 'notice':
                $icon_html = '<i class="fas fa-info-circle me-2"></i>';
                $alert_class = 'alert-info';
                break;
            default:
                $icon_html = '<i class="fas fa-bell me-2"></i>';
                $alert_class = 'alert-secondary';
                break;
        }

        return "<div class='alert {$alert_class} alert-dismissible fade show mb-3' role='alert'>
                    {$icon_html}{$message_to_display}
                    <button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button>
                </div>";
    }
    return ''; // Kembalikan string kosong jika tidak ada pesan
}

// Tidak perlu tag PHP penutup