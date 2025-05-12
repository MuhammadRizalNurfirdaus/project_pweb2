<?php
// File: C:\xampp\htdocs\Cilengkrang-Web-Wisata\admin\jenis_tiket\proses_tambah_jenis_tiket.php

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../controllers/JenisTiketController.php';

require_admin(); // Pastikan admin sudah login

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Ambil data dari form
    $nama_layanan = input('nama_layanan_display');
    $tipe_hari = input('tipe_hari');
    $harga_input = input('harga'); // Ambil sebagai string dulu, akan divalidasi sebagai int
    $deskripsi = input('deskripsi');
    $wisata_id_input = input('wisata_id'); // Opsional
    $aktif = isset($_POST['aktif']) ? 1 : 0; // Checkbox: jika dicentang, nilainya 1, jika tidak, hidden input akan mengirim 0

    // Data untuk dikirim ke controller
    $data_jenis_tiket = [
        'nama_layanan_display' => $nama_layanan,
        'tipe_hari' => $tipe_hari,
        'harga' => $harga_input, // Controller akan memvalidasi dan mengkonversi ke int
        'deskripsi' => $deskripsi,
        'aktif' => $aktif,
        'wisata_id' => !empty($wisata_id_input) ? (int)$wisata_id_input : null
    ];

    // Simpan data ke session untuk repopulasi jika ada error redirect dari Controller
    $_SESSION['form_data_jenis_tiket'] = [
        'nama_layanan_display' => $nama_layanan,
        'tipe_hari' => $tipe_hari,
        'harga' => $harga_input,
        'deskripsi' => $deskripsi,
        'aktif' => $aktif,
        'wisata_id' => $wisata_id_input
    ];

    $new_id = JenisTiketController::create($data_jenis_tiket);

    if ($new_id) {
        unset($_SESSION['form_data_jenis_tiket']); // Hapus data form dari session jika berhasil
        set_flash_message('success', 'Jenis tiket "' . e($nama_layanan) . ' - ' . e($tipe_hari) . '" berhasil ditambahkan dengan ID: ' . $new_id . '.');
        redirect('admin/jenis_tiket/kelola_jenis_tiket.php');
    } else {
        // Pesan error sudah di-set oleh Controller jika validasi gagal di sana,
        // atau jika Model gagal menyimpan.
        // Jika tidak ada flash message (error dari Model tanpa set flash di Controller), set pesan umum.
        if (!isset($_SESSION['flash_message'])) {
            set_flash_message('danger', 'Gagal menambahkan jenis tiket. Silakan periksa kembali data yang Anda masukkan.');
        }
        redirect('admin/jenis_tiket/tambah_jenis_tiket.php'); // Kembali ke form tambah
    }
} else {
    // Jika bukan POST, redirect
    set_flash_message('danger', 'Akses tidak sah.');
    redirect('admin/jenis_tiket/tambah_jenis_tiket.php');
}
