<?php
// File: C:\xampp\htdocs\Cilengkrang-Web-Wisata\controllers\ContactController.php

require_once __DIR__ . '/../config/config.php'; // Memuat konfigurasi dasar
require_once __DIR__ . '/../models/Contact.php';   // Memuat Model Contact

class ContactController
{
    /**
     * Memproses pembuatan pesan kontak baru.
     * Menerima data dari handler form, melakukan validasi tambahan jika perlu,
     * dan memanggil Model untuk menyimpan data.
     * @param array $data Array asosiatif dengan kunci ['nama', 'email', 'pesan'].
     * @return int|false ID pesan kontak baru jika berhasil, false jika gagal.
     */
    public static function create($data)
    {
        // Validasi dasar bisa juga ada di sini sebagai lapisan tambahan,
        // meskipun Model sudah memiliki validasi.
        if (empty($data['nama']) || empty($data['email']) || empty($data['pesan'])) {
            // Pesan flash biasanya di-set oleh script pemanggil (handler form)
            error_log("ContactController::create() - Error: Data input tidak lengkap.");
            return false;
        }
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            error_log("ContactController::create() - Error: Format email tidak valid.");
            return false;
        }

        // Data yang akan dikirim ke Model
        $data_to_save = [
            'nama' => $data['nama'],
            'email' => $data['email'],
            'pesan' => $data['pesan']
        ];

        $new_contact_id = Contact::create($data_to_save);

        if ($new_contact_id) {
            // Di sini Anda bisa menambahkan logika lain, misalnya mengirim notifikasi email ke admin
            // send_admin_notification_new_contact($new_contact_id, $data_to_save);
            return $new_contact_id;
        } else {
            // Error sudah di-log oleh Model
            return false;
        }
    }

    /**
     * Mengambil semua pesan kontak.
     * @return array Array data pesan kontak, atau array kosong jika tidak ada/error.
     */
    public static function getAll()
    {
        return Contact::getAll(); // Mendelegasikan ke Model
    }

    /**
     * Memproses penghapusan pesan kontak.
     * @param int $id ID pesan kontak yang akan dihapus.
     * @return bool True jika berhasil, false jika gagal.
     */
    public static function delete($id)
    {
        $id_val = filter_var($id, FILTER_VALIDATE_INT);
        if ($id_val === false || $id_val <= 0) {
            error_log("ContactController::delete() - Error: ID tidak valid (" . $id . ").");
            return false;
        }
        return Contact::delete($id_val); // Mendelegasikan ke Model
    }
}
