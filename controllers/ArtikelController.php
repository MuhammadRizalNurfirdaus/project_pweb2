<?php
// File: C:\xampp\htdocs\Cilengkrang-Web-Wisata\controllers\ArtikelController.php

/**
 * ArtikelController
 * Bertanggung jawab untuk logika bisnis terkait Artikel.
 * Berinteraksi dengan Model Artikel dan Feedback.
 * 
 * PENTING:
 * - Diasumsikan config.php sudah memuat SEMUA file Model yang diperlukan
 *   DAN sudah memanggil metode statis `ModelName::setDbConnection($conn)` atau `ModelName::init()` 
 *   untuk setiap Model (Artikel, Feedback).
 * - Fungsi helper (set_flash_message, e, redirect) diasumsikan tersedia dari config.php.
 */

// Tidak perlu require_once Model di sini jika config.php sudah menangani pemuatan dan inisialisasi Model.
// Jika belum, uncomment ini:
/*
if (!class_exists('Artikel')) { require_once __DIR__ . '/../models/Artikel.php'; }
if (!class_exists('Feedback')) { require_once __DIR__ . '/../models/Feedback.php'; }
*/

class ArtikelController
{
    /**
     * Membuat artikel baru.
     * @param array $data Data dari form, diharapkan memiliki 'judul', 'isi'. Opsional: 'gambar_filename'.
     * @return int|false ID artikel baru jika berhasil, false jika gagal. Flash message akan diatur.
     */
    public static function create(array $data)
    {
        // Validasi input dasar
        $judul = trim($data['judul'] ?? '');
        $isi = trim($data['isi'] ?? ''); // Biarkan HTML jika dari WYSIWYG, escape saat output
        $gambar_filename = isset($data['gambar_filename']) && !empty($data['gambar_filename']) ? trim($data['gambar_filename']) : null;

        if (empty($judul)) {
            if (function_exists('set_flash_message')) set_flash_message('danger', 'Judul artikel tidak boleh kosong.');
            return false;
        }
        if (empty($isi)) {
            if (function_exists('set_flash_message')) set_flash_message('danger', 'Isi artikel tidak boleh kosong.');
            return false;
        }

        // Pastikan Model Artikel dan metodenya tersedia
        if (!class_exists('Artikel') || !method_exists('Artikel', 'create')) {
            error_log("ArtikelController::create() - Model Artikel atau metode create tidak ditemukan.");
            if (function_exists('set_flash_message')) set_flash_message('danger', 'Kesalahan sistem: Komponen artikel tidak siap (M01C).');
            return false;
        }

        $data_to_model = [
            'judul' => $judul,
            'isi' => $isi,
            'gambar' => $gambar_filename
        ];

        $new_artikel_id = Artikel::create($data_to_model);

        if ($new_artikel_id) {
            if (function_exists('set_flash_message')) set_flash_message('success', 'Artikel baru berhasil ditambahkan dengan ID: ' . $new_artikel_id);
            return $new_artikel_id;
        } else {
            if (function_exists('set_flash_message') && !isset($_SESSION['flash_message'])) { // Hanya set jika Model belum
                $db_error = method_exists('Artikel', 'getLastError') ? Artikel::getLastError() : 'Tidak diketahui';
                set_flash_message('danger', 'Gagal menyimpan artikel ke database. ' . $db_error);
            }
            error_log("ArtikelController::create() - Artikel::create gagal. DB Error: " . (method_exists('Artikel', 'getLastError') ? Artikel::getLastError() : 'N/A'));
            return false;
        }
    }

    /**
     * Mengambil semua artikel untuk tampilan admin.
     * @return array Array data artikel atau array kosong.
     */
    public static function getAllForAdmin()
    {
        if (!class_exists('Artikel') || !method_exists('Artikel', 'getAll')) {
            error_log("ArtikelController::getAllForAdmin() - Model Artikel atau metode getAll tidak ditemukan.");
            return [];
        }
        return Artikel::getAll('created_at DESC'); // Urutkan terbaru dulu untuk admin
    }

