<?php
// File: C:\xampp\htdocs\Cilengkrang-Web-Wisata\includes\helpers.php

/**
 * File ini berisi fungsi-fungsi bantuan umum yang digunakan di seluruh aplikasi.
 * Pastikan file config.php dimuat sebelum file ini jika fungsi di sini
 * bergantung pada konstanta atau variabel dari config.php (misalnya BASE_URL untuk redirect).
 */

if (!function_exists('e')) {
    /**
     * Melakukan escape HTML pada string untuk mencegah XSS.
     * Mengkonversi semua karakter yang berlaku menjadi entitas HTML.
     * @param string|null $string String yang akan di-escape. Jika null, akan dikembalikan string kosong.
     * @return string String yang sudah di-escape.
     */
    function e($string)
    {
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

        // Cek apakah path sudah merupakan URL absolut
        if (preg_match('#^https?://#i', $path_str)) {
            $location = $path_str;
        } else {
            // Jika bukan URL absolut, gabungkan dengan BASE_URL
            if (!defined('BASE_URL')) {
                $error_msg = "FATAL ERROR di fungsi redirect(): Konstanta BASE_URL tidak terdefinisi. Tidak dapat melakukan redirect ke path relatif: " . e($path_str);
                error_log($error_msg);
                http_response_code(500); // Internal Server Error
                // Tampilkan pesan error yang lebih aman di produksi
                exit("Kesalahan Konfigurasi Server: URL dasar aplikasi tidak dapat ditentukan. Proses redirect dibatalkan.");
            }
            // Pastikan tidak ada double slash dan path relatif dimulai dengan benar
            $location = rtrim(BASE_URL, '/') . '/' . ltrim($path_str, '/');
        }

        if (!headers_sent($file, $line)) {
            header('Location: ' . $location);
            exit; // Penting untuk menghentikan eksekusi skrip setelah redirect header
        } else {
            // Fallback jika header sudah terkirim
            error_log("PERINGATAN di fungsi redirect(): Headers sudah terkirim. Output dimulai di {$file} pada baris {$line}. Redirect ke '{$location}' mungkin gagal di sisi server. Melakukan redirect via JavaScript fallback.");
            echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Redirecting...</title>";
            // Menggunakan addslashes untuk keamanan di dalam atribut JavaScript
            echo "<script type='text/javascript'>window.location.href = '" . addslashes($location) . "';</script>";
            echo "<noscript><meta http-equiv='refresh' content='0;url=" . e($location) . "'></noscript>"; // Fallback untuk non-JS
            echo "</head><body>";
            echo "<p>Sedang mengarahkan... Jika Anda tidak diarahkan secara otomatis dalam beberapa detik, silakan <a href=\"" . e($location) . "\">klik di sini</a>.</p>";
            echo "</body></html>";
            exit; // Tetap exit
        }
    }
}

if (!function_exists('redirect_to_previous_or_default')) {
    /**
     * Mengarahkan pengguna ke halaman sebelumnya dari HTTP_REFERER jika ada dan aman (dari domain yang sama),
     * atau ke halaman default jika tidak.
     * @param string $default_path Path default tujuan (relatif terhadap BASE_URL).
     */
    function redirect_to_previous_or_default($default_path)
    {
        $referer = $_SERVER['HTTP_REFERER'] ?? null;

        if ($referer && defined('BASE_URL')) {
            // Pastikan referer berasal dari domain yang sama untuk mencegah open redirect vulnerability
            $base_url_host = parse_url(BASE_URL, PHP_URL_HOST);
            $referer_host = parse_url($referer, PHP_URL_HOST);

            if (strtolower($base_url_host) === strtolower($referer_host)) {
                redirect($referer); // Redirect ke referer jika dari domain yang sama
                exit;
            }
        }
        redirect($default_path); // Redirect ke default jika referer tidak ada atau tidak aman
        exit;
    }
}


if (!function_exists('formatRupiah')) {
    /**
     * Memformat angka menjadi format mata uang Rupiah.
     * @param float|int|string|null $angka Angka yang akan diformat.
     * @return string Angka dalam format Rupiah atau "Rp 0" jika input tidak valid/null.
     */
    function formatRupiah($angka)
    {
        if ($angka === null || !is_numeric($angka) || trim((string)$angka) === '') {
            return 'Rp 0';
        }
        return "Rp " . number_format((float)$angka, 0, ',', '.');
    }
}

