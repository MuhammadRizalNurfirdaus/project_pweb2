<?php
// File: C:\xampp\htdocs\Cilengkrang-Web-Wisata\includes\helpers.php

/**
 * File ini berisi fungsi-fungsi bantuan umum yang digunakan di seluruh aplikasi.
 * Pastikan file config.php dimuat sebelum file ini jika fungsi di sini
 * bergantung pada konstanta atau variabel dari config.php (misalnya BASE_URL untuk redirect).
 */

if (!function_exists('e')) {
    function e($string)
    { /* ... kode Anda sudah baik ... */
        if ($string === null) {
            return '';
        }
        return htmlspecialchars((string)$string, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

if (!function_exists('redirect')) {
    /**
     * Mengarahkan pengguna ke URL lain dalam aplikasi.
     * @param string $path Path tujuan relatif terhadap BASE_URL (misal: 'auth/login.php') atau URL absolut.
     */
    function redirect($path)
    {
        $path_str = (string)$path;
        $location = '';
        $timestamp_redirect = date("Y-m-d H:i:s"); // Untuk logging

        error_log("[$timestamp_redirect] REDIRECT HELPER: Menerima path awal = " . $path_str);

        if (preg_match('#^https?://#i', $path_str)) {
            $location = $path_str;
        } else {
            if (!defined('BASE_URL')) {
                $error_msg = "[$timestamp_redirect] REDIRECT FATAL ERROR: Konstanta BASE_URL tidak terdefinisi. Path relatif: " . e($path_str);
                error_log($error_msg);
                http_response_code(500);
                // Hindari output HTML di sini jika memungkinkan, biarkan error log yang utama
                exit("Kesalahan Konfigurasi Kritis: URL dasar aplikasi tidak terdefinisi.");
            }
            $base_url_val = BASE_URL;
            // Pembentukan URL yang lebih sederhana dan aman
            $location = rtrim($base_url_val, '/') . '/' . ltrim($path_str, '/');
            // Menghapus duplikasi slash (kecuali untuk http:// atau https://)
            $location = preg_replace('#(?<!:)/{2,}#', '/', $location);
        }

        error_log("[$timestamp_redirect] REDIRECT HELPER: Akan redirect ke Lokasi Final = " . $location);

        if (!headers_sent($file_header, $line_header)) {
            header('Location: ' . $location);
            error_log("[$timestamp_redirect] REDIRECT SUCCESS: Header Location terkirim ke: " . $location);
            exit; // Sangat penting untuk menghentikan eksekusi setelah header dikirim
        } else {
            // Jika headers sudah terkirim, redirect PHP tidak akan bekerja. Lakukan fallback.
            error_log("[$timestamp_redirect] REDIRECT PERINGATAN: Headers sudah terkirim. Output dimulai di {$file_header} pada baris {$line_header}. Fallback JS ke '{$location}'.");
            echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Redirecting...</title>";
            echo "<script type='text/javascript'>window.location.href = '" . addslashes($location) . "';</script>";
            echo "<noscript><meta http-equiv='refresh' content='0;url=" . e($location) . "'></noscript>";
            echo "</head><body><p>Sedang mengarahkan... Jika Anda tidak diarahkan secara otomatis dalam beberapa detik, silakan <a href=\"" . e($location) . "\">klik di sini</a>.</p></body></html>";
            exit; // Penting juga untuk menghentikan eksekusi setelah fallback
        }
    }
}
if (!function_exists('redirect_to_previous_or_default')) {
    function redirect_to_previous_or_default($default_path)
    { /* ... kode Anda sudah baik ... */
        $referer = $_SERVER['HTTP_REFERER'] ?? null;
        if ($referer && defined('BASE_URL')) {
            $base_url_host = parse_url(BASE_URL, PHP_URL_HOST);
            $referer_host = parse_url($referer, PHP_URL_HOST);
            if (strtolower($base_url_host ?? '') === strtolower($referer_host ?? '')) {
                redirect($referer);
                exit;
            }
        }
        redirect($default_path);
        exit;
    }
}

if (!function_exists('formatRupiah')) {
    function formatRupiah($angka)
    { /* ... kode Anda sudah baik ... */
        if ($angka === null || !is_numeric($angka) || trim((string)$angka) === '') return 'Rp 0';
        return "Rp " . number_format((float)$angka, 0, ',', '.');
    }
}

if (!function_exists('formatTanggalIndonesia')) {
    function formatTanggalIndonesia($tanggal_input, $dengan_waktu = false, $dengan_hari = false, $hari_singkat = false)
    {
        if (empty($tanggal_input) || (is_string($tanggal_input) && (trim($tanggal_input) === '0000-00-00' || trim($tanggal_input) === '0000-00-00 00:00:00'))) return '-';
        try {
            $app_timezone_str = date_default_timezone_get(); // Diambil dari config.php
            $app_timezone = new DateTimeZone($app_timezone_str);

            if ($tanggal_input instanceof DateTimeInterface) {
                $date_obj = $tanggal_input;
                if ($date_obj->getTimezone()->getName() !== $app_timezone->getName()) {
                    // Jika DateTimeImmutable, buat objek baru. Jika DateTime, set timezone.
                    $date_obj = ($date_obj instanceof DateTime) ? $date_obj->setTimezone($app_timezone) : new DateTime($date_obj->format('Y-m-d H:i:s'), $app_timezone);
                }
            } else {
                $date_obj = new DateTime((string)$tanggal_input, $app_timezone);
            }
        } catch (Exception $e) {
            error_log("Error di formatTanggalIndonesia(): " . $e->getMessage() . " untuk input: '" . e((string)$tanggal_input) . "'");
            return '-';
        }
        // ... sisa logika format Anda sudah baik ...
        $nama_hari_panjang_id = ['Sunday' => 'Minggu', 'Monday' => 'Senin', 'Tuesday' => 'Selasa', 'Wednesday' => 'Rabu', 'Thursday' => 'Kamis', 'Friday' => 'Jumat', 'Saturday' => 'Sabtu'];
        $nama_hari_singkat_id = ['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab'];
        $nama_bulan_id = [1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'];
        $format_indonesia = '';
        if ($dengan_hari) {
            $hari_inggris = $date_obj->format('l');
            $hari_id = $nama_hari_panjang_id[$hari_inggris] ?? $hari_inggris;
            if ($hari_singkat) {
                $hari_idx_php = (int)$date_obj->format('w');
                $hari_id = $nama_hari_singkat_id[$hari_idx_php] ?? $date_obj->format('D');
            }
            if (!empty($hari_id)) $format_indonesia .= $hari_id . ', ';
        }
        $tanggal = $date_obj->format('j');
        $bulan_idx = (int)$date_obj->format('n');
        $bulan_id = $nama_bulan_id[$bulan_idx] ?? $date_obj->format('F');
        $tahun = $date_obj->format('Y');
        $format_indonesia .= $tanggal . ' ' . $bulan_id . ' ' . $tahun;
        if ($dengan_waktu) {
            $original_time_part = $date_obj->format('H:i:s');
            if ($original_time_part !== '00:00:00' || (is_string($tanggal_input) && strpos($tanggal_input, ':') !== false)) {
                $format_indonesia .= ', ' . $date_obj->format('H:i');
            }
        }
        return $format_indonesia;
    }
}

if (!function_exists('is_post')) {
    function is_post()
    { /* ... kode Anda sudah baik ... */
        return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
    }
}
if (!function_exists('is_get')) {
    function is_get()
    { /* ... kode Anda sudah baik ... */
        return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET';
    }
}
if (!function_exists('input')) {
    function input($key, $default = null, $method = null)
    { /* ... kode Anda sudah baik ... */
        $source = null;
        $request_method_server = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        $method_lower = $method !== null ? strtolower((string)$method) : null;
        if ($method_lower === 'post' || ($method === null && $request_method_server === 'POST')) $source = $_POST;
        elseif ($method_lower === 'get' || ($method === null && $request_method_server === 'GET')) $source = $_GET;
        else return $default;
        return isset($source[$key]) ? (is_string($source[$key]) ? trim($source[$key]) : $source[$key]) : $default;
    }
}
if (!function_exists('excerpt')) {
    function excerpt($text, $limit = 100, $ellipsis = '...')
    { /* ... kode Anda sudah baik ... */
        if ($text === null || trim((string)$text) === '') return '';
        $text_clean = strip_tags((string)$text);
        if (mb_strlen($text_clean, 'UTF-8') > $limit) {
            $text_cut = mb_substr($text_clean, 0, $limit, 'UTF-8');
            $last_space = mb_strrpos($text_cut, ' ', 0, 'UTF-8');
            $text_final = ($last_space !== false && $last_space > ($limit / 2)) ? rtrim(mb_substr($text_cut, 0, $last_space, 'UTF-8')) : $text_cut;
            return $text_final . $ellipsis;
        }
        return $text_clean;
    }
}
if (!function_exists('is_valid_email')) {
    function is_valid_email($email)
    { /* ... kode Anda sudah baik ... */
        return filter_var((string)$email, FILTER_VALIDATE_EMAIL) !== false;
    }
}

if (!function_exists('generate_csrf_token')) {
    function generate_csrf_token()
    { /* ... kode Anda sudah baik ... */
        if (session_status() == PHP_SESSION_NONE) {
            error_log("KRITIKAL generate_csrf_token(): Session belum dimulai.");
            if (!headers_sent($f, $l)) session_start();
            else {
                error_log("Gagal memulai session di generate_csrf_token() krn headers terkirim dari {$f}:{$l}");
                return 'csrf_error';
            }
        }
        if (empty($_SESSION['csrf_token'])) {
            try {
                $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            } catch (Exception $e) {
                $_SESSION['csrf_token'] = md5(uniqid(microtime(true), true));
                error_log("Peringatan: random_bytes() gagal untuk CSRF: " . $e->getMessage());
            }
        }
        return $_SESSION['csrf_token'];
    }
}
if (!function_exists('generate_csrf_token_input')) {
    function generate_csrf_token_input($name = 'csrf_token')
    {
        return '<input type="hidden" name="' . e($name) . '" value="' . e(generate_csrf_token()) . '">';
    }
}
if (!function_exists('verify_csrf_token')) {
    function verify_csrf_token($name = 'csrf_token', $unset = true, $method = null)
    {
        if (session_status() == PHP_SESSION_NONE) {
            error_log("CSRF: Sesi belum ada.");
            return false;
        }
        $s_token = $_SESSION[$name] ?? null;
        $r_token = ($method === 'GET') ? ($_GET[$name] ?? null) : ($_POST[$name] ?? null);
        if (!$s_token || !$r_token) {
            error_log("CSRF: Token sesi/request hilang.");
            return false;
        }
        $valid = hash_equals((string)$s_token, (string)$r_token);
        if (!$valid) error_log("CSRF: Token mismatch.");
        if ($valid && $unset) unset($_SESSION[$name]);
        return $valid;
    }
}

if (!function_exists('getStatusBadgeClass')) {
    function getStatusBadgeClass($status)
    { /* ... kode Anda sudah baik ... */
        switch (strtolower(trim((string)$status))) {
            case 'success':
            case 'paid':
            case 'confirmed':
            case 'completed':
            case 'lunas':
            case 'aktif':
                return 'success';
            case 'pending':
            case 'waiting_payment':
            case 'awaiting_confirmation':
            case 'menunggu pembayaran':
            case 'menunggu konfirmasi':
                return 'warning text-dark';
            case 'failed':
            case 'expired':
            case 'cancelled':
            case 'refunded':
            case 'gagal':
            case 'kadaluarsa':
            case 'dibatalkan':
            case 'tidak aktif':
            case 'non-aktif':
            case 'diblokir':
                return 'danger';
            case 'info':
            case 'diproses':
                return 'info text-dark';
            default:
                return 'secondary';
        }
    }
}
if (!function_exists('getStatusBadgeClassHTML')) {
    function getStatusBadgeClassHTML($s, $d = 'Tidak Diketahui')
    {
        $c = strtolower(trim((string)$s));
        $t = !empty($c) ? ucfirst(str_replace(['_', '-'], ' ', $c)) : $d;
        $b = getStatusBadgeClass($c);
        return '<span class="badge rounded-pill bg-' . e($b) . '">' . e($t) . '</span>';
    }
}
if (!function_exists('getSewaStatusBadgeClass')) {
    function getSewaStatusBadgeClass($status)
    { /* ... kode Anda sudah baik ... */
        switch (strtolower(trim((string)$status))) {
            case 'dipesan':
                return 'warning text-dark';
            case 'diambil':
            case 'disewa':
                return 'info text-dark';
            case 'dikembalikan':
                return 'success';
            case 'hilang':
            case 'rusak':
            case 'dibatalkan_sewa':
                return 'danger';
            default:
                return 'secondary';
        }
    }
}
if (!function_exists('getSewaStatusBadgeClassHTML')) {
    function getSewaStatusBadgeClassHTML($s, $d = 'Tidak Diketahui')
    {
        $c = strtolower(trim((string)$s));
        $t = !empty($c) ? ucfirst(str_replace('_', ' ', $c)) : $d;
        $b = getSewaStatusBadgeClass($c);
        return '<span class="badge rounded-pill bg-' . e($b) . '">' . e($t) . '</span>';
    }
}

if (!function_exists('sanitize_url')) {
    function sanitize_url($url)
    { /* ... kode Anda sudah baik ... */
        return filter_var((string)$url, FILTER_SANITIZE_URL);
    }
}

if (!function_exists('time_elapsed_string')) {
    function time_elapsed_string($datetime, $full = false)
    {
        if (empty($datetime) || $datetime === '0000-00-00 00:00:00' || $datetime === '0000-00-00') {
            return 'N/A';
        }
        try {
            // Mengambil timezone yang sudah di-set secara global oleh config.php
            $app_timezone_str = date_default_timezone_get();
            $app_timezone = new DateTimeZone($app_timezone_str);

            $now = new DateTime("now", $app_timezone);

            // Asumsi $datetime dari database. Jika dari DB adalah UTC:
            // $ago = new DateTime($datetime, new DateTimeZone('UTC'));
            // $ago->setTimezone($app_timezone); 

            // Jika $datetime dari DB SUDAH dalam timezone aplikasi (default dari config.php):
            $ago = new DateTime($datetime, $app_timezone);

            $diff = $now->diff($ago);

            $weeks = floor($diff->d / 7);
            $diff->d -= $weeks * 7; // Perbaikan typo sebelumnya sudah benar

            $string_parts = [];
            if ($diff->y) $string_parts[] = $diff->y . ' tahun';
            if ($diff->m) $string_parts[] = $diff->m . ' bulan';
            if ($weeks)   $string_parts[] = $weeks . ' minggu';
            if ($diff->d) $string_parts[] = $diff->d . ' hari';
            if ($diff->h) $string_parts[] = $diff->h . ' jam';
            if ($diff->i) $string_parts[] = $diff->i . ' menit';
            if ($diff->s) $string_parts[] = $diff->s . ' detik';

            if (empty($string_parts)) return 'baru saja';
            if (!$full) $string_parts = array_slice($string_parts, 0, 1);

            return implode(', ', $string_parts) . ' lalu';
        } catch (Exception $e) {
            error_log("Error di time_elapsed_string: " . $e->getMessage() . " untuk datetime: " . htmlspecialchars((string)$datetime));
            return 'invalid date';
        }
    }
}
