<?php
session_start();
require_once '../config/Koneksi.php';
$conn = Koneksi::getInstance();

$user = trim($_POST['user']);
$pass = $_POST['password'];

// Cek admin
$stmt = $conn->prepare("SELECT * FROM admin WHERE username=?");
$stmt->bind_param("s", $user);
$stmt->execute();
$res = $stmt->get_result();
if ($row = $res->fetch_assoc()) {
    if (password_verify($pass, $row['password'])) {
        $_SESSION['role'] = 'admin';
        $_SESSION['nama'] = $row['nama_lengkap'];
        header('Location: ../admin/dashboard.php');
        exit;
    }
}

// Cek user
$stmt = $conn->prepare("SELECT * FROM user WHERE email=?");
$stmt->bind_param("s", $user);
$stmt->execute();
$res = $stmt->get_result();
if ($row = $res->fetch_assoc()) {
    if (password_verify($pass, $row['password'])) {
        $_SESSION['role'] = 'user';
        $_SESSION['nama'] = $row['nama_lengkap'];
        $_SESSION['user_id'] = $row['id'];
        header('Location: ../user/dashboard.php');
        exit;
    }
}

echo 'Login gagal. <a href="login.php">Coba lagi</a>';
