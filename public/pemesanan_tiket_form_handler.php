<?php
// File: C:\xampp\htdocs\Cilengkrang-Web-Wisata\public\pemesanan_tiket_form_handler.php

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../models/PemesananTiket.php'; // Menggunakan model PemesananTiket
require_once __DIR__ . '/../models/DetailPemesananTiket.php'; // Menggunakan model DetailPemesananTiket
require_once __DIR__ . '/../models/JenisTiket.php'; // Menggunakan model JenisTiket
require_once __DIR__ . '/../models/SewaAlat.php'; // Menggunakan model SewaAlat
require_once __DIR__ . '/../models/JadwalKetersediaanTiket.php'; // Menggunakan model JadwalKetersediaan
require_once __DIR__ . '/../models/Pembayaran.php'; // Menggunakan model Pembayaran
require_once __DIR__ . '/../models/User.php'; // Menggunakan model User

if (is_post()) {
    // Ambil data dari POST
    $nama_lengkap = input('nama_lengkap');
    $email = input('email');
    $no_hp = input('no_hp');
    $nama_destinasi = input('nama_destinasi'); // Sesuai dengan form dan tabel
    $tanggal_kunjungan = input('tanggal_kunjungan');
    $jumlah_item = (int)input('jumlah_item'); // Sesuai dengan form dan tabel (jumlah tiket/orang)
    // Catatan tidak ada di tabel pemesanan_tiket

    // Validasi dasar
    if (empty($nama_lengkap) || empty($email) || empty($no_hp) || empty($nama_destinasi) || empty($tanggal_kunjungan) || $jumlah_item < 1) {
        set_flash_message('danger', 'Semua field yang wajib (Nama, Email, No HP, Destinasi, Tanggal, Jumlah Item) harus diisi dengan benar.');
        // Ganti 'nama_file_form_pemesanan_publik.php' dengan path yang benar ke file HTML form Anda
        redirect('nama_file_form_pemesanan_publik.php'); // Arahkan kembali ke form
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        set_flash_message('danger', 'Format email tidak valid.');
        redirect('nama_file_form_pemesanan_publik.php'); // Arahkan kembali ke form
    } else {
        // Data untuk dimasukkan ke tabel pemesanan_tiket
        // Informasi tamu (nama_lengkap, email, no_hp) tidak disimpan langsung ke tabel ini
        // karena tidak ada kolomnya. Anda bisa menggunakannya untuk notifikasi atau logging.
        $data_pemesanan = [
            'user_id' => null, // Untuk pemesanan tamu, user_id adalah null
            'nama_destinasi' => $nama_destinasi,
            'tanggal_kunjungan' => $tanggal_kunjungan,
            'jumlah_item' => $jumlah_item,
            'nama_item' => 'Tiket Masuk Publik: ' . $nama_destinasi . ' (Tamu: ' . $nama_lengkap . ')', // Menyertakan info tamu di nama_item
            'jenis_pemesanan' => 'Publik Online', // Jenis pemesanan untuk tamu
            'status' => 'pending' // Status awal pemesanan
            // 'total_harga' idealnya dihitung di Model berdasarkan destinasi dan jumlah item
        ];

        $pemesanan_id = PemesananTiket::create($data_pemesanan); // Panggil method static dari Model PemesananTiket

        if ($pemesanan_id) {
            // Anda bisa menggunakan $nama_lengkap, $email, $no_hp di sini untuk mengirim email konfirmasi
            set_flash_message('success', 'Pemesanan tiket Anda berhasil dikirim! ID Pemesanan Anda: PT-' . $pemesanan_id . '. Kami akan segera menghubungi Anda melalui email/No. HP yang diberikan untuk konfirmasi lebih lanjut.');
            // Ganti 'nama_halaman_sukses_pemesanan.php' dengan path yang benar
            redirect('nama_halaman_sukses_pemesanan.php?id_pemesanan=PT-' . $pemesanan_id);
        } else {
            set_flash_message('danger', 'Maaf, terjadi kesalahan saat mengirim pemesanan tiket. Silakan coba lagi.');
            redirect('nama_file_form_pemesanan_publik.php'); // Arahkan kembali ke form
        }
    }
} else {
    // Jika bukan POST, redirect ke halaman utama atau halaman form pemesanan
    redirect(''); // Ke halaman utama
}
