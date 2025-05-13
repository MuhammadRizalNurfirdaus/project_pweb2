<?php
// File: C:\xampp\htdocs\Cilengkrang-Web-Wisata\controllers\GaleriController.php

/**
 * GaleriController
 * Bertanggung jawab untuk logika bisnis terkait Galeri Foto.
 * Berinteraksi dengan Model Galeri.
 * 
 * PENTING:
 * - Diasumsikan config.php sudah memuat Model Galeri.php
 *   DAN sudah memanggil Galeri::init($conn, UPLOADS_GALERI_PATH).
 * - Fungsi helper (set_flash_message, e, redirect) diasumsikan tersedia dari config.php.
 */

// Tidak perlu require_once Model di sini jika config.php sudah menangani pemuatan dan inisialisasi Model.
// if (!class_exists('Galeri')) { require_once __DIR__ . '/../models/Galeri.php'; }

class GaleriController
{
    /**
     * Menangani pembuatan item galeri baru.
     * @param array $data Data dari form, diharapkan memiliki 'keterangan'.
     * @param string|null $nama_file_gambar Nama file gambar yang sudah diupload dan divalidasi.
     * @return int|false ID item galeri baru jika berhasil, false jika gagal.
     */
    public static function create(array $data, $nama_file_gambar)
    {
        if (!class_exists('Galeri') || !method_exists('Galeri', 'create')) {
            error_log("GaleriController::create() - Model Galeri atau metode create tidak ditemukan.");
            if (function_exists('set_flash_message')) set_flash_message('danger', 'Kesalahan sistem: Komponen galeri tidak siap (GC-C01).');
            return false;
        }

        $keterangan = trim($data['keterangan'] ?? '');

        if (empty($nama_file_gambar)) { // Nama file gambar dari proses upload, harus ada
            if (function_exists('set_flash_message')) set_flash_message('danger', 'File gambar wajib diunggah untuk item galeri baru.');
            error_log("GaleriController::create() - Nama file gambar kosong.");
            return false;
        }
        // Validasi tambahan untuk keterangan bisa dilakukan di sini jika perlu.

        $data_to_model = [
            'nama_file' => $nama_file_gambar,
            'keterangan' => $keterangan
        ];

        $new_id = Galeri::create($data_to_model);

        if ($new_id) {
            if (function_exists('set_flash_message')) set_flash_message('success', 'Foto baru berhasil ditambahkan ke galeri.');
            return $new_id;
        } else {
            $error_detail = method_exists('Galeri', 'getLastError') ? Galeri::getLastError() : 'Tidak diketahui.';
            if (function_exists('set_flash_message') && !isset($_SESSION['flash_message'])) {
                set_flash_message('danger', 'Gagal menyimpan foto ke galeri. ' . $error_detail);
            }
            error_log("GaleriController::create() - Galeri::create gagal. Error: " . $error_detail);
            return false;
        }
    }

