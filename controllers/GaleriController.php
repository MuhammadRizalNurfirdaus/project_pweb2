<?php
// File: C:\xampp\htdocs\Cilengkrang-Web-Wisata\controllers\GaleriController.php

class GaleriController
{
    /**
     * Menangani pembuatan item galeri baru.
     * @param array $data Data dari form, diharapkan memiliki 'keterangan'.
     * @param string|null $nama_file_gambar Nama file gambar yang sudah diupload dan divalidasi.
     * @return int|string ID item galeri baru jika berhasil, string 'error' jika gagal.
     */
    public static function create(array $data, $nama_file_gambar)
    {
        if (!class_exists('Galeri') || !method_exists('Galeri', 'create') || !method_exists('Galeri', 'getLastError')) {
            error_log("GaleriController::create() - Model/Metode Galeri (create/getLastError) tidak ditemukan.");
            // set_flash_message di sini mungkin tidak terlihat jika dipanggil via AJAX atau proses murni
            return 'system_error_model_unavailable';
        }

        $keterangan = trim($data['keterangan'] ?? '');

        if (empty($nama_file_gambar)) {
            error_log("GaleriController::create() - Nama file gambar kosong.");
            return 'missing_file';
        }

        $data_to_model = [
            'nama_file' => $nama_file_gambar,
            'keterangan' => $keterangan
        ];

        $new_id = Galeri::create($data_to_model);

        if ($new_id) {
            return $new_id; // Berhasil, kembalikan ID
        } else {
            error_log("GaleriController::create() - Galeri::create gagal. Error: " . Galeri::getLastError());
            return 'db_create_failed'; // Kode error spesifik
        }
    }

    /**
     * Mengambil semua item galeri untuk tampilan admin.
     * @return array|false Array data galeri atau false jika error.
     */
    public static function getAllForAdmin()
    {
        if (!class_exists('Galeri') || !method_exists('Galeri', 'getAll')) {
            error_log("GaleriController::getAllForAdmin() - Model/Metode Galeri::getAll tidak ditemukan.");
            return false; // Indikasikan error
        }
        return Galeri::getAll('uploaded_at DESC');
    }

    /**
     * Mengambil satu item galeri berdasarkan ID.
     * @param int $id ID Galeri.
     * @return array|null Data galeri atau null.
     */
    public static function getById($id)
    {
        $id_val = filter_var($id, FILTER_VALIDATE_INT);
        if ($id_val === false || $id_val <= 0) {
            error_log("GaleriController::getById() - ID galeri tidak valid: " . $id);
            return null;
        }
        if (!class_exists('Galeri') || !method_exists('Galeri', 'getById')) {
            error_log("GaleriController::getById() - Model/Metode Galeri::getById tidak ditemukan.");
            return null;
        }
        return Galeri::getById($id_val);
    }

    /**
     * Menangani proses update foto galeri.
     * @param int $id ID foto galeri yang akan diupdate.
     * @param string $keterangan Keterangan baru untuk foto.
     * @param string|null $nama_file_baru_untuk_db Nama file gambar baru yang akan disimpan ke DB.
     * @param string|null $nama_file_lama_untuk_dihapus_fisik Nama file gambar lama di server.
     * @return bool|string True jika update berhasil, string kode error jika gagal.
     */
    public static function update($id, $keterangan, $nama_file_baru_untuk_db, $nama_file_lama_untuk_dihapus_fisik)
    {
        $id_val = filter_var($id, FILTER_VALIDATE_INT);
        if ($id_val === false || $id_val <= 0) {
            error_log("GaleriController::update() - ID galeri tidak valid: " . $id);
            return 'invalid_id';
        }

        if (!class_exists('Galeri') || !method_exists('Galeri', 'update') || !method_exists('Galeri', 'getLastError')) {
            error_log("GaleriController::update() - Model/Metode Galeri (update/getLastError) tidak ditemukan.");
            return 'system_error_model_unavailable';
        }

        $data_to_model = [
            'id' => $id_val,
            'keterangan' => trim($keterangan)
        ];

        $ada_aksi_gambar = ($nama_file_baru_untuk_db !== null || ($nama_file_lama_untuk_dihapus_fisik !== null && $nama_file_baru_untuk_db === null));

        if ($ada_aksi_gambar) {
            $data_to_model['nama_file'] = $nama_file_baru_untuk_db;
        }

        $update_db_success = Galeri::update($data_to_model);

        if ($update_db_success) {
            if ($nama_file_lama_untuk_dihapus_fisik && $ada_aksi_gambar) {
                if ($nama_file_baru_untuk_db === null || ($nama_file_baru_untuk_db !== null && $nama_file_baru_untuk_db !== $nama_file_lama_untuk_dihapus_fisik)) {
                    if (defined('UPLOADS_GALERI_PATH')) {
                        $old_file_path = rtrim(UPLOADS_GALERI_PATH, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . basename($nama_file_lama_untuk_dihapus_fisik);
                        if (file_exists($old_file_path) && is_file($old_file_path)) {
                            if (!@unlink($old_file_path)) {
                                error_log("GaleriController::update Peringatan: Gagal menghapus file gambar lama: " . $old_file_path);
                                // Ini hanya peringatan, update DB sudah berhasil.
                            } else {
                                error_log("GaleriController::update Info: File gambar lama berhasil dihapus: " . $old_file_path);
                            }
                        }
                    } else {
                        error_log("GaleriController::update Peringatan: Konstanta UPLOADS_GALERI_PATH tidak terdefinisi.");
                    }
                }
            }
            return true;
        } else {
            error_log("GaleriController::update() - Galeri::update gagal untuk ID {$id_val}. Error: " . Galeri::getLastError());
            return 'db_update_failed';
        }
    }

    /**
     * Menghapus item galeri berdasarkan ID.
     * @param int $id ID Galeri.
     * @return bool|string True jika berhasil, string kode error jika gagal.
     */
    public static function delete($id)
    {
        $id_val = filter_var($id, FILTER_VALIDATE_INT);
        if ($id_val === false || $id_val <= 0) {
            error_log("GaleriController::delete() - ID galeri tidak valid: " . $id);
            return 'invalid_id';
        }

        if (!class_exists('Galeri') || !method_exists('Galeri', 'delete') || !method_exists('Galeri', 'getLastError')) {
            error_log("GaleriController::delete() - Model/Metode Galeri (delete/getLastError) tidak ada.");
            return 'system_error_model_unavailable';
        }

        if (Galeri::delete($id_val)) {
            return true;
        } else {
            error_log("GaleriController::delete() - Galeri::delete gagal untuk ID {$id_val}. Error: " . Galeri::getLastError());
            return 'db_delete_failed';
        }
    }
}
