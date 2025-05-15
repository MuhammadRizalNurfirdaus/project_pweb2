<?php
// File: C:\xampp\htdocs\Cilengkrang-Web-Wisata\controllers\DetailPemesananTiketController.php

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../models/DetailPemesananTiket.php';
require_once __DIR__ . '/../models/JenisTiket.php'; // Untuk validasi dan ambil harga
require_once __DIR__ . '/../models/JadwalKetersediaanTiket.php'; // Untuk update stok

class DetailPemesananTiketController
{
    /**
     * Membuat (menyimpan) satu item detail pemesanan tiket.
     * Fungsi ini biasanya dipanggil oleh PemesananTiketController saat memproses pemesanan lengkap.
     *
     * @param array $data Array asosiatif data untuk satu item tiket.
     *                    Kunci yang diharapkan: 
     *                    'pemesanan_tiket_id' (int),
     *                    'jenis_tiket_id' (int),
     *                    'jumlah' (int),
     *                    'tanggal_kunjungan' (string YYYY-MM-DD, untuk cek jadwal ketersediaan)
     * @return int|false ID dari detail pemesanan tiket baru jika berhasil, false jika gagal.
     */
    public static function create($data)
    {
        // Validasi input dasar
        $pemesanan_tiket_id = isset($data['pemesanan_tiket_id']) ? filter_var($data['pemesanan_tiket_id'], FILTER_VALIDATE_INT) : null;
        $jenis_tiket_id = isset($data['jenis_tiket_id']) ? filter_var($data['jenis_tiket_id'], FILTER_VALIDATE_INT) : null;
        $jumlah = isset($data['jumlah']) ? filter_var($data['jumlah'], FILTER_VALIDATE_INT) : null;
        $tanggal_kunjungan = isset($data['tanggal_kunjungan']) ? trim($data['tanggal_kunjungan']) : null; // Diperlukan untuk cek stok

        if (!$pemesanan_tiket_id || $pemesanan_tiket_id <= 0) {
            error_log("DetailPemesananTiketController::create() - pemesanan_tiket_id tidak valid.");
            // set_flash_message('danger', 'ID Pemesanan Tiket tidak valid untuk detail.'); // Biasanya dihandle oleh controller utama
            return false;
        }
        if (!$jenis_tiket_id || $jenis_tiket_id <= 0) {
            error_log("DetailPemesananTiketController::create() - jenis_tiket_id tidak valid.");
            set_flash_message('danger', 'Jenis tiket tidak valid untuk detail pemesanan.');
            return false;
        }
        if ($jumlah === null || $jumlah === false || $jumlah <= 0) {
            error_log("DetailPemesananTiketController::create() - Jumlah tiket tidak valid.");
            set_flash_message('danger', 'Jumlah tiket tidak valid untuk detail pemesanan.');
            return false;
        }
        if (empty($tanggal_kunjungan) || !DateTime::createFromFormat('Y-m-d', $tanggal_kunjungan)) {
            error_log("DetailPemesananTiketController::create() - Tanggal kunjungan tidak valid untuk cek stok.");
            set_flash_message('danger', 'Tanggal kunjungan tidak valid untuk pengecekan stok tiket.');
            return false;
        }


        // Ambil informasi harga dari JenisTiket Model
        $jenisTiketInfo = JenisTiket::findById($jenis_tiket_id);
        if (!$jenisTiketInfo || $jenisTiketInfo['aktif'] == 0) {
            error_log("DetailPemesananTiketController::create() - Jenis tiket ID {$jenis_tiket_id} tidak ditemukan atau tidak aktif.");
            set_flash_message('danger', "Jenis tiket yang dipilih (ID: {$jenis_tiket_id}) tidak ditemukan atau tidak aktif.");
            return false;
        }
        $harga_satuan_saat_pesan = (int)$jenisTiketInfo['harga'];
        $subtotal_item = $harga_satuan_saat_pesan * $jumlah;

        // Cek dan Update Stok di JadwalKetersediaanTiket (jika diimplementasikan)
        if (class_exists('JadwalKetersediaanTiket') && method_exists('JadwalKetersediaanTiket', 'getActiveKetersediaan') && method_exists('JadwalKetersediaanTiket', 'updateJumlahSaatIniTersedia')) {
            $ketersediaan = JadwalKetersediaanTiket::getActiveKetersediaan($jenis_tiket_id, $tanggal_kunjungan);
            if (!$ketersediaan || $ketersediaan['jumlah_saat_ini_tersedia'] < $jumlah) {
                $nama_layanan_display = $jenisTiketInfo['nama_layanan_display'] ?? 'Tiket';
                $tipe_hari_display = $jenisTiketInfo['tipe_hari'] ?? '';
                set_flash_message('danger', 'Kuota untuk "' . e($nama_layanan_display) . ($tipe_hari_display ? ' (' . e($tipe_hari_display) . ')' : '') . '" pada tanggal ' . e($tanggal_kunjungan) . ' tidak mencukupi (tersisa: ' . ($ketersediaan['jumlah_saat_ini_tersedia'] ?? 0) . ', diminta: ' . $jumlah . ').');
                return false; // Gagal karena stok tidak cukup
            }
            // Jika stok cukup, update stok (pengurangan) akan dilakukan setelah berhasil create detail
            $id_jadwal_to_update = $ketersediaan['id'];
        }


        $data_to_save = [
            'pemesanan_tiket_id' => $pemesanan_tiket_id,
            'jenis_tiket_id' => $jenis_tiket_id,
            'jumlah' => $jumlah,
            'harga_satuan_saat_pesan' => $harga_satuan_saat_pesan,
            'subtotal_item' => $subtotal_item
        ];

        $new_detail_id = DetailPemesananTiket::create($data_to_save);

        if ($new_detail_id) {
            // Jika berhasil menyimpan detail, update stok di jadwal ketersediaan
            if (isset($id_jadwal_to_update)) {
                if (!JadwalKetersediaanTiket::updateJumlahSaatIniTersedia($id_jadwal_to_update, -$jumlah)) {
                    // Ini adalah masalah serius, detail tersimpan tapi stok gagal diupdate.
                    // Idealnya ini ada dalam transaksi database. Untuk sekarang, log error.
                    error_log("CRITICAL: DetailPemesananTiketController::create() - Berhasil create detail ID {$new_detail_id}, TAPI GAGAL update stok jadwal ID {$id_jadwal_to_update} untuk jenis tiket ID {$jenis_tiket_id}.");
                    // Mungkin perlu mekanisme untuk rollback manual atau notifikasi admin.
                }
            }
            return $new_detail_id;
        } else {
            // Pesan error sudah di-log oleh Model
            // set_flash_message('danger', 'Gagal menyimpan salah satu item detail tiket.'); // Dihandle oleh PemesananTiketController
            return false;
        }
    }

