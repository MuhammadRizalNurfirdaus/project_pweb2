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
     * Selalu panggil exit() setelah header redirect untuk menghentikan eksekusi skrip.
     * @param string $path Path tujuan relatif terhadap BASE_URL (misal: 'auth/login.php' atau 'admin/dashboard.php').
     */
    function redirect($path)
    {
        if (!defined('BASE_URL')) {
            error_log("FATAL ERROR di fungsi redirect(): Konstanta BASE_URL tidak terdefinisi. Pastikan config.php dimuat dengan benar dan mendefinisikan BASE_URL.");
            // Darurat jika BASE_URL tidak ada, coba redirect ke root path relatif.
            // Ini mungkin tidak selalu bekerja tergantung konfigurasi server.
            $fallback_path = '/' . ltrim((string)$path, '/'); // Pastikan path adalah string
            header('Location: ' . $fallback_path);
            exit("Kesalahan konfigurasi fatal: BASE_URL tidak ditemukan. Proses redirect dibatalkan.");
        }
        // Hapus slash di awal $path jika ada, karena BASE_URL sudah diakhiri slash.
        header('Location: ' . BASE_URL . ltrim((string)$path, '/'));
        exit;
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
     * menjadi format Indonesia (contoh: 12 Mei 2024, 15:30 atau 12 Mei 2024).
     * @param string|null $tanggal_mysql Tanggal dalam format MySQL.
     * @param bool $dengan_waktu True untuk menyertakan waktu, false hanya tanggal.
     * @param bool $hari_singkat True untuk nama hari singkat (Min, Sen), false untuk nama hari lengkap.
     * @return string Tanggal dalam format Indonesia atau '-' jika input tidak valid atau kosong.
     */
    function formatTanggalIndonesia($tanggal_mysql, $dengan_waktu = false, $hari_singkat = false)
    {
        if (empty($tanggal_mysql) || $tanggal_mysql === '0000-00-00' || $tanggal_mysql === '0000-00-00 00:00:00') {
            return '-'; // Return placeholder untuk tanggal tidak valid/kosong
        }

        try {
            // Pastikan timezone default sudah diset (seharusnya di config.php)
            // Ini sebagai pengaman tambahan jika fungsi dipanggil dari konteks lain.
            if (empty(date_default_timezone_get()) || date_default_timezone_get() === 'UTC') {
                date_default_timezone_set('Asia/Jakarta'); // Set default jika belum atau UTC
            }
            $date_obj = new DateTime($tanggal_mysql);
        } catch (Exception $e) {
            error_log("Error formatTanggalIndonesia: Tanggal input tidak valid - '" . e($tanggal_mysql) . "' - Pesan Exception: " . $e->getMessage());
            return '-'; // Kembalikan placeholder jika tanggal tidak bisa diparsing
        }

        $nama_hari_id = [
            'Sunday'    => $hari_singkat ? 'Min' : 'Minggu',
            'Monday'    => $hari_singkat ? 'Sen' : 'Senin',
            'Tuesday'   => $hari_singkat ? 'Sel' : 'Selasa',
            'Wednesday' => $hari_singkat ? 'Rab' : 'Rabu',
            'Thursday'  => $hari_singkat ? 'Kam' : 'Kamis',
            'Friday'    => $hari_singkat ? 'Jum' : 'Jumat',
            'Saturday'  => $hari_singkat ? 'Sab' : 'Sabtu'
        ];
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

        $hari_en = $date_obj->format('l'); // Nama hari lengkap dalam bahasa Inggris
        $hari_id = $nama_hari_id[$hari_en] ?? $hari_en; // Fallback ke nama Inggris jika tidak ada

        $tanggal = $date_obj->format('j');    // Tanggal (1-31)
        $bulan_idx = (int)$date_obj->format('n'); // Bulan (1-12)
        $bulan_id = $nama_bulan_id[$bulan_idx] ?? $date_obj->format('M'); // Fallback ke nama bulan singkat Inggris
        $tahun = $date_obj->format('Y');    // Tahun (4 digit)

        $format_indonesia = $tanggal . ' ' . $bulan_id . ' ' . $tahun;

        if ($dengan_waktu) {
            // Cek apakah string tanggal input mengandung ':' yang menandakan ada waktu
            if (strpos($tanggal_mysql, ':') !== false) {
                $format_indonesia .= ', ' . $date_obj->format('H:i'); // Tambah Jam:Menit
            }
        }
        // Jika ingin menyertakan nama hari:
        // return $hari_id . ', ' . $format_indonesia;
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
     * Mengambil data input dari POST atau GET.
     * Melakukan trim pada string.
     * @param string $key Kunci dari data input (nama field form).
     * @param mixed $default Nilai default jika kunci tidak ditemukan atau kosong setelah trim (opsional).
     * @param string $method Metode 'post' atau 'get' (default 'post').
     * @return mixed Nilai input yang sudah di-trim atau nilai default.
     */
    function input($key, $default = null, $method = 'post')
    {
        $source = null;
        if (strtolower($method) === 'post') {
            $source = $_POST;
        } elseif (strtolower($method) === 'get') {
            $source = $_GET;
        }

        if ($source !== null && isset($source[$key])) {
            if (is_string($source[$key])) {
                $value = trim($source[$key]);
                // Kembalikan default jika string menjadi kosong setelah trim,
                // kecuali jika defaultnya memang string kosong dan itu yang diinginkan.
                // Ini opsional, tergantung kebutuhan Anda.
                // if ($value === '' && $default !== '') {
                //     return $default;
                // }
                return $value;
            }
            return $source[$key]; // Kembalikan nilai asli jika bukan string (misal array)
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
        if ($text === null) {
            return '';
        }
        $text_clean = strip_tags((string)$text); // Hapus tag HTML dan pastikan string
        if (mb_strlen($text_clean, 'UTF-8') > $limit) {
            $text_cut = mb_substr($text_clean, 0, $limit, 'UTF-8');
            // Cari spasi terakhir dalam potongan teks
            $last_space = mb_strrpos($text_cut, ' ', 0, 'UTF-8');
            if ($last_space !== false) {
                // Potong pada spasi terakhir jika ditemukan
                $text_final = mb_substr($text_cut, 0, $last_space, 'UTF-8');
            } else {
                // Jika tidak ada spasi, potong langsung pada limit (mungkin memotong kata)
                $text_final = $text_cut;
            }
            return $text_final . $ellipsis;
        }
        return $text_clean; // Kembalikan teks bersih jika sudah di bawah limit
    }
}

// Tambahkan fungsi helper lain yang mungkin Anda perlukan
// seperti fungsi untuk validasi email, URL, angka, dll.
// Contoh:
if (!function_exists('is_valid_email')) {
    function is_valid_email($email)
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
}
