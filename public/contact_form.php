<?php
// File: C:\xampp\htdocs\Cilengkrang-Web-Wisata\public\contact_form.php
// Ini adalah HANDLER untuk form kontak publik.
// Diasumsikan form HTML-nya ada di halaman kontak (misalnya kontak.php) dan action-nya ke file ini.

require_once __DIR__ . '/../config/config.php';    // Path ke config.php
require_once __DIR__ . '/../models/Contact.php'; // Path ke Contact.php (Model)

if (is_post()) {
    $nama_input = input('nama');
    $email_input = input('email');
    $pesan_input = input('pesan');
    // Anda bisa menambahkan input subjek jika ada di form
    // $subjek_input = input('subjek');

    if (empty($nama_input) || empty($email_input) || empty($pesan_input)) {
        set_flash_message('danger', 'Nama, Email, dan Pesan wajib diisi.');
        // Redirect kembali ke halaman form kontak (ganti 'kontak.php' dengan path yang benar jika beda)
        redirect('kontak.php');
    } elseif (!filter_var($email_input, FILTER_VALIDATE_EMAIL)) {
        set_flash_message('danger', 'Format email tidak valid.');
        redirect('kontak.php');
    } else {
        $data_kontak = [
            'nama' => $nama_input,
            'email' => $email_input,
            'pesan' => $pesan_input
            // 'subjek' => $subjek_input // Jika ada
        ];
        if (Contact::create($data_kontak)) { // Panggil method static dari Model
            set_flash_message('success', 'Pesan Anda telah berhasil dikirim! Kami akan segera merespons.');
            redirect('kontak.php#form-kontak'); // Redirect kembali ke form kontak, mungkin dengan anchor ke form
        } else {
            set_flash_message('danger', 'Maaf, terjadi kesalahan saat mengirim pesan. Silakan coba lagi.');
            redirect('kontak.php#form-kontak');
        }
    }
} else {
    // Jika bukan POST, redirect ke halaman utama atau halaman form kontak
    redirect('kontak.php');
}
