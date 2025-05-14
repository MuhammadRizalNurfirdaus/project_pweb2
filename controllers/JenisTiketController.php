<?php
// File: C:\xampp\htdocs\Cilengkrang-Web-Wisata\controllers\JenisTiketController.php

/**
 * JenisTiketController
 * Bertanggung jawab untuk logika bisnis terkait Jenis Tiket.
 * Berinteraksi dengan Model JenisTiket dan mungkin Wisata.
 * 
 * PENTING:
 * - Diasumsikan config.php sudah memuat SEMUA file Model yang diperlukan
 *   DAN sudah memanggil metode statis `ModelName::setDbConnection($conn)` atau `ModelName::init()` 
 *   untuk setiap Model (JenisTiket, Wisata jika dipakai).
 * - Fungsi helper (set_flash_message, e, redirect, input) diasumsikan tersedia dari config.php.
 */

// Pemuatan Model idealnya ditangani oleh config.php atau autoloader.
// Jika tidak, uncomment baris yang relevan di bawah ini atau di skrip pemanggil.
// if (!class_exists('JenisTiket')) { require_once __DIR__ . '/../models/JenisTiket.php'; }
// if (!class_exists('Wisata')) { require_once __DIR__ . '/../models/Wisata.php'; } // Jika validasi wisata_id diaktifkan

class JenisTiketController
{
    /**
     * Memproses pembuatan jenis tiket baru.
     * @param array $data Data dari form.
     * @return int|string|false ID jenis tiket baru, string kode error ('duplicate', dll.), atau false jika gagal.
     */
    public static function create(array $data)
    {
        // Validasi dasar di Controller
        $nama_layanan = trim($data['nama_layanan_display'] ?? '');
        $tipe_hari = trim($data['tipe_hari'] ?? '');
        $harga = isset($data['harga']) ? filter_var($data['harga'], FILTER_VALIDATE_FLOAT) : null; // Harga bisa float
        $wisata_id_input = $data['wisata_id'] ?? null;

        if (empty($nama_layanan)) {
            if (function_exists('set_flash_message')) set_flash_message('danger', 'Nama layanan untuk jenis tiket wajib diisi.');
            return 'missing_nama_layanan';
        }

        // Ambil ALLOWED_TIPE_HARI dari Model jika ada
        $allowed_tipe_hari = (class_exists('JenisTiket') && defined('JenisTiket::ALLOWED_TIPE_HARI')) ? JenisTiket::ALLOWED_TIPE_HARI : ['Hari Kerja', 'Hari Libur', 'Semua Hari'];
        if (empty($tipe_hari) || !in_array($tipe_hari, $allowed_tipe_hari)) {
            if (function_exists('set_flash_message')) set_flash_message('danger', 'Tipe hari untuk jenis tiket tidak valid atau wajib diisi.');
            return 'invalid_tipe_hari';
        }
        if ($harga === null || $harga === false || $harga < 0) {
            if (function_exists('set_flash_message')) set_flash_message('danger', 'Harga untuk jenis tiket wajib diisi dan harus angka non-negatif.');
            return 'invalid_harga';
        }

        $wisata_id_to_save = null;
        if (!empty($wisata_id_input)) {
            $wisata_id_val = filter_var($wisata_id_input, FILTER_VALIDATE_INT);
            if ($wisata_id_val && $wisata_id_val > 0) {
                // Opsional: Validasi apakah wisata_id ada di tabel wisata
                if (class_exists('Wisata') && method_exists('Wisata', 'getById')) {
                    if (!Wisata::getById($wisata_id_val)) {
                        if (function_exists('set_flash_message')) set_flash_message('danger', 'ID Destinasi Wisata terkait tidak valid atau tidak ditemukan.');
                        return 'invalid_wisata_id';
                    }
                }
                $wisata_id_to_save = $wisata_id_val;
            } elseif (!empty($wisata_id_input)) { // Jika diisi tapi tidak valid int > 0
                if (function_exists('set_flash_message')) set_flash_message('danger', 'Format ID Destinasi Wisata tidak valid.');
                return 'invalid_wisata_id_format';
            }
        }

        $data_to_model = [
            'nama_layanan_display' => $nama_layanan,
            'tipe_hari' => $tipe_hari,
            'harga' => (float)$harga, // Pastikan float
            'deskripsi' => trim($data['deskripsi'] ?? null),
            'aktif' => isset($data['aktif']) ? (int)$data['aktif'] : 1,
            'wisata_id' => $wisata_id_to_save
        ];

        if (!class_exists('JenisTiket') || !method_exists('JenisTiket', 'create') || !method_exists('JenisTiket', 'getLastError')) {
            error_log("JenisTiketController::create() - Model/Metode JenisTiket tidak siap.");
            if (function_exists('set_flash_message')) set_flash_message('danger', 'Kesalahan sistem: Komponen data tidak siap (JTC-C01).');
            return false;
        }

        $result = JenisTiket::create($data_to_model);

        if (is_numeric($result) && $result > 0) {
            // Pesan sukses akan diset oleh skrip pemanggil (proses_tambah_jenis_tiket.php)
            return $result; // Kembalikan ID
        } elseif (is_string($result)) { // Model mengembalikan kode error spesifik seperti 'duplicate'
            if (function_exists('set_flash_message') && $result === 'duplicate') {
                set_flash_message('danger', 'Gagal: Jenis tiket dengan kombinasi nama layanan, tipe hari, dan destinasi yang sama sudah ada.');
            } elseif (function_exists('set_flash_message') && !isset($_SESSION['flash_message'])) {
                set_flash_message('danger', 'Gagal menambahkan jenis tiket: ' . htmlspecialchars($result));
            }
            return $result; // Kembalikan kode error
        } else { // Model mengembalikan false (error DB umum)
            if (function_exists('set_flash_message') && !isset($_SESSION['flash_message'])) {
                set_flash_message('danger', 'Gagal menyimpan jenis tiket ke database. ' . JenisTiket::getLastError());
            }
            error_log("JenisTiketController::create() - JenisTiket::create gagal. Error: " . JenisTiket::getLastError());
            return false;
        }
    }

