<?php
// admin/dashboard.php

session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: ' . BASE_URL . 'auth/login.php');
    exit();
}

$title = 'Admin Dashboard';
include __DIR__ . '/../template/header.php';
?>

<h2>Selamat datang, Admin!</h2>
<p>Ini adalah dashboard admin di Cilengkrang Wisata.</p>

<?php include __DIR__ . '/../template/footer.php'; ?>