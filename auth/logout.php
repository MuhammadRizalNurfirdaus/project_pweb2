<?php
// File: C:\xampp\htdocs\Cilengkrang-Web-Wisata\auth\logout.php
require_once __DIR__ . '/../config/config.php'; // Menyediakan $base_url, helpers, session
require_once __DIR__ . '/../controllers/AuthController.php'; // Controller untuk logika logout

AuthController::processLogout(); // Memanggil method static dari controller

set_flash_message('success', 'Anda telah berhasil logout.'); // Fungsi dari flash_message.php
redirect('auth/login.php'); // Fungsi dari helpers.php, akan menggunakan $base_url
