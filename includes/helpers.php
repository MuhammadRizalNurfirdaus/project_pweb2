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
    function redirect($path)
    {
        $path_str = (string)$path;
        $location = '';
        // Hapus timestamp logging dari fungsi inti redirect, bisa dilakukan oleh pemanggil jika perlu
        // error_log("REDIRECT HELPER: Menerima path awal = " . $path_str);

        if (preg_match('#^https?://#i', $path_str)) {
            $location = $path_str;
        } else {
            if (!defined('BASE_URL')) {
                error_log("REDIRECT FATAL ERROR: Konstanta BASE_URL tidak terdefinisi. Path relatif: " . e($path_str));
                http_response_code(500);
                exit("Kesalahan Konfigurasi Kritis: URL dasar aplikasi tidak terdefinisi.");
            }
            $base_url_val = BASE_URL;
            $location = rtrim($base_url_val, '/') . '/' . ltrim($path_str, '/');
            $location = preg_replace('#(?<!:)/{2,}#', '/', $location);
        }

        // error_log("REDIRECT HELPER: Akan redirect ke Lokasi Final = " . $location);

        if (!headers_sent($file_header, $line_header)) {
            header('Location: ' . $location);
            // error_log("REDIRECT SUCCESS: Header Location terkirim ke: " . $location);
            exit;
        } else {
            error_log("REDIRECT PERINGATAN: Headers sudah terkirim. Output dimulai di {$file_header} pada baris {$line_header}. Fallback JS ke '{$location}'.");
            echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Redirecting...</title>";
            echo "<script type='text/javascript'>window.location.href = '" . addslashes($location) . "';</script>";
            echo "<noscript><meta http-equiv='refresh' content='0;url=" . e($location) . "'></noscript>";
            echo "</head><body><p>Sedang mengarahkan... Jika Anda tidak diarahkan secara otomatis dalam beberapa detik, silakan <a href=\"" . e($location) . "\">klik di sini</a>.</p></body></html>";
            exit;
        }
    }
}

