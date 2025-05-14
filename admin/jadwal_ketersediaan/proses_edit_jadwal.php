<?php
// File: C:\xampp\htdocs\Cilengkrang-Web-Wisata\admin\jadwal_ketersediaan\proses_edit_jadwal.php

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../controllers/JadwalKetersediaanTiketController.php';

require_admin(); // Pastikan admin sudah login

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validasi ID dari POST
    if (!isset($_POST['id']) || !filter_var($_POST['id'], FILTER_VALIDATE_INT) || (int)$_POST['id'] <= 0) {
        set_flash_message('danger', 'ID Jadwal Ketersediaan tidak valid untuk pembaruan.');
        redirect('admin/jadwal_ketersediaan/kelola_jadwal.php');
    }
    $id_jadwal = (int)$_POST['id'];

    // Ambil data dari form
    $jenis_tiket_id = input('jenis_tiket_id');
    $tanggal = input('tanggal');
    $jumlah_total_tersedia = input('jumlah_total_tersedia');
    $jumlah_saat_ini_tersedia = input('jumlah_saat_ini_tersedia');
    $aktif = isset($_POST['aktif']) ? 1 : 0; // 1 jika checkbox 'aktif' dicentang, 0 jika tidak

    // Data untuk dikirim ke controller
    $data_jadwal = [
        'id' => $id_jadwal, // Sertakan ID untuk operasi update
        'jenis_tiket_id' => $jenis_tiket_id,
        'tanggal' => $tanggal,
        'jumlah_total_tersedia' => $jumlah_total_tersedia,
        'jumlah_saat_ini_tersedia' => $jumlah_saat_ini_tersedia,
        'aktif' => $aktif
    ];

    // Simpan data ke session untuk repopulasi jika ada error redirect dari Controller
    $_SESSION['form_data_jadwal_edit'] = $data_jadwal; // Gunakan key session yang berbeda untuk edit

    if (JadwalKetersediaanTiketController::update($data_jadwal)) {
        unset($_SESSION['form_data_jadwal_edit']); // Hapus data form dari session jika berhasil
        // Ambil nama jenis tiket untuk pesan flash yang lebih informatif
        require_once __DIR__ . '/../../models/JenisTiket.php';
        $jenisTiketInfo = JenisTiket::findById((int)$jenis_tiket_id);
        $namaJenisTiketDisplay = $jenisTiketInfo ? (e($jenisTiketInfo['nama_layanan_display']) . ' - ' . e($jenisTiketInfo['tipe_hari'])) : "ID Jenis Tiket: " . e($jenis_tiket_id);

        set_flash_message('success', 'Jadwal ketersediaan untuk "' . $namaJenisTiketDisplay . '" pada tanggal ' . e($tanggal) . ' berhasil diperbarui.');
        redirect('admin/jadwal_ketersediaan/kelola_jadwal.php');
    } else {
        // Pesan error seharusnya sudah di-set oleh Controller (misalnya karena validasi gagal atau duplikasi)
        if (!isset($_SESSION['flash_message'])) { // Fallback jika Controller tidak set pesan
            set_flash_message('danger', 'Gagal memperbarui jadwal ketersediaan. Periksa kembali data input atau log server.');
        }
        // Redirect kembali ke form edit dengan ID yang benar
        redirect('admin/jadwal_ketersediaan/edit_jadwal.php?id=' . $id_jadwal);
    }
} else {
    // Jika bukan POST, redirect
    set_flash_message('danger', 'Akses tidak sah.');
    redirect('admin/jadwal_ketersediaan/kelola_jadwal.php');
}
