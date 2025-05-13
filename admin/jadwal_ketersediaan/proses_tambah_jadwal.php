<?php
// File: C:\xampp\htdocs\Cilengkrang-Web-Wisata\admin\jadwal_ketersediaan\proses_tambah_jadwal.php

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../controllers/JadwalKetersediaanTiketController.php';

require_admin(); // Pastikan admin sudah login

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $jenis_tiket_id = input('jenis_tiket_id');
    $tanggal = input('tanggal');
    $jumlah_total_tersedia = input('jumlah_total_tersedia');
    $jumlah_saat_ini_tersedia = input('jumlah_saat_ini_tersedia', $jumlah_total_tersedia); // Default ke total jika tidak diisi
    $aktif = isset($_POST['aktif']) ? 1 : 0;

    $data_jadwal = [
        'jenis_tiket_id' => $jenis_tiket_id,
        'tanggal' => $tanggal,
        'jumlah_total_tersedia' => $jumlah_total_tersedia,
        'jumlah_saat_ini_tersedia' => ($jumlah_saat_ini_tersedia > $jumlah_total_tersedia && $jumlah_total_tersedia >= 0) ? $jumlah_total_tersedia : $jumlah_saat_ini_tersedia,
        'aktif' => $aktif
    ];

    $_SESSION['form_data_jadwal'] = $data_jadwal;

    $new_id = JadwalKetersediaanTiketController::create($data_jadwal);

    if ($new_id) {
        unset($_SESSION['form_data_jadwal']);
        require_once __DIR__ . '/../../models/JenisTiket.php'; // Diperlukan untuk getById
        $jenisTiketInfo = JenisTiket::findById((int)$jenis_tiket_id);
        $namaJenisTiketDisplay = $jenisTiketInfo ? (e($jenisTiketInfo['nama_layanan_display']) . ' - ' . e($jenisTiketInfo['tipe_hari'])) : "ID Jenis Tiket: " . e($jenis_tiket_id);

        set_flash_message('success', 'Jadwal ketersediaan untuk "' . $namaJenisTiketDisplay . '" pada tanggal ' . e($tanggal) . ' berhasil ditambahkan.');
        redirect('admin/jadwal_ketersediaan/kelola_jadwal.php');
    } else {
        if (!isset($_SESSION['flash_message'])) {
            set_flash_message('danger', 'Gagal menambahkan jadwal ketersediaan. Kemungkinan jadwal untuk jenis tiket dan tanggal tersebut sudah ada, atau periksa input Anda.');
        }
        redirect('admin/jadwal_ketersediaan/tambah_jadwal.php');
    }
} else {
    set_flash_message('danger', 'Akses tidak sah.');
    redirect('admin/jadwal_ketersediaan/tambah_jadwal.php');
}
