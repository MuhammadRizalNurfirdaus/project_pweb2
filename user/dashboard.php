<?php
// user/dashboard.php

session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'user') {
    header('Location: ' . BASE_URL . 'auth/login.php');
    exit();
}

$title = 'User Dashboard';
include __DIR__ . '/../template/header.php';
?>

<h2>Selamat datang, User!</h2>
<p>Ini adalah dashboard pengguna di Cilengkrang Wisata.</p>

<?php include __DIR__ . '/../template/footer.php'; ?>