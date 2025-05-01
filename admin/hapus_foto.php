<?php
session_start();
if ($_SESSION['role'] != 'admin') header('Location: ../auth/login.php');
require_once '../config/Koneksi.php';
$conn = Koneksi::getInstance();

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $stmt = $conn->prepare("DELETE FROM galeri WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
}
header('Location: kelola_galeri.php');
exit();
