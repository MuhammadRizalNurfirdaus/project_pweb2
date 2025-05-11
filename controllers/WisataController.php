<?php
// File: C:\xampp\htdocs\Cilengkrang-Web-Wisata\controllers\WisataController.php

// Memperbaiki kesalahan ketik __DIR__
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../models/Wisata.php';

class WisataController
{
    /**
     * Menangani pembuatan data destinasi wisata baru.
     * Menerima data dari view (misalnya tambah_wisata.php), melakukan validasi dasar,
     * dan memanggil Model untuk menyimpan data.
     * @param array $data Data dari form (kunci yang diharapkan: 'nama_wisata', 'deskripsi', opsional 'lokasi').
     * @param string|null $gambar_filename Nama file gambar yang sudah diunggah dan divalidasi (jika ada).
     * @return int|false ID record baru jika berhasil, false jika gagal.
     */
    public static function create($data, $gambar_filename = null)
    {
        // Validasi dasar di Controller sebelum ke Model
        // Model juga akan melakukan validasi internal.
        if (empty($data['nama_wisata'])) {
            error_log("WisataController::create() - Error: Nama Wisata tidak boleh kosong.");
            set_flash_message('danger', 'Nama destinasi wajib diisi.'); // Memberikan feedback ke pengguna
            return false;
        }
        if (empty($data['deskripsi'])) {
            error_log("WisataController::create() - Error: Deskripsi tidak boleh kosong.");
            set_flash_message('danger', 'Deskripsi destinasi wajib diisi.');
            return false;
        }

        // Data yang akan disimpan ke database melalui Model
        // Kunci array di sini harus konsisten dengan yang diharapkan oleh Model::create()
        $data_to_save = [
            'nama_wisata' => trim($data['nama_wisata']), // 'nama_wisata' akan dipetakan ke kolom 'nama' di Model
            'deskripsi' => trim($data['deskripsi']),
            'lokasi' => trim($data['lokasi'] ?? ''), // Memberikan default string kosong jika tidak ada
            'gambar' => $gambar_filename // Nama file gambar yang sudah diproses (bisa null)
        ];

        $new_id = Wisata::create($data_to_save);
        if ($new_id) {
            return $new_id;
        }
        // Pesan error spesifik sudah di-log oleh Model jika gagal
        set_flash_message('danger', 'Gagal menyimpan data destinasi wisata ke database.');
        return false;
    }

    /**
     * Mengambil semua data destinasi wisata.
     * @return array Array data wisata, atau array kosong jika tidak ada/error.
     */
    public static function getAll()
    {
        return Wisata::getAll(); // Langsung mendelegasikan ke Model
    }

    /**
     * Mengambil satu data destinasi wisata berdasarkan ID.
     * @param int $id ID destinasi wisata.
     * @return array|null Data wisata dalam bentuk array asosiatif, atau null jika tidak ditemukan/error.
     */
    public static function getById($id)
    {
        $id_val = filter_var($id, FILTER_VALIDATE_INT);
        if ($id_val === false || $id_val <= 0) {
            error_log("WisataController::getById() - Error: ID tidak valid (" . $id . ").");
            return null;
        }
        return Wisata::getById($id_val); // Mendelegasikan ke Model
    }

    /**
     * Menangani pembaruan data destinasi wisata.
     * @param array $data Data dari form (harus ada 'id', 'nama_wisata', 'deskripsi', opsional 'lokasi').
     * @param string|null $new_image_filename Nama file gambar baru, atau "REMOVE_IMAGE", atau null (tidak ada perubahan gambar).
     * @param string|null $old_image_filename Nama file gambar lama yang ada di database.
     * @return bool True jika berhasil, false jika gagal.
     */
    public static function update($data, $new_image_filename = null, $old_image_filename = null)
    {
        // Validasi dasar di Controller
        if (empty($data['id']) || !is_numeric($data['id']) || $data['id'] <= 0) {
            error_log("WisataController::update() - Error: ID tidak valid atau tidak ada.");
            set_flash_message('danger', 'ID destinasi tidak valid untuk pembaruan.');
            return false;
        }
        if (empty($data['nama_wisata'])) {
            error_log("WisataController::update() - Error: Nama Wisata tidak boleh kosong.");
            set_flash_message('danger', 'Nama destinasi wajib diisi.');
            return false;
        }
        if (empty($data['deskripsi'])) {
            error_log("WisataController::update() - Error: Deskripsi tidak boleh kosong.");
            set_flash_message('danger', 'Deskripsi destinasi wajib diisi.');
            return false;
        }

        // Data yang akan dikirim ke Model untuk diupdate
        $data_to_update = [
            'id' => (int)$data['id'],
            'nama_wisata' => trim($data['nama_wisata']), // 'nama_wisata' akan dipetakan ke kolom 'nama' di Model
            'deskripsi' => trim($data['deskripsi']),
            'lokasi' => trim($data['lokasi'] ?? '')
            // Penanganan 'gambar' akan dilakukan oleh Model berdasarkan $new_image_filename dan $old_image_filename
        ];

        $success = Wisata::update($data_to_update, $new_image_filename, $old_image_filename);
        if (!$success) {
            // Pesan error spesifik sudah di-log oleh Model jika gagal
            set_flash_message('danger', 'Gagal memperbarui data destinasi wisata di database.');
        }
        return $success;
    }

    /**
     * Menangani penghapusan data destinasi wisata.
     * @param int $id ID destinasi wisata yang akan dihapus.
     * @return bool True jika berhasil, false jika gagal.
     */
    public static function delete($id)
    {
        $id_val = filter_var($id, FILTER_VALIDATE_INT);
        if ($id_val === false || $id_val <= 0) {
            error_log("WisataController::delete() - Error: ID tidak valid (" . $id . ").");
            set_flash_message('danger', 'ID destinasi tidak valid untuk penghapusan.');
            return false;
        }

        $success = Wisata::delete($id_val);
        if (!$success) {
            // Pesan error spesifik sudah di-log oleh Model jika gagal (misalnya ID tidak ditemukan)
            set_flash_message('danger', 'Gagal menghapus destinasi wisata dari database atau destinasi tidak ditemukan.');
        }
        return $success;
    }
}
