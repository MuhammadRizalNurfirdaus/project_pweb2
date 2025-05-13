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
        // Pastikan dikonversi ke string untuk mencegah error jika tipe lain diberikan
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
        $path_str = (string)$path; // Pastikan path adalah string

        if (preg_match('#^https?://#i', $path_str)) { // Cek jika path adalah URL absolut
            $location = $path_str;
        } else { // Jika path relatif
            if (!defined('BASE_URL')) {
                error_log("FATAL ERROR di fungsi redirect(): Konstanta BASE_URL tidak terdefinisi. Tidak dapat melakukan redirect ke path relatif: " . e($path_str));
                // Jika BASE_URL tidak ada, ini adalah masalah konfigurasi serius.
                http_response_code(500); // Internal Server Error
                exit("Kesalahan Konfigurasi Server: URL dasar aplikasi tidak ditemukan. Proses redirect dibatalkan.");
            }
            // BASE_URL sudah dipastikan diakhiri dengan slash oleh config.php
            // ltrim() untuk menghapus slash di awal path jika ada, agar tidak jadi double slash
            $location = BASE_URL . ltrim($path_str, '/');
        }

        if (!headers_sent($file, $line)) {
            header('Location: ' . $location);
            exit; // Selalu exit setelah header redirect
        } else {
            // Jika header sudah terkirim, redirect via header tidak akan berfungsi.
            error_log("Peringatan di fungsi redirect(): Headers sudah terkirim. Output dimulai di {$file} pada baris {$line}. Redirect ke '{$location}' mungkin gagal di sisi server.");
            // Tampilkan link fallback ke pengguna
            echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Redirecting...</title></head><body>";
            echo "<p>Proses selesai. Jika Anda tidak diarahkan secara otomatis, silakan <a href=\"" . e($location) . "\">klik di sini</a>.</p>";
            echo "</body></html>";
            exit; // Tetap exit untuk menghentikan eksekusi skrip saat ini
        }
    }
}


if (!function_exists('formatRupiah')) {
    /**
     * Memformat angka menjadi format mata uang Rupiah.
     * @param float|int|string $angka Angka yang akan diformat.
     * @return string Angka dalam format Rupiah (misal: "Rp 1.250.000") atau "Rp 0" jika input tidak valid.
     */
    function formatRupiah($angka)
    {
        if (!is_numeric($angka)) {
            return 'Rp 0'; // Default jika input bukan numerik
        }
        return "Rp " . number_format((float)$angka, 0, ',', '.');
    }
}

if (!function_exists('formatTanggalIndonesia')) {
    /**
     * Mengubah format tanggal dari YYYY-MM-DD HH:MM:SS atau YYYY-MM-DD
     * menjadi format Indonesia.
     * @param string|null $tanggal_mysql Tanggal dalam format MySQL.
     * @param bool $dengan_waktu True untuk menyertakan waktu (HH:MM).
     * @param bool $dengan_hari True untuk menyertakan nama hari (Senin, Selasa, dst. atau Sen, Sel, dst.).
     * @param bool $hari_singkat True jika $dengan_hari true, gunakan nama hari singkat (Sen, Sel), false untuk nama lengkap.
     * @return string Tanggal dalam format Indonesia atau '-' jika input tidak valid atau kosong.
     */
    function formatTanggalIndonesia($tanggal_mysql, $dengan_waktu = false, $dengan_hari = false, $hari_singkat = false)
    {
        if (empty($tanggal_mysql) || $tanggal_mysql === '0000-00-00' || $tanggal_mysql === '0000-00-00 00:00:00' || $tanggal_mysql === null) {
            return '-';
        }

        try {
            // Zona waktu seharusnya sudah diset di config.php
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

        $nama_bulan_id = [
            1 => 'Januari',
            2 => 'Februari',
            3 => 'Maret',
            4 => 'April',
            5 => 'Mei',
            6 => 'Juni',
            7 => 'Juli',
            8 => 'Agustus',
            9 => 'September',
            10 => 'Oktober',
            11 => 'November',
            12 => 'Desember'
        ];

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

        if ($dengan_waktu) {
            if (strpos($tanggal_mysql, ':') !== false && $tanggal_mysql !== $date_obj->format('Y-m-d')) {
                $format_indonesia .= ', ' . $date_obj->format('H:i');
            }
        }
        return $format_indonesia;
    }
}


if (!function_exists('is_post')) {
    /**
     * Mengecek apakah request method saat ini adalah POST.
     * @return bool True jika POST, false jika tidak.
     */
    function is_post()
    {
        return strtoupper($_SERVER['REQUEST_METHOD']) === 'POST';
    }
}

if (!function_exists('is_get')) {
    /**
     * Mengecek apakah request method saat ini adalah GET.
     * @return bool True jika GET, false jika tidak.
     */
    function is_get()
    {
        return strtoupper($_SERVER['REQUEST_METHOD']) === 'GET';
    }
}

if (!function_exists('input')) {
    /**
     * Mengambil data input dari POST, GET, atau REQUEST.
     * Melakukan trim pada string.
     * @param string $key Kunci dari data input.
     * @param mixed $default Nilai default jika kunci tidak ditemukan.
     * @param string|null $method Metode 'post', 'get', atau null (untuk $_REQUEST).
     * @return mixed Nilai input yang sudah di-trim atau nilai default.
     */
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
            if (is_string($source[$key])) {
                return trim($source[$key]);
            }
            return $source[$key];
        }
        return $default;
    }
}

