<?php
// File: C:\xampp\htdocs\Cilengkrang-Web-Wisata\admin\dashboard.php

// 1. Selalu sertakan config.php pertama kali
// Path dari admin/dashboard.php ke config/ adalah ../config/
require_once __DIR__ . '/../config/config.php'; // Menyediakan $base_url, helpers, session, $conn

// 2. Sertakan header admin
// header_admin.php akan memanggil require_admin() untuk proteksi halaman
// Path dari admin/dashboard.php ke admin/template/ adalah template/
include_once __DIR__ . '/template/header_admin.php';

// Di sini Anda bisa mengambil data ringkasan dari berbagai model jika diperlukan
// Contoh:
// require_once __DIR__ . '/../models/Artikel.php';
// require_once __DIR__ . '/../models/Booking.php';
// $artikel_model = new Artikel($conn);
// $total_artikel = count($artikel_model->getAll()); // Contoh sederhana
// $total_booking_pending = Booking::countByStatus('pending'); // Contoh method yang mungkin Anda buat di model
?>

<!-- Konten Dashboard Admin Dimulai -->
<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800">Dashboard Ringkasan</h1>
    <a href="#" class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm">
        <i class="fas fa-download fa-sm text-white-50"></i> Unduh Laporan
    </a>
</div>

<!-- Baris Kartu Statistik -->
<div class="row">
    <!-- Card Contoh: Total Artikel -->
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-start-primary shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                            Total Artikel</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                            <?php
                            // Contoh mengambil data (Anda perlu implementasi logic ini di model)
                            // echo isset($total_artikel) ? $total_artikel : 'N/A';
                            echo '78'; // Placeholder
                            ?>
                        </div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-newspaper fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Card Contoh: Booking Pending -->
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-start-success shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                            Booking Pending</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                            <?php
                            // echo isset($total_booking_pending) ? $total_booking_pending : 'N/A';
                            echo '12'; // Placeholder
                            ?>
                        </div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-calendar-alt fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Card Contoh: Feedback Baru -->
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-start-info shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Feedback Baru
                        </div>
                        <div class="row no-gutters align-items-center">
                            <div class="col-auto">
                                <div class="h5 mb-0 me-3 font-weight-bold text-gray-800">5</div> <!-- Placeholder -->
                            </div>
                            <!-- <div class="col">
                                <div class="progress progress-sm mr-2">
                                    <div class="progress-bar bg-info" role="progressbar" style="width: 50%" aria-valuenow="50" aria-valuemin="0" aria-valuemax="100"></div>
                                </div>
                            </div> -->
                        </div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-comments fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Card Contoh: Pengguna Terdaftar -->
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-start-warning shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                            Pengguna Terdaftar</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">250</div> <!-- Placeholder -->
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-users fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Contoh Bagian Lain -->
<div class="row">
    <div class="col-lg-6 mb-4">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Aktivitas Terkini</h6>
            </div>
            <div class="card-body">
                <p>Belum ada aktivitas terkini untuk ditampilkan.</p>
                <!-- Daftar aktivitas akan muncul di sini -->
            </div>
        </div>
    </div>
    <div class="col-lg-6 mb-4">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Tautan Cepat</h6>
            </div>
            <div class="card-body">
                <a href="<?= $base_url ?>admin/artikel/tambah_artikel.php" class="btn btn-info btn-sm mb-2 d-block">Tambah Artikel Baru</a>
                <a href="<?= $base_url ?>admin/booking/kelola_booking.php" class="btn btn-info btn-sm mb-2 d-block">Lihat Semua Booking</a>
                <a href="<?= $base_url ?>admin/users/kelola_users.php" class="btn btn-info btn-sm d-block">Manajemen Pengguna</a>
            </div>
        </div>
    </div>
</div>

<!-- Konten Dashboard Admin Selesai -->

<?php
// 3. Sertakan footer admin
include_once __DIR__ . '/template/footer_admin.php';
?>