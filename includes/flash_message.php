<?php
// File: C:\xampp\htdocs\Cilengkrang-Web-Wisata\includes\flash_message.php

/**
 * Mengatur pesan flash yang akan disimpan di session.
 * Pesan ini akan ditampilkan satu kali pada request berikutnya.
 *
 * @param string $type Tipe pesan (misalnya, 'success', 'danger', 'warning', 'info').
 *                     Ini akan digunakan sebagai bagian dari kelas CSS alert Bootstrap.
 * @param string $message Isi pesan yang akan ditampilkan.
 */
function set_flash_message($type, $message)
{
    if (session_status() == PHP_SESSION_NONE) {
        // Ini seharusnya tidak terjadi jika config.php sudah memulai session
        error_log("Peringatan: Session belum dimulai saat set_flash_message() dipanggil.");
        session_start();
    }
    // Pesan disimpan mentah, akan di-escape saat ditampilkan
    $_SESSION['flash_message'] = [
        'type' => $type,
        'message' => $message
    ];
}

/**
 * Menampilkan pesan flash jika ada, lalu menghapusnya dari session.
 * Fungsi ini mengembalikan string HTML untuk alert Bootstrap.
 * Menggunakan helper e() jika tersedia untuk escaping.
 *
 * @return string String HTML untuk alert, atau string kosong jika tidak ada pesan flash.
 */
function display_flash_message()
{
    if (isset($_SESSION['flash_message'])) {
        $flash = $_SESSION['flash_message'];
        unset($_SESSION['flash_message']); // Hapus pesan setelah diambil

        // Menggunakan helper e() jika ada, jika tidak gunakan htmlspecialchars langsung
        $type = function_exists('e') ? e($flash['type']) : htmlspecialchars($flash['type'], ENT_QUOTES, 'UTF-8');
        $message = function_exists('e') ? e($flash['message']) : htmlspecialchars($flash['message'], ENT_QUOTES, 'UTF-8');

        $icon_html = '';
        // Menentukan ikon berdasarkan tipe pesan
        switch ($flash['type']) { // Gunakan $flash['type'] asli untuk switch
            case 'success':
                $icon_html = '<i class="fas fa-check-circle me-2"></i>';
                break;
            case 'danger':
            case 'error': // Bisa tambahkan alias untuk tipe 'error'
                $icon_html = '<i class="fas fa-times-circle me-2"></i>';
                break;
            case 'warning':
                $icon_html = '<i class="fas fa-exclamation-triangle me-2"></i>';
                break;
            case 'info':
            case 'notice': // Alias untuk tipe 'notice'
                $icon_html = '<i class="fas fa-info-circle me-2"></i>';
                break;
        }

        return "<div class='alert alert-{$type} alert-dismissible fade show mb-0' role='alert'>
                    {$icon_html}{$message}
                    <button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button>
                </div>";
    }
    return ''; // Kembalikan string kosong jika tidak ada pesan
}

// Pastikan session_start() sudah dipanggil di file utama (config.php)
// sebelum fungsi-fungsi ini digunakan.
// Tidak perlu tag PHP penutup jika file ini hanya berisi kode PHP
// 
