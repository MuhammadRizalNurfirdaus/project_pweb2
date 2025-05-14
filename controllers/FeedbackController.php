<?php
// File: C:\xampp\htdocs\Cilengkrang-Web-Wisata\controllers\FeedbackController.php

/**
 * FeedbackController
 * Bertanggung jawab untuk logika bisnis terkait Feedback.
 * Berinteraksi dengan Model Feedback.
 * 
 * PENTING:
 * - Diasumsikan config.php sudah memuat Model Feedback.php
 *   DAN sudah memanggil metode statis `Feedback::setDbConnection($conn)`.
 * - Fungsi helper (set_flash_message, redirect, verify_csrf_token, is_post, dll.) 
 *   diasumsikan tersedia global (dimuat oleh config.php).
 * - Diasumsikan session sudah dimulai (`session_start()`) di config.php.
 */

// Tidak perlu require_once Model di sini jika config.php sudah menangani.
// if (!class_exists('Feedback')) { require_once __DIR__ . '/../models/Feedback.php'; }

class FeedbackController
{
    /**
     * Menangani submit feedback untuk sebuah artikel dari sisi pengguna.
     * @param array $data_feedback_post Data dari form, harus berisi:
     *        'artikel_id', 'komentar', 'rating'.
     *        'user_id' akan diambil dari session jika user login.
     * @return bool True jika berhasil, false jika gagal. Flash message akan diatur.
     */
    public static function submitFeedbackForArtikel(array $data_feedback_post)
    {
        // Validasi input dasar
        $artikel_id = isset($data_feedback_post['artikel_id']) ? filter_var($data_feedback_post['artikel_id'], FILTER_VALIDATE_INT) : null;
        $komentar   = isset($data_feedback_post['komentar']) ? trim($data_feedback_post['komentar']) : '';
        $rating_input = $data_feedback_post['rating'] ?? null;
        $rating     = ($rating_input !== null && $rating_input !== '') ? filter_var($rating_input, FILTER_VALIDATE_INT) : null;


        if (!$artikel_id || $artikel_id <= 0) {
            if (function_exists('set_flash_message')) set_flash_message('danger', 'ID Artikel tidak valid untuk feedback.');
            return false;
        }
        if (empty($komentar)) {
            if (function_exists('set_flash_message')) set_flash_message('danger', 'Komentar tidak boleh kosong.');
            return false;
        }
        if ($rating === null || $rating < 1 || $rating > 5) {
            if (function_exists('set_flash_message')) set_flash_message('danger', 'Rating tidak valid (harus 1-5).');
            return false;
        }

        // Cek apakah user login dan ambil user_id
        $user_id = null;
        if (isset($_SESSION['user_id'])) { // Sesuaikan dengan cara Anda manage session user
            $user_id = (int)$_SESSION['user_id'];
        } else {
            // Jika feedback tamu tidak diizinkan, paksa login atau return false
            // Untuk contoh ini, kita asumsikan feedback tamu tidak diizinkan jika user_id diperlukan oleh DB
            // dan kolom user_id di tabel feedback adalah NOT NULL.
            // Jika kolom user_id bisa NULL, maka biarkan $user_id null.
            // Untuk sistem yang mewajibkan login:
            if (function_exists('set_flash_message')) set_flash_message('warning', 'Anda harus login untuk memberikan feedback.');
            // Jika ada fungsi redirect:
            // if (function_exists('redirect') && defined('AUTH_URL') && defined('CURRENT_URL')) {
            //    redirect(AUTH_URL . '/login.php?redirect=' . rawurlencode(CURRENT_URL));
            //    exit;
            // }
            return false;
        }

        // Pastikan Model Feedback dan metodenya tersedia
        if (!class_exists('Feedback') || !method_exists('Feedback', 'create')) {
            error_log("FeedbackController::submitFeedbackForArtikel() - Model Feedback atau metode Feedback::create tidak ditemukan.");
            if (function_exists('set_flash_message')) set_flash_message('danger', 'Kesalahan sistem: Komponen feedback tidak siap (FC01).');
            return false;
        }

        $data_to_model = [
            'artikel_id' => $artikel_id,
            'user_id'    => $user_id, // Bisa null jika tamu diizinkan & kolom DB NULLABLE
            'komentar'   => $komentar,
            'rating'     => $rating
        ];

        $new_feedback_id = Feedback::create($data_to_model);

        if ($new_feedback_id) {
            if (function_exists('set_flash_message')) set_flash_message('success', 'Terima kasih! Feedback Anda telah berhasil dikirim.');
            return true;
        } else {
            $model_error = method_exists('Feedback', 'getLastError') ? Feedback::getLastError() : 'Operasi database gagal.';
            error_log("FeedbackController::submitFeedbackForArtikel() - Feedback::create gagal. Error: " . $model_error);
            if (function_exists('set_flash_message')) {
                // Cek jika flash message belum di-set oleh model atau validasi sebelumnya
                if (!isset($_SESSION['flash_message'])) {
                    set_flash_message('danger', 'Gagal mengirim feedback: ' . $model_error);
                }
            }
            return false;
        }
    }

