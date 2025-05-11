<?php
// File: C:\xampp\htdocs\Cilengkrang-Web-Wisata\includes\helpers.php

/**
 * Mengarahkan pengguna ke URL lain.
 * @param string $url URL tujuan, bisa relatif terhadap $base_url atau URL absolut.
 */
function redirect($url)
{
    global $base_url; // $base_url harus sudah terdefinisi di config.php

    if (empty($base_url)) {
        error_log("FATAL ERROR: \$base_url tidak terdefinisi saat memanggil redirect() untuk URL: " . $url . ". Pastikan config.php dimuat dengan benar sebelum helpers.php.");
        // Sebagai fallback paling akhir, coba redirect relatif jika URL tidak absolut.
        // Ini berisiko dan sebaiknya tidak diandalkan.
        if (!preg_match("~^(?:f|ht)tps?://~i", $url)) {
            // Coba buat path yang sangat dasar jika $base_url tidak ada
            // Ini akan sangat bergantung dari mana file ini dipanggil.
            // Untuk struktur Anda, jika helpers dipanggil dari root file, $url saja mungkin cukup
            // jika $url adalah 'admin/login.php'.
            // Jika dipanggil dari dalam folder, Anda perlu '../'
            // Solusi terbaik: pastikan $base_url SELALU ada.
        }
    }

    // Jika URL yang diberikan bukan URL absolut (tidak diawali http/https/ftp)
    // maka gabungkan dengan $base_url.
    if (!preg_match("~^(?:f|ht)tps?://~i", $url)) {
        // $base_url sudah dijamin diakhiri slash oleh config.php
        $processed_base_url = $base_url ?? ''; // Jika $base_url tidak ada, gunakan string kosong
        $url = $processed_base_url . ltrim($url, '/');
    }

    if (!headers_sent()) {
        header("Location: " . $url);
        exit;
    } else {
        $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1);
        $caller = $backtrace[0] ?? ['file' => 'unknown', 'line' => 'unknown'];
        error_log("Peringatan: Gagal melakukan redirect PHP ke '$url' karena headers sudah terkirim. Output kemungkinan dimulai di: " . $caller['file'] . ' pada baris ' . $caller['line']);

        // Fallback redirect menggunakan JavaScript
        echo "<script type='text/javascript'>console.warn('Redirect PHP gagal, mencoba redirect via JavaScript ke: " . addslashes($url) . "'); window.location.href='" . addslashes($url) . "';</script>";
        echo "<noscript><meta http-equiv='refresh' content='0;url=" . addslashes($url) . "'></noscript>";
        exit; // Tetap hentikan eksekusi skrip
    }
}

/**
 * Memeriksa apakah metode request adalah POST.
 * @return bool True jika POST, false jika tidak.
 */
function is_post()
{
    return $_SERVER['REQUEST_METHOD'] === 'POST';
}

/**
 * Melakukan htmlspecialchars pada string untuk mencegah XSS.
 * @param string|null $string String yang akan di-escape. Konversi ke string jika null.
 * @return string String yang sudah di-escape.
 */
function e($string)
{
    // Mengkonversi nilai null atau tipe lain ke string sebelum htmlspecialchars
    return htmlspecialchars((string)$string, ENT_QUOTES, 'UTF-8');
}

/**
 * Mengambil nilai dari array superglobal GET, POST, atau REQUEST dengan aman.
 * @param string $key Kunci array.
 * @param mixed $default Nilai default jika kunci tidak ditemukan.
 * @param string|null $method Metode HTTP ('get', 'post'). Jika null, akan mencoba POST lalu GET.
 * @return mixed Nilai dari input atau nilai default.
 */
function input($key, $default = null, $method = null)
{
    $method_to_check = $method ? strtolower($method) : null;

    if ($method_to_check === 'post' || ($method_to_check === null && isset($_POST[$key]))) {
        return $_POST[$key];
    }
    if ($method_to_check === 'get' || ($method_to_check === null && isset($_GET[$key]))) {
        return $_GET[$key];
    }
    // Hindari $_REQUEST jika tidak benar-benar dibutuhkan karena masalah keamanan/prediktabilitas
    // if ($method_to_check === 'request' && isset($_REQUEST[$key])) {
    //     return $_REQUEST[$key];
    // }
    if ($method_to_check && !in_array($method_to_check, ['post', 'get'])) {
        error_log("Fungsi input() dipanggil dengan method tidak dikenal: " . $method);
    }
    return $default;
}

/**
 * Membuat ringkasan teks dengan batas jumlah kata atau karakter.
 * @param string $text Teks asli.
 * @param int $limit Batas (jumlah kata atau karakter).
 * @param string $type 'words' atau 'chars'.
 * @param string $ellipsis Tanda elipsis yang ditambahkan.
 * @return string Teks ringkasan.
 */
function summary($text, $limit = 100, $type = 'chars', $ellipsis = '...')
{
    $cleaned_text = strip_tags((string)$text);
    if ($type === 'words') {
        $words = explode(' ', $cleaned_text);
        if (count($words) > $limit) {
            return implode(' ', array_slice($words, 0, $limit)) . $ellipsis;
        }
    } else { // default ke karakter
        if (mb_strlen($cleaned_text) > $limit) {
            return mb_substr($cleaned_text, 0, $limit) . $ellipsis;
        }
    }
    return $cleaned_text;
}

// Anda bisa menambahkan helper lain di sini
// seperti format tanggal, format angka, dll.

// Tidak perlu tag PHP penutup jika file ini hanya berisi kode PHP
// 
