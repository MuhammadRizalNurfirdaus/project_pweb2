<?php
// File: C:\xampp\htdocs\Cilengkrang-Web-Wisata\controllers\ArtikelController.php

/**
 * ArtikelController
 * Bertanggung jawab untuk logika bisnis terkait Artikel.
 * Berinteraksi dengan Model Artikel dan Feedback.
 * 
 * PENTING:
 * - Diasumsikan config.php sudah memuat SEMUA file Model yang diperlukan
 *   (Artikel.php, Feedback.php) DAN sudah memanggil metode statis 
 *   ModelName::init($conn, $upload_path) untuk setiap Model.
 * - Fungsi helper (set_flash_message, e, redirect, dll.) diasumsikan tersedia
 *   global (dimuat oleh config.php).
 * - Konstanta seperti UPLOADS_ARTIKEL_PATH harus sudah didefinisikan di config.php 
 *   dan path ini harus sama dengan yang diinisialisasikan ke Model Artikel.
 */

// Tidak perlu require_once Model di sini jika config.php sudah menangani pemuatan
// dan inisialisasi Model.

class ArtikelController
{
    /**
     * Membuat artikel baru.
     * @param array $data Data dari form, diharapkan memiliki 'judul', 'isi'. 
     *                    Opsional: 'gambar_filename' (nama file gambar yang sudah diupload).
     * @return int|false ID artikel baru jika berhasil, false jika gagal. Flash message akan diatur.
     */
    public static function create(array $data)
    {
        $judul = trim($data['judul'] ?? '');
        $isi = trim($data['isi'] ?? ''); // Isi bisa mengandung HTML dari editor WYSIWYG
        $gambar_filename = isset($data['gambar_filename']) && !empty($data['gambar_filename']) ? trim($data['gambar_filename']) : null;

        // Validasi input dasar di Controller
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
            error_log("ArtikelController::create() - Model Artikel atau metode Artikel::create tidak ditemukan.");
            if (function_exists('set_flash_message')) set_flash_message('danger', 'Kesalahan sistem: Komponen artikel tidak siap (AC01).');
            return false;
        }

        $data_to_model = [
            'judul' => $judul,
            'isi' => $isi,
            'gambar' => $gambar_filename // Model akan menangani jika ini null
        ];

        $new_artikel_id = Artikel::create($data_to_model);