if (!function_exists('excerpt')) {
    /**
     * Membuat ringkasan teks dengan batas karakter, mencoba memotong pada spasi.
     * Menghapus tag HTML dari teks.
     * @param string|null $text Teks asli.
     * @param int $limit Batas karakter.
     * @param string $ellipsis String penutup (misal "...").
     * @return string Teks ringkasan.
     */
    function excerpt($text, $limit = 100, $ellipsis = '...')
    {
        if ($text === null || $text === '') {
            return '';
        }
        $text_clean = strip_tags((string)$text);
        if (mb_strlen($text_clean, 'UTF-8') > $limit) {
            $text_cut = mb_substr($text_clean, 0, $limit, 'UTF-8');
            $last_space = mb_strrpos($text_cut, ' ', 0, 'UTF-8');
            if ($last_space !== false) {
                $text_final = rtrim(mb_substr($text_cut, 0, $last_space, 'UTF-8'));
            } else {
                $text_final = $text_cut;
            }
            return $text_final . $ellipsis;
        }
        return $text_clean;
    }
}

if (!function_exists('is_valid_email')) {
    /**
     * Memvalidasi apakah format email valid.
     * @param string $email Email yang akan divalidasi.
     * @return bool True jika valid, false jika tidak.
     */
    function is_valid_email($email)
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
}

if (!function_exists('getStatusBadgeClass')) {
    /**
     * Mengembalikan kelas CSS Bootstrap berdasarkan status umum.
     * @param string $status Status.
     * @return string Kelas CSS.
     */
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
    /**
     * Menghasilkan HTML untuk badge status umum.
     * @param string $status_raw Status mentah.
     * @param string $default_text Teks default jika status kosong.
     * @return string HTML badge.
     */
    function getStatusBadgeClassHTML($status_raw, $default_text = 'Tidak Diketahui')
    {
        $status_clean = strtolower(trim((string)$status_raw));
        $display_text = !empty($status_clean) ? ucfirst(str_replace('_', ' ', $status_clean)) : $default_text;
        $badge_class_suffix = getStatusBadgeClass($status_clean);

        return '<span class="badge rounded-pill bg-' . e($badge_class_suffix) . '">' . e($display_text) . '</span>';
    }
}

if (!function_exists('getSewaStatusBadgeClass')) {
    /**
     * Mengembalikan kelas CSS Bootstrap berdasarkan status sewa.
     * @param string $status Status sewa.
     * @return string Kelas CSS.
     */
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
    /**
     * Menghasilkan HTML untuk badge status sewa.
     * @param string $status_raw Status sewa mentah.
     * @param string $default_text Teks default jika status kosong.
     * @return string HTML badge.
     */
    function getSewaStatusBadgeClassHTML($status_raw, $default_text = 'Tidak Diketahui')
    {
        $status_clean = strtolower(trim((string)$status_raw));
        $display_text = !empty($status_clean) ? ucfirst(str_replace('_', ' ', $status_clean)) : $default_text;
        $badge_class_suffix = getSewaStatusBadgeClass($status_clean);

        return '<span class="badge rounded-pill bg-' . e($badge_class_suffix) . '">' . e($display_text) . '</span>';
    }
}
function generate_csrf_token()
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Tidak ada tag PHP penutup jika ini adalah akhir dari file dan hanya berisi kode PHP.