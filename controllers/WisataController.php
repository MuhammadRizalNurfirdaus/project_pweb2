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
                throw new RuntimeException($error_msg); // Hentikan jika model inti tidak ada
            }
            if (is_array($methods)) {
                foreach ($methods as $method_name) {
                    if (!method_exists($model_name, $method_name)) {
                        $error_msg = get_called_class() . " Fatal Error: Metode {$model_name}::{$method_name} tidak ditemukan.";
                        error_log($error_msg);
                        throw new RuntimeException($error_msg); // Hentikan jika metode inti tidak ada
                    }
                }
            }
        }
    }

    /**
     * Menangani pembuatan data destinasi wisata baru.
     * @param array $data_form Data dari form POST.
     * @param string|null $gambar_filename_uploaded Nama file gambar yang sudah diupload dan divalidasi.
     * @return int|string ID destinasi baru jika berhasil, string kode error jika gagal.
     */
    public static function create(array $data_form, $gambar_filename_uploaded = null): int|string
    {
        try {
            self::checkRequiredModelsAndMethods(['Wisata' => ['create', 'getLastError']]);
        } catch (RuntimeException $e) {
            error_log(get_called_class() . "::create() - Exception saat cek model: " . $e->getMessage());
            return 'system_error_model_unavailable';
        }

        $nama_untuk_db = trim($data_form['nama'] ?? '');
        $deskripsi = trim($data_form['deskripsi'] ?? '');
        $lokasi = isset($data_form['lokasi']) ? trim($data_form['lokasi']) : null; // Lokasi bisa opsional dan null

        // Validasi input dasar di Controller
        if (empty($nama_untuk_db)) {
            return 'missing_nama';
        }
        if (empty($deskripsi)) {
            return 'missing_deskripsi';
        }
        // Gambar mungkin wajib saat create, tergantung kebijakan Anda.
        // Jika tidak wajib, hapus pengecekan ini atau buat opsional.
        if (empty($gambar_filename_uploaded)) {
            return 'missing_gambar';
        }

        $data_to_model = [
            'nama' => $nama_untuk_db,
            'deskripsi' => $deskripsi,
            'lokasi' => $lokasi,
            'gambar' => $gambar_filename_uploaded // Nama file yang sudah diproses
        ];

        error_log("WisataController::create() - Data ke Model::create(): " . print_r($data_to_model, true));
        $new_id = Wisata::create($data_to_model);

        if (is_int($new_id) && $new_id > 0) {
            return $new_id;
        } else {
            $model_error = Wisata::getLastError();
            error_log("WisataController::create() - Wisata::create gagal. Model Error: " . ($model_error ?? 'Tidak ada detail') . " | Data: " . print_r($data_to_model, true));
            return $model_error ?: 'db_create_failed';
        }
    }

    /**
     * Mengambil semua data wisata.
     * Metode ini bisa digunakan oleh admin atau publik.
     * @param string $orderBy Kriteria pengurutan, contoh: 'nama ASC' atau 'created_at DESC'.
     * @param int|null $limit Jumlah maksimal data yang diambil. Null berarti tanpa batas.
     * @return array|false Array data wisata, atau false jika terjadi error.
     */
    public static function getAllForAdmin(string $orderBy = 'nama ASC', ?int $limit = null): array|false
    {
        try {
            // Pastikan Model Wisata dan metode getAll ada
            if (!class_exists('Wisata') || !method_exists('Wisata', 'getAll')) {
                error_log(get_called_class() . "::getAllForAdmin() - Model Wisata atau metode getAll tidak ditemukan.");
                throw new RuntimeException("Komponen data wisata tidak tersedia.");
            }
            // Teruskan parameter $orderBy dan $limit ke Model Wisata::getAll()
            return Wisata::getAll($orderBy, $limit);
        } catch (RuntimeException $e) {
            error_log(get_called_class() . "::getAllForAdmin() - RuntimeException: " . $e->getMessage());
            return false; // Kembalikan false pada exception
        } catch (Throwable $th) { // Menangkap semua jenis error/exception lain
            error_log(get_called_class() . "::getAllForAdmin() - Throwable: " . $th->getMessage());
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
            return Wisata::findById($id_wisata);
        } catch (RuntimeException $e) {
            error_log(get_called_class() . "::getById() - Exception untuk ID {$id_wisata}: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Menangani pembaruan data destinasi wisata.
     * @param array $data_from_form Data dari form (kunci: 'id', 'nama', 'deskripsi', 'lokasi', 'gambar_action', 'gambar_lama_db').
     * @param array|null $file_input_data Data dari $_FILES['nama_input_gambar_baru'] jika ada aksi 'change'.
     * @return bool|string True jika berhasil, string kode error jika gagal.
     */
    public static function handleUpdateWisata(array $data_from_form, ?array $file_input_data = null): bool|string
    {
        try {
            self::checkRequiredModelsAndMethods(['Wisata' => ['update', 'findById', 'getLastError']]);
        } catch (RuntimeException $e) {
            error_log(get_called_class() . "::handleUpdateWisata() - Exception saat cek model: " . $e->getMessage());
            return 'system_error_model_unavailable';
        }

        $id = isset($data_from_form['id']) ? filter_var($data_from_form['id'], FILTER_VALIDATE_INT) : 0;
        if ($id <= 0) {
            error_log("WisataController::handleUpdateWisata() - ID tidak valid untuk update.");
            return 'invalid_id';
        }

        $current_wisata_data = Wisata::findById($id);
        if (!$current_wisata_data) {
            error_log("WisataController::handleUpdateWisata() - Data Wisata ID {$id} tidak ditemukan.");
            return 'item_not_found';
        }
        $gambar_lama_di_db = $current_wisata_data['gambar'] ?? null;

        $nama_form = trim($data_from_form['nama'] ?? '');
        $deskripsi_form = trim($data_from_form['deskripsi'] ?? '');
        $lokasi_form = isset($data_from_form['lokasi']) ? trim($data_from_form['lokasi']) : null;

        if (empty($nama_form)) return 'missing_nama';
        if (empty($deskripsi_form)) return 'missing_deskripsi';

        $data_to_model_update = [
            'id' => $id,
            'nama' => $nama_form,
            'deskripsi' => $deskripsi_form,
            'lokasi' => $lokasi_form
        ];

        $gambar_action = $data_from_form['gambar_action'] ?? 'keep';
        $nama_file_gambar_final_untuk_db = $gambar_lama_di_db; // Default: pertahankan yang lama
        $file_fisik_lama_untuk_dihapus_dari_server = null;
        $path_file_baru_di_server_untuk_rollback = null;

        if ($gambar_action === 'remove') {
            $nama_file_gambar_final_untuk_db = null;
            if (!empty($gambar_lama_di_db)) {
                $file_fisik_lama_untuk_dihapus_dari_server = $gambar_lama_di_db;
            }
        } elseif ($gambar_action === 'change') {
            if ($file_input_data && isset($file_input_data['error']) && $file_input_data['error'] == UPLOAD_ERR_OK && !empty($file_input_data['name'])) {
                $upload_error_message = null;
                if (!defined('UPLOADS_WISATA_PATH') || !is_dir(UPLOADS_WISATA_PATH) || !is_writable(UPLOADS_WISATA_PATH)) {
                    $upload_error_message = "Konfigurasi direktori unggah wisata bermasalah.";
                    error_log("WisataController::handleUpdateWisata - " . $upload_error_message);
                } else {
                    $upload_dir = rtrim(UPLOADS_WISATA_PATH, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
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
                            $nama_file_gambar_final_untuk_db = "wisata_" . uniqid() . '_' . time() . '.' . $file_ext;
                            $path_file_baru_di_server_untuk_rollback = $upload_dir . $nama_file_gambar_final_untuk_db;
                            if (!move_uploaded_file($file_tmp_name, $path_file_baru_di_server_untuk_rollback)) {
                                $upload_error_message = 'Gagal memindahkan file gambar baru.';
                                $nama_file_gambar_final_untuk_db = $gambar_lama_di_db; // Gagal upload, revert
                                $path_file_baru_di_server_untuk_rollback = null;
                            }
                        }
                    }
                }
                if ($upload_error_message) {
                    if (function_exists('set_flash_message')) set_flash_message('danger', $upload_error_message);
                    return 'upload_failed';
                }
                if (!empty($gambar_lama_di_db) && $gambar_lama_di_db !== $nama_file_gambar_final_untuk_db) {
                    $file_fisik_lama_untuk_dihapus_dari_server = $gambar_lama_di_db;
                }
            } else {
                if (function_exists('set_flash_message')) set_flash_message('danger', 'Anda memilih ganti gambar, tetapi tidak ada file baru yang valid diunggah atau terjadi error upload.');
                return 'upload_required_for_change';
            }
        }

        // Set 'gambar_alat' dalam $data_to_model_update HANYA jika ada perubahan atau aksi remove
        // Jika 'keep' dan $nama_file_gambar_final_untuk_db sama dengan $gambar_lama_di_db,
        // maka kita tidak perlu mengirim 'gambar' ke Model::update agar tidak di-SET ulang jika tidak berubah.
        // Namun, Model::update kita sudah dirancang untuk hanya SET field yang ada di $data.
        // Jadi, mengirimnya selalu tidak masalah asalkan nilainya benar.
        $data_to_model_update['gambar'] = $nama_file_gambar_final_untuk_db;

        error_log("WisataController::handleUpdateWisata - Data ke Model::update(): " . print_r($data_to_model_update, true));
        $update_db_success = Wisata::update($data_to_model_update);

        if ($update_db_success) {
            if ($file_fisik_lama_untuk_dihapus_dari_server && defined('UPLOADS_WISATA_PATH')) {
                $old_file_path_server = rtrim(UPLOADS_WISATA_PATH, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . basename($file_fisik_lama_untuk_dihapus_dari_server);
                if (file_exists($old_file_path_server) && is_file($old_file_path_server)) {
                    if (!@unlink($old_file_path_server)) {
                        error_log("WisataController::handleUpdateWisata Peringatan: Gagal menghapus file gambar lama: " . $old_file_path_server);
                    } else {
                        error_log("WisataController::handleUpdateWisata Info: File gambar lama berhasil dihapus: " . $old_file_path_server);
                    }
                }
            }
            return true;
        } else {
            // Rollback: Hapus file BARU yang sudah terupload jika update DB gagal
            if ($gambar_action === 'change' && $nama_file_gambar_final_untuk_db && !empty($path_file_baru_di_server_untuk_rollback) && file_exists($path_file_baru_di_server_untuk_rollback)) {
                @unlink($path_file_baru_di_server_untuk_rollback);
                error_log("WisataController::handleUpdateWisata - Rollback Upload: File baru {$nama_file_gambar_final_untuk_db} dihapus karena update DB gagal.");
            }
            $model_error = Wisata::getLastError();
            error_log("WisataController::handleUpdateWisata() - Wisata::update gagal untuk ID {$id}. Error Model: " . ($model_error ?? 'Tidak ada detail') . " | Data: " . print_r($data_to_model_update, true));
            return $model_error ?: 'db_update_failed';
        }
    }

    public static function delete(int $id): bool|string
    {
        $id_val = filter_var($id, FILTER_VALIDATE_INT);
        if ($id_val === false || $id_val <= 0) {
            error_log(get_called_class() . "::delete() - ID tidak valid: " . htmlspecialchars((string)$id));
            return 'invalid_id';
        }
        try {
            self::checkRequiredModelsAndMethods(['Wisata' => ['delete', 'getLastError', 'findById']]); // findById untuk info sebelum delete

            $wisata_to_delete = Wisata::findById($id_val); // Ambil info untuk hapus file
            if (!$wisata_to_delete && !Wisata::getLastError()) { // Tidak ditemukan, bukan error DB
                error_log("WisataController::delete() - Wisata ID {$id_val} tidak ditemukan untuk dihapus.");
                return 'item_not_found_on_delete';
            } elseif (!$wisata_to_delete && Wisata::getLastError()) { // Error DB saat findById
                error_log("WisataController::delete() - Gagal mengambil data Wisata ID {$id_val} sebelum hapus. Error: " . Wisata::getLastError());
                return 'db_error_before_delete';
            }


            if (Wisata::delete($id_val)) { // Model Wisata::delete() sudah handle hapus file fisik
                return true;
            } else {
                $error_model = Wisata::getLastError();
                error_log("WisataController::delete() - Wisata::delete gagal untuk ID {$id_val}. Error Model: " . ($error_model ?? 'Tidak ada detail'));
                // Model::delete mungkin sudah set flash message jika ada FK constraint
                // Jika tidak, kita bisa cek errornya di sini.
                // Contoh: jika error terkait foreign key, bisa beri pesan spesifik
                return $error_model ?: 'db_delete_failed';
            }
        } catch (RuntimeException $e) {
            error_log(get_called_class() . "::delete() - Exception: " . $e->getMessage());
            return 'system_error_model_unavailable';
        }
    }
}