    /**
     * Mengambil semua jenis tiket untuk admin.
     * @return array Array data jenis tiket, atau array kosong.
     */
    public static function getAllForAdmin()
    {
        if (!class_exists('JenisTiket') || !method_exists('JenisTiket', 'getAll')) {
            error_log("JenisTiketController::getAllForAdmin() - Model/Metode JenisTiket::getAll tidak ada.");
            return [];
        }
        return JenisTiket::getAll(); // Model sudah memiliki default order by
    }

    /**
     * Mengambil satu jenis tiket berdasarkan ID.
     * @param int $id ID jenis tiket.
     * @return array|null Data jenis tiket atau null.
     */
    public static function getById($id)
    {
        $id_val = filter_var($id, FILTER_VALIDATE_INT);
        if ($id_val === false || $id_val <= 0) {
            error_log("JenisTiketController::getById() - ID tidak valid: " . $id);
            return null;
        }
        if (!class_exists('JenisTiket') || !method_exists('JenisTiket', 'findById')) { // Pastikan findById ada
            error_log("JenisTiketController::getById() - Model/Metode JenisTiket::findById tidak ada.");
            return null;
        }
        return JenisTiket::findById($id_val); // Menggunakan findById dari Model
    }

    /**
     * Memproses pembaruan data jenis tiket.
     * @param array $data Array data yang akan diupdate.
     * @return bool|string True jika berhasil, string kode error, atau false jika gagal umum.
     */
    public static function update(array $data)
    {
        $id = isset($data['id']) ? filter_var($data['id'], FILTER_VALIDATE_INT) : null;
        if ($id === null || $id === false || $id <= 0) {
            if (function_exists('set_flash_message')) set_flash_message('danger', 'ID Jenis Tiket tidak valid untuk pembaruan.');
            return 'invalid_id';
        }

        $nama_layanan = trim($data['nama_layanan_display'] ?? '');
        $tipe_hari = trim($data['tipe_hari'] ?? '');
        $harga = isset($data['harga']) ? filter_var($data['harga'], FILTER_VALIDATE_FLOAT) : null;
        $wisata_id_input = $data['wisata_id'] ?? null;

        if (empty($nama_layanan)) {
            if (function_exists('set_flash_message')) set_flash_message('danger', 'Nama layanan wajib diisi.');
            return 'missing_nama_layanan';
        }
        $allowed_tipe_hari = (class_exists('JenisTiket') && defined('JenisTiket::ALLOWED_TIPE_HARI')) ? JenisTiket::ALLOWED_TIPE_HARI : ['Hari Kerja', 'Hari Libur', 'Semua Hari'];
        if (empty($tipe_hari) || !in_array($tipe_hari, $allowed_tipe_hari)) {
            if (function_exists('set_flash_message')) set_flash_message('danger', 'Tipe hari tidak valid.');
            return 'invalid_tipe_hari';
        }
        if ($harga === null || $harga === false || $harga < 0) {
            if (function_exists('set_flash_message')) set_flash_message('danger', 'Harga wajib diisi dan valid.');
            return 'invalid_harga';
        }

        $wisata_id_to_save = null;
        if (!empty($wisata_id_input)) {
            $wisata_id_val = filter_var($wisata_id_input, FILTER_VALIDATE_INT);
            if ($wisata_id_val && $wisata_id_val > 0) {
                if (class_exists('Wisata') && method_exists('Wisata', 'getById')) {
                    if (!Wisata::getById($wisata_id_val)) {
                        if (function_exists('set_flash_message')) set_flash_message('danger', 'ID Destinasi Wisata terkait tidak valid.');
                        return 'invalid_wisata_id';
                    }
                }
                $wisata_id_to_save = $wisata_id_val;
            } elseif (!empty($wisata_id_input)) {
                if (function_exists('set_flash_message')) set_flash_message('danger', 'Format ID Destinasi Wisata tidak valid.');
                return 'invalid_wisata_id_format';
            }
        }

        $data_to_model = [
            'id' => $id,
            'nama_layanan_display' => $nama_layanan,
            'tipe_hari' => $tipe_hari,
            'harga' => (float)$harga,
            'deskripsi' => trim($data['deskripsi'] ?? null),
            'aktif' => isset($data['aktif']) ? (int)$data['aktif'] : 0,
            'wisata_id' => $wisata_id_to_save
        ];

        if (!class_exists('JenisTiket') || !method_exists('JenisTiket', 'update') || !method_exists('JenisTiket', 'getLastError')) {
            error_log("JenisTiketController::update() - Model/Metode JenisTiket tidak siap.");
            if (function_exists('set_flash_message')) set_flash_message('danger', 'Kesalahan sistem: Komponen update tidak siap (JTC-U01).');
            return false;
        }

        $result = JenisTiket::update($data_to_model);

        if ($result === true) {
            return true;
        } elseif ($result === 'duplicate') {
            if (function_exists('set_flash_message')) set_flash_message('danger', 'Gagal: Jenis tiket dengan kombinasi nama, tipe hari, dan destinasi yang sama sudah ada untuk entri lain.');
            return 'duplicate';
        } else {
            if (function_exists('set_flash_message') && !isset($_SESSION['flash_message'])) {
                set_flash_message('danger', 'Gagal memperbarui jenis tiket di database. ' . JenisTiket::getLastError());
            }
            error_log("JenisTiketController::update() - JenisTiket::update gagal. Error: " . JenisTiket::getLastError());
            return false;
        }
    }

