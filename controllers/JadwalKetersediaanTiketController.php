<?php
// File: C:\xampp\htdocs\Cilengkrang-Web-Wisata\controllers\JadwalKetersediaanTiketController.php

// Diasumsikan config.php sudah memuat Model dan menginisialisasinya.
// if (!class_exists('JadwalKetersediaanTiket')) require_once __DIR__ . '/../models/JadwalKetersediaanTiket.php';
// if (!class_exists('JenisTiket')) require_once __DIR__ . '/../models/JenisTiket.php';

class JadwalKetersediaanTiketController
{
    /**
     * Memproses pembuatan jadwal ketersediaan tiket baru.
     * @param array $data Data dari form.
     * @return int|string|false ID jadwal baru, string kode error, atau false jika gagal.
     */
    public static function create(array $data)
    {
        // Validasi dasar di Controller
        $jenis_tiket_id = isset($data['jenis_tiket_id']) ? filter_var($data['jenis_tiket_id'], FILTER_VALIDATE_INT) : null;
        $tanggal = trim($data['tanggal'] ?? '');
        $jumlah_total = isset($data['jumlah_total_tersedia']) ? filter_var($data['jumlah_total_tersedia'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]) : null;
        // Default jumlah saat ini sama dengan jumlah total jika tidak dispesifikkan
        $jumlah_saat_ini = isset($data['jumlah_saat_ini_tersedia']) ?
            filter_var($data['jumlah_saat_ini_tersedia'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]) :
            $jumlah_total;
        $aktif = isset($data['aktif']) ? (int)$data['aktif'] : 1;

        if (!$jenis_tiket_id || $jenis_tiket_id <= 0) {
            if (function_exists('set_flash_message')) set_flash_message('danger', 'Jenis Tiket harus dipilih.');
            return 'invalid_jenis_tiket_id';
        }
        if (class_exists('JenisTiket') && method_exists('JenisTiket', 'findById')) { // Pastikan Model JenisTiket ada
            if (!JenisTiket::findById($jenis_tiket_id)) { // Menggunakan findById
                if (function_exists('set_flash_message')) set_flash_message('danger', 'Jenis Tiket yang dipilih tidak valid atau tidak ditemukan.');
                return 'jenis_tiket_not_found';
            }
        } else {
            error_log("JadwalKetersediaanTiketController::create() - Model JenisTiket atau metode findById tidak tersedia untuk validasi.");
        }

        if (empty($tanggal) || !DateTime::createFromFormat('Y-m-d', $tanggal) || $tanggal < date('Y-m-d')) {
            if (function_exists('set_flash_message')) set_flash_message('danger', 'Format tanggal tidak valid (YYYY-MM-DD) atau tanggal sudah lewat.');
            return 'invalid_tanggal';
        }
        if ($jumlah_total === null || $jumlah_total === false) { // Harga 0 diizinkan
            if (function_exists('set_flash_message')) set_flash_message('danger', 'Jumlah Total Tiket Tersedia harus berupa angka (minimal 0).');
            return 'invalid_jumlah_total';
        }
        if ($jumlah_saat_ini === null || $jumlah_saat_ini === false || $jumlah_saat_ini > $jumlah_total) {
            if (function_exists('set_flash_message')) set_flash_message('danger', 'Jumlah Tiket Saat Ini Tersedia tidak valid atau melebihi Jumlah Total.');
            return 'invalid_jumlah_saat_ini';
        }
        if (!in_array($aktif, [0, 1])) $aktif = 1; // Default ke aktif jika tidak valid

        $data_to_model = [
            'jenis_tiket_id' => $jenis_tiket_id,
            'tanggal' => $tanggal,
            'jumlah_total_tersedia' => $jumlah_total,
            'jumlah_saat_ini_tersedia' => $jumlah_saat_ini, // Model akan mengoreksi jika > total
            'aktif' => $aktif
        ];

        if (!class_exists('JadwalKetersediaanTiket') || !method_exists('JadwalKetersediaanTiket', 'create') || !method_exists('JadwalKetersediaanTiket', 'getLastError')) {
            error_log("JadwalKetersediaanTiketController::create() - Model/Metode JadwalKetersediaanTiket tidak siap.");
            if (function_exists('set_flash_message')) set_flash_message('danger', 'Kesalahan sistem: Komponen data tidak siap (JKTC-C01).');
            return false;
        }

        $result = JadwalKetersediaanTiket::create($data_to_model);

        if (is_numeric($result) && $result > 0) {
            return $result; // Sukses, kembalikan ID
        } elseif ($result === 'duplicate') {
            if (function_exists('set_flash_message')) set_flash_message('danger', 'Gagal: Jadwal untuk jenis tiket dan tanggal tersebut sudah ada.');
            return 'duplicate';
        } else {
            if (function_exists('set_flash_message') && !isset($_SESSION['flash_message'])) {
                set_flash_message('danger', 'Gagal menyimpan jadwal ketersediaan. ' . JadwalKetersediaanTiket::getLastError());
            }
            error_log("JadwalKetersediaanTiketController::create() - JadwalKetersediaanTiket::create gagal. Error: " . JadwalKetersediaanTiket::getLastError());
            return false;
        }
    }

    /**
     * Mengambil semua jadwal ketersediaan.
     * @return array|false Daftar jadwal atau false jika error.
     */
    public static function getAllForAdmin() // Nama metode disesuaikan
    {
        if (!class_exists('JadwalKetersediaanTiket') || !method_exists('JadwalKetersediaanTiket', 'getAll')) {
            error_log("JadwalKetersediaanTiketController::getAllForAdmin() - Model/Metode tidak ada.");
            return false;
        }
        return JadwalKetersediaanTiket::getAll();
    }

