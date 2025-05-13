<?php
// File: C:\xampp\htdocs\Cilengkrang-Web-Wisata\includes\flash_message.php

/**
 * Mengatur pesan flash yang akan disimpan di session.
 * Pesan ini akan ditampilkan satu kali pada request berikutnya.
 *
 * PENTING: Diasumsikan config.php sudah memanggil session_start()
 *          sebelum fungsi ini digunakan.
 *
 * @param string $type Tipe pesan (misalnya, 'success', 'danger', 'warning', 'info').
 *                     Ini akan digunakan sebagai bagian dari kelas CSS alert Bootstrap.
 * @param string $message Isi pesan yang akan ditampilkan.
 */
function set_flash_message($type, $message)
{
    if (session_status() == PHP_SESSION_NONE) {
        // Ini seharusnya tidak terjadi jika config.php sudah memulai session.
        // Mencoba memulai session di sini bisa berisiko jika header sudah terkirim.
        // Lebih baik hanya log dan tidak melanjutkan jika session kritis.
        error_log("KRITIKAL di flash_message.php: Session belum dimulai saat set_flash_message() dipanggil. Pesan flash mungkin tidak tersimpan.");
        // Pertimbangkan untuk tidak mencoba session_start() di sini jika config.php adalah satu-satunya pengelola session.
        // Jika Anda tetap ingin ada fallback:
        /*
        if (!headers_sent()) {
            session_start();
        } else {
            error_log("ERROR KRITIKAL di flash_message.php: Tidak bisa memulai session darurat karena header sudah terkirim sebelum set_flash_message.");
            return; // Tidak bisa menyimpan flash message
        }
        */
        return; // Hentikan jika session tidak aktif, biarkan config.php yang handle
    }

    // Normalisasi tipe dan simpan pesan mentah (akan di-escape saat ditampilkan)
    $_SESSION['flash_message'] = [
        'type' => strtolower(trim($type)),
        'message' => $message
    ];
}

/**
 * Menampilkan pesan flash jika ada, lalu menghapusnya dari session.
 * Fungsi ini mengembalikan string HTML untuk alert Bootstrap.
 * Menggunakan helper e() jika tersedia untuk escaping output.
 *
 * PENTING: Diasumsikan config.php sudah memanggil session_start() dan
 *          memuat helpers.php (untuk fungsi e()) sebelum fungsi ini digunakan.
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

        // Gunakan helper e() jika ada, jika tidak gunakan htmlspecialchars langsung
        // Ini adalah fallback yang baik jika helpers.php tidak dimuat karena suatu alasan,
        // meskipun idealnya selalu dimuat.
        $escaper_func = function_exists('e') ? 'e' : function ($string_to_escape) {
            return htmlspecialchars((string)$string_to_escape, ENT_QUOTES, 'UTF-8');
        };

        $type_for_class = $escaper_func($flash['type']); // Untuk nama kelas CSS
        $message = $escaper_func($flash['message']);    // Untuk konten pesan

        $icon_html = '';
        $alert_class = 'alert-' . $type_for_class; // Default kelas alert

        // Menentukan ikon dan kelas alert yang lebih spesifik berdasarkan tipe pesan asli (sebelum di-escape)
        switch ($flash['type']) { // Gunakan $flash['type'] yang asli (belum di-escape) untuk switch
            case 'success':
                $icon_html = '<i class="fas fa-check-circle me-2"></i>';
                break;
            case 'danger':
            case 'error': // Alias untuk 'error'
                $icon_html = '<i class="fas fa-times-circle me-2"></i>';
                $alert_class = 'alert-danger'; // Pastikan kelas danger jika alias error digunakan
                break;
            case 'warning':
                $icon_html = '<i class="fas fa-exclamation-triangle me-2"></i>';
                break;
            case 'info':
            case 'notice': // Alias untuk 'notice'
                $icon_html = '<i class="fas fa-info-circle me-2"></i>';
                $alert_class = 'alert-info'; // Pastikan kelas info jika alias notice digunakan
                break;
            default: // Untuk tipe yang tidak dikenali
                $icon_html = '<i class="fas fa-bell me-2"></i>'; // Ikon default
                $alert_class = 'alert-secondary'; // Kelas alert default
                break;
        }

        // Margin bawah dihilangkan (mb-0) karena biasanya flash message diletakkan
        // di dalam kontainer yang sudah memiliki margin, atau bisa disesuaikan oleh pemanggil.
        // Bisa juga diganti mb-3 atau mb-4 jika diinginkan ada spasi bawah default.
        return "<div class='alert {$alert_class} alert-dismissible fade show mb-3' role='alert'>
                    {$icon_html}{$message}
                    <button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button>
                </div>";
    }
    return ''; // Kembalikan string kosong jika tidak ada pesan
}

// Tidak perlu tag PHP penutup jika file ini hanya berisi kode PHP