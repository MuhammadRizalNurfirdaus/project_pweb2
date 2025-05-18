<?php
// File: C:\xampp\htdocs\Cilengkrang-Web-Wisata\controllers\GaleriController.php

// config.php seharusnya sudah memuat Model Galeri dan menginisialisasinya
// require_once __DIR__ . '/../config/config.php'; // Biasanya sudah di file pemanggil

class GaleriController
{
    /**
     * Memeriksa apakah model dan metode yang dibutuhkan ada.
     * Melempar RuntimeException jika tidak ditemukan.
     */
    private static function checkRequiredModelsAndMethods(array $models_with_methods)
    {
        foreach ($models_with_methods as $model_name => $methods) {
            if (!class_exists($model_name)) {
                $error_msg = get_called_class() . " Fatal Error: Model {$model_name} tidak ditemukan atau tidak dimuat.";
                error_log($error_msg);
                throw new RuntimeException($error_msg); // Hentikan eksekusi jika komponen inti hilang
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


    /**
     * Menangani pembuatan item galeri baru.
     * @param array $data Data dari form, diharapkan memiliki 'keterangan'.
     * @param string|null $nama_file_gambar_uploaded Nama file gambar yang sudah diupload dan divalidasi.
     * @return int|string ID item galeri baru jika berhasil, string kode error jika gagal.
     */
    public static function create(array $data_form, $nama_file_gambar_uploaded)
    {
        try {
            self::checkRequiredModelsAndMethods(['Galeri' => ['create', 'getLastError']]);
        } catch (RuntimeException $e) {
            // Error sudah di-log oleh checkRequiredModelsAndMethods
            return 'system_error_model_unavailable';
        }

        $keterangan = trim($data_form['keterangan'] ?? '');

        if (empty($nama_file_gambar_uploaded)) {
            error_log("GaleriController::create() - Nama file gambar wajib diisi namun kosong.");
            return 'missing_file';
        }
        // Keterangan bisa jadi opsional tergantung kebutuhan Anda, jika wajib tambahkan validasi:
        // if (empty($keterangan)) {
        //     error_log("GaleriController::create() - Keterangan foto wajib diisi.");
        //     return 'missing_keterangan';
        // }

        $data_to_model = [
            'nama_file' => $nama_file_gambar_uploaded,
            'keterangan' => $keterangan
        ];

        $new_id = Galeri::create($data_to_model);

        if ($new_id) {
            return $new_id;
        } else {
            error_log("GaleriController::create() - Galeri::create gagal. Error Model: " . Galeri::getLastError());
            return 'db_create_failed';
        }
    }

    /**
     * Mengambil semua item galeri untuk tampilan admin.
     * @param string $orderBy Urutan data.
     * @param int|null $limit Batas data.
     * @return array|false Array data galeri atau false jika error.
     */
    public static function getAllForAdmin(string $orderBy = 'uploaded_at DESC', ?int $limit = null)
    {
        try {
            self::checkRequiredModelsAndMethods(['Galeri' => ['getAll']]);
            return Galeri::getAll($orderBy, $limit);
        } catch (RuntimeException $e) {
            return false; // Indikasikan error
        }
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
            error_log("GaleriController::getById() - ID galeri tidak valid: " . htmlspecialchars((string)$id));
            return null;
        }
        try {
            self::checkRequiredModelsAndMethods(['Galeri' => ['findById']]);
            return Galeri::findById($id_val);
        } catch (RuntimeException $e) {
            return null;
        }
    }

    /**
     * Menangani keseluruhan proses update foto galeri dari data form yang diterima oleh edit_foto.php.
     * @param array $data_controller Array data dari form, diharapkan kunci:
     *        'id' (int), 
     *        'keterangan' (string),
     *        'nama_file_baru_uploaded' (string|null|'REMOVE_IMAGE_FLAG') - Nama file yang BARU diupload ke server, atau flag untuk hapus, atau null.
     *        'nama_file_lama_db' (string|null) - Nama file yang saat ini ada di database.
     * @return bool|string True jika sukses, string kode error jika gagal.
     */
    public static function handleUpdateFoto(array $data_controller)
    {
        $id_foto = $data_controller['id'] ?? 0;
        $keterangan_baru_form = $data_controller['keterangan'] ?? '';
        $nama_file_baru_hasil_upload = $data_controller['nama_file_baru_uploaded'] ?? null; // Ini adalah nama file BARU yang sudah di server jika ada upload
        $nama_file_lama_dari_db = $data_controller['nama_file_lama_db'] ?? null;

        $nama_file_untuk_disimpan_ke_db = $nama_file_lama_dari_db; // Default, pertahankan file lama
        $file_fisik_lama_yang_mungkin_dihapus = null;

        if ($nama_file_baru_hasil_upload === "REMOVE_IMAGE_FLAG") {
            $nama_file_untuk_disimpan_ke_db = null; // Set null di DB untuk menghapus nama file
            if (!empty($nama_file_lama_dari_db)) {
                $file_fisik_lama_yang_mungkin_dihapus = $nama_file_lama_dari_db;
            }
        } elseif ($nama_file_baru_hasil_upload !== null) { // Ada file baru yang diupload
            $nama_file_untuk_disimpan_ke_db = $nama_file_baru_hasil_upload;
            if (!empty($nama_file_lama_dari_db) && $nama_file_lama_dari_db !== $nama_file_baru_hasil_upload) {
                $file_fisik_lama_yang_mungkin_dihapus = $nama_file_lama_dari_db;
            }
        }
        // Jika $nama_file_baru_hasil_upload adalah null dan action bukan 'remove', berarti gambar dipertahankan,
        // $nama_file_untuk_disimpan_ke_db akan tetap $nama_file_lama_dari_db.

        // Panggil metode update inti
        return self::update($id_foto, $keterangan_baru_form, $nama_file_untuk_disimpan_ke_db, $file_fisik_lama_yang_mungkin_dihapus);
    }


    /**
     * Mengupdate item galeri di database dan menghapus file lama jika perlu.
     * Metode ini dipanggil oleh handleUpdateFoto setelah logika file diproses.
     * @param int $id ID foto galeri yang akan diupdate.
     * @param string $keterangan Keterangan baru untuk foto.
     * @param string|null $nama_file_final_untuk_db Nama file gambar BARU yang akan disimpan ke DB (bisa null jika dihapus dari DB).
     * @param string|null $nama_file_lama_fisik_untuk_dihapus Nama file gambar LAMA di server yang perlu dihapus jika ada penggantian atau aksi remove.
     * @return bool|string True jika update berhasil, string kode error jika gagal.
     */
    public static function update($id, $keterangan, $nama_file_final_untuk_db, $nama_file_lama_fisik_untuk_dihapus)
    {
        $id_val = filter_var($id, FILTER_VALIDATE_INT);
        if ($id_val === false || $id_val <= 0) {
            error_log("GaleriController::update() - ID galeri tidak valid: " . htmlspecialchars((string)$id));
            return 'invalid_id';
        }

        try {
            self::checkRequiredModelsAndMethods(['Galeri' => ['update', 'findById', 'getLastError']]);
        } catch (RuntimeException $e) {
            return 'system_error_model_unavailable';
        }

        $current_item = Galeri::findById($id_val);
        if (!$current_item) {
            error_log("GaleriController::update() - Item galeri ID {$id_val} tidak ditemukan.");
            return 'item_not_found';
        }

        $data_to_model = ['id' => $id_val];
        $perlu_update_db = false;

        $keterangan_baru_trimmed = trim($keterangan);
        if ($keterangan_baru_trimmed !== ($current_item['keterangan'] ?? '')) {
            $data_to_model['keterangan'] = $keterangan_baru_trimmed;
            $perlu_update_db = true;
        }

        // $nama_file_final_untuk_db adalah nama file yang akan disimpan ke DB.
        // Bisa null (jika REMOVE_IMAGE_FLAG dari handleUpdateFoto),
        // bisa nama file baru, atau sama dengan nama file lama (jika 'keep' dari handleUpdateFoto).
        if ($nama_file_final_untuk_db !== $current_item['nama_file']) {
            $data_to_model['nama_file'] = $nama_file_final_untuk_db; // Bisa null jika gambar dihapus dari DB
            $perlu_update_db = true;
        }

        if (!$perlu_update_db) {
            // Jika tidak ada perubahan data di DB, tapi ada aksi hapus file lama
            // (misal, user memilih 'remove' tapi keterangan tidak diubah)
            if ($nama_file_lama_fisik_untuk_dihapus && $nama_file_final_untuk_db === null) {
                if (defined('UPLOADS_GALERI_PATH')) {
                    $old_file_path = rtrim(UPLOADS_GALERI_PATH, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . basename($nama_file_lama_fisik_untuk_dihapus);
                    if (file_exists($old_file_path) && is_file($old_file_path)) {
                        if (!@unlink($old_file_path)) {
                            error_log("GaleriController::update Peringatan (No DB Change): Gagal menghapus file lama: " . $old_file_path);
                        } else {
                            error_log("GaleriController::update Info (No DB Change): File lama berhasil dihapus: " . $old_file_path);
                        }
                    }
                }
            }
            return true; // Dianggap sukses karena tidak ada perubahan data DB yang diperlukan
        }

        $update_db_success = Galeri::update($data_to_model);

        if ($update_db_success) {
            // Jika update DB berhasil DAN ada file lama yang perlu dihapus (karena diganti atau aksi remove)
            if ($nama_file_lama_fisik_untuk_dihapus) {
                if (defined('UPLOADS_GALERI_PATH') && !empty($nama_file_lama_fisik_untuk_dihapus)) {
                    $old_file_path = rtrim(UPLOADS_GALERI_PATH, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . basename($nama_file_lama_fisik_untuk_dihapus);
                    if (file_exists($old_file_path) && is_file($old_file_path)) {
                        if (!@unlink($old_file_path)) {
                            error_log("GaleriController::update Peringatan: Gagal menghapus file gambar lama setelah update DB: " . $old_file_path);
                            // Bisa tambahkan flash warning untuk user jika ini penting
                            // set_flash_message('warning', 'Data berhasil diupdate, tetapi file gambar lama gagal dihapus.');
                        } else {
                            error_log("GaleriController::update Info: File gambar lama berhasil dihapus setelah update DB: " . $old_file_path);
                        }
                    }
                } else {
                    error_log("GaleriController::update Peringatan: Konstanta UPLOADS_GALERI_PATH tidak terdefinisi atau nama file lama kosong, tidak dapat menghapus file lama.");
                }
            }
            return true;
        } else {
            error_log("GaleriController::update() - Galeri::update gagal untuk ID {$id_val}. Error Model: " . Galeri::getLastError());
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
            error_log("GaleriController::delete() - ID galeri tidak valid: " . htmlspecialchars((string)$id));
            return 'invalid_id';
        }

        try {
            self::checkRequiredModelsAndMethods(['Galeri' => ['delete', 'getLastError']]);
            // Model Galeri::delete() sudah menghandle penghapusan file fisik
            if (Galeri::delete($id_val)) {
                return true;
            } else {
                $error_model = Galeri::getLastError();
                error_log("GaleriController::delete() - Galeri::delete gagal untuk ID {$id_val}. Error Model: " . $error_model);
                // Jika error model adalah "item tidak ditemukan", itu juga kegagalan dari perspektif controller ini
                if (strpos(strtolower($error_model ?? ''), 'tidak ditemukan') !== false || strpos(strtolower($error_model ?? ''), 'no rows') !== false) {
                    return 'item_not_found_on_delete';
                }
                return 'db_delete_failed';
            }
        } catch (RuntimeException $e) {
            return 'system_error_model_unavailable';
        }
    }
}
