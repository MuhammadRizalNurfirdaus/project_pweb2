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
     * @param string|null $string String yang akan di-escape. Jika null, akan dikembalikan string kosong.
     * @return string String yang sudah di-escape.
     */
    function e($string)
    {
        if ($string === null) {
            return '';
        }
        return htmlspecialchars((string)$string, ENT_QUOTES, 'UTF-8');
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

        if (preg_match('#^https?://#i', $path_str)) {
            $location = $path_str;
        } else {
            if (!defined('BASE_URL')) {
                error_log("FATAL ERROR di fungsi redirect(): Konstanta BASE_URL tidak terdefinisi. Tidak dapat melakukan redirect ke path relatif: " . e($path_str));
                http_response_code(500);
                exit("Kesalahan Konfigurasi Server: URL dasar aplikasi tidak ditemukan. Proses redirect dibatalkan.");
            }
            $location = BASE_URL . ltrim($path_str, '/');
        }

        if (!headers_sent($file, $line)) {
            header('Location: ' . $location);
            exit;
        } else {
            error_log("Peringatan di fungsi redirect(): Headers sudah terkirim. Output dimulai di {$file} pada baris {$line}. Redirect ke '{$location}' mungkin gagal di sisi server.");
            echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Redirecting...</title></head><body>";
            echo "<p>Proses selesai. Jika Anda tidak diarahkan secara otomatis, silakan <a href=\"" . e($location) . "\">klik di sini</a>.</p>";
            echo "</body></html>";
            exit;
        }
    }
}


if (!function_exists('formatRupiah')) {
    /**
     * Memformat angka menjadi format mata uang Rupiah.
     * @param float|int|string $angka Angka yang akan diformat.
     * @return string Angka dalam format Rupiah atau "Rp 0" jika input tidak valid.
     */
    function formatRupiah($angka)
    {
        if (!is_numeric($angka)) {
            return 'Rp 0';
        }
        return "Rp " . number_format((float)$angka, 0, ',', '.');
    }
}

