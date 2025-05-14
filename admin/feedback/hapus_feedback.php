<?php
// File: C:\xampp\htdocs\Cilengkrang-Web-Wisata\admin\feedback\hapus_feedback.php

if (!require_once __DIR__ . '/../../config/config.php') {
    http_response_code(503);
    error_log("FATAL: Gagal memuat config.php dari admin/feedback/hapus_feedback.php");
    exit("Kesalahan konfigurasi server. Penghapusan tidak dapat diproses.");
}

try {
    require_admin();
} catch (Exception $e) {
    set_flash_message('danger', 'Akses ditolak. Anda harus login sebagai admin.');
    redirect(AUTH_URL . '/login.php');
    exit;
}

if (!is_post()) {
    set_flash_message('warning', 'Metode request tidak valid untuk menghapus feedback.');
    redirect(ADMIN_URL . '/feedback/kelola_feedback.php');
    exit;
}

if (!verify_csrf_token()) {
    set_flash_message('danger', 'Permintaan tidak valid atau sesi telah berakhir (CSRF). Silakan coba lagi.');
    unset($_SESSION['csrf_token']);
    redirect(ADMIN_URL . '/feedback/kelola_feedback.php');
    exit;
}
unset($_SESSION['csrf_token']);

$feedback_id = null;
if (isset($_POST['feedback_id']) && isset($_POST['hapus_feedback_submit'])) {
    $feedback_id = filter_var($_POST['feedback_id'], FILTER_VALIDATE_INT);
}

if (!$feedback_id || $feedback_id <= 0) {
    set_flash_message('danger', 'ID Feedback tidak valid atau tidak disertakan untuk dihapus.');
    redirect(ADMIN_URL . '/feedback/kelola_feedback.php');
    exit;
}

if (class_exists('FeedbackController') && method_exists('FeedbackController', 'deleteFeedback')) {
    FeedbackController::deleteFeedback($feedback_id);
} else {
    set_flash_message('danger', 'Kesalahan sistem: Komponen untuk menghapus feedback tidak ditemukan.');
    error_log("FATAL: FeedbackController atau method deleteFeedback tidak ditemukan saat mencoba hapus ID: " . $feedback_id);
}

redirect(ADMIN_URL . '/feedback/kelola_feedback.php');
exit;
