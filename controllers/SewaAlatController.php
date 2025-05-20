<?php
// File: C:\xampp\htdocs\Cilengkrang-Web-Wisata\controllers\SewaAlatController.php

// config.php diasumsikan sudah memuat Model SewaAlat.php
// dan sudah memanggil SewaAlat::init($conn, UPLOADS_ALAT_SEWA_PATH).

class SewaAlatController
{
    private static function checkModelIntegrity(): bool
    {
        if (
            !class_exists('SewaAlat') ||
            !method_exists('SewaAlat', 'create') ||
            !method_exists('SewaAlat', 'getAll') ||
            !method_exists('SewaAlat', 'getById') ||
            !method_exists('SewaAlat', 'update') || // Pastikan Model Anda memiliki metode update yang menerima array $data
            !method_exists('SewaAlat', 'delete') ||
            !method_exists('SewaAlat', 'updateStok') ||
            !method_exists('SewaAlat', 'getLastError')
        ) {
            $missing_method = '';
            if (!class_exists('SewaAlat')) $missing_method = 'Kelas SewaAlat';
            else if (!method_exists('SewaAlat', 'update')) $missing_method = 'SewaAlat::update(array $data)';
            $error_msg = get_called_class() . " Fatal Error: Model SewaAlat atau metode penting tidak ditemukan ({$missing_method}).";
            error_log($error_msg);
            throw new RuntimeException($error_msg);
        }
        return true;
    }