    /**
     * Mengambil semua item galeri untuk tampilan admin.
     * @return array Array data galeri atau array kosong.
     */
    public static function getAllForAdmin()
    {
        if (!class_exists('Galeri') || !method_exists('Galeri', 'getAll')) {
            error_log("GaleriController::getAllForAdmin() - Model Galeri atau metode getAll tidak ditemukan.");
            return [];
        }
        return Galeri::getAll('uploaded_at DESC'); // Urutkan berdasarkan tanggal upload terbaru
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
            error_log("GaleriController::getById() - Model Galeri atau metode getById tidak ditemukan.");
            return null;
        }
        return Galeri::getById($id_val);
    }

    /**
     * Menangani proses update foto galeri.
     * @param int $id ID foto galeri yang akan diupdate.
     * @param string $keterangan Keterangan baru untuk foto.
     * @param string|null $nama_file_baru_untuk_db Nama file gambar baru yang akan disimpan ke DB (bisa null jika gambar dihapus, atau nama file jika diganti).
     * @param string|null $nama_file_lama_untuk_dihapus_fisik Nama file gambar lama di server yang perlu dihapus jika gambar diganti atau dihapus dari DB.
     * @return bool True jika update berhasil, false jika gagal. Flash message akan diatur.
     */
    public static function update($id, $keterangan, $nama_file_baru_untuk_db, $nama_file_lama_untuk_dihapus_fisik)
    {
        $id_val = filter_var($id, FILTER_VALIDATE_INT);
        if ($id_val === false || $id_val <= 0) {
            if (function_exists('set_flash_message')) set_flash_message('danger', 'ID Foto tidak valid untuk proses update.');
            error_log("GaleriController::update() - ID galeri tidak valid: " . $id);
            return false;
        }

        if (!class_exists('Galeri') || !method_exists('Galeri', 'update')) {
            error_log("GaleriController::update() - Model Galeri atau metode update tidak ditemukan.");
            if (function_exists('set_flash_message')) set_flash_message('danger', 'Kesalahan sistem: Komponen update galeri tidak siap (GC-U01).');
            return false;
        }

        $data_to_model = [
            'id' => $id_val,
            'keterangan' => trim($keterangan)
        ];

        // Hanya sertakan 'nama_file' dalam data ke model JIKA ada intensi untuk mengubahnya
        // (yaitu, jika $nama_file_baru_untuk_db adalah nama file baru, atau jika itu adalah NULL karena aksi 'remove')
        // Jika $nama_file_baru_untuk_db adalah NULL dan $nama_file_lama_untuk_dihapus_fisik juga NULL,
        // berarti tidak ada aksi gambar, jadi 'nama_file' tidak perlu dikirim ke Model::update
        // kecuali jika Model::update Anda mengharapkan 'nama_file' selalu ada.
        $ada_aksi_gambar = ($nama_file_baru_untuk_db !== null || ($nama_file_lama_untuk_dihapus_fisik !== null && $nama_file_baru_untuk_db === null));

        if ($ada_aksi_gambar) {
            $data_to_model['nama_file'] = $nama_file_baru_untuk_db; // Bisa null jika gambar dihapus dari record
        }
        // Jika tidak ada aksi gambar, Model Galeri::update hanya akan mengupdate keterangan.

        $update_db_success = Galeri::update($data_to_model);

        if ($update_db_success) {
            // Jika update DB berhasil DAN ada file lama yang perlu dihapus dari server
            if ($nama_file_lama_untuk_dihapus_fisik && $ada_aksi_gambar) {
                // Kondisi $ada_aksi_gambar memastikan kita hanya hapus jika memang ada perubahan/penghapusan gambar
                // dan $nama_file_lama_untuk_dihapus_fisik tidak sama dengan $nama_file_baru_untuk_db (jika diganti)
                if ($nama_file_baru_untuk_db === null || $nama_file_baru_untuk_db !== $nama_file_lama_untuk_dihapus_fisik) {
                    if (defined('UPLOADS_GALERI_PATH')) {
                        $old_file_path = rtrim(UPLOADS_GALERI_PATH, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . basename($nama_file_lama_untuk_dihapus_fisik);
                        if (file_exists($old_file_path) && is_file($old_file_path)) {
                            if (!@unlink($old_file_path)) {
                                error_log("GaleriController::update Peringatan: Gagal menghapus file gambar lama: " . $old_file_path);
                            } else {
                                error_log("GaleriController::update Info: File gambar lama berhasil dihapus: " . $old_file_path);
                            }
                        }
                    } else {
                        error_log("GaleriController::update Peringatan: Konstanta UPLOADS_GALERI_PATH tidak terdefinisi. Gambar lama mungkin tidak terhapus.");
                    }
                }
            }
            // Pesan sukses sebaiknya di-set oleh skrip pemanggil (edit_foto.php) setelah redirect
            return true;
        } else {
            $error_detail = method_exists('Galeri', 'getLastError') ? Galeri::getLastError() : 'Tidak diketahui.';
            // Pesan error juga sebaiknya di-set oleh skrip pemanggil
            error_log("GaleriController::update() - Galeri::update gagal untuk ID {$id_val}. Error: " . $error_detail);
            return false;
        }
    }

    /**
     * Menghapus item galeri berdasarkan ID.
     * @param int $id ID Galeri.
     * @return bool True jika berhasil, false jika gagal.
     */
    public static function delete($id)
    {
        $id_val = filter_var($id, FILTER_VALIDATE_INT);
        if ($id_val === false || $id_val <= 0) {
            if (function_exists('set_flash_message')) set_flash_message('danger', 'ID Foto tidak valid untuk dihapus.');
            error_log("GaleriController::delete() - ID galeri tidak valid: " . $id);
            return false;
        }

        if (!class_exists('Galeri') || !method_exists('Galeri', 'delete') || !method_exists('Galeri', 'getById')) {
            error_log("GaleriController::delete() - Model/Metode Galeri tidak ada.");
            if (function_exists('set_flash_message')) set_flash_message('danger', 'Kesalahan sistem: Komponen hapus galeri tidak siap (GC-D01).');
            return false;
        }

        // Model Galeri::delete() yang direvisi sudah menangani penghapusan file fisik
        // dengan mengambil data foto (termasuk nama file) sebelum menghapus record DB.
        if (Galeri::delete($id_val)) {
            if (function_exists('set_flash_message')) set_flash_message('success', 'Foto berhasil dihapus dari galeri.');
            return true;
        } else {
            $error_detail = method_exists('Galeri', 'getLastError') ? Galeri::getLastError() : 'Tidak diketahui.';
            if (function_exists('set_flash_message') && !isset($_SESSION['flash_message'])) {
                set_flash_message('danger', 'Gagal menghapus foto dari galeri. ' . $error_detail);
            }
            error_log("GaleriController::delete() - Galeri::delete gagal untuk ID {$id_val}. Error: " . $error_detail);
            return false;
        }
    }

    // Anda bisa menambahkan metode lain di sini, seperti getLatestPhotosForPublicView, dll.

} // End of class GaleriController