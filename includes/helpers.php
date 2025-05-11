<?php
// File: C:\xampp\htdocs\Cilengkrang-Web-Wisata\includes\helpers.php

function redirect($url)
{
    global $base_url; // Pastikan $base_url sudah terdefinisi (dari config.php)

    // Jika $base_url tidak ada atau kosong, coba buat path relatif dasar
    if (empty($base_url)) {
        // Ini adalah fallback kasar dan mungkin perlu disesuaikan tergantung struktur Anda
        // dan dari mana helper ini dipanggil.
        // Untuk struktur Anda, ../ akan naik satu level.
        // Jika dipanggil dari includes/, maka ../ akan ke root proyek.
        // Namun, redirect dari helper sebaiknya selalu mengandalkan $base_url yang sudah fix.
        // error_log("Peringatan: \$base_url tidak terdefinisi saat memanggil redirect() untuk URL: " . $url);
        // $base_url = '../'; // Atau sesuaikan
    }

    if (!preg_match("~^(?:f|ht)tps?://~i", $url)) {
        // Pastikan $base_url diakhiri dengan slash dan $url tidak diawali slash
        $processed_base_url = isset($base_url) ? rtrim($base_url, '/') . '/' : '';
        $url = $processed_base_url . ltrim($url, '/');
    }

    if (!headers_sent()) {
        header("Location: " . $url);
        exit;
    } else {
        // Jika header sudah terkirim, log error dan mungkin coba redirect via JS (fallback)
        $backtrace = debug_backtrace();
        $caller = array_shift($backtrace);
        error_log("Gagal melakukan redirect PHP ke '$url' karena headers sudah terkirim. Output dimulai di: " . $caller['file'] . ' on line ' . $caller['line']);

        // Fallback redirect menggunakan JavaScript jika memungkinkan (meskipun tidak ideal)
        echo "<script type='text/javascript'>window.location.href='" . addslashes($url) . "';</script>";
        echo "<noscript><meta http-equiv='refresh' content='0;url=" . addslashes($url) . "'></noscript>";
        exit; // Tetap exit
    }
}

function is_post()
{
    return $_SERVER['REQUEST_METHOD'] === 'POST';
}

function e($string)
{
    return htmlspecialchars((string)$string, ENT_QUOTES, 'UTF-8');
}

function input($key, $default = null, $method = null) // Ubah $method default ke null
{
    // Jika method tidak dispesifikkan, coba deteksi dari $_SERVER['REQUEST_METHOD']
    // atau default ke POST jika itu adalah kasus penggunaan utama Anda.
    // Namun, lebih eksplisit lebih baik. Untuk sekarang, kita buat prioritas.
    if ($method === null) {
        if (isset($_POST[$key])) {
            return $_POST[$key];
        }
        if (isset($_GET[$key])) {
            return $_GET[$key];
        }
        return $default;
    }

    switch (strtolower($method)) {
        case 'get':
            return isset($_GET[$key]) ? $_GET[$key] : $default;
        case 'post':
            return isset($_POST[$key]) ? $_POST[$key] : $default;
        case 'request': // $_REQUEST bisa berisi GET, POST, COOKIE - gunakan dengan hati-hati
            return isset($_REQUEST[$key]) ? $_REQUEST[$key] : $default;
        default:
            return $default;
    }
}

// HILANGKAN TAG PHP PENUTUP JIKA FILE INI HANYA BERISI KODE PHP