        if ($new_artikel_id) {
            if (function_exists('set_flash_message')) set_flash_message('success', 'Artikel baru berhasil ditambahkan.');
            return $new_artikel_id;
        } else {
            $model_error = method_exists('Artikel', 'getLastError') ? Artikel::getLastError() : 'Operasi database gagal.';
            error_log("ArtikelController::create() - Artikel::create gagal. Error: " . $model_error);
            // Hanya set flash message jika belum ada (misalnya, Model belum set via controllernya)
            if (function_exists('set_flash_message') && (!isset($_SESSION['flash_message']) || empty($_SESSION['flash_message']))) {
                set_flash_message('danger', 'Gagal menyimpan artikel: ' . $model_error);
            }
            return false;
        }
    }

    /**
     * Mengambil semua artikel untuk tampilan admin, diurutkan berdasarkan tanggal terbaru.
     * @return array Array data artikel atau array kosong jika tidak ada atau error.
     */
    public static function getAllForAdmin()
    {
        if (!class_exists('Artikel') || !method_exists('Artikel', 'getAll')) {
            error_log("ArtikelController::getAllForAdmin() - Model Artikel atau metode Artikel::getAll tidak ditemukan.");
            return [];
        }
        return Artikel::getAll('created_at DESC');
    }

    /**
     * Mengambil satu artikel berdasarkan ID-nya. (Digunakan internal atau oleh admin)
     * @param int $id ID Artikel.
     * @return array|null Data artikel jika ditemukan, atau null jika tidak atau error.
     */
    public static function getById($id) // Nama metode di controller bisa tetap getById jika diinginkan
    {
        $artikel_id = filter_var($id, FILTER_VALIDATE_INT);
        if ($artikel_id === false || $artikel_id <= 0) {
            error_log("ArtikelController::getById() - ID artikel tidak valid: " . print_r($id, true));
            return null;
        }

        // Pengecekan harus ke findById
        if (!class_exists('Artikel') || !method_exists('Artikel', 'findById')) {
            error_log("ArtikelController::getById() - Model Artikel atau metode Artikel::findById tidak ditemukan.");
            return null;
        }
        return Artikel::findById($artikel_id); // Pemanggilan ke Model sudah benar
    }

    /**
     * Menangani proses update artikel.
     * Model Artikel bertanggung jawab untuk menghapus file gambar lama jika gambar diubah atau dihapus.
     * @param array $data Data dari form edit, HARUS berisi 'id'.
     *                    Kunci lain yang diharapkan: 'judul', 'isi'.
     *                    Opsional: 'gambar_baru' (nama file baru hasil upload), 
     *                    'hapus_gambar' (flag boolean/string '1' untuk menghapus gambar).
     * @return bool True jika berhasil, false jika gagal. Pesan error akan di-set sebagai flash message.
     */
    public static function handleUpdateArtikel(array $data)
    {
        $artikel_id = isset($data['id']) ? filter_var($data['id'], FILTER_VALIDATE_INT) : 0;
        if ($artikel_id <= 0) {
            if (function_exists('set_flash_message')) set_flash_message('danger', 'ID Artikel tidak valid untuk update.');
            return false;
        }

        $judul = trim($data['judul'] ?? '');
        $isi = trim($data['isi'] ?? '');
        $gambar_baru_filename = $data['gambar_baru'] ?? null; // Nama file BARU yang diupload
        $hapus_gambar_flag = isset($data['hapus_gambar']) && ($data['hapus_gambar'] == '1' || $data['hapus_gambar'] === true);

        if (empty($judul)) {
            if (function_exists('set_flash_message')) set_flash_message('danger', 'Judul artikel tidak boleh kosong.');
            return false;
        }
        if (empty($isi)) {
            if (function_exists('set_flash_message')) set_flash_message('danger', 'Isi artikel tidak boleh kosong.');
            return false;
        }

        // Pengecekan method_exists harus ke findById jika Anda menggunakannya untuk mengambil artikel saat ini
        if (!class_exists('Artikel') || !method_exists('Artikel', 'update') || !method_exists('Artikel', 'findById')) {
            error_log("ArtikelController::handleUpdateArtikel() - Model Artikel atau metode yang dibutuhkan (update, findById) tidak ditemukan.");
            if (function_exists('set_flash_message')) set_flash_message('danger', 'Kesalahan sistem: Komponen update artikel tidak siap (AC02).');
            return false;
        }

        $data_to_update_model = [
            'id' => $artikel_id,
            'judul' => $judul,
            'isi' => $isi,
        ];

        if ($hapus_gambar_flag) {
            $data_to_update_model['hapus_gambar'] = true;
            // Model akan menangani set 'gambar' ke null di DB dan hapus file fisik
        } elseif (!empty($gambar_baru_filename) && is_string($gambar_baru_filename)) {
            $data_to_update_model['gambar'] = $gambar_baru_filename; // Nama file gambar baru
            // Model akan menangani penghapusan file lama jika ada
        }
        // Jika tidak ada $gambar_baru_filename dan $hapus_gambar_flag false,
        // 'gambar' dan 'hapus_gambar' tidak dimasukkan ke $data_to_update_model,
        // Model Artikel::update tidak akan mengubah kolom gambar atau menghapus file.

        $update_success = Artikel::update($data_to_update_model);

        if ($update_success) {
            // Model Artikel::update sudah menangani penghapusan file gambar lama.
            // Tidak perlu logika unlink di sini.
            if (function_exists('set_flash_message')) {
                // Cek apakah model sudah set pesan warning (misal, gagal hapus file tapi DB update)
                $model_error_for_flash = method_exists('Artikel', 'getLastError') ? Artikel::getLastError() : null;
                if ($model_error_for_flash && stripos($model_error_for_flash, "Peringatan:") === 0) {
                    set_flash_message('warning', $model_error_for_flash);
                } elseif (!isset($_SESSION['flash_message']) || empty($_SESSION['flash_message'])) {
                    set_flash_message('success', 'Artikel berhasil diperbarui.');
                }
            }
            return true;
        } else {
            $model_error = method_exists('Artikel', 'getLastError') ? Artikel::getLastError() : 'Operasi database gagal.';
            error_log("ArtikelController::handleUpdateArtikel() - Artikel::update gagal untuk ID {$artikel_id}. Error: " . $model_error);
            if (function_exists('set_flash_message') && (!isset($_SESSION['flash_message']) || empty($_SESSION['flash_message']))) {
                set_flash_message('danger', 'Gagal memperbarui artikel: ' . $model_error);
            }
            return false;
        }
    }

    /**
     * Menghapus artikel berdasarkan ID.
     * Model Artikel bertanggung jawab untuk menghapus file gambar terkait dan feedback.
     * @param int $id ID Artikel.
     * @return bool True jika berhasil, false jika gagal. Flash message akan diatur.
     */
    public static function delete($id)
    {
        $artikel_id = filter_var($id, FILTER_VALIDATE_INT);
        if ($artikel_id === false || $artikel_id <= 0) {
            if (function_exists('set_flash_message')) set_flash_message('danger', 'ID Artikel tidak valid untuk dihapus.');
            error_log("ArtikelController::delete() - ID artikel tidak valid: " . print_r($id, true));
            return false;
        }

        // Pastikan Model dan metode yang dibutuhkan tersedia
        // Artikel::findById mungkin tidak secara langsung dibutuhkan oleh Artikel::delete,
        // tetapi baik untuk konsistensi jika ada logika pre-delete di controller.
        // Model Artikel::delete sendiri sudah mengambil data artikel untuk hapus gambar.
        if (
            !class_exists('Artikel') || !method_exists('Artikel', 'delete') ||
            (class_exists('Feedback') && !method_exists('Feedback', 'deleteByArtikelId')) // Jika Feedback ada, metodenya juga harus ada
        ) {
            $missing_artikel_delete = !method_exists('Artikel', 'delete') ? "Artikel::delete " : "";
            $missing_feedback_delete = (class_exists('Feedback') && !method_exists('Feedback', 'deleteByArtikelId')) ? "Feedback::deleteByArtikelId " : "";
            error_log("ArtikelController::delete() - Metode yang dibutuhkan tidak ditemukan: " . $missing_artikel_delete . $missing_feedback_delete);
            if (function_exists('set_flash_message')) set_flash_message('danger', 'Kesalahan sistem: Komponen hapus artikel tidak lengkap (AC03).');
            return false;
        }

        // Model Artikel::delete akan menangani penghapusan artikel, feedback terkait (jika diimplementasikan di sana), dan file gambar.
        $delete_result = Artikel::delete($artikel_id);

        if ($delete_result) {
            // Model Artikel::delete sudah menangani penghapusan file gambar.
            // Tidak perlu logika unlink di sini.
            // Model juga sudah melakukan error_log jika ada Peringatan (misal, gagal hapus file).
            if (function_exists('set_flash_message')) {
                $model_error_for_flash = method_exists('Artikel', 'getLastError') ? Artikel::getLastError() : null;
                // Jika ada pesan peringatan dari model (misal, artikel DB dihapus tapi file gagal)
                if ($model_error_for_flash && stripos($model_error_for_flash, "Peringatan:") === 0) {
                    set_flash_message('warning', $model_error_for_flash);
                } elseif (!isset($_SESSION['flash_message']) || empty($_SESSION['flash_message'])) {
                    set_flash_message('success', 'Artikel berhasil dihapus.');
                }
            }
            return true;
        } else {
            $model_error = method_exists('Artikel', 'getLastError') ? Artikel::getLastError() : 'Operasi database gagal.';
            error_log("ArtikelController::delete() - Artikel::delete gagal untuk ID {$artikel_id}. Error: " . $model_error);
            if (function_exists('set_flash_message') && (!isset($_SESSION['flash_message']) || empty($_SESSION['flash_message']))) {
                set_flash_message('danger', 'Gagal menghapus artikel: ' . $model_error);
            }
            return false;
        }
    }

    /**
     * Mengambil sejumlah artikel terbaru untuk tampilan publik atau widget.
     * @param int $limit Jumlah artikel yang ingin diambil.
     * @return array Daftar artikel atau array kosong jika gagal/tidak ada.
     */
    public static function getLatestPublished($limit = 5)
    {
        $limit_val = filter_var($limit, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($limit_val === false) $limit_val = 5; // Default jika input tidak valid

        if (!class_exists('Artikel') || !method_exists('Artikel', 'getLatest')) {
            error_log("ArtikelController::getLatestPublished() - Model Artikel atau metode Artikel::getLatest tidak ditemukan.");
            return [];
        }
        return Artikel::getLatest($limit_val);
    }

    /**
     * Menghitung total semua artikel.
     * @return int Jumlah artikel atau 0 jika error.
     */
    public static function countAllArticles()
    {
        if (!class_exists('Artikel') || !method_exists('Artikel', 'countAll')) {
            error_log("ArtikelController::countAllArticles() - Model Artikel atau metode Artikel::countAll tidak ditemukan.");
            return 0;
        }
        return Artikel::countAll();
    }

    /**
     * Mengambil detail artikel tunggal beserta feedback-nya untuk halaman publik.
     * @param int $artikel_id ID artikel.
     * @return array|null Array dengan kunci 'artikel' dan 'feedbacks', atau null jika artikel tidak ditemukan atau error.
     */
    public static function getArtikelDetailForUserPage($artikel_id)
    {
        $id_val = filter_var($artikel_id, FILTER_VALIDATE_INT);
        if ($id_val === false || $id_val <= 0) {
            error_log("ArtikelController::getArtikelDetailForUserPage() - ID artikel tidak valid: " . print_r($artikel_id, true));
            return null;
        }

        // Pastikan Model Artikel dan metodenya findById tersedia
        if (!class_exists('Artikel') || !method_exists('Artikel', 'findById')) {
            error_log("ArtikelController::getArtikelDetailForUserPage() - Model Artikel atau metode Artikel::findById tidak ditemukan.");
            if (function_exists('set_flash_message')) set_flash_message('danger', 'Kesalahan sistem: Komponen artikel tidak siap (AC04A).');
            return null;
        }
        // Pastikan Model Feedback dan metodenya tersedia
        if (class_exists('Feedback') && !method_exists('Feedback', 'getByArtikelId')) {
            error_log("ArtikelController::getArtikelDetailForUserPage() - Model Feedback atau metode Feedback::getByArtikelId tidak ditemukan.");
            if (function_exists('set_flash_message')) set_flash_message('danger', 'Kesalahan sistem: Komponen feedback tidak siap (AC04B).');
            // Mungkin tidak fatal jika feedback hanya tambahan, tergantung kebutuhan
            // return null; 
        }

        $artikel = Artikel::findById($id_val);

        if (!$artikel) {
            // Pesan error sudah di-log oleh Model Artikel::findById jika ada masalah query.
            // Controller bisa memutuskan untuk tidak menampilkan flash message di sini,
            // karena halaman mungkin hanya menampilkan "Artikel tidak ditemukan".
            return null;
        }

        $feedbacks = [];
        if (class_exists('Feedback') && method_exists('Feedback', 'getByArtikelId')) {
            $feedbacks = Feedback::getByArtikelId($id_val);
        }

        return [
            'artikel' => $artikel,
            'feedbacks' => $feedbacks
        ];
    }

    /**
     * Mengambil sejumlah artikel lain (selain yang sedang ditampilkan).
     * Berguna untuk sidebar "Artikel Lainnya".
     * @param int $limit Jumlah artikel yang diinginkan.
     * @param array $exclude_ids Array ID artikel yang TIDAK ingin ditampilkan.
     * @return array Daftar artikel atau array kosong.
     */
    public static function getArtikelLain($limit = 3, array $exclude_ids = [])
    {
        $limit_val = filter_var($limit, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($limit_val === false) $limit_val = 3;

        $valid_exclude_ids = [];
        if (!empty($exclude_ids)) {
            foreach ($exclude_ids as $ex_id) {
                $ex_id_val = filter_var($ex_id, FILTER_VALIDATE_INT);
                if ($ex_id_val && $ex_id_val > 0) {
                    $valid_exclude_ids[] = $ex_id_val;
                }
            }
        }

        if (!class_exists('Artikel') || !method_exists('Artikel', 'getOtherArticles')) {
            error_log("ArtikelController::getArtikelLain() - Model Artikel atau metode Artikel::getOtherArticles tidak ditemukan.");
            return [];
        }
        return Artikel::getOtherArticles($limit_val, $valid_exclude_ids);
    }
} // End of class ArtikelController