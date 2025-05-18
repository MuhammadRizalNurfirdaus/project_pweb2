<?php
// File: C:\xampp\htdocs\Cilengkrang-Web-Wisata\controllers\WisataController.php

class WisataController
{
    private static function checkRequiredModelsAndMethods(array $models_with_methods)
    {
        foreach ($models_with_methods as $model_name => $methods) {
            if (!class_exists($model_name)) {
                $error_msg = get_called_class() . " Fatal Error: Model {$model_name} tidak ditemukan.";
                error_log($error_msg);
                throw new RuntimeException($error_msg);
            }
            if (is_array($methods)) {
                foreach ($methods as $method_name) {
                    if (!method_exists($model_name, $method_name)) {
                        $error_msg = get_called_class() . " Fatal Error: Metode {$model_name}::{$method_name} tidak ditemukan.";
                        error_log($error_msg);
                        throw new RuntimeException($error_msg);
                    }
                }
            }
        }
    }

    public static function create(array $data_form, $gambar_filename_uploaded = null)
    {
        try {
            self::checkRequiredModelsAndMethods(['Wisata' => ['create', 'getLastError']]);
        } catch (RuntimeException $e) {
            return 'system_error_model_unavailable';
        }

        // Di Model Wisata, kolomnya adalah 'nama', bukan 'nama_wisata'
        $nama_untuk_db = trim($data_form['nama'] ?? ''); // Ambil 'nama' dari form
        $deskripsi = trim($data_form['deskripsi'] ?? '');
        $lokasi = trim($data_form['lokasi'] ?? null);

        if (empty($nama_untuk_db)) return 'missing_nama';
        if (empty($deskripsi)) return 'missing_deskripsi';
        if (empty($gambar_filename_uploaded)) return 'missing_gambar'; // Gambar wajib saat create

        $data_to_model = [
            'nama' => $nama_untuk_db, // Kirim 'nama' ke Model
            'deskripsi' => $deskripsi,
            'lokasi' => $lokasi,
            'gambar' => $gambar_filename_uploaded
        ];

        $new_id = Wisata::create($data_to_model);
        if ($new_id) {
            return $new_id;
        } else {
            error_log("WisataController::create() - Wisata::create gagal. Error Model: " . Wisata::getLastError());
            return 'db_create_failed';
        }
    }

    public static function getAllForAdmin(string $orderBy = 'nama ASC')
    {
        try {
            self::checkRequiredModelsAndMethods(['Wisata' => ['getAll']]);
            return Wisata::getAll($orderBy); // Model sudah menggunakan 'nama' untuk default order
        } catch (RuntimeException $e) {
            return false;
        }
    }

    public static function getById(int $id_wisata): ?array
    {
        if ($id_wisata <= 0) {
            error_log(get_called_class() . "::getById() - ID wisata tidak valid: " . $id_wisata);
            return null;
        }
        try {
            self::checkRequiredModelsAndMethods(['Wisata' => ['findById']]);
            return Wisata::findById($id_wisata); // Model mengembalikan data dengan key 'nama'
        } catch (RuntimeException $e) {
            return null;
        }
    }

