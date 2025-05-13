<?php
// File: C:\xampp\htdocs\Cilengkrang-Web-Wisata\controllers\WisataController.php

// Diasumsikan config.php sudah memuat Model Wisata.php
// dan sudah memanggil Wisata::init($conn, UPLOADS_WISATA_PATH).
// if (!class_exists('Wisata')) { require_once __DIR__ . '/../models/Wisata.php'; }

class WisataController
{
    /**
     * Menangani pembuatan data destinasi wisata baru.
     * @param array $data Data dari form (kunci: 'nama_wisata', 'deskripsi', opsional 'lokasi').
     * @param string|null $gambar_filename Nama file gambar yang sudah diunggah (jika ada).
     * @return int|string|false ID record baru, string kode error, atau false.
     */
    public static function create(array $data, $gambar_filename = null)
    {
        if (!class_exists('Wisata') || !method_exists('Wisata', 'create') || !method_exists('Wisata', 'getLastError')) {
            error_log("WisataController::create() - Model/Metode Wisata tidak tersedia.");
            return 'system_error_model_unavailable';
        }

        $nama_wisata = trim($data['nama_wisata'] ?? '');
        $deskripsi = trim($data['deskripsi'] ?? '');

        if (empty($nama_wisata)) return 'missing_nama';
        if (empty($deskripsi)) return 'missing_deskripsi';

        $data_to_model = [
            'nama_wisata' => $nama_wisata, // Model akan memetakan ini ke kolom 'nama'
            'deskripsi' => $deskripsi,
            'lokasi' => trim($data['lokasi'] ?? null),
            'gambar' => $gambar_filename
        ];

        $new_id = Wisata::create($data_to_model);

        if ($new_id) {
            return $new_id;
        } else {
            error_log("WisataController::create() - Wisata::create gagal. Error: " . Wisata::getLastError());
            return 'db_create_failed';
        }
    }

    /**
     * Mengambil semua data destinasi wisata untuk admin.
     * @return array|false Array data wisata, atau false jika error.
     */
    public static function getAllForAdmin() // Nama metode disesuaikan
    {
        if (!class_exists('Wisata') || !method_exists('Wisata', 'getAll')) {
            error_log("WisataController::getAllForAdmin() - Model/Metode Wisata::getAll tidak tersedia.");
            return false;
        }
        return Wisata::getAll('nama ASC'); // Menggunakan nama kolom 'nama' untuk ORDER BY
    }

    /**
     * Mengambil satu data destinasi wisata berdasarkan ID.
     * @param int $id ID destinasi wisata.
     * @return array|null Data wisata atau null.
     */
    public static function getById($id)
    {
        $id_val = filter_var($id, FILTER_VALIDATE_INT);
        if ($id_val === false || $id_val <= 0) {
            error_log("WisataController::getById() - ID tidak valid: " . $id);
            return null;
        }
        if (!class_exists('Wisata') || !method_exists('Wisata', 'getById')) {
            error_log("WisataController::getById() - Model/Metode Wisata::getById tidak tersedia.");
            return null;
        }
        return Wisata::getById($id_val);
    }

