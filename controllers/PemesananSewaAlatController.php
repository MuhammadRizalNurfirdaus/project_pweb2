<?php
// File: C:\xampp\htdocs\Cilengkrang-Web-Wisata\controllers\PemesananSewaAlatController.php

require_once __DIR__ . '/../models/PemesananSewaAlat.php';
require_once __DIR__ . '/../models/SewaAlat.php'; // Untuk cek & update stok
require_once __DIR__ . '/../models/PemesananTiket.php'; // Jika sewa terkait tiket
// config.php diasumsikan sudah dimuat oleh file pemanggil, menyediakan $conn dan helpers

class PemesananSewaAlatController
{

    /**
     * Membuat entri pemesanan sewa alat baru.
     * Dipanggil dari sisi publik (form pemesanan) atau admin (pemesanan manual).
     * @param array $data Data lengkap pemesanan sewa.
     *        Kunci yang diharapkan: 'pemesanan_tiket_id' (jika sewa terkait tiket),
     *        'sewa_alat_id', 'jumlah', 'tanggal_mulai_sewa', 'tanggal_akhir_sewa_rencana',
     *        'catatan_item_sewa' (opsional).
     *        Harga akan diambil dari Model SewaAlat saat itu.
     * @return int|false ID pemesanan sewa baru jika berhasil, false jika gagal.
     */
    public static function createPemesananSewa($data)
    {
        global $conn; // Diperlukan untuk transaksi jika create melibatkan beberapa tabel
        if (!$conn) {
            set_flash_message('danger', 'Koneksi database tidak tersedia.');
            error_log("PemesananSewaAlatController::createPemesananSewa() - Koneksi DB gagal.");
            return false;
        }

        // Validasi dasar
        $pemesanan_tiket_id = isset($data['pemesanan_tiket_id']) ? filter_var($data['pemesanan_tiket_id'], FILTER_VALIDATE_INT) : null;
        $sewa_alat_id = isset($data['sewa_alat_id']) ? filter_var($data['sewa_alat_id'], FILTER_VALIDATE_INT) : 0;
        $jumlah = isset($data['jumlah']) ? filter_var($data['jumlah'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) : 0;
        $tgl_mulai = trim($data['tanggal_mulai_sewa'] ?? '');
        $tgl_akhir = trim($data['tanggal_akhir_sewa_rencana'] ?? '');

        if ($sewa_alat_id <= 0 || $jumlah <= 0) {
            set_flash_message('danger', 'ID Alat Sewa atau Jumlah tidak valid.');
            return false;
        }
        if (empty($tgl_mulai) || !DateTime::createFromFormat('Y-m-d H:i:s', $tgl_mulai) || empty($tgl_akhir) || !DateTime::createFromFormat('Y-m-d H:i:s', $tgl_akhir) || (new DateTime($tgl_mulai) >= new DateTime($tgl_akhir))) {
            set_flash_message('danger', 'Format atau rentang tanggal sewa tidak valid.');
            return false;
        }
        // Jika pemesanan_tiket_id wajib dan ada, validasi keberadaannya
        if ($pemesanan_tiket_id !== null && $pemesanan_tiket_id > 0) {
            if (!PemesananTiket::getById($pemesanan_tiket_id)) { // Panggil statis
                set_flash_message('danger', 'ID Pemesanan Tiket terkait tidak valid.');
                return false;
            }
        } elseif ($pemesanan_tiket_id === 0 && !empty($data['pemesanan_tiket_id'])) { // Jika dikirim tapi tidak valid
            set_flash_message('danger', 'ID Pemesanan Tiket terkait tidak valid.');
            return false;
        }


        // Ambil info alat untuk harga dan stok
        $alatInfo = SewaAlat::getById($sewa_alat_id); // Panggil statis
        if (!$alatInfo) {
            set_flash_message('danger', 'Informasi alat sewa tidak ditemukan.');
            return false;
        }
        if ($alatInfo['stok_tersedia'] < $jumlah) {
            set_flash_message('danger', 'Stok untuk alat "' . e($alatInfo['nama_item']) . '" tidak mencukupi (tersisa: ' . e($alatInfo['stok_tersedia']) . ').');
            return false;
        }

        // Persiapkan data untuk Model PemesananSewaAlat::create()
        $data_to_model = [
            'pemesanan_tiket_id' => $pemesanan_tiket_id, // Bisa null jika sewa mandiri & tabel mengizinkan
            'sewa_alat_id' => $sewa_alat_id,
            'jumlah' => $jumlah,
            'harga_satuan_saat_pesan' => (float)$alatInfo['harga_sewa'],
            'durasi_satuan_saat_pesan' => (int)$alatInfo['durasi_harga_sewa'],
            'satuan_durasi_saat_pesan' => $alatInfo['satuan_durasi_harga'],
            'tanggal_mulai_sewa' => $tgl_mulai,
            'tanggal_akhir_sewa_rencana' => $tgl_akhir,
            'status_item_sewa' => 'Dipesan', // Status awal
            'catatan_item_sewa' => $data['catatan_item_sewa'] ?? null,
            'denda' => 0.0 // Denda awal 0
        ];

        // Logika perhitungan total harga bisa di sini atau di Model::create()
        // Untuk konsistensi, biarkan Model::create() menghitung total_harga_item
        // berdasarkan harga satuan, jumlah, dan durasi.

        mysqli_begin_transaction($conn);
        try {
            $new_pemesanan_sewa_id = PemesananSewaAlat::create($data_to_model); // Panggil statis
            if (!$new_pemesanan_sewa_id) {
                throw new Exception("Gagal menyimpan pemesanan sewa ke database.");
            }

            // Kurangi stok alat (logika ini juga ada di Model PemesananSewaAlat::create(), jadi pilih salah satu tempat)
            // Jika tidak ada di Model::create(), lakukan di sini:
            // if (!SewaAlat::updateStok($sewa_alat_id, -$jumlah)) {
            //     throw new Exception("Gagal mengupdate stok alat sewa.");
            // }

            mysqli_commit($conn);
            set_flash_message('success', 'Pemesanan sewa alat berhasil dibuat.');
            return $new_pemesanan_sewa_id;
        } catch (Exception $e) {
            mysqli_rollback($conn);
            error_log("PemesananSewaAlatController::createPemesananSewa() - Exception: " . $e->getMessage());
            if (!isset($_SESSION['flash_message'])) { // Hindari menimpa pesan flash yang lebih spesifik
                set_flash_message('danger', 'Terjadi kesalahan saat membuat pemesanan sewa: ' . $e->getMessage());
            }
            return false;
        }
    }


    public static function getAllPemesananSewaForAdmin()
    {
        $data = PemesananSewaAlat::getAll();
        return $data ?: [];
    }

    public static function getPemesananSewaById($id_pemesanan_sewa)
    {
        $id_val = filter_var($id_pemesanan_sewa, FILTER_VALIDATE_INT);
        if ($id_val === false || $id_val <= 0) {
            set_flash_message('danger', 'ID Pemesanan Sewa tidak valid.');
            return null;
        }
        return PemesananSewaAlat::getById($id_val);
    }


    public static function getPemesananSewaByUserIdForUser($user_id)
    {
        // ... (implementasi seperti sebelumnya, query melalui pemesanan_tiket) ...
        global $conn;
        if (!$conn) {
            return [];
        }
        $user_id_val = filter_var($user_id, FILTER_VALIDATE_INT);
        if ($user_id_val === false || $user_id_val <= 0) {
            return [];
        }
        $sql = "SELECT psa.*, sa.nama_item AS nama_alat, pt.kode_pemesanan FROM pemesanan_sewa_alat psa JOIN sewa_alat sa ON psa.sewa_alat_id = sa.id JOIN pemesanan_tiket pt ON psa.pemesanan_tiket_id = pt.id WHERE pt.user_id = ? ORDER BY psa.created_at DESC";
        $stmt = mysqli_prepare($conn, $sql);
        if (!$stmt) {
            return [];
        }
        mysqli_stmt_bind_param($stmt, "i", $user_id_val);
        if (mysqli_stmt_execute($stmt)) {
            $result = mysqli_stmt_get_result($stmt);
            $data = mysqli_fetch_all($result, MYSQLI_ASSOC);
            mysqli_stmt_close($stmt);
            return $data;
        }
        mysqli_stmt_close($stmt);
        return [];
    }

    public static function updateStatusSewa($id_pemesanan_sewa, $new_status)
    {
        $id_val = filter_var($id_pemesanan_sewa, FILTER_VALIDATE_INT);
        if ($id_val === false || $id_val <= 0) {
            set_flash_message('danger', 'ID Pemesanan Sewa tidak valid.');
            return false;
        }
        $allowed_status = ['Dipesan', 'Diambil', 'Dikembalikan', 'Hilang', 'Rusak', 'Dibatalkan'];
        if (empty($new_status) || !in_array($new_status, $allowed_status)) {
            set_flash_message('danger', 'Status item sewa tidak valid.');
            return false;
        }

        // Model PemesananSewaAlat::updateStatus() sudah menghandle logika update stok
        $success = PemesananSewaAlat::updateStatus($id_val, $new_status);
        if (!$success) {
            if (!isset($_SESSION['flash_message'])) {
                set_flash_message('danger', 'Gagal memperbarui status pemesanan sewa.');
            }
            return false;
        }
        // Pesan sukses bisa diset di sini atau di file proses yang memanggilnya
        // set_flash_message('success', 'Status pemesanan sewa berhasil diperbarui.');
        return true;
    }

    public static function deletePemesananSewa($id_pemesanan_sewa)
    {
        $id_val = filter_var($id_pemesanan_sewa, FILTER_VALIDATE_INT);
        if ($id_val === false || $id_val <= 0) {
            set_flash_message('danger', 'ID Pemesanan Sewa tidak valid untuk dihapus.');
            return false;
        }
        // Model PemesananSewaAlat::delete() akan menghandle pengembalian stok jika perlu
        if (PemesananSewaAlat::delete($id_val)) {
            set_flash_message('success', 'Pemesanan sewa alat berhasil dihapus.');
            return true;
        } else {
            set_flash_message('danger', 'Gagal menghapus pemesanan sewa alat.');
            return false;
        }
    }

    public static function countByStatus($status_item_sewa)
    {
        if (empty(trim($status_item_sewa))) {
            return 0;
        }
        return PemesananSewaAlat::countByStatus(trim($status_item_sewa));
    }
}