if (!function_exists('formatTanggalIndonesia')) {
    /**
     * Mengubah format tanggal MySQL (atau objek DateTime) menjadi format Indonesia.
     * @param string|DateTimeInterface|null $tanggal_input Tanggal dalam format string yang bisa diparse atau objek DateTime.
     * @param bool $dengan_waktu True untuk menyertakan waktu (HH:MM).
     * @param bool $dengan_hari True untuk menyertakan nama hari.
     * @param bool $hari_singkat True jika $dengan_hari true, gunakan nama hari singkat.
     * @return string Tanggal dalam format Indonesia atau '-' jika input tidak valid.
     */
    function formatTanggalIndonesia($tanggal_input, $dengan_waktu = false, $dengan_hari = false, $hari_singkat = false)
    {
        if (empty($tanggal_input) || (is_string($tanggal_input) && (trim($tanggal_input) === '0000-00-00' || trim($tanggal_input) === '0000-00-00 00:00:00'))) {
            return '-';
        }

        try {
            // Pastikan timezone default sudah diset di config.php, jika tidak, set di sini sebagai fallback
            if (empty(date_default_timezone_get()) || @date_default_timezone_get() === 'UTC') { // @ untuk menekan warning jika belum diset
                date_default_timezone_set('Asia/Jakarta');
            }

            if ($tanggal_input instanceof DateTimeInterface) {
                $date_obj = $tanggal_input;
            } else {
                $date_obj = new DateTime((string)$tanggal_input);
            }
        } catch (Exception $e) {
            error_log("Error di formatTanggalIndonesia(): Tanggal input tidak valid - '" . e((string)$tanggal_input) . "' - Pesan: " . $e->getMessage());
            return '-'; // Kembalikan '-' jika parsing gagal
        }

        $nama_hari_panjang_id = ['Sunday' => 'Minggu', 'Monday' => 'Senin', 'Tuesday' => 'Selasa', 'Wednesday' => 'Rabu', 'Thursday' => 'Kamis', 'Friday' => 'Jumat', 'Saturday' => 'Sabtu'];
        $nama_hari_singkat_id = ['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab'];
        $nama_bulan_id = [1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'];

        $format_indonesia = '';
        if ($dengan_hari) {
            $hari_idx = (int)$date_obj->format('w');
            $hari_id = $hari_singkat ? ($nama_hari_singkat_id[$hari_idx] ?? $date_obj->format('D')) : ($nama_hari_panjang_id[$date_obj->format('l')] ?? $date_obj->format('l'));
            if (!empty($hari_id)) {
                $format_indonesia .= $hari_id . ', ';
            }
        }

        $tanggal = $date_obj->format('j');
        $bulan_idx = (int)$date_obj->format('n');
        $bulan_id = $nama_bulan_id[$bulan_idx] ?? $date_obj->format('M'); // Fallback ke singkatan bulan Inggris jika indeks tidak ada
        $tahun = $date_obj->format('Y');

        $format_indonesia .= $tanggal . ' ' . $bulan_id . ' ' . $tahun;

        if ($dengan_waktu) {
            $original_time_part = $date_obj->format('H:i:s');
            // Hanya tampilkan waktu jika memang ada informasi waktu yang signifikan di input asli
            if ($original_time_part !== '00:00:00' || (is_string($tanggal_input) && strpos($tanggal_input, ':') !== false)) {
                $format_indonesia .= ', ' . $date_obj->format('H:i');
            }
        }
        return $format_indonesia;
    }
}

if (!function_exists('is_post')) {
    /** @return bool True jika request method adalah POST. */
    function is_post()
    {
        return strtoupper($_SERVER['REQUEST_METHOD']) === 'POST';
    }
}
if (!function_exists('is_get')) {
    /** @return bool True jika request method adalah GET. */
    function is_get()
    {
        return strtoupper($_SERVER['REQUEST_METHOD']) === 'GET';
    }
}

if (!function_exists('input')) {
    /**
     * Mengambil nilai dari array request ($_GET, $_POST) dengan aman.
     * Lebih disarankan untuk spesifik menggunakan $_GET atau $_POST daripada $_REQUEST.
     * @param string $key Kunci array.
     * @param mixed $default Nilai default jika kunci tidak ditemukan.
     * @param string|null $method 'get', 'post'. Jika null, akan mencoba POST lalu GET.
     * @return mixed Nilai dari request atau nilai default.
     */
    function input($key, $default = null, $method = null)
    {
        $source = null;
        $request_method_lower = $method !== null ? strtolower((string)$method) : null;

        if ($request_method_lower === 'post' || ($method === null && is_post())) {
            $source = $_POST;
        } elseif ($request_method_lower === 'get' || ($method === null && is_get())) {
            $source = $_GET;
        } else {
            // Jika method dispesifikkan tapi bukan get/post, atau tidak ada method & bukan get/post
            return $default;
        }

        if (isset($source[$key])) {
            return is_string($source[$key]) ? trim($source[$key]) : $source[$key];
        }
        return $default;
    }
}

if (!function_exists('excerpt')) {
    /**
     * Membuat potongan teks (excerpt) dengan dukungan multibyte.
     * @param string|null $text Teks asli.
     * @param int $limit Batas jumlah karakter.
     * @param string $ellipsis Teks tambahan di akhir jika dipotong.
     * @return string Potongan teks.
     */
    function excerpt($text, $limit = 100, $ellipsis = '...')
    {
        if ($text === null || trim((string)$text) === '') return '';
        $text_clean = strip_tags((string)$text);
        if (mb_strlen($text_clean, 'UTF-8') > $limit) {
            $text_cut = mb_substr($text_clean, 0, $limit, 'UTF-8');
            $last_space = mb_strrpos($text_cut, ' ', 0, 'UTF-8');
            // Jika tidak ada spasi, potong saja di limit. Jika ada, potong di spasi terakhir.
            $text_final = ($last_space !== false && $last_space > ($limit / 2)) ? rtrim(mb_substr($text_cut, 0, $last_space, 'UTF-8')) : $text_cut;
            return $text_final . $ellipsis;
        }
        return $text_clean;
    }
}

if (!function_exists('is_valid_email')) {
    /** @param string $email @return bool True jika format email valid. */
    function is_valid_email($email)
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
}

// --- Fungsi-fungsi CSRF Token ---
if (!function_exists('generate_csrf_token')) {
    function generate_csrf_token()
    {
        if (session_status() == PHP_SESSION_NONE) {
            error_log("KRITIKAL di generate_csrf_token(): Session belum dimulai.");
            if (!headers_sent()) {
                session_start();
            } else {
                return 'csrf_session_error_headers_sent_on_generate'; /* Seharusnya tidak terjadi jika config benar */
            }
        }
        if (empty($_SESSION['csrf_token'])) {
            try {
                $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            } catch (Exception $e) {
                $_SESSION['csrf_token'] = md5(uniqid(microtime(true), true));
                error_log("Peringatan: random_bytes() gagal untuk CSRF token, menggunakan fallback md5. Pesan: " . $e->getMessage());
            }
        }
        return $_SESSION['csrf_token'];
    }
}

if (!function_exists('generate_csrf_token_input')) {
    function generate_csrf_token_input($token_name = 'csrf_token')
    {
        return '<input type="hidden" name="' . e($token_name) . '" value="' . e(generate_csrf_token()) . '">';
    }
}

if (!function_exists('verify_csrf_token')) {
    function verify_csrf_token($token_name = 'csrf_token', $unset_after_verify = true)
    {
        if (session_status() == PHP_SESSION_NONE) {
            error_log("CSRF Verify Error: Sesi belum dimulai.");
            return false;
        }
        $request_token = $_POST[$token_name] ?? $_GET[$token_name] ?? null;
        $session_token = $_SESSION['csrf_token'] ?? null;

        if ($request_token === null || $session_token === null) {
            error_log("CSRF Verify Error: Token dari request atau session tidak ditemukan. Request Token: " . ($request_token ? 'ADA' : 'KOSONG') . ", Session Token: " . ($session_token ? 'ADA' : 'KOSONG'));
            return false;
        }
        $valid = hash_equals((string)$session_token, (string)$request_token);
        if (!$valid) {
            error_log("CSRF Token Mismatch. Request: [" . htmlspecialchars((string)$request_token) . "]");
        }
        if ($valid && $unset_after_verify) {
            unset($_SESSION['csrf_token']);
        }
        return $valid;
    }
}

// --- Fungsi-fungsi Badge Status (Implementasi Anda sudah baik) ---
if (!function_exists('getStatusBadgeClass')) {
    function getStatusBadgeClass($status)
    {
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
    function getStatusBadgeClassHTML($status_raw, $default_text = 'Tidak Diketahui')
    {
        $status_clean = strtolower(trim((string)$status_raw));
        $display_text = !empty($status_clean) ? ucfirst(str_replace(['_', '-'], ' ', $status_clean)) : $default_text;
        $badge_class_suffix = getStatusBadgeClass($status_clean);
        return '<span class="badge rounded-pill bg-' . e($badge_class_suffix) . '">' . e($display_text) . '</span>';
    }
}
// Fungsi untuk sewa alat status, bisa digabungkan dengan yang di atas jika statusnya tidak tumpang tindih
if (!function_exists('getSewaStatusBadgeClass')) {
    function getSewaStatusBadgeClass($status)
    {
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
    function getSewaStatusBadgeClassHTML($status_raw, $default_text = 'Tidak Diketahui')
    {
        $status_clean = strtolower(trim((string)$status_raw));
        $display_text = !empty($status_clean) ? ucfirst(str_replace('_', ' ', $status_clean)) : $default_text;
        $badge_class_suffix = getSewaStatusBadgeClass($status_clean);
        return '<span class="badge rounded-pill bg-' . e($badge_class_suffix) . '">' . e($display_text) . '</span>';
    }
}

if (!function_exists('sanitize_url')) {
    /** Membersihkan URL dari karakter yang tidak valid. */
    function sanitize_url($url)
    {
        return filter_var((string)$url, FILTER_SANITIZE_URL);
    }
}

// Tidak ada tag PHP penutup jika ini adalah akhir dari file dan hanya berisi kode PHP.