    /**
     * Menangani pembaruan data destinasi wisata.
     * @param array $data Data dari form (harus ada 'id', 'nama_wisata', 'deskripsi').
     * @param string|null $new_uploaded_filename Nama file gambar BARU yang berhasil diunggah ke server.
     * @param string $gambar_action Tindakan untuk gambar ('keep', 'remove', 'change').
     * @param string|null $current_db_filename Nama file gambar yang saat ini tersimpan di DB.
     * @return bool|string True jika berhasil, string kode error jika gagal.
     */
    public static function handleUpdateWisata(array $data, $new_uploaded_filename = null, $gambar_action = 'keep', $current_db_filename = null)
    {
        $id = isset($data['id']) ? filter_var($data['id'], FILTER_VALIDATE_INT) : 0;
        if ($id <= 0) {
            error_log("WisataController::handleUpdateWisata() - ID tidak valid.");
            return 'invalid_id';
        }

        $nama_wisata = trim($data['nama_wisata'] ?? '');
        $deskripsi = trim($data['deskripsi'] ?? '');

        if (empty($nama_wisata)) return 'missing_nama';
        if (empty($deskripsi)) return 'missing_deskripsi';

        if (!class_exists('Wisata') || !method_exists('Wisata', 'update') || !method_exists('Wisata', 'getLastError')) {
            error_log("WisataController::handleUpdateWisata() - Model/Metode Wisata tidak tersedia.");
            return 'system_error_model_unavailable';
        }

        $data_to_update_model = [
            'id' => $id,
            'nama_wisata' => $nama_wisata, // Model akan map ke kolom 'nama'
            'deskripsi' => $deskripsi,
            'lokasi' => trim($data['lokasi'] ?? null)
        ];

        $file_to_delete_on_server = null;
        $filename_for_db = $current_db_filename; // Defaultnya pertahankan gambar lama

        if ($gambar_action === 'remove') {
            if (!empty($current_db_filename)) {
                $file_to_delete_on_server = $current_db_filename;
            }
            $filename_for_db = null; // Hapus dari DB
            $data_to_update_model['gambar'] = null; // Kirim null ke Model untuk di-set NULL
        } elseif ($gambar_action === 'change' && !empty($new_uploaded_filename)) {
            if (!empty($current_db_filename) && $current_db_filename !== $new_uploaded_filename) {
                $file_to_delete_on_server = $current_db_filename; // Tandai file lama untuk dihapus
            }
            $filename_for_db = $new_uploaded_filename;
            $data_to_update_model['gambar'] = $filename_for_db; // Kirim nama file baru ke Model
        }
        // Jika $gambar_action === 'keep', $data_to_update_model tidak akan memiliki key 'gambar',
        // sehingga Model::update() tidak akan mengubah field gambar.

        $update_db_success = Wisata::update($data_to_update_model);

        if ($update_db_success) {
            // Jika update DB berhasil DAN ada file lama yang perlu dihapus dari server
            if ($file_to_delete_on_server) {
                if (defined('UPLOADS_WISATA_PATH')) {
                    $old_file_path = rtrim(UPLOADS_WISATA_PATH, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . basename($file_to_delete_on_server);
                    if (file_exists($old_file_path) && is_file($old_file_path)) {
                        if (!@unlink($old_file_path)) {
                            error_log("WisataController::handleUpdateWisata Peringatan: Gagal menghapus file gambar lama: " . $old_file_path);
                        } else {
                            error_log("WisataController::handleUpdateWisata Info: File gambar lama berhasil dihapus: " . $old_file_path);
                        }
                    }
                } else {
                    error_log("WisataController::handleUpdateWisata Peringatan: Konstanta UPLOADS_WISATA_PATH tidak terdefinisi.");
                }
            }
            return true;
        } else {
            error_log("WisataController::handleUpdateWisata() - Wisata::update gagal untuk ID {$id}. Error: " . Wisata::getLastError());
            // Jika update DB gagal, dan ada file BARU yang sudah diupload, hapus file baru tersebut (rollback file upload)
            if ($gambar_action === 'change' && !empty($new_uploaded_filename) && defined('UPLOADS_WISATA_PATH')) {
                $new_file_path_on_server = rtrim(UPLOADS_WISATA_PATH, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . basename($new_uploaded_filename);
                if (file_exists($new_file_path_on_server)) {
                    @unlink($new_file_path_on_server);
                    error_log("WisataController::handleUpdateWisata - Rollback Upload: File baru {$new_uploaded_filename} dihapus karena update DB gagal.");
                }
            }
            return 'db_update_failed';
        }
    }


    /**
     * Menangani penghapusan data destinasi wisata.
     * @param int $id ID destinasi wisata yang akan dihapus.
     * @return bool|string True jika berhasil, string kode error jika gagal.
     */
    public static function delete($id)
    {
        $id_val = filter_var($id, FILTER_VALIDATE_INT);
        if ($id_val === false || $id_val <= 0) {
            error_log("WisataController::delete() - ID tidak valid: " . $id);
            return 'invalid_id';
        }

        if (!class_exists('Wisata') || !method_exists('Wisata', 'delete') || !method_exists('Wisata', 'getLastError')) {
            error_log("WisataController::delete() - Model/Metode Wisata tidak tersedia.");
            return 'system_error_model_unavailable';
        }

        // Model Wisata::delete() sudah menangani penghapusan file fisik.
        if (Wisata::delete($id_val)) {
            return true;
        } else {
            error_log("WisataController::delete() - Wisata::delete gagal untuk ID {$id_val}. Error: " . Wisata::getLastError());
            return 'db_delete_failed';
        }
    }
}
