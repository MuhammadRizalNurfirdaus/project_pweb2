<?php
// File: C:\xampp\htdocs\Cilengkrang-Web-Wisata\controllers\JenisTiketController.php

require_once __DIR__ . '/../config/config.php'; // Memuat konfigurasi dasar
require_once __DIR__ . '/../models/JenisTiket.php';   // Memuat Model JenisTiket
// require_once __DIR__ . '/../models/Wisata.php'; // Diperlukan jika ingin validasi wisata_id dari tabel wisata

class JenisTiketController
{
    /**
     * Memproses pembuatan jenis tiket baru.
     * Menerima data dari handler form, melakukan validasi, dan memanggil Model.
     * @param array $data Array asosiatif data. Kunci: 'nama_layanan_display', 'tipe_hari', 'harga', 
     *                    'deskripsi' (opsional), 'aktif' (opsional), 'wisata_id' (opsional).
     * @return int|false ID jenis tiket baru jika berhasil, false jika gagal.
     */
    public static function create($data)
    {
        // Validasi dasar di Controller sebelum ke Model
        $nama_layanan = trim($data['nama_layanan_display'] ?? '');
        $tipe_hari = trim($data['tipe_hari'] ?? '');
        $harga = isset($data['harga']) ? filter_var($data['harga'], FILTER_VALIDATE_INT) : null;

        if (empty($nama_layanan)) {
            set_flash_message('danger', 'Nama layanan untuk jenis tiket wajib diisi.');
            return false;
        }
        $allowed_tipe_hari = ['Hari Kerja', 'Hari Libur', 'Semua Hari'];
        if (empty($tipe_hari) || !in_array($tipe_hari, $allowed_tipe_hari)) {
            set_flash_message('danger', 'Tipe hari untuk jenis tiket tidak valid atau wajib diisi.');
            return false;
        }
        if ($harga === null || $harga === false || $harga < 0) {
            set_flash_message('danger', 'Harga untuk jenis tiket wajib diisi dan harus angka non-negatif.');
            return false;
        }

        // // Opsional: Validasi wisata_id jika diberikan
        // if (isset($data['wisata_id']) && !empty($data['wisata_id'])) {
        //     if (!filter_var($data['wisata_id'], FILTER_VALIDATE_INT) || !Wisata::getById((int)$data['wisata_id'])) {
        //         set_flash_message('danger', 'ID Destinasi Wisata terkait tidak valid.');
        //         return false;
        //     }
        // }

        $data_to_save = [
            'nama_layanan_display' => $nama_layanan,
            'tipe_hari' => $tipe_hari,
            'harga' => $harga,
            'deskripsi' => trim($data['deskripsi'] ?? null),
            'aktif' => isset($data['aktif']) ? (int)$data['aktif'] : 1, // Default 1 (true)
            'wisata_id' => isset($data['wisata_id']) && !empty($data['wisata_id']) ? (int)$data['wisata_id'] : null
        ];

        $new_id = JenisTiket::create($data_to_save);
        if ($new_id) {
            return $new_id;
        }
        // Pesan error spesifik sudah di-log oleh Model atau set di atas
        if (!isset($_SESSION['flash_message'])) { // Hindari menimpa pesan error yang lebih spesifik dari validasi
            set_flash_message('danger', 'Gagal menyimpan jenis tiket ke database.');
        }
        return false;
    }

    /**
     * Mengambil semua jenis tiket.
     * @return array Array data jenis tiket, atau array kosong jika tidak ada/error.
     */
    public static function getAll()
    {
        return JenisTiket::getAll(); // Mendelegasikan ke Model
    }

    /**
     * Mengambil satu jenis tiket berdasarkan ID.
     * @param int $id ID jenis tiket.
     * @return array|null Data jenis tiket atau null jika tidak ditemukan/error.
     */
    public static function getById($id)
    {
        $id_val = filter_var($id, FILTER_VALIDATE_INT);
        if ($id_val === false || $id_val <= 0) {
            error_log("JenisTiketController::getById() - Error: ID tidak valid (" . e($id) . ").");
            return null;
        }
        return JenisTiket::getById($id_val); // Mendelegasikan ke Model
    }

