<?php
// File: C:\xampp\htdocs\Cilengkrang-Web-Wisata\includes\helpers.php

/**
 * File ini berisi fungsi-fungsi bantuan umum yang digunakan di seluruh aplikasi.
 * Pastikan file config.php dimuat sebelum file ini jika fungsi di sini
 * bergantung pada konstanta atau variabel dari config.php (misalnya BASE_URL untuk redirect).
 */

if (!function_exists('e')) {
    function e($string)
    {
        if ($string === null) {
            return '';
        }
        return htmlspecialchars((string)$string, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

if (!function_exists('redirect')) {
    function redirect(string $path, ?string $base_or_prefix_url = null)
    {
        $location = '';
        if (preg_match('#^https?://#i', $path)) {
            $location = $path;
        } elseif ($base_or_prefix_url !== null) {
            $location = rtrim($base_or_prefix_url, '/') . '/' . ltrim($path, '/');
        } else {
            if (!defined('BASE_URL')) {
                error_log("REDIRECT FATAL ERROR: Konstanta BASE_URL tidak terdefinisi. Path relatif: " . e($path));
                http_response_code(500);
                exit("Kesalahan Konfigurasi Kritis: URL dasar aplikasi tidak terdefinisi untuk redirect.");
            }
            $location = rtrim(BASE_URL, '/') . '/' . ltrim($path, '/');
        }
        $location = preg_replace('#(?<!:)/{2,}#', '/', $location);

        if (!headers_sent($file_header, $line_header)) {
            header('Location: ' . $location);
            exit;
        } else {
            error_log("REDIRECT PERINGATAN: Headers sudah terkirim. Output dimulai di {$file_header} pada baris {$line_header}. Fallback JS ke '{$location}'.");
            echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Redirecting...</title>";
            echo "<script type='text/javascript'>window.location.href = '" . addslashes($location) . "';</script>";
            echo "<noscript><meta http-equiv='refresh' content='0;url=" . e($location) . "'></noscript>";
            echo "</head><body><p>Sedang mengarahkan... Jika tidak otomatis, <a href=\"" . e($location) . "\">klik di sini</a>.</p></body></html>";
            exit;
        }
    }
}

if (!function_exists('redirect_to_previous_or_default')) {
    function redirect_to_previous_or_default($default_path)
    {
        $referer = $_SERVER['HTTP_REFERER'] ?? null;
        if ($referer && defined('BASE_URL')) {
            $base_url_host = parse_url(BASE_URL, PHP_URL_HOST);
            $referer_host = parse_url($referer, PHP_URL_HOST);
            if (strtolower($base_url_host ?? '') === strtolower($referer_host ?? '')) {
                redirect($referer);
            }
        }
        redirect($default_path);
    }
}

if (!function_exists('formatRupiah')) {
    function formatRupiah($angka)
    {
        if ($angka === null || !is_numeric($angka) || trim((string)$angka) === '') return 'Rp 0';
        return "Rp " . number_format((float)$angka, 0, ',', '.');
    }
}

if (!function_exists('formatTanggalIndonesia')) {
    function formatTanggalIndonesia($tanggal_input, $dengan_waktu = false, $dengan_hari = false, $hari_singkat = false)
    {
        if (empty($tanggal_input) || (is_string($tanggal_input) && (trim($tanggal_input) === '0000-00-00' || trim($tanggal_input) === '0000-00-00 00:00:00'))) return '-';
        try {
            $app_timezone_str = date_default_timezone_get();
            $app_timezone = new DateTimeZone($app_timezone_str);

            if ($tanggal_input instanceof DateTimeInterface) {
                $date_obj = $tanggal_input;
                if ($date_obj->getTimezone()->getName() !== $app_timezone->getName()) {
                    $date_obj = ($date_obj instanceof DateTime) ? $date_obj->setTimezone($app_timezone) : new DateTime($date_obj->format('Y-m-d H:i:s'), $app_timezone);
                }
            } else {
                $date_obj = new DateTime((string)$tanggal_input, $app_timezone);
            }
        } catch (Exception $e) {
            error_log("Error di formatTanggalIndonesia(): " . $e->getMessage() . " untuk input: '" . e((string)$tanggal_input) . "'");
            return '-';
        }
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
    function is_post(): bool
    {
        return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
    }
}
if (!function_exists('is_get')) {
    function is_get(): bool
    {
        return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET';
    }
}
if (!function_exists('input')) {
    function input(string $key, $default = null, ?string $method = null)
    {
        $source = null;
        $request_method_server = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        $method_lower = $method !== null ? strtolower($method) : null;
        if ($method_lower === 'post' || ($method === null && $request_method_server === 'POST')) {
            $source = $_POST;
        } elseif ($method_lower === 'get' || ($method === null && $request_method_server === 'GET')) {
            $source = $_GET;
        } else {
            return $default;
        }
        return isset($source[$key]) ? (is_string($source[$key]) ? trim($source[$key]) : $source[$key]) : $default;
    }
}
if (!function_exists('excerpt')) {
    function excerpt(string $text, int $limit = 100, string $ellipsis = '...'): string
    {
        if (trim($text) === '') return '';
        $text_clean = strip_tags($text);
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
    function is_valid_email(string $email): bool
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
}

// --- FUNGSI CSRF TOKEN YANG DISEMPURNAKAN --- (Sama seperti sebelumnya)
if (!function_exists('get_csrf_session_key')) {
    function get_csrf_session_key(): string
    {
        return 'csrf_main_token_val';
    }
}
if (!function_exists('generate_csrf_token')) {
    function generate_csrf_token(): string
    {
        if (session_status() == PHP_SESSION_NONE) {
            if (!headers_sent($f, $l)) {
                session_start();
            } else {
                error_log("GAGAL session_start di generate_csrf_token() dari {$f}:{$l}");
                return 'csrf_error_session';
            }
        }
        $k = get_csrf_session_key();
        if (empty($_SESSION[$k])) {
            try {
                $_SESSION[$k] = bin2hex(random_bytes(32));
            } catch (Exception $e) {
                $_SESSION[$k] = md5(uniqid(microtime(true) . mt_rand(), true) . 'xs');
                error_log("CSRF rand_bytes() gagal: " . $e->getMessage());
            }
        }
        return $_SESSION[$k];
    }
}
if (!function_exists('generate_csrf_token_input')) {
    function generate_csrf_token_input(string $input_field_name = 'csrf_token'): string
    {
        $t = generate_csrf_token();
        if (strpos($t, 'csrf_error') === 0) return '<!-- CSRF Gen Error -->';
        return '<input type="hidden" name="' . e($input_field_name) . '" value="' . e($t) . '">';
    }
}
if (!function_exists('verify_csrf_token')) {
    function verify_csrf_token(string $token_input_name = 'csrf_token', bool $unset_after_verify = true, ?string $request_method_override = null): bool
    {
        if (session_status() == PHP_SESSION_NONE) {
            error_log("CSRF Verify: Sesi belum ada.");
            return false;
        }
        $k = get_csrf_session_key();
        $s_t = $_SESSION[$k] ?? null;
        $r_t = null;
        $m = $request_method_override ?? strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        if ($m === 'POST') $r_t = $_POST[$token_input_name] ?? null;
        elseif ($m === 'GET') $r_t = $_GET[$token_input_name] ?? null;
        else {
            error_log("CSRF Verify: Metode request tidak didukung: " . $m);
            return false;
        }
        if (empty($s_t) || empty($r_t)) {
            error_log("CSRF Verify: Token sesi/request hilang. Input='{$token_input_name}', Metode='{$m}'. Sesi:" . ($s_t ? 'ADA' : 'KOSONG') . ", Req:" . ($r_t ? 'ADA' : 'KOSONG'));
            if ($unset_after_verify && isset($_SESSION[$k])) unset($_SESSION[$k]);
            return false;
        }
        $v = hash_equals($s_t, $r_t);
        if (!$v) error_log("CSRF Verify: Mismatch. Input='{$token_input_name}', Metode='{$m}'.");
        if ($unset_after_verify) unset($_SESSION[$k]);
        return $v;
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
if (!function_exists('adjustBrightness')) {
    /**
     * Menyesuaikan kecerahan warna hex.
     * @param string $hex Kode warna hex (misal: #RRGGBB atau RRGGBB).
     * @param int $steps Jumlah penyesuaian (-255 hingga 255). Negatif untuk lebih gelap, positif untuk lebih terang.
     * @return string Kode warna hex baru.
     */
    function adjustBrightness(string $hex, int $steps): string
    {
        $steps = max(-255, min(255, $steps));
        $hex = str_replace('#', '', $hex);

        if (strlen($hex) == 3) {
            $hex = str_repeat(substr($hex, 0, 1), 2) .
                str_repeat(substr($hex, 1, 1), 2) .
                str_repeat(substr($hex, 2, 1), 2);
        }
        if (strlen($hex) !== 6) { // Pastikan panjangnya 6 setelah konversi
            return '#000000'; // Fallback jika format hex salah
        }

        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));

        $r = max(0, min(255, $r + $steps));
        $g = max(0, min(255, $g + $steps));
        $b = max(0, min(255, $b + $steps));

        return '#' . str_pad(dechex($r), 2, '0', STR_PAD_LEFT) .
            str_pad(dechex($g), 2, '0', STR_PAD_LEFT) .
            str_pad(dechex($b), 2, '0', STR_PAD_LEFT);
    }
}

if (!function_exists('get_site_settings')) {
    /**
     * Mengambil nilai pengaturan situs dari variabel global $pengaturan_situs_global.
     * Variabel $pengaturan_situs_global harus sudah di-set di config.php.
     * @param string|null $key Kunci spesifik pengaturan yang ingin diambil. Jika null, kembalikan semua pengaturan.
     * @param mixed $default Nilai default jika kunci tidak ditemukan.
     * @return mixed Nilai pengaturan, array semua pengaturan, atau nilai default.
     */
    function get_site_settings(?string $key = null, $default = null)
    {
        global $pengaturan_situs_global; // Mengakses variabel global

        if (!isset($pengaturan_situs_global) || !is_array($pengaturan_situs_global)) {
            // Jika $pengaturan_situs_global tidak ada atau bukan array (seharusnya tidak terjadi jika config.php benar)
            error_log("Peringatan di get_site_settings(): Variabel global \$pengaturan_situs_global tidak tersedia atau bukan array.");
            return $key === null ? [] : $default; // Kembalikan array kosong atau default
        }

        if ($key === null) {
            return $pengaturan_situs_global; // Kembalikan semua pengaturan
        }
        return $pengaturan_situs_global[$key] ?? $default;
    }
}
