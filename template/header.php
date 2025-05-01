<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config/config.php';
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title><?php echo $title ?? 'Cilengkrang Wisata'; ?></title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>public/css/style.css">
</head>

<body>
    <header class="site-header">
        <div class="header-image"></div>
        <div class="header-overlay">
            <h1 class="site-title"><a href="<?php echo BASE_URL; ?>index.php">Cilengkrang Wisata</a></h1>
            <nav class="site-nav">
                <a href="<?php echo BASE_URL; ?>index.php">Home</a>
                <a href="<?php echo BASE_URL; ?>wisata/list.php">Daftar Wisata</a>
                <a href="<?php echo BASE_URL; ?>galeri.php">Galeri Foto</a>
                <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                    <a href="<?php echo BASE_URL; ?>admin/dashboard.php">Admin</a>
                    <a href="<?php echo BASE_URL; ?>auth/logout.php">Logout</a>
                <?php elseif (isset($_SESSION['role']) && $_SESSION['role'] === 'user'): ?>
                    <a href="<?php echo BASE_URL; ?>user/dashboard.php">Dashboard</a>
                    <a href="<?php echo BASE_URL; ?>auth/logout.php">Logout</a>
                <?php else: ?>
                    <a href="<?php echo BASE_URL; ?>auth/login.php">Login</a>
                    <a href="<?php echo BASE_URL; ?>auth/register.php">Register</a>
                <?php endif; ?>
            </nav>
        </div>
    </header>
    <main>