    /**
     * Memproses pembaruan data jenis tiket.
     * @param array $data Array data yang akan diupdate (harus ada 'id').
     * Kunci: 'id', 'nama_layanan_display', 'tipe_hari', 'harga', 'deskripsi', 'aktif', 'wisata_id'.
     * @return bool True jika berhasil, false jika gagal.
     */
    public static function update($data)
    {
        $id = isset($data['id']) ? filter_var($data['id'], FILTER_VALIDATE_INT) : null;
        if ($id === null || $id === false || $id <= 0) {
            set_flash_message('danger', 'ID Jenis Tiket tidak valid untuk pembaruan.');
            return false;
        }

        $nama_layanan = trim($data['nama_layanan_display'] ?? '');
        $tipe_hari = trim($data['tipe_hari'] ?? '');
        $harga = isset($data['harga']) ? filter_var($data['harga'], FILTER_VALIDATE_INT) : null;

        if (empty($nama_layanan)) {
            set_flash_message('danger', 'Nama layanan untuk jenis tiket wajib diisi.');
            return false;
        }
        $allowed_tipe_hari = ['Hari Kerja', 'Hari Libur', 'Semua Hari'];
        if (empty($tipe_hari) || !in_array($tipe_hari, $allowed_tipe_hari)) {
            set_flash_message('danger', 'Tipe hari untuk jenis tiket tidak valid atau wajib diisi.');
            return false;
        }
        if ($harga === null || $harga === false || $harga < 0) {
            set_flash_message('danger', 'Harga untuk jenis tiket wajib diisi dan harus angka non-negatif.');
            return false;
        }

        // // Opsional: Validasi wisata_id jika diberikan
        // if (isset($data['wisata_id']) && !empty($data['wisata_id'])) {
        //     if (!filter_var($data['wisata_id'], FILTER_VALIDATE_INT) || !Wisata::getById((int)$data['wisata_id'])) {
        //         set_flash_message('danger', 'ID Destinasi Wisata terkait tidak valid.');
        //         return false;
        //     }
        // }

        $data_to_update = [
            'id' => $id,
            'nama_layanan_display' => $nama_layanan,
            'tipe_hari' => $tipe_hari,
            'harga' => $harga,
            'deskripsi' => trim($data['deskripsi'] ?? null),
            'aktif' => isset($data['aktif']) ? (int)$data['aktif'] : 0, // Jika checkbox tidak dikirim, anggap 0 (false)
            'wisata_id' => isset($data['wisata_id']) && !empty($data['wisata_id']) ? (int)$data['wisata_id'] : null
        ];

        $success = JenisTiket::update($data_to_update);
        if (!$success && !isset($_SESSION['flash_message'])) {
            set_flash_message('danger', 'Gagal memperbarui jenis tiket di database.');
        }
        return $success;
    }

    /**
     * Memproses penghapusan jenis tiket.
     * @param int $id ID jenis tiket yang akan dihapus.
     * @return bool True jika berhasil, false jika gagal.
     */
    public static function delete($id)
    {
        $id_val = filter_var($id, FILTER_VALIDATE_INT);
        if ($id_val === false || $id_val <= 0) {
            set_flash_message('danger', 'ID jenis tiket tidak valid untuk penghapusan.');
            return false;
        }

        $success = JenisTiket::delete($id_val);
        if (!$success && !isset($_SESSION['flash_message'])) {
            // Model mungkin sudah set error log, kita set flash message jika belum ada
            set_flash_message('danger', 'Gagal menghapus jenis tiket. Mungkin masih digunakan dalam pemesanan atau terjadi kesalahan.');
        }
        return $success;
    }

    /**
     * Mengambil jenis tiket yang aktif berdasarkan layanan dan tipe hari.
     * Digunakan oleh sistem pemesanan untuk menentukan harga.
     * @param string $nama_layanan
     * @param string $tipe_hari
     * @return array|null
     */
    public static function getHargaTiketAktif($nama_layanan, $tipe_hari)
    {
        if (empty($nama_layanan) || empty($tipe_hari)) {
            return null;
        }
        return JenisTiket::getActiveByLayananAndTipeHari($nama_layanan, $tipe_hari);
    }
}