    /**
     * Menangani pembaruan data destinasi wisata.
     * @param array $data_from_form Data dari form (kunci: 'id', 'nama', 'deskripsi', 'lokasi').
     * @param string|null $new_uploaded_filename Nama file gambar BARU yang berhasil diunggah ke server, ATAU "REMOVE_IMAGE_FLAG".
     * @param string|null $current_db_filename Nama file gambar yang saat ini tersimpan di DB (untuk perbandingan dan penghapusan).
     * @return bool|string True jika berhasil, string kode error jika gagal.
     */
    public static function handleUpdateWisata(array $data_from_form, $new_uploaded_filename = null, $current_db_filename = null)
    {
        $id = isset($data_from_form['id']) ? filter_var($data_from_form['id'], FILTER_VALIDATE_INT) : 0;
        if ($id <= 0) {
            error_log("WisataController::handleUpdateWisata() - ID tidak valid.");
            return 'invalid_id';
        }

        // Nama input dari form adalah 'nama', sesuai dengan perbaikan di edit_wisata.php
        $nama_form = trim($data_from_form['nama'] ?? '');
        $deskripsi_form = trim($data_from_form['deskripsi'] ?? ''); // Jangan trim jika WYSIWYG butuh spasi
        $lokasi_form = trim($data_from_form['lokasi'] ?? null);

        if (empty($nama_form)) return 'missing_nama'; // Kode error yang bisa ditafsirkan view
        if (empty($deskripsi_form)) return 'missing_deskripsi'; // Kode error

        try {
            self::checkRequiredModelsAndMethods(['Wisata' => ['update', 'findById', 'getLastError']]);
        } catch (RuntimeException $e) {
            return 'system_error_model_unavailable';
        }

        $data_to_model_update = [
            'id' => $id,
            'nama' => $nama_form, // Model akan mengupdate kolom 'nama' di DB
            'deskripsi' => $deskripsi_form,
            'lokasi' => $lokasi_form
        ];

        $file_fisik_lama_untuk_dihapus_server = null;

        if ($new_uploaded_filename === "REMOVE_IMAGE_FLAG") {
            $data_to_model_update['gambar'] = null; // Set kolom gambar di DB menjadi NULL
            if (!empty($current_db_filename)) {
                $file_fisik_lama_untuk_dihapus_server = $current_db_filename;
            }
        } elseif ($new_uploaded_filename !== null) { // Ada file baru yang diupload
            $data_to_model_update['gambar'] = $new_uploaded_filename; // Nama file baru untuk DB
            if (!empty($current_db_filename) && $current_db_filename !== $new_uploaded_filename) {
                $file_fisik_lama_untuk_dihapus_server = $current_db_filename;
            }
        }
        // Jika $new_uploaded_filename null dan bukan "REMOVE_IMAGE_FLAG", berarti 'keep',
        // maka 'gambar' tidak dimasukkan ke $data_to_model_update agar Model tidak mengubahnya.

        $update_db_success = Wisata::update($data_to_model_update);

        if ($update_db_success) {
            // Jika update DB berhasil DAN ada file lama yang perlu dihapus dari server
            if ($file_fisik_lama_untuk_dihapus_server) {
                if (defined('UPLOADS_WISATA_PATH') && !empty($file_fisik_lama_untuk_dihapus_server)) {
                    $old_file_path_server = rtrim(UPLOADS_WISATA_PATH, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . basename($file_fisik_lama_untuk_dihapus_server);
                    if (file_exists($old_file_path_server) && is_file($old_file_path_server)) {
                        if (!@unlink($old_file_path_server)) {
                            error_log("WisataController::handleUpdateWisata Peringatan: Gagal menghapus file gambar lama: " . $old_file_path_server);
                            // Ini hanya peringatan, update DB sudah berhasil.
                            // Bisa set flash message 'warning' tambahan jika perlu.
                        } else {
                            error_log("WisataController::handleUpdateWisata Info: File gambar lama berhasil dihapus: " . $old_file_path_server);
                        }
                    }
                } else {
                    error_log("WisataController::handleUpdateWisata Peringatan: Konstanta UPLOADS_WISATA_PATH tidak terdefinisi atau nama file lama kosong, tidak dapat menghapus file lama dari server.");
                }
            }
            return true;
        } else {
            error_log("WisataController::handleUpdateWisata() - Wisata::update gagal untuk ID {$id}. Error Model: " . Wisata::getLastError());
            // Jika update DB gagal, dan ada file BARU yang sudah diupload, hapus file baru tersebut (rollback file upload)
            if ($new_uploaded_filename && $new_uploaded_filename !== "REMOVE_IMAGE_FLAG" && defined('UPLOADS_WISATA_PATH')) {
                $new_file_path_on_server = rtrim(UPLOADS_WISATA_PATH, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . basename($new_uploaded_filename);
                if (file_exists($new_file_path_on_server)) {
                    @unlink($new_file_path_on_server);
                    error_log("WisataController::handleUpdateWisata - Rollback Upload: File baru {$new_uploaded_filename} dihapus karena update DB gagal.");
                }
            }
            return Wisata::getLastError() ?: 'db_update_failed'; // Kembalikan error dari Model atau kode generik
        }
    }

    public static function delete($id)
    {
        $id_val = filter_var($id, FILTER_VALIDATE_INT);
        if ($id_val === false || $id_val <= 0) {
            error_log("WisataController::delete() - ID tidak valid: " . htmlspecialchars((string)$id));
            return 'invalid_id';
        }
        try {
            self::checkRequiredModelsAndMethods(['Wisata' => ['delete', 'getLastError']]);
            if (Wisata::delete($id_val)) { // Model Wisata::delete() sudah handle hapus file
                return true;
            } else {
                $error_model = Wisata::getLastError();
                error_log("WisataController::delete() - Wisata::delete gagal untuk ID {$id_val}. Error: " . $error_model);
                if (strpos(strtolower($error_model ?? ''), 'tidak ditemukan') !== false || strpos(strtolower($error_model ?? ''), 'no data') !== false) {
                    return 'item_not_found_on_delete';
                }
                return $error_model ?: 'db_delete_failed';
            }
        } catch (RuntimeException $e) {
            return 'system_error_model_unavailable';
        }
    }
}
// End of file: C:\xampp\htdocs\Cilengkrang-Web-Wisata\controllers\WisataController.php