    public static function handleCreateAlat(array $data_form, ?array $file_data = null): int|string
    {
        try {
            self::checkModelIntegrity();
        } catch (RuntimeException $e) {
            return 'system_error_model_unavailable';
        }

        $nama_item = trim($data_form['nama_item'] ?? '');
        $kategori_alat = trim($data_form['kategori_alat'] ?? null);
        $deskripsi = trim($data_form['deskripsi'] ?? '');
        $harga_sewa_str = $data_form['harga_sewa'] ?? '';
        $durasi_harga_sewa_str = $data_form['durasi_harga_sewa'] ?? '';
        $satuan_durasi_harga = trim($data_form['satuan_durasi_harga'] ?? 'Hari');
        $stok_tersedia_str = $data_form['stok_tersedia'] ?? '';
        $kondisi_alat = trim($data_form['kondisi_alat'] ?? 'Baik');

        if (empty($nama_item)) return 'missing_nama';
        if ($harga_sewa_str === '' || !is_numeric($harga_sewa_str) || (float)$harga_sewa_str < 1) return 'invalid_harga_min_1';
        if ($satuan_durasi_harga !== 'Peminjaman' && ($durasi_harga_sewa_str === '' || !is_numeric($durasi_harga_sewa_str) || (int)$durasi_harga_sewa_str < 1)) {
            return 'invalid_durasi_min_1';
        }
        if ($stok_tersedia_str === '' || !is_numeric($stok_tersedia_str) || (int)$stok_tersedia_str < 1) return 'invalid_stok_min_1';
        if (!in_array($satuan_durasi_harga, SewaAlat::ALLOWED_DURATION_UNITS)) return 'invalid_satuan_durasi';
        if (!in_array($kondisi_alat, SewaAlat::ALLOWED_CONDITIONS)) return 'invalid_kondisi';

        $gambar_filename_to_save_in_db = null;
        $upload_error_message = null;
        $target_file_upload_path_server = null;

        if ($file_data && isset($file_data['error']) && $file_data['error'] == UPLOAD_ERR_OK && !empty($file_data['name'])) {
            if (!defined('UPLOADS_ALAT_SEWA_PATH') || !is_dir(UPLOADS_ALAT_SEWA_PATH) || !is_writable(UPLOADS_ALAT_SEWA_PATH)) {
                $upload_error_message = "Konfigurasi direktori unggah alat sewa bermasalah.";
                error_log("SewaAlatController::handleCreateAlat - " . $upload_error_message . " Path: " . (defined('UPLOADS_ALAT_SEWA_PATH') ? UPLOADS_ALAT_SEWA_PATH : 'TIDAK ADA'));
            } else {
                $upload_dir = rtrim(UPLOADS_ALAT_SEWA_PATH, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
                $file_tmp_name = $file_data['tmp_name'];
                $file_original_name = basename($file_data['name']);
                $file_size = $file_data['size'];
                $file_ext = strtolower(pathinfo($file_original_name, PATHINFO_EXTENSION));
                $allowed_extensions = ["jpg", "png", "jpeg", "gif", "webp"];

                if (!in_array($file_ext, $allowed_extensions)) $upload_error_message = 'Ekstensi file tidak diizinkan.';
                elseif ($file_size > 2 * 1024 * 1024) $upload_error_message = 'Ukuran file maks 2MB.';
                else {
                    $check_image = @getimagesize($file_tmp_name);
                    if ($check_image === false) $upload_error_message = 'File bukan gambar valid.';
                    else {
                        $gambar_filename_to_save_in_db = "alat_" . uniqid() . '_' . time() . '.' . $file_ext;
                        $target_file_upload_path_server = $upload_dir . $gambar_filename_to_save_in_db;
                        if (!move_uploaded_file($file_tmp_name, $target_file_upload_path_server)) {
                            $upload_error_message = 'Gagal memindahkan file gambar.';
                            error_log("SewaAlatController::handleCreateAlat - Gagal move_uploaded_file ke " . $target_file_upload_path_server);
                            $gambar_filename_to_save_in_db = null;
                        }
                    }
                }
            }
        } elseif ($file_data && isset($file_data['error']) && $file_data['error'] != UPLOAD_ERR_NO_FILE) {
            $upload_error_message = "Error unggah gambar (kode: " . $file_data['error'] . ").";
        }

        if ($upload_error_message) {
            if (function_exists('set_flash_message')) set_flash_message('danger', $upload_error_message);
            return 'upload_failed';
        }

        $data_to_model = [
            'nama_item' => $nama_item,
            'kategori_alat' => $kategori_alat,
            'deskripsi' => $deskripsi,
            'harga_sewa' => (float)$harga_sewa_str,
            'durasi_harga_sewa' => ($satuan_durasi_harga === 'Peminjaman') ? 1 : (int)$durasi_harga_sewa_str,
            'satuan_durasi_harga' => $satuan_durasi_harga,
            'stok_tersedia' => (int)$stok_tersedia_str,
            'gambar_alat' => $gambar_filename_to_save_in_db,
            'kondisi_alat' => $kondisi_alat
        ];

        error_log("SewaAlatController::handleCreateAlat - Data ke Model::create(): " . print_r($data_to_model, true));

        $new_id = SewaAlat::create($data_to_model);
        if (is_int($new_id) && $new_id > 0) {
            return $new_id;
        } else {
            if ($gambar_filename_to_save_in_db && !empty($target_file_upload_path_server) && file_exists($target_file_upload_path_server)) {
                @unlink($target_file_upload_path_server);
                error_log("SewaAlatController::handleCreateAlat - Rollback upload: File {$gambar_filename_to_save_in_db} dihapus karena insert DB gagal.");
            }
            $model_error = SewaAlat::getLastError();
            error_log("SewaAlatController::handleCreateAlat() - SewaAlat::create gagal. Model Error: " . $model_error . " | Data: " . print_r($data_to_model, true));
            return $model_error ?: 'db_create_failed';
        }
    }
    public static function getAll(): array
    {
        try {
            self::checkModelIntegrity();
            return SewaAlat::getAll();
        } catch (RuntimeException $e) {
            return [];
        }
    }

    public static function getById(int $id): ?array
    {
        if ($id <= 0) {
            error_log("SewaAlatController::getById() - ID tidak valid: " . $id);
            return null;
        }
        try {
            self::checkModelIntegrity();
            return SewaAlat::getById($id);
        } catch (RuntimeException $e) {
            return null;
        }
    }

    public static function handleUpdateAlat(array $data_from_form, ?array $file_input_data = null): bool|string
    {
        try {
            self::checkModelIntegrity();
        } catch (RuntimeException $e) {
            return 'system_error_model_unavailable';
        }

        $id_alat = isset($data_from_form['id']) ? filter_var($data_from_form['id'], FILTER_VALIDATE_INT) : 0;
        if ($id_alat <= 0) return 'invalid_id';

        $current_alat_data = SewaAlat::getById($id_alat);
        if (!$current_alat_data) {
            return 'item_not_found';
        }
        $gambar_lama_di_db_sebelum_update = $current_alat_data['gambar_alat'] ?? null;

        $nama_item = trim($data_from_form['nama_item'] ?? '');
        $harga_sewa_str = $data_from_form['harga_sewa'] ?? '';
        $durasi_harga_sewa_str = $data_from_form['durasi_harga_sewa'] ?? '';
        $stok_tersedia_str = $data_from_form['stok_tersedia'] ?? '';
        $satuan_durasi_harga = trim($data_from_form['satuan_durasi_harga'] ?? 'Hari');
        $kondisi_alat = trim($data_from_form['kondisi_alat'] ?? 'Baik');

        if (empty($nama_item)) return 'missing_nama';
        if ($harga_sewa_str === '' || !is_numeric($harga_sewa_str) || (float)$harga_sewa_str < 1) return 'invalid_harga_min_1';
        if ($satuan_durasi_harga !== 'Peminjaman' && ($durasi_harga_sewa_str === '' || !is_numeric($durasi_harga_sewa_str) || (int)$durasi_harga_sewa_str < 1)) {
            return 'invalid_durasi_min_1';
        }
        if ($stok_tersedia_str === '' || !is_numeric($stok_tersedia_str) || (int)$stok_tersedia_str < 1) return 'invalid_stok_min_1';
        if (!in_array($satuan_durasi_harga, SewaAlat::ALLOWED_DURATION_UNITS)) return 'invalid_satuan_durasi';
        if (!in_array($kondisi_alat, SewaAlat::ALLOWED_CONDITIONS)) return 'invalid_kondisi';

        $data_to_model = [
            'id' => $id_alat,
            'nama_item' => $nama_item,
            'kategori_alat' => trim($data_from_form['kategori_alat'] ?? null),
            'deskripsi' => trim($data_from_form['deskripsi'] ?? ''),
            'harga_sewa' => (float)$harga_sewa_str,
            'durasi_harga_sewa' => ($satuan_durasi_harga === 'Peminjaman') ? 1 : (int)$durasi_harga_sewa_str,
            'satuan_durasi_harga' => $satuan_durasi_harga,
            'stok_tersedia' => (int)$stok_tersedia_str,
            'kondisi_alat' => $kondisi_alat
        ];

        $gambar_action = $data_from_form['gambar_action'] ?? 'keep';
        $nama_file_gambar_final_untuk_db = $gambar_lama_di_db_sebelum_update; // Default: pertahankan yang lama
        $file_fisik_lama_untuk_dihapus_dari_server = null;
        $path_file_baru_di_server_untuk_rollback = null;

        if ($gambar_action === 'remove') {
            $nama_file_gambar_final_untuk_db = null;
            if (!empty($gambar_lama_di_db_sebelum_update)) {
                $file_fisik_lama_untuk_dihapus_dari_server = $gambar_lama_di_db_sebelum_update;
            }
        } elseif ($gambar_action === 'change') {
            if ($file_input_data && isset($file_input_data['error']) && $file_input_data['error'] == UPLOAD_ERR_OK && !empty($file_input_data['name'])) {
                $upload_error_message = null;
                // ... (logika upload file sama seperti di handleCreateAlat) ...
                if (!defined('UPLOADS_ALAT_SEWA_PATH') || !is_dir(UPLOADS_ALAT_SEWA_PATH) || !is_writable(UPLOADS_ALAT_SEWA_PATH)) {
                    $upload_error_message = "Konfigurasi direktori unggah alat sewa bermasalah.";
                } else {
                    $upload_dir = rtrim(UPLOADS_ALAT_SEWA_PATH, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
                    $file_tmp_name = $file_input_data['tmp_name'];
                    $file_original_name = basename($file_input_data['name']);
                    $file_ext = strtolower(pathinfo($file_original_name, PATHINFO_EXTENSION));
                    $allowed_extensions = ["jpg", "png", "jpeg", "gif", "webp"];

                    if (!in_array($file_ext, $allowed_extensions)) $upload_error_message = 'Ekstensi file baru tidak diizinkan.';
                    elseif ($file_input_data['size'] > 2 * 1024 * 1024) $upload_error_message = 'Ukuran file baru maks 2MB.';
                    else {
                        $check_image = @getimagesize($file_tmp_name);
                        if ($check_image === false) $upload_error_message = 'File baru bukan gambar valid.';
                        else {
                            $nama_file_gambar_final_untuk_db = "alat_" . uniqid() . '_' . time() . '.' . $file_ext;
                            $path_file_baru_di_server_untuk_rollback = $upload_dir . $nama_file_gambar_final_untuk_db;
                            if (!move_uploaded_file($file_tmp_name, $path_file_baru_di_server_untuk_rollback)) {
                                $upload_error_message = 'Gagal memindahkan file gambar baru.';
                                $nama_file_gambar_final_untuk_db = $gambar_lama_di_db_sebelum_update; // Gagal upload, revert ke nama lama
                                $path_file_baru_di_server_untuk_rollback = null; // Tidak ada file baru yang perlu di-rollback
                            }
                        }
                    }
                }
                if ($upload_error_message) {
                    if (function_exists('set_flash_message')) set_flash_message('danger', $upload_error_message);
                    return 'upload_failed';
                }
                // Jika upload berhasil, tandai file lama untuk dihapus jika berbeda
                if (!empty($gambar_lama_di_db_sebelum_update) && $gambar_lama_di_db_sebelum_update !== $nama_file_gambar_final_untuk_db) {
                    $file_fisik_lama_untuk_dihapus_dari_server = $gambar_lama_di_db_sebelum_update;
                }
            } else {
                if (function_exists('set_flash_message')) set_flash_message('danger', 'Anda memilih ganti gambar, tetapi tidak ada file baru yang valid diunggah atau terjadi error upload.');
                return 'upload_required_for_change';
            }
        }

        // Set field 'gambar_alat' di array data yang akan dikirim ke Model
        // Ini akan selalu ada, nilainya bisa nama file baru, null (jika remove), atau nama file lama (jika keep)
        $data_to_model['gambar_alat'] = $nama_file_gambar_final_untuk_db;

        error_log("SewaAlatController::handleUpdateAlat - Data ke Model::update(): " . print_r($data_to_model, true));

        // Panggil Model dengan satu array data. Model akan menangani field mana yang diupdate.
        $update_db_success = SewaAlat::update($data_to_model);

        if ($update_db_success) {
            // Hapus file fisik lama JIKA update DB berhasil DAN ada file lama yang ditandai untuk dihapus
            if ($file_fisik_lama_untuk_dihapus_dari_server && defined('UPLOADS_ALAT_SEWA_PATH')) {
                $old_file_path_on_server = rtrim(UPLOADS_ALAT_SEWA_PATH, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . basename($file_fisik_lama_untuk_dihapus_dari_server);
                if (file_exists($old_file_path_on_server) && is_file($old_file_path_on_server)) {
                    if (!@unlink($old_file_path_on_server)) {
                        error_log("SewaAlatController::handleUpdateAlat Peringatan: Gagal menghapus file gambar lama: " . $old_file_path_on_server);
                    } else {
                        error_log("SewaAlatController::handleUpdateAlat Info: File gambar lama berhasil dihapus: " . $old_file_path_on_server);
                    }
                }
            }
            return true;
        } else {
            // Rollback: Hapus file BARU yang sudah terupload jika update DB gagal
            if ($gambar_action === 'change' && $nama_file_gambar_final_untuk_db && !empty($path_file_baru_di_server_untuk_rollback) && file_exists($path_file_baru_di_server_untuk_rollback)) {
                @unlink($path_file_baru_di_server_untuk_rollback);
                error_log("SewaAlatController::handleUpdateAlat - Rollback Upload: File baru {$nama_file_gambar_final_untuk_db} dihapus karena update DB gagal.");
            }
            $model_error = SewaAlat::getLastError();
            error_log("SewaAlatController::handleUpdateAlat() - SewaAlat::update gagal. Model Error: " . $model_error . " | Data: " . print_r($data_to_model, true));
            return $model_error ?: 'db_update_failed';
        }
    }


    public static function delete(int $id): bool|string
    {
        if ($id <= 0) return 'invalid_id';
        try {
            self::checkModelIntegrity();
        } catch (RuntimeException $e) {
            return 'system_error_model_unavailable';
        }

        $result = SewaAlat::delete($id);
        if ($result === true) {
            return true;
        } else {
            $error_model = SewaAlat::getLastError();
            error_log("SewaAlatController::delete() - SewaAlat::delete gagal ID {$id}. Model Error: " . $error_model);
            if (strpos(strtolower((string)($error_model ?? '')), 'masih digunakan') !== false) {
                return 'delete_failed_in_use';
            }
            return $error_model ?: 'db_delete_failed';
        }
    }
}
// SewaAlatController.php