    /**
     * Mengambil satu artikel berdasarkan ID.
     * @param int $id ID Artikel.
     * @return array|null Data artikel atau null.
     */
    public static function getById($id)
    {
        $id_val = filter_var($id, FILTER_VALIDATE_INT);
        if ($id_val === false || $id_val <= 0) {
            error_log("ArtikelController::getById() - ID artikel tidak valid: " . $id);
            return null;
        }
        if (!class_exists('Artikel') || !method_exists('Artikel', 'getById')) {
            error_log("ArtikelController::getById() - Model Artikel atau metode getById tidak ditemukan.");
            return null;
        }
        return Artikel::getById($id_val);
    }

    /**
     * Menangani proses update artikel.
     * @param array $data Data dari form edit, HARUS berisi 'id'.
     *                    Kunci lain yang diharapkan: 'judul', 'isi', 
     *                    'gambar_baru' (nama file baru, atau null, atau "REMOVE_IMAGE_FLAG"),
     *                    'gambar_lama' (nama file gambar saat ini sebelum update).
     * @return bool|string True jika berhasil, string pesan error spesifik, atau false jika gagal umum.
     */
    public static function handleUpdateArtikel(array $data)
    {
        $artikel_id = isset($data['id']) ? filter_var($data['id'], FILTER_VALIDATE_INT) : 0;
        if ($artikel_id <= 0) {
            return "ID Artikel tidak valid untuk update.";
        }

        $judul = trim($data['judul'] ?? '');
        $isi = trim($data['isi'] ?? '');
        $gambar_baru_filename = $data['gambar_baru'] ?? null; // Bisa null, nama file, atau "REMOVE_IMAGE_FLAG"
        $gambar_lama_filename = $data['gambar_lama'] ?? null;

        if (empty($judul)) return "Judul artikel tidak boleh kosong.";
        if (empty($isi)) return "Isi artikel tidak boleh kosong.";

        if (!class_exists('Artikel') || !method_exists('Artikel', 'update') || !method_exists('Artikel', 'getById')) {
            error_log("ArtikelController::handleUpdateArtikel() - Model Artikel atau metode yang dibutuhkan tidak ditemukan.");
            return "Kesalahan sistem: Komponen update artikel tidak siap (M02U).";
        }

        $data_to_update_model = [
            'id' => $artikel_id,
            'judul' => $judul,
            'isi' => $isi,
        ];

        $gambar_final_for_db = null;
        $delete_old_image_on_success = false;

        if ($gambar_baru_filename === "REMOVE_IMAGE_FLAG") {
            $data_to_update_model['gambar'] = ""; // Kirim string kosong untuk di-set NULL oleh Model::update
            if (!empty($gambar_lama_filename)) {
                $delete_old_image_on_success = true;
            }
        } elseif (!empty($gambar_baru_filename) && is_string($gambar_baru_filename)) {
            $data_to_update_model['gambar'] = $gambar_baru_filename;
            if (!empty($gambar_lama_filename) && $gambar_lama_filename !== $gambar_baru_filename) {
                $delete_old_image_on_success = true;
            }
        } else {
            // Tidak ada perubahan gambar, jangan sertakan 'gambar' di $data_to_update_model
            // agar Model::update tidak mengubahnya. Atau Model::update bisa handle jika $data['gambar'] null.
            // Jika Model::update selalu mengharapkan 'gambar', kirim gambar lama:
            // $data_to_update_model['gambar'] = $gambar_lama_filename;
        }

        $update_success = Artikel::update($data_to_update_model);

        if ($update_success) {
            if ($delete_old_image_on_success && !empty($gambar_lama_filename)) {
                // Pastikan UPLOADS_ARTIKEL_PATH sudah didefinisikan dan Model Artikel sudah di-init dengan path ini
                // atau Controller mengambil path dari konstanta.
                if (defined('UPLOADS_ARTIKEL_PATH')) {
                    $old_file_path = UPLOADS_ARTIKEL_PATH . DIRECTORY_SEPARATOR . basename($gambar_lama_filename);
                    if (file_exists($old_file_path) && is_file($old_file_path)) {
                        if (!@unlink($old_file_path)) {
                            error_log("ArtikelController::handleUpdateArtikel Peringatan: Gagal menghapus file gambar lama: " . $old_file_path);
                            // Tidak menggagalkan operasi utama, hanya log
                        }
                    }
                } else {
                    error_log("ArtikelController::handleUpdateArtikel Peringatan: Konstanta UPLOADS_ARTIKEL_PATH tidak terdefinisi. Gambar lama mungkin tidak terhapus.");
                }
            }
            return true;
        } else {
            $db_error = method_exists('Artikel', 'getLastError') ? Artikel::getLastError() : 'Tidak diketahui';
            error_log("ArtikelController::handleUpdateArtikel() - Artikel::update gagal untuk ID {$artikel_id}. DB Error: " . $db_error);
            return "Gagal memperbarui artikel di database. " . $db_error;
        }
    }


