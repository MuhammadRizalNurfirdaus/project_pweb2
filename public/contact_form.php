<?php
// File: C:\xampp\htdocs\Cilengkrang-Web-Wisata\public\contact_form.php
// INI ADALAH SCRIPT HANDLER (PEMROSES DATA) UNTUK FORMULIR KONTAK.
// Form HTML dari halaman kontak.php (di root) akan mengirimkan data ke file ini.

require_once __DIR__ . '/../config/config.php'; // Path ke config.php
require_once __DIR__ . '/../controllers/ContactController.php'; // Path ke ContactController.php

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama_input = input('nama'); // Menggunakan helper input()
    $email_input = input('email');
    $pesan_input = input('pesan');
    // $subjek_input = input('subjek'); // Jika Anda memiliki field subjek di form

    // Validasi (Controller juga bisa melakukan validasi lebih lanjut jika diperlukan)
    if (empty($nama_input) || empty($email_input) || empty($pesan_input)) {
        set_flash_message('danger', 'Nama, Email, dan Pesan wajib diisi.');
        // Redirect kembali ke halaman formulir kontak (kontak.php di root)
        redirect($base_url . 'kontak.php#form-kontak-section');
    } elseif (!filter_var($email_input, FILTER_VALIDATE_EMAIL)) {
        set_flash_message('danger', 'Format email tidak valid.');
        redirect($base_url . 'kontak.php#form-kontak-section');
    } else {
        $data_kontak = [
            'nama' => $nama_input,
            'email' => $email_input,
            'pesan' => $pesan_input
            // 'subjek' => $subjek_input // Tambahkan jika ada
        ];

        // Memanggil method create dari ContactController
        if (ContactController::create($data_kontak)) {
            set_flash_message('success', 'Pesan Anda telah berhasil dikirim! Terima kasih telah menghubungi kami.');
            // Redirect kembali ke halaman kontak dengan pesan sukses
            redirect($base_url . 'kontak.php?status=sukses#form-kontak-section');
        } else {
            // Simpan input ke session agar bisa di-repopulate di form jika perlu
            $_SESSION['form_data_kontak'] = ['nama' => $nama_input, 'email' => $email_input, 'pesan' => $pesan_input];
            set_flash_message('danger', 'Maaf, terjadi kesalahan saat mengirim pesan. Silakan coba lagi.');
            redirect($base_url . 'kontak.php#form-kontak-section');
        }
    }
} else {
    // Jika file ini diakses langsung tanpa metode POST, redirect ke halaman kontak atau beranda
    set_flash_message('info', 'Silakan gunakan formulir kontak untuk mengirim pesan.');
    redirect($base_url . 'kontak.php'); // Arahkan ke halaman yang menampilkan form kontak
}