    /**
     * Mengambil semua item detail tiket untuk satu pemesanan_tiket_id.
     * @param int $pemesanan_tiket_id
     * @return array Array data detail tiket, atau array kosong jika tidak ada/error.
     */
    public static function getByPemesananTiketId($pemesanan_tiket_id)
    {
        $id_val = filter_var($pemesanan_tiket_id, FILTER_VALIDATE_INT);
        if ($id_val === false || $id_val <= 0) {
            error_log("DetailPemesananTiketController::getByPemesananTiketId() - pemesanan_tiket_id tidak valid.");
            return [];
        }
        return DetailPemesananTiket::getByPemesananTiketId($id_val);
    }

    // Method update dan delete untuk item detail individual bisa ditambahkan di sini jika diperlukan,
    // tapi akan lebih kompleks karena harus update total_harga_akhir di pemesanan_tiket
    // dan juga stok di jadwal_ketersediaan_tiket.
    // Biasanya, jika ada kesalahan, pemesanan dibatalkan dan dibuat ulang.

    /**
     * Menghapus semua detail item tiket berdasarkan pemesanan_tiket_id.
     * Ini biasanya dipanggil jika pemesanan tiket utama dibatalkan/dihapus.
     * Harus mengembalikan stok tiket jika ada.
     * @param int $pemesanan_tiket_id
     * @return bool
     */
    // public static function deleteByPemesananTiketId($pemesanan_tiket_id)
    // {
    //     $id_val = filter_var($pemesanan_tiket_id, FILTER_VALIDATE_INT);
    //     if ($id_val === false || $id_val <= 0) { return false; }

    //     // 1. Ambil semua detail item untuk tahu apa saja yang stoknya perlu dikembalikan
    //     $items_to_delete = DetailPemesananTiket::getByPemesananTiketId($id_val);
    //     if (empty($items_to_delete)) {
    //         return true; // Tidak ada yang perlu dihapus
    //     }

    //     // 2. Hapus semua detail item dari database
    //     if (DetailPemesananTiket::deleteByPemesananTiketId($id_val)) { // Anda perlu method ini di Model
    //         // 3. Kembalikan stok untuk setiap item
    //         if (class_exists('JadwalKetersediaanTiket') && method_exists('JadwalKetersediaanTiket', 'updateJumlahSaatIniTersedia')) {
    //             foreach ($items_to_delete as $item) {
    //                 $jadwal = JadwalKetersediaanTiket::getActiveKetersediaan($item['jenis_tiket_id'], $item['tanggal_kunjungan_dari_header_pemesanan']); // Perlu tanggal kunjungan
    //                 if ($jadwal) {
    //                     JadwalKetersediaanTiket::updateJumlahSaatIniTersedia($jadwal['id'], +$item['jumlah']);
    //                 }
    //             }
    //         }
    //         return true;
    //     }
    //     return false;
    // }
}