    /**
     * Menghapus artikel berdasarkan ID.
     * Ini juga akan menghapus semua feedback yang terkait dan file gambar.
     * @param int $id ID Artikel.
     * @return bool True jika berhasil, false jika gagal.
     */
    public static function delete($id)
    {
        $id_val = filter_var($id, FILTER_VALIDATE_INT);
        if ($id_val === false || $id_val <= 0) {
            if (function_exists('set_flash_message')) set_flash_message('danger', 'ID Artikel tidak valid untuk dihapus.');
            error_log("ArtikelController::delete() - ID artikel tidak valid: " . $id);
            return false;
        }

        if (
            !class_exists('Artikel') || !method_exists('Artikel', 'delete') ||
            !class_exists('Feedback') || !method_exists('Feedback', 'deleteByArtikelId')
        ) {
            error_log("ArtikelController::delete() - Model Artikel/Feedback atau metode yang dibutuhkan tidak ditemukan.");
            if (function_exists('set_flash_message')) set_flash_message('danger', 'Kesalahan sistem: Komponen hapus artikel tidak lengkap (M03D).');
            return false;
        }

        // Model Artikel::delete() yang direvisi sudah menangani penghapusan feedback dan gambar.
        $delete_result = Artikel::delete($id_val);

        if ($delete_result) {
            if (function_exists('set_flash_message')) set_flash_message('success', 'Artikel berhasil dihapus.');
            return true;
        } else {
            if (function_exists('set_flash_message') && !isset($_SESSION['flash_message'])) { // Hanya set jika Model belum
                $db_error = method_exists('Artikel', 'getLastError') ? Artikel::getLastError() : 'Tidak diketahui';
                set_flash_message('danger', 'Gagal menghapus artikel. ' . $db_error);
            }
            error_log("ArtikelController::delete() - Artikel::delete gagal untuk ID {$id_val}. DB Error: " . (method_exists('Artikel', 'getLastError') ? Artikel::getLastError() : 'N/A'));
            return false;
        }
    }

    /**
     * Mengambil sejumlah artikel terbaru untuk tampilan publik atau widget.
     * @param int $limit Jumlah artikel.
     * @return array Daftar artikel.
     */
    public static function getLatestPublished($limit = 5)
    {
        if (!class_exists('Artikel') || !method_exists('Artikel', 'getLatest')) {
            error_log("ArtikelController::getLatestPublished() - Model Artikel atau metode getLatest tidak ditemukan.");
            return [];
        }
        // Di sini bisa ditambahkan logika filter tambahan jika perlu,
        // misalnya hanya artikel dengan status 'published' jika ada kolom status di tabel artikel.
        return Artikel::getLatest((int)$limit);
    }

    /**
     * Menghitung semua artikel.
     * @return int Jumlah artikel.
     */
    public static function countAllArticles()
    {
        if (!class_exists('Artikel') || !method_exists('Artikel', 'countAll')) {
            error_log("ArtikelController::countAllArticles() - Model Artikel atau metode countAll tidak ditemukan.");
            return 0;
        }
        return Artikel::countAll();
    }
} // End of class ArtikelController