    /**
     * Memproses penghapusan jenis tiket.
     * @param int $id ID jenis tiket.
     * @return bool|string True jika berhasil, string kode error, atau false jika gagal umum.
     */
    public static function delete($id)
    {
        $id_val = filter_var($id, FILTER_VALIDATE_INT);
        if ($id_val === false || $id_val <= 0) {
            if (function_exists('set_flash_message')) set_flash_message('danger', 'ID jenis tiket tidak valid untuk penghapusan.');
            error_log("JenisTiketController::delete() - ID tidak valid: " . $id);
            return 'invalid_id';
        }

        if (!class_exists('JenisTiket') || !method_exists('JenisTiket', 'delete') || !method_exists('JenisTiket', 'getLastError')) {
            error_log("JenisTiketController::delete() - Model/Metode JenisTiket tidak siap.");
            if (function_exists('set_flash_message')) set_flash_message('danger', 'Kesalahan sistem: Komponen hapus tidak siap (JTC-D01).');
            return false;
        }

        $result = JenisTiket::delete($id_val);

        if ($result === true) {
            // Pesan sukses diset oleh skrip pemanggil (hapus_jenis_tiket.php)
            return true;
        } elseif ($result === false && isset($_SESSION['flash_message'])) {
            // Model mungkin sudah set flash message (misal karena foreign key constraint)
            return false;
        } else { // Gagal umum dari Model
            if (function_exists('set_flash_message')) {
                set_flash_message('danger', 'Gagal menghapus jenis tiket. ' . JenisTiket::getLastError());
            }
            error_log("JenisTiketController::delete() - JenisTiket::delete gagal. Error: " . JenisTiket::getLastError());
            return false;
        }
    }

    /**
     * Mengambil jenis tiket yang aktif berdasarkan layanan dan tipe hari.
     * @param string $nama_layanan
     * @param string $tipe_hari
     * @return array|null
     */
    public static function getActiveByLayananAndTipeHari($nama_layanan, $tipe_hari)
    {
        if (empty($nama_layanan) || empty($tipe_hari)) {
            return null;
        }
        if (!class_exists('JenisTiket') || !method_exists('JenisTiket', 'getActiveByLayananAndTipeHari')) {
            error_log("JenisTiketController::getActiveByLayananAndTipeHari() - Model/Metode tidak ada.");
            return null;
        }
        return JenisTiket::getActiveByLayananAndTipeHari($nama_layanan, $tipe_hari);
    }
} // End of class JenisTiketController