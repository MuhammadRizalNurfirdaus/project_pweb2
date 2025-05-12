<?php
// File: C:\xampp\htdocs\Cilengkrang-Web-Wisata\admin\jenis_tiket\proses_edit_jenis_tiket.php

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../controllers/JenisTiketController.php';

require_admin(); // Pastikan admin sudah login

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validasi ID dari POST
    if (!isset($_POST['id']) || !filter_var($_POST['id'], FILTER_VALIDATE_INT) || (int)$_POST['id'] <= 0) {
        set_flash_message('danger', 'ID Jenis Tiket tidak valid untuk pembaruan.');
        redirect('admin/jenis_tiket/kelola_jenis_tiket.php');
    }
    $id_jenis_tiket = (int)$_POST['id'];

    // Ambil data dari form
    $nama_layanan = input('nama_layanan_display');
    $tipe_hari = input('tipe_hari');
    $harga_input = input('harga');
    $deskripsi = input('deskripsi');
    $wisata_id_input = input('wisata_id');
    $aktif = isset($_POST['aktif']) ? 1 : 0;

    // Data untuk dikirim ke controller
    $data_jenis_tiket = [
        'id' => $id_jenis_tiket, // Sertakan ID untuk update
        'nama_layanan_display' => $nama_layanan,
        'tipe_hari' => $tipe_hari,
        'harga' => $harga_input, // Controller akan memvalidasi dan mengkonversi ke int
        'deskripsi' => $deskripsi,
        'aktif' => $aktif,
        'wisata_id' => !empty($wisata_id_input) ? (int)$wisata_id_input : null
    ];

    // Simpan input ke session untuk repopulasi jika ada error redirect dari Controller
    $_SESSION['form_data_jenis_tiket'] = $data_jenis_tiket; // Simpan semua data termasuk ID

    if (JenisTiketController::update($data_jenis_tiket)) {
        unset($_SESSION['form_data_jenis_tiket']); // Hapus data form dari session jika berhasil
        set_flash_message('success', 'Jenis tiket "' . e($nama_layanan) . ' - ' . e($tipe_hari) . '" berhasil diperbarui.');
        redirect('admin/jenis_tiket/kelola_jenis_tiket.php');
    } else {
        // Pesan error sudah di-set oleh Controller jika validasi gagal di sana,
        // atau jika Model gagal menyimpan.
        if (!isset($_SESSION['flash_message'])) {
            set_flash_message('danger', 'Gagal memperbarui jenis tiket. Silakan periksa kembali data yang Anda masukkan.');
        }
        // Redirect kembali ke form edit dengan ID yang benar
        redirect('admin/jenis_tiket/edit_jenis_tiket.php?id=' . $id_jenis_tiket);
    }
} else {
    // Jika bukan POST, redirect
    set_flash_message('danger', 'Akses tidak sah.');
    redirect('admin/jenis_tiket/kelola_jenis_tiket.php');
}
