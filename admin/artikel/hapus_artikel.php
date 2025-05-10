<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../controllers/ArtikelController.php';
// Asumsikan header_admin.php atau config.php sudah melakukan session check untuk admin

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['flash_message'] = ['type' => 'danger', 'message' => 'Permintaan tidak valid.'];
    header('Location: ' . $base_url . 'admin/artikel/kelola_artikel.php');
    exit();
}

// Bisa tambahkan CSRF token check di sini jika ada form untuk delete
// if (!isset($_POST['csrf_token']) || !verifyCsrfToken($_POST['csrf_token'])) {
//     die('CSRF token validation failed.');
// }

$id = (int)$_GET['id'];

if (ArtikelController::delete($id)) {
    $_SESSION['flash_message'] = ['type' => 'success', 'message' => 'Artikel berhasil dihapus!'];
} else {
    $_SESSION['flash_message'] = ['type' => 'danger', 'message' => 'Gagal menghapus artikel.'];
}

header('Location: ' . $base_url . 'admin/artikel/kelola_artikel.php');
exit();
