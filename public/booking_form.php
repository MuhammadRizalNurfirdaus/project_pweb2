<?php
// File: C:\xampp\htdocs\Cilengkrang-Web-Wisata\public\booking_form.php
// Ini adalah HANDLER untuk form booking publik.
// Diasumsikan form HTML-nya ada di halaman lain dan action-nya ke file ini.

require_once __DIR__ . '/../config/config.php'; // Path ke config.php
require_once __DIR__ . '/../models/Booking.php';   // Path ke Booking.php (Model)

if (is_post()) {
    // Ambil data dari POST
    // Sesuaikan nama field ini dengan nama input di form HTML Anda
    $nama_lengkap = input('nama_lengkap');
    $email = input('email');
    $no_hp = input('no_hp');
    $nama_wisata = input('nama_wisata');
    $tanggal_kunjungan = input('tanggal_kunjungan');
    $jumlah_orang = (int)input('jumlah_orang');
    $catatan = input('catatan', ''); // Catatan opsional

    // Validasi dasar (tambahkan validasi lebih detail jika perlu)
    if (empty($nama_lengkap) || empty($email) || empty($no_hp) || empty($nama_wisata) || empty($tanggal_kunjungan) || $jumlah_orang < 1) {
        set_flash_message('danger', 'Semua field yang wajib (Nama, Email, No HP, Destinasi, Tanggal, Jumlah Orang) harus diisi dengan benar.');
        // Redirect kembali ke halaman form booking publik (ganti 'halaman_form_booking_publik.php' dengan path yang benar)
        redirect('halaman_form_booking_publik.php');
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        set_flash_message('danger', 'Format email tidak valid.');
        redirect('halaman_form_booking_publik.php');
    } else {
        $data_booking = [
            'user_id' => null, // Untuk booking tamu, user_id bisa null atau Anda punya logika khusus
            'nama_lengkap_tamu' => $nama_lengkap, // Kolom tambahan jika user_id null
            'email_tamu' => $email,             // Kolom tambahan
            'no_hp_tamu' => $no_hp,             // Kolom tambahan
            'nama_wisata' => $nama_wisata,
            'tanggal_kunjungan' => $tanggal_kunjungan,
            'jumlah_orang' => $jumlah_orang,
            'status' => 'pending', // Status awal booking
            'catatan' => $catatan  // Jika model dan tabel booking Anda mendukung kolom ini
        ];

        // PERHATIKAN: Model Booking::create() saat ini mengharapkan 'user_id'.
        // Anda perlu menyesuaikan Model Booking.php dan tabel 'bookings' jika ingin mendukung
        // booking oleh tamu tanpa login (misalnya dengan menambahkan kolom nama_lengkap_tamu, email_tamu, no_hp_tamu).
        // Untuk contoh ini, saya asumsikan Anda akan menyesuaikan model/tabel.
        // Atau, Anda bisa membuat method terpisah di model khusus untuk booking tamu.

        $booking_id = Booking::create($data_booking); // Panggil method static dari Model

        if ($booking_id) {
            set_flash_message('success', 'Pemesanan Anda berhasil dikirim! Kode booking Anda: BOOK-' . $booking_id . '. Kami akan segera menghubungi Anda untuk konfirmasi.');
            // Redirect ke halaman sukses atau kembali ke halaman utama
            redirect('halaman_sukses_booking.php?kode=BOOK-' . $booking_id); // Buat halaman sukses ini
        } else {
            set_flash_message('danger', 'Maaf, terjadi kesalahan saat mengirim pemesanan. Silakan coba lagi.');
            redirect('halaman_form_booking_publik.php');
        }
    }
} else {
    // Jika bukan POST, redirect ke halaman utama atau halaman form
    redirect(''); // Ke halaman utama
}