    /**
     * Mengambil satu jadwal berdasarkan ID.
     * @param int $id ID Jadwal.
     * @return array|null Data jadwal atau null.
     */
    public static function getById($id) // Nama metode ini sudah benar
    {
        $id_val = filter_var($id, FILTER_VALIDATE_INT);
        if ($id_val === false || $id_val <= 0) {
            error_log("JadwalKetersediaanTiketController::getById() - ID tidak valid: " . $id);
            return null;
        }
        // PERBAIKAN: Panggil findById jika itu nama metode di Model
        if (!class_exists('JadwalKetersediaanTiket') || !method_exists('JadwalKetersediaanTiket', 'findById')) {
            error_log("JadwalKetersediaanTiketController::getById() - Model/Metode JadwalKetersediaanTiket::findById tidak ada.");
            return null;
        }
        return JadwalKetersediaanTiket::findById($id_val); // Menggunakan findById
    }

    /**
     * Memproses pembaruan data jadwal ketersediaan.
     * @param array $data Array data yang akan diupdate.
     * @return bool|string True jika berhasil, string kode error, atau false jika gagal umum.
     */
    public static function update(array $data)
    {
        $id = isset($data['id']) ? filter_var($data['id'], FILTER_VALIDATE_INT) : null;
        if (!$id || $id <= 0) {
            if (function_exists('set_flash_message')) set_flash_message('danger', 'ID Jadwal tidak valid untuk pembaruan.');
            return 'invalid_id';
        }

        // Validasi lain seperti di metode create()
        $jenis_tiket_id = isset($data['jenis_tiket_id']) ? filter_var($data['jenis_tiket_id'], FILTER_VALIDATE_INT) : null;
        $tanggal = trim($data['tanggal'] ?? '');
        $jumlah_total = isset($data['jumlah_total_tersedia']) ? filter_var($data['jumlah_total_tersedia'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]) : null;
        $jumlah_saat_ini = isset($data['jumlah_saat_ini_tersedia']) ? filter_var($data['jumlah_saat_ini_tersedia'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]) : null;
        $aktif = isset($data['aktif']) ? (int)$data['aktif'] : 0;

        if (!$jenis_tiket_id || $jenis_tiket_id <= 0) {
            if (function_exists('set_flash_message')) set_flash_message('danger', 'Jenis Tiket harus dipilih.');
            return 'invalid_jenis_tiket_id';
        }
        if (class_exists('JenisTiket') && method_exists('JenisTiket', 'findById')) {
            if (!JenisTiket::findById($jenis_tiket_id)) {
                if (function_exists('set_flash_message')) set_flash_message('danger', 'Jenis Tiket tidak valid.');
                return 'jenis_tiket_not_found';
            }
        }
        if (empty($tanggal) || !DateTime::createFromFormat('Y-m-d', $tanggal)) {
            if (function_exists('set_flash_message')) set_flash_message('danger', 'Format tanggal tidak valid.');
            return 'invalid_tanggal';
        }
        if ($jumlah_total === null || $jumlah_total === false) {
            if (function_exists('set_flash_message')) set_flash_message('danger', 'Jumlah Total harus angka.');
            return 'invalid_jumlah_total';
        }
        if ($jumlah_saat_ini === null || $jumlah_saat_ini === false || $jumlah_saat_ini > $jumlah_total) {
            if (function_exists('set_flash_message')) set_flash_message('danger', 'Jumlah Saat Ini tidak valid.');
            return 'invalid_jumlah_saat_ini';
        }
        if (!in_array($aktif, [0, 1])) $aktif = 0;

        $data_to_model = [
            'id' => $id,
            'jenis_tiket_id' => $jenis_tiket_id,
            'tanggal' => $tanggal,
            'jumlah_total_tersedia' => $jumlah_total,
            'jumlah_saat_ini_tersedia' => $jumlah_saat_ini,
            'aktif' => $aktif
        ];

        if (!class_exists('JadwalKetersediaanTiket') || !method_exists('JadwalKetersediaanTiket', 'update') || !method_exists('JadwalKetersediaanTiket', 'getLastError')) {
            error_log("JadwalKetersediaanTiketController::update() - Model/Metode tidak siap.");
            if (function_exists('set_flash_message')) set_flash_message('danger', 'Kesalahan sistem: Komponen update tidak siap (JKTC-U01).');
            return false;
        }

        $result = JadwalKetersediaanTiket::update($data_to_model);

        if ($result === true) {
            return true;
        } elseif ($result === 'duplicate') {
            if (function_exists('set_flash_message')) set_flash_message('danger', 'Gagal: Jadwal untuk jenis tiket dan tanggal tersebut sudah ada (untuk entri lain).');
            return 'duplicate';
        } else {
            if (function_exists('set_flash_message') && !isset($_SESSION['flash_message'])) {
                set_flash_message('danger', 'Gagal memperbarui jadwal. ' . JadwalKetersediaanTiket::getLastError());
            }
            error_log("JadwalKetersediaanTiketController::update() - JadwalKetersediaanTiket::update gagal. Error: " . JadwalKetersediaanTiket::getLastError());
            return false;
        }
    }

    // Metode delete, dll. bisa ditambahkan di sini jika ada logika bisnis tambahan
    // sebelum memanggil Model. Jika tidak, bisa dipanggil langsung dari skrip proses.

} // End of class JadwalKetersediaanTiketController