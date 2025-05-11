<?php
// File: C:\xampp\htdocs\Cilengkrang-Web-Wisata\controllers\PemesananTiketController.php

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../models/PemesananTiket.php';

class PemesananTiketController
{
    /**
     * Membuat pemesanan tiket baru.
     * @param array $data Data untuk membuat pemesanan.
     * Kunci yang diharapkan: 'user_id', 'nama_destinasi', 'jenis_pemesanan', 'nama_item', 'jumlah_item', 'tanggal_kunjungan', 'total_harga', 'status'.
     * @return int|false ID pemesanan baru jika berhasil, false jika gagal.
     */
    public static function create($data)
    {
        if (empty($data['nama_destinasi']) || !isset($data['jumlah_item']) || $data['jumlah_item'] <= 0 || !isset($data['total_harga']) || $data['total_harga'] < 0 || empty($data['tanggal_kunjungan'])) {
            error_log("PemesananTiketController Error: Data tidak lengkap atau tidak valid untuk membuat pemesanan.");
            return false;
        }
        if (isset($data['user_id']) && !is_numeric($data['user_id']) && !is_null($data['user_id'])) {
            error_log("PemesananTiketController Error: User ID tidak valid.");
            return false;
        }

        $new_pemesanan_id = PemesananTiket::create($data);

        if ($new_pemesanan_id) {
            return $new_pemesanan_id;
        } else {
            error_log("PemesananTiketController: Gagal membuat pemesanan melalui Model.");
            return false;
        }
    }

    /**
     * Mengambil semua data pemesanan tiket.
     * @return array Daftar pemesanan tiket.
     */
    public static function getAll()
    {
        return PemesananTiket::getAll();
    }

    /**
     * Mengambil satu pemesanan tiket berdasarkan ID.
     * @param int $id ID Pemesanan.
     * @return array|null Data pemesanan atau null jika tidak ditemukan.
     */
    public static function getById($id)
    {
        if (!is_numeric($id) || $id <= 0) {
            error_log("PemesananTiketController Error: ID tidak valid untuk getById.");
            return null;
        }
        return PemesananTiket::getById((int)$id);
    }

    /**
     * Menghapus data pemesanan tiket.
     * @param int $id ID pemesanan yang akan dihapus.
     * @return bool True jika berhasil, false jika gagal.
     */
    public static function delete($id)
    {
        if (!is_numeric($id) || $id <= 0) {
            error_log("PemesananTiketController Error: ID tidak valid untuk penghapusan.");
            return false;
        }
        return PemesananTiket::delete((int)$id);
    }

    /**
     * Memperbarui status pemesanan tiket.
     * @param int $id ID pemesanan.
     * @param string $status Status baru.
     * @return bool True jika berhasil, false jika gagal.
     */
    public static function updateStatus($id, $status)
    {
        if (!is_numeric($id) || $id <= 0 || empty(trim($status))) {
            error_log("PemesananTiketController Error: Data tidak valid untuk update status.");
            return false;
        }
        return PemesananTiket::updateStatus((int)$id, trim($status));
    }

    /**
     * Mengambil pemesanan tiket berdasarkan User ID.
     * @param int $user_id ID Pengguna.
     * @param int|null $limit Batas jumlah record.
     * @return array Daftar pemesanan tiket pengguna.
     */
    public static function getByUserId($user_id, $limit = null)
    {
        if (!is_numeric($user_id) || $user_id <= 0) {
            error_log("PemesananTiketController Error: User ID tidak valid.");
            return [];
        }
        return PemesananTiket::getByUserId((int)$user_id, null, $limit);
    }


    /**
     * Menghitung jumlah pemesanan tiket berdasarkan status.
     * @param string $status Status pemesanan.
     * @return int Jumlah pemesanan.
     */
    public static function countByStatus($status)
    {
        if (empty(trim($status))) {
            error_log("PemesananTiketController Error: Status tidak valid untuk dihitung.");
            return 0;
        }
        return PemesananTiket::countByStatus(trim($status));
    }
}
