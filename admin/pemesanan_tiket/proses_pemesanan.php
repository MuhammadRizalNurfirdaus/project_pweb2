<?php
// File: C:\xampp\htdocs\Cilengkrang-Web-Wisata\admin\pemesanan_tiket\proses_pemesanan.php

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../controllers/PemesananTiketController.php';
// require_once __DIR__ . '/../../models/Wisata.php'; // Diperlukan jika ingin menghitung harga dari sini

// require_admin(); // Pastikan hanya admin yang bisa akses

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Ambil data dari POST. Nama field di form admin mungkin berbeda, sesuaikan.
    $user_id = isset($_POST['user_id']) && !empty($_POST['user_id']) ? (int)$_POST['user_id'] : null;

    // Jika user_id tidak ada (untuk tamu), ambil info tamu
    $nama_tamu = null;
    if (is_null($user_id)) {
        $nama_tamu = isset($_POST['nama_tamu']) ? trim($_POST['nama_tamu']) : null;
        // Anda bisa menambahkan validasi untuk email_tamu, no_hp_tamu jika form admin menyediakannya untuk tamu
    }

    $nama_destinasi = isset($_POST['nama_destinasi']) ? trim($_POST['nama_destinasi']) : '';
    $jenis_pemesanan = isset($_POST['jenis_pemesanan']) ? trim($_POST['jenis_pemesanan']) : 'Manual Admin'; // Default jika admin yang input
    $nama_item = isset($_POST['nama_item']) ? trim($_POST['nama_item']) : 'Tiket Masuk ' . $nama_destinasi;
    $jumlah_item = isset($_POST['jumlah_item']) ? intval($_POST['jumlah_item']) : 0;
    $tanggal_kunjungan = isset($_POST['tanggal_kunjungan']) ? trim($_POST['tanggal_kunjungan']) : '';
    $total_harga_input = isset($_POST['total_harga']) ? (float)str_replace(['Rp ', '.', ','], ['', '', '.'], $_POST['total_harga']) : null; // Menghilangkan format Rupiah jika ada
    $status = isset($_POST['status']) ? trim($_POST['status']) : 'pending';

    // Validasi dasar
    $errors = [];
    if (empty($nama_destinasi)) {
        $errors[] = "Nama destinasi wajib diisi.";
    }
    if ($jumlah_item <= 0) {
        $errors[] = "Jumlah item/tiket harus lebih dari 0.";
    }
    if (empty($tanggal_kunjungan)) {
        $errors[] = "Tanggal kunjungan wajib diisi.";
    }
    // Jika input total_harga ada, validasi. Jika tidak, perhitungan otomatis diharapkan (lihat bawah).
    if (!is_null($total_harga_input) && $total_harga_input < 0) {
        $errors[] = "Total harga tidak boleh negatif.";
    } elseif (is_null($total_harga_input)) {
        // PERHITUNGAN TOTAL HARGA OTOMATIS (CONTOH SEDERHANA - IDEALNYA DI MODEL/SERVICE)
        // Anda perlu logika untuk mengambil harga tiket dari tabel 'wisata' berdasarkan $nama_destinasi
        // Misalnya, jika ada Model Wisata:
        // $wisata_detail = Wisata::getByNama($nama_destinasi); // Asumsi method ada
        // if ($wisata_detail && isset($wisata_detail['harga_tiket'])) {
        //     $total_harga_input = (float)$wisata_detail['harga_tiket'] * $jumlah_item;
        // } else {
        //     $errors[] = "Tidak dapat menentukan harga tiket untuk destinasi: " . e($nama_destinasi);
        // }
        // Untuk sementara, jika tidak ada input harga dan tidak ada perhitungan otomatis, set error
        // atau Anda bisa mewajibkan admin menginput total harga.
        $errors[] = "Total harga wajib diisi atau dihitung otomatis (fitur belum diimplementasikan sepenuhnya di sini).";
    }

    if (is_null($user_id) && empty($nama_tamu)) {
        $nama_item_final = $nama_item; // Jika tidak ada user_id dan tidak ada nama_tamu, gunakan nama_item default
    } elseif (is_null($user_id) && !empty($nama_tamu)) {
        $nama_item_final = 'Tiket Masuk: ' . $nama_destinasi . ' (Tamu Admin: ' . e($nama_tamu) . ')';
    } else {
        $nama_item_final = $nama_item; // Untuk pengguna terdaftar, nama_item sesuai input atau default
    }


    if (!empty($errors)) {
        foreach ($errors as $error) {
            set_flash_message('danger', $error);
        }
        // Redirect kembali ke form tambah pemesanan manual (Anda perlu membuat form ini)
        redirect('admin/pemesanan_tiket/tambah_pemesanan.php'); // Sesuaikan path jika nama form berbeda
    } else {
        $data_pemesanan = [
            'user_id' => $user_id,
            'nama_destinasi' => $nama_destinasi,
            'jenis_pemesanan' => $jenis_pemesanan,
            'nama_item' => $nama_item_final,
            'jumlah_item' => $jumlah_item,
            'tanggal_kunjungan' => $tanggal_kunjungan,
            'total_harga' => $total_harga_input, // Gunakan total_harga yang sudah divalidasi/dihitung
            'status' => $status
        ];

        $pemesanan_id = PemesananTiketController::create($data_pemesanan);

        if ($pemesanan_id) {
            set_flash_message('success', 'Pemesanan tiket baru (ID: ' . $pemesanan_id . ') berhasil ditambahkan oleh admin.');
            redirect('admin/pemesanan_tiket/kelola_pemesanan.php');
        } else {
            set_flash_message('danger', 'Gagal menambahkan pemesanan tiket baru. Silakan coba lagi.');
            redirect('admin/pemesanan_tiket/tambah_pemesanan.php'); // Kembali ke form tambah
        }
    }
} else {
    // Jika bukan POST, redirect ke halaman kelola pemesanan atau dashboard admin
    set_flash_message('info', 'Metode request tidak valid.');
    redirect('admin/pemesanan_tiket/kelola_pemesanan.php');
}