    /**
     * Mengambil semua feedback untuk tampilan admin.
     * Di-join dengan nama user dan judul artikel, diurutkan berdasarkan tanggal terbaru.
     * @return array Array data feedback atau array kosong jika tidak ada/error.
     */
    public static function getAllFeedbacksForAdmin()
    {
        error_log("FeedbackController::getAllFeedbacksForAdmin() dipanggil.");
        if (!class_exists('Feedback') || !method_exists('Feedback', 'getAll')) {
            error_log("FeedbackController::getAllFeedbacksForAdmin() - Model Feedback atau metode Feedback::getAll tidak ditemukan.");
            // Bisa juga set flash message jika ini dipanggil dari konteks yang bisa menampilkannya
            // if (function_exists('set_flash_message')) set_flash_message('danger', 'Kesalahan sistem: Komponen feedback tidak siap (FC02).');
            return [];
        }

        $data = Feedback::getAll(); // Asumsi Feedback::getAll() mengembalikan array dan menangani error internalnya

        // Logging untuk debugging
        if ($data === false || $data === null) { // Jika model mengembalikan false/null pada error
            error_log("FeedbackController::getAllFeedbacksForAdmin() - Feedback::getAll() mengembalikan nilai yang tidak diharapkan atau error.");
            $model_error = method_exists('Feedback', 'getLastError') ? Feedback::getLastError() : 'Tidak diketahui';
            error_log("FeedbackController::getAllFeedbacksForAdmin() - Model Error: " . $model_error);
            return [];
        }

        error_log("FeedbackController::getAllFeedbacksForAdmin() - Data dari Model (jumlah: " . count($data) . "): " . mb_substr(print_r($data, true), 0, 500));
        return $data; // $data sudah pasti array jika Feedback::getAll() konsisten
    }

    /**
     * Menghapus feedback berdasarkan ID dari sisi admin.
     * @param int $id ID Feedback.
     * @return bool True jika berhasil, false jika gagal. Flash message akan diatur.
     */
    public static function deleteFeedback($id)
    {
        error_log("FeedbackController::deleteFeedback() dipanggil dengan ID: " . $id);

        if (!class_exists('Feedback') || !method_exists('Feedback', 'delete')) {
            error_log("FeedbackController::deleteFeedback() - Model Feedback atau metode Feedback::delete tidak ditemukan.");
            if (function_exists('set_flash_message')) set_flash_message('danger', 'Kesalahan sistem: Komponen untuk menghapus feedback tidak tersedia (FC03).');
            return false;
        }

        $feedback_id_to_delete = filter_var($id, FILTER_VALIDATE_INT);
        if ($feedback_id_to_delete === false || $feedback_id_to_delete <= 0) {
            error_log("FeedbackController::deleteFeedback() - ID feedback tidak valid: " . print_r($id, true));
            if (function_exists('set_flash_message')) set_flash_message('danger', 'ID feedback tidak valid.');
            return false;
        }

        if (Feedback::delete($feedback_id_to_delete)) {
            if (function_exists('set_flash_message')) set_flash_message('success', 'Feedback berhasil dihapus.');
            return true;
        } else {
            $model_error = method_exists('Feedback', 'getLastError') ? Feedback::getLastError() : 'Operasi database gagal.';
            error_log("FeedbackController::deleteFeedback() - Gagal menghapus feedback ID {$feedback_id_to_delete}. Error: " . $model_error);
            if (function_exists('set_flash_message')) set_flash_message('danger', 'Gagal menghapus feedback: ' . $model_error);
            return false;
        }
    }

    // Anda bisa menambahkan metode lain di sini sesuai kebutuhan, misalnya:
    // - getFeedbackByIdForAdmin($id) untuk melihat detail satu feedback
    // - updateFeedbackStatus($id, $status) jika ada sistem moderasi feedback
}