// ... (fungsi lain seperti formatRupiah, formatTanggalIndonesia, dll. tetap sama) ...
if (!function_exists('redirect_to_previous_or_default')) {
    function redirect_to_previous_or_default($default_path)
    {
        $referer = $_SERVER['HTTP_REFERER'] ?? null;
        if ($referer && defined('BASE_URL')) {
            $base_url_host = parse_url(BASE_URL, PHP_URL_HOST);
            $referer_host = parse_url($referer, PHP_URL_HOST);
            if (strtolower($base_url_host ?? '') === strtolower($referer_host ?? '')) {
                redirect($referer); // redirect() sudah ada exit
                // exit; // Tidak perlu exit ganda
            }
        }
        redirect($default_path); // redirect() sudah ada exit
        // exit; // Tidak perlu exit ganda
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
    function is_post()
    {
        return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
    }
}
if (!function_exists('is_get')) {
    function is_get()
    {
        return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET';
    }
}
if (!function_exists('input')) {
    function input($key, $default = null, $method = null)
    {
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
    {
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
    {
        return filter_var((string)$email, FILTER_VALIDATE_EMAIL) !== false;
    }
}


// --- FUNGSI CSRF TOKEN YANG DIREKOMENDASIKAN ---
if (!function_exists('get_session_csrf_token_name')) {
    /**
     * Mendapatkan nama kunci session yang digunakan untuk menyimpan CSRF token.
     * Ini untuk memastikan konsistensi.
     */
    function get_session_csrf_token_name()
    {
        return 'csrf_main_token'; // Gunakan satu nama kunci session yang konsisten
    }
}

if (!function_exists('generate_csrf_token')) {
    /**
     * Membuat atau mengambil CSRF token dari session.
     * Token akan disimpan di $_SESSION[get_session_csrf_token_name()].
     * @return string CSRF token.
     */
    function generate_csrf_token()
    {
        if (session_status() == PHP_SESSION_NONE) {
            error_log("KRITIKAL generate_csrf_token(): Session belum dimulai. Mencoba memulai...");
            if (!headers_sent($file, $line)) {
                session_start();
            } else {
                error_log("GAGAL memulai session di generate_csrf_token() karena headers sudah terkirim dari {$file}:{$line}");
                return 'csrf_error_session_not_started'; // Mengembalikan string error
            }
        }
        $token_session_key = get_session_csrf_token_name();
        if (empty($_SESSION[$token_session_key])) {
            try {
                $_SESSION[$token_session_key] = bin2hex(random_bytes(32));
            } catch (Exception $e) {
                // Fallback jika random_bytes gagal (sangat jarang)
                $_SESSION[$token_session_key] = md5(uniqid(microtime(true) . mt_rand(), true));
                error_log("Peringatan: random_bytes() gagal untuk CSRF token: " . $e->getMessage() . ". Menggunakan fallback md5.");
            }
        }
        return $_SESSION[$token_session_key];
    }
}

if (!function_exists('generate_csrf_token_input')) {
    /**
     * Menghasilkan input hidden HTML untuk CSRF token.
     * @param string $input_field_name Nama untuk field input hidden (default: 'csrf_token').
     * @return string String HTML untuk input hidden.
     */
    function generate_csrf_token_input($input_field_name = 'csrf_token')
    {
        $token_value = generate_csrf_token();
        if ($token_value === 'csrf_error_session_not_started') {
            // Jika ada error saat generate token, jangan buat input atau buat dengan value error
            return '<!-- CSRF Token Generation Error -->';
        }
        return '<input type="hidden" name="' . e($input_field_name) . '" value="' . e($token_value) . '">';
    }
}

if (!function_exists('verify_csrf_token')) {
    /**
     * Memverifikasi CSRF token yang dikirim dari form/request terhadap token di session.
     * @param string $token_input_name Nama field input di mana token dikirim (default: 'csrf_token').
     * @param bool $unset_after_verify Jika true, token di session akan dihapus setelah verifikasi berhasil.
     * @param string|null $request_method_override 'POST' atau 'GET'. Jika null, akan coba deteksi otomatis.
     * @return bool True jika token valid, false jika tidak.
     */
    function verify_csrf_token($token_input_name = 'csrf_token', $unset_after_verify = true, $request_method_override = null)
    {
        if (session_status() == PHP_SESSION_NONE) {
            error_log("CSRF Verify: Sesi belum dimulai saat verifikasi.");
            return false;
        }

        $token_session_key = get_session_csrf_token_name();
        $session_csrf_token = $_SESSION[$token_session_key] ?? null;

        $request_token_value = null;
        $method_to_check = $request_method_override ?? strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');

        if ($method_to_check === 'POST') {
            $request_token_value = $_POST[$token_input_name] ?? null;
        } elseif ($method_to_check === 'GET') {
            $request_token_value = $_GET[$token_input_name] ?? null;
        } else {
            error_log("CSRF Verify: Metode request tidak didukung atau tidak diketahui: " . $method_to_check);
            return false;
        }

        if (empty($session_csrf_token) || empty($request_token_value)) {
            error_log("CSRF Verify: Token sesi ('" . ($session_csrf_token ? '***' : 'KOSONG') . // Jangan log token sesi penuh
                "') atau request ('" . ($request_token_value ? '***' : 'KOSONG') . // Jangan log token request penuh
                "') kosong/hilang. Nama input dicek: {$token_input_name}, Metode: {$method_to_check}");
            return false;
        }

        $is_valid = hash_equals($session_csrf_token, $request_token_value);

        if (!$is_valid) {
            error_log("CSRF Verify: Token Mismatch! Sesi: [HASH_SESS_TOKEN_HIDDEN], Request (dari input '{$token_input_name}'): [HASH_REQ_TOKEN_HIDDEN]");
        }

        // PENTING: Selalu unset token setelah diverifikasi (baik valid maupun tidak) untuk mencegah replay attack
        // jika ini adalah token sekali pakai. Jika Anda ingin token bertahan lebih lama per sesi,
        // maka jangan unset di sini, tapi Anda perlu mekanisme regenerasi yang berbeda.
        // Untuk kebanyakan form POST, unset setelah verifikasi adalah praktik yang baik.
        if ($unset_after_verify) {
            unset($_SESSION[$token_session_key]);
        }

        return $is_valid;
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