if (!function_exists('formatTanggalIndonesia')) {
    /**
     * Mengubah format tanggal MySQL menjadi format Indonesia.
     * @param string|null $tanggal_mysql Tanggal dalam format MySQL.
     * @param bool $dengan_waktu True untuk menyertakan waktu (HH:MM).
     * @param bool $dengan_hari True untuk menyertakan nama hari.
     * @param bool $hari_singkat True jika $dengan_hari true, gunakan nama hari singkat.
     * @return string Tanggal dalam format Indonesia atau '-' jika input tidak valid.
     */
    function formatTanggalIndonesia($tanggal_mysql, $dengan_waktu = false, $dengan_hari = false, $hari_singkat = false)
    {
        if (empty($tanggal_mysql) || $tanggal_mysql === '0000-00-00' || $tanggal_mysql === '0000-00-00 00:00:00' || $tanggal_mysql === null) {
            return '-';
        }
        try {
            if (empty(date_default_timezone_get()) || !in_array(date_default_timezone_get(), timezone_identifiers_list(DateTimeZone::ASIA))) {
                error_log("Peringatan di formatTanggalIndonesia(): Timezone default belum diset atau bukan Asia. Menggunakan Asia/Jakarta.");
                date_default_timezone_set('Asia/Jakarta');
            }
            $date_obj = new DateTime($tanggal_mysql);
        } catch (Exception $e) {
            error_log("Error di formatTanggalIndonesia(): Tanggal input tidak valid - '" . e((string)$tanggal_mysql) . "' - Pesan: " . $e->getMessage());
            return '-';
        }
        $nama_hari_panjang_id = ['Sunday' => 'Minggu', 'Monday' => 'Senin', 'Tuesday' => 'Selasa', 'Wednesday' => 'Rabu', 'Thursday' => 'Kamis', 'Friday' => 'Jumat', 'Saturday' => 'Sabtu'];
        $nama_hari_singkat_id = ['Sunday' => 'Min', 'Monday' => 'Sen', 'Tuesday' => 'Sel', 'Wednesday' => 'Rab', 'Thursday' => 'Kam', 'Friday' => 'Jum', 'Saturday' => 'Sab'];
        $nama_bulan_id = [1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'];
        $hari_en = $date_obj->format('l');
        $nama_hari_yang_dipakai = $hari_singkat ? $nama_hari_singkat_id : $nama_hari_panjang_id;
        $hari_id = $nama_hari_yang_dipakai[$hari_en] ?? $hari_en;
        $tanggal = $date_obj->format('j');
        $bulan_idx = (int)$date_obj->format('n');
        $bulan_id = $nama_bulan_id[$bulan_idx] ?? $date_obj->format('M');
        $tahun = $date_obj->format('Y');
        $format_indonesia = '';
        if ($dengan_hari) {
            $format_indonesia .= $hari_id . ', ';
        }
        $format_indonesia .= $tanggal . ' ' . $bulan_id . ' ' . $tahun;
        if ($dengan_waktu && strpos($tanggal_mysql, ':') !== false && $tanggal_mysql !== $date_obj->format('Y-m-d')) {
            $format_indonesia .= ', ' . $date_obj->format('H:i');
        }
        return $format_indonesia;
    }
}

if (!function_exists('is_post')) {
    function is_post()
    {
        return strtoupper($_SERVER['REQUEST_METHOD']) === 'POST';
    }
}
if (!function_exists('is_get')) {
    function is_get()
    {
        return strtoupper($_SERVER['REQUEST_METHOD']) === 'GET';
    }
}

if (!function_exists('input')) {
    function input($key, $default = null, $method = null)
    {
        $source = null;
        $request_method = strtolower((string)$method);
        if ($request_method === 'post') {
            $source = $_POST;
        } elseif ($request_method === 'get') {
            $source = $_GET;
        } else {
            $source = $_REQUEST;
        }
        if (isset($source[$key])) {
            return is_string($source[$key]) ? trim($source[$key]) : $source[$key];
        }
        return $default;
    }
}

if (!function_exists('excerpt')) {
    function excerpt($text, $limit = 100, $ellipsis = '...')
    {
        if ($text === null || $text === '') return '';
        $text_clean = strip_tags((string)$text);
        if (mb_strlen($text_clean, 'UTF-8') > $limit) {
            $text_cut = mb_substr($text_clean, 0, $limit, 'UTF-8');
            $last_space = mb_strrpos($text_cut, ' ', 0, 'UTF-8');
            $text_final = ($last_space !== false) ? rtrim(mb_substr($text_cut, 0, $last_space, 'UTF-8')) : $text_cut;
            return $text_final . $ellipsis;
        }
        return $text_clean;
    }
}

if (!function_exists('is_valid_email')) {
    function is_valid_email($email)
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
}

// --- Fungsi-fungsi CSRF Token ---
if (!function_exists('generate_csrf_token')) {
    /**
     * Menghasilkan atau mengambil CSRF token yang sudah ada dari session.
     * @return string CSRF token.
     */
    function generate_csrf_token()
    {
        if (session_status() == PHP_SESSION_NONE) {
            error_log("KRITIKAL di generate_csrf_token(): Session belum dimulai. Seharusnya sudah dimulai oleh config.php.");
            // Di lingkungan produksi, mungkin lebih baik tidak memulai session di sini secara langsung
            // dan mengandalkan config.php untuk menangani ini.
            // Jika session tidak bisa dimulai, token tidak bisa disimpan/diambil.
            if (!headers_sent()) session_start();
            else return 'csrf_session_error_headers_sent';
        }
        if (empty($_SESSION['csrf_token'])) {
            try {
                $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            } catch (Exception $e) {
                $_SESSION['csrf_token'] = md5(uniqid(rand(), true)); // Fallback
                error_log("Peringatan: random_bytes() gagal untuk CSRF token, menggunakan fallback md5. Pesan: " . $e->getMessage());
            }
        }
        return $_SESSION['csrf_token'];
    }
}

if (!function_exists('generate_csrf_token_input')) {
    /**
     * Menghasilkan input field HTML tersembunyi yang berisi CSRF token.
     * @return string HTML untuk input field CSRF token.
     */
    function generate_csrf_token_input()
    {
        return '<input type="hidden" name="csrf_token" value="' . e(generate_csrf_token()) . '">';
    }
}

if (!function_exists('verify_csrf_token')) {
    /**
     * Memverifikasi CSRF token.
     * @param string|null $token_from_request Token yang diterima dari request (GET atau POST). 
     *                                         Jika null, akan coba ambil dari $_POST['csrf_token'] atau $_GET['csrf_token'].
     * @param bool $unset_after_verify Jika true, token akan dihapus dari session setelah verifikasi berhasil.
     * @return bool True jika token valid, false jika tidak.
     */
    function verify_csrf_token($token_from_request = null, $unset_after_verify = true)
    {
        if (session_status() == PHP_SESSION_NONE) {
            error_log("CSRF Verify Error: Sesi belum dimulai.");
            return false;
        }

        $request_token = $token_from_request;
        if ($request_token === null) {
            // Prioritaskan POST karena CSRF sering untuk form submission
            $request_token = $_POST['csrf_token'] ?? $_GET['csrf_token'] ?? null;
        }

        $session_token = $_SESSION['csrf_token'] ?? null;

        if ($request_token === null || $session_token === null) {
            error_log("CSRF Verify Error: Token dari request atau session tidak ditemukan.");
            return false;
        }

        $valid = hash_equals((string)$session_token, (string)$request_token);

        if (!$valid) {
            // Jangan log $session_token secara langsung di produksi jika sensitif, cukup request_token
            error_log("CSRF Token Mismatch. Request Token: [" . htmlspecialchars((string)$request_token) . "]");
        }

        if ($valid && $unset_after_verify) {
            unset($_SESSION['csrf_token']);
        }
        return $valid;
    }
}


// --- Fungsi-fungsi Badge Status ---
if (!function_exists('getStatusBadgeClass')) {
    function getStatusBadgeClass($status)
    { /* ... implementasi sama seperti sebelumnya ... */
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
    { /* ... implementasi sama ... */
        $status_clean = strtolower(trim((string)$status_raw));
        $display_text = !empty($status_clean) ? ucfirst(str_replace('_', ' ', $status_clean)) : $default_text;
        $badge_class_suffix = getStatusBadgeClass($status_clean);
        return '<span class="badge rounded-pill bg-' . e($badge_class_suffix) . '">' . e($display_text) . '</span>';
    }
}
if (!function_exists('getSewaStatusBadgeClass')) {
    function getSewaStatusBadgeClass($status)
    { /* ... implementasi sama ... */
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
    { /* ... implementasi sama ... */
        $status_clean = strtolower(trim((string)$status_raw));
        $display_text = !empty($status_clean) ? ucfirst(str_replace('_', ' ', $status_clean)) : $default_text;
        $badge_class_suffix = getSewaStatusBadgeClass($status_clean);
        return '<span class="badge rounded-pill bg-' . e($badge_class_suffix) . '">' . e($display_text) . '</span>';
    }
}

// Tidak ada tag PHP penutup jika ini adalah akhir dari file dan hanya berisi kode PHP.