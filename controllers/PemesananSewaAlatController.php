<?php
// File: C:\xampp\htdocs\Cilengkrang-Web-Wisata\controllers\PemesananSewaAlatController.php

// config.php diasumsikan sudah dimuat oleh file pemanggil (misal: kelola_pemesanan_sewa.php)
// yang menyediakan $conn dan fungsi helpers (set_flash_message, e, redirect).

require_once __DIR__ . '/../models/PemesananSewaAlat.php'; // Model utama (statis)
require_once __DIR__ . '/../models/SewaAlat.php';         // Untuk cek & update stok (statis)
require_once __DIR__ . '/../models/PemesananTiket.php';    // Jika sewa terkait tiket (statis)

class PemesananSewaAlatController
{

    /**
     * Membuat entri pemesanan sewa alat baru.
     * Ini adalah metode utama yang bisa dipanggil dari form pemesanan publik
     * atau jika admin membuat pemesanan sewa secara manual yang terkait dengan pemesanan tiket.
     * @param array $data Data lengkap pemesanan sewa.
     * Kunci yang diharapkan:
     * 'pemesanan_tiket_id' (WAJIB, berdasarkan struktur tabel Anda),
     * 'sewa_alat_id', 'jumlah', 'tanggal_mulai_sewa' (YYYY-MM-DD HH:MM:SS),
     * 'tanggal_akhir_sewa_rencana' (YYYY-MM-DD HH:MM:SS),
     * 'catatan_item_sewa' (opsional).
     * Harga akan diambil dari Model SewaAlat saat itu.
     * @return int|false ID pemesanan sewa baru jika berhasil, false jika gagal.
     */
    public static function createPemesananSewa($data)
    {
        global $conn;
        if (!$conn) {
            set_flash_message('danger', 'Koneksi database tidak tersedia.');
            error_log("PemesananSewaAlatController::createPemesananSewa() - Koneksi DB gagal.");
            return false;
        }

        // Validasi input dasar
        $pemesanan_tiket_id = isset($data['pemesanan_tiket_id']) ? filter_var($data['pemesanan_tiket_id'], FILTER_VALIDATE_INT) : 0;
        $sewa_alat_id = isset($data['sewa_alat_id']) ? filter_var($data['sewa_alat_id'], FILTER_VALIDATE_INT) : 0;
        $jumlah = isset($data['jumlah']) ? filter_var($data['jumlah'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) : 0;
        $tgl_mulai = trim($data['tanggal_mulai_sewa'] ?? '');
        $tgl_akhir = trim($data['tanggal_akhir_sewa_rencana'] ?? '');

        if ($pemesanan_tiket_id <= 0) {
            set_flash_message('danger', 'ID Pemesanan Tiket terkait wajib diisi dan valid.');
            return false;
        }
        if ($sewa_alat_id <= 0 || $jumlah <= 0) {
            set_flash_message('danger', 'ID Alat Sewa atau Jumlah tidak valid.');
            return false;
        }
        // Validasi format tanggal dan rentang
        try {
            $dtMulai = new DateTime($tgl_mulai);
            $dtAkhir = new DateTime($tgl_akhir);
            if (empty($tgl_mulai) || $dtMulai->format('Y-m-d H:i:s') !== $tgl_mulai || empty($tgl_akhir) || $dtAkhir->format('Y-m-d H:i:s') !== $tgl_akhir || $dtMulai >= $dtAkhir) {
                throw new Exception("Format atau rentang tanggal sewa tidak valid.");
            }
        } catch (Exception $e) {
            set_flash_message('danger', 'Format atau rentang tanggal sewa tidak valid. Pastikan formatnya YYYY-MM-DD HH:MM:SS dan tanggal mulai sebelum tanggal akhir.');
            error_log("PemesananSewaAlatController::createPemesananSewa() - Validasi Tanggal: " . $e->getMessage());
            return false;
        }

        // Validasi keberadaan Pemesanan Tiket
        if (!PemesananTiket::findById($pemesanan_tiket_id)) { // Panggil statis
            set_flash_message('danger', 'ID Pemesanan Tiket terkait tidak ditemukan di database.');
            return false;
        }

        // Ambil info alat untuk harga dan stok
        $alatInfo = SewaAlat::getById($sewa_alat_id); // Panggil statis
        if (!$alatInfo) {
            set_flash_message('danger', 'Informasi alat sewa tidak ditemukan.');
            return false;
        }
        if (!isset($alatInfo['stok_tersedia']) || $alatInfo['stok_tersedia'] < $jumlah) {
            set_flash_message('danger', 'Stok untuk alat "' . e($alatInfo['nama_item'] ?? 'ID: ' . $sewa_alat_id) . '" tidak mencukupi (tersisa: ' . ($alatInfo['stok_tersedia'] ?? 0) . ').');
            return false;
        }

        // Persiapkan data untuk Model PemesananSewaAlat::create()
        $data_to_model = [
            'pemesanan_tiket_id' => $pemesanan_tiket_id,
            'sewa_alat_id' => $sewa_alat_id,
            'jumlah' => $jumlah,
            'harga_satuan_saat_pesan' => (float)($alatInfo['harga_sewa'] ?? 0),
            'durasi_satuan_saat_pesan' => (int)($alatInfo['durasi_harga_sewa'] ?? 1),
            'satuan_durasi_saat_pesan' => $alatInfo['satuan_durasi_harga'] ?? 'Peminjaman',
            'tanggal_mulai_sewa' => $tgl_mulai,
            'tanggal_akhir_sewa_rencana' => $tgl_akhir,
            'status_item_sewa' => 'Dipesan', // Status awal
            'catatan_item_sewa' => $data['catatan_item_sewa'] ?? null,
            'denda' => 0.0
        ];
        // Logika perhitungan total_harga_item sudah ada di Model PemesananSewaAlat::create()

        // Transaksi database disarankan jika create melibatkan update stok juga
        mysqli_begin_transaction($conn);
        try {
            $new_pemesanan_sewa_id = PemesananSewaAlat::create($data_to_model); // Panggil statis
            if (!$new_pemesanan_sewa_id) {
                // Pesan flash mungkin sudah diset di Model jika ada validasi gagal
                throw new Exception("Gagal menyimpan pemesanan sewa ke database (Model return false).");
            }

            // Logika pengurangan stok sudah ada di Model PemesananSewaAlat::create(),
            // jadi tidak perlu dipanggil lagi di sini kecuali jika ingin dipisahkan.

            mysqli_commit($conn);
            // Jangan set flash message sukses di sini jika ini bagian dari prosesPemesananLengkap
            // Biarkan prosesPemesananLengkap yang set flash message akhir.
            // Jika ini dipanggil mandiri, baru set flash di sini.
            // set_flash_message('success', 'Pemesanan sewa alat berhasil dibuat dengan ID: ' . $new_pemesanan_sewa_id);
            return $new_pemesanan_sewa_id;
        } catch (Exception $e) {
            mysqli_rollback($conn);
            error_log("PemesananSewaAlatController::createPemesananSewa() - Exception: " . $e->getMessage());
            if (!isset($_SESSION['flash_message'])) {
                set_flash_message('danger', 'Terjadi kesalahan sistem saat membuat pemesanan sewa.');
            }
            return false;
        }
    }

    /**
     * Mengambil semua data pemesanan sewa untuk ditampilkan di admin.
     * @return array Array data pemesanan atau array kosong.
     */
    public static function getAllPemesananSewaForAdmin()
    {
        // Model PemesananSewaAlat::getAll() sudah melakukan JOIN yang diperlukan
        $data = PemesananSewaAlat::getAll(); // Panggil statis
        return $data ?: [];
    }

    /**
     * Mengambil detail satu pemesanan sewa alat berdasarkan ID-nya.
     * @param int $id_pemesanan_sewa
     * @return array|null Data detail pemesanan sewa atau null.
     */
    public static function getDetailSewaByIdForAdmin($id_pemesanan_sewa)
    {
        $id_val = filter_var($id_pemesanan_sewa, FILTER_VALIDATE_INT);
        if ($id_val === false || $id_val <= 0) {
            set_flash_message('danger', 'ID Pemesanan Sewa tidak valid.');
            return null;
        }
        // Model PemesananSewaAlat::getById() sudah melakukan JOIN yang diperlukan
        return PemesananSewaAlat::getById($id_val); // Panggil statis
    }

    /**
     * Mengambil semua pemesanan sewa alat yang terkait dengan user ID tertentu
     * (melalui pemesanan tiket).
     * @param int $user_id
     * @return array
     */
    public static function getPemesananSewaByUserIdForUser($user_id)
    {
        global $conn;
        if (!$conn) {
            error_log("PemesananSewaAlatController::getPemesananSewaByUserIdForUser() - Koneksi DB gagal.");
            return [];
        }

        $user_id_val = filter_var($user_id, FILTER_VALIDATE_INT);
        if ($user_id_val === false || $user_id_val <= 0) {
            return [];
        }

        // Query ini mengambil item sewa berdasarkan user_id di tabel pemesanan_tiket
        $sql = "SELECT psa.*, sa.nama_item AS nama_alat, pt.kode_pemesanan AS kode_pemesanan_tiket
                FROM pemesanan_sewa_alat psa 
                JOIN sewa_alat sa ON psa.sewa_alat_id = sa.id 
                JOIN pemesanan_tiket pt ON psa.pemesanan_tiket_id = pt.id 
                WHERE pt.user_id = ? 
                ORDER BY psa.created_at DESC";
        $stmt = mysqli_prepare($conn, $sql);
        if (!$stmt) {
            error_log("PemesananSewaAlatController::getPemesananSewaByUserIdForUser() - Prepare failed: " . mysqli_error($conn));
            return [];
        }

        mysqli_stmt_bind_param($stmt, "i", $user_id_val);
        if (mysqli_stmt_execute($stmt)) {
            $result = mysqli_stmt_get_result($stmt);
            $data = mysqli_fetch_all($result, MYSQLI_ASSOC);
            mysqli_stmt_close($stmt);
            return $data;
        }
        error_log("PemesananSewaAlatController::getPemesananSewaByUserIdForUser() - Execute failed: " . mysqli_stmt_error($stmt));
        mysqli_stmt_close($stmt);
        return [];
    }

    /**
     * Mengupdate status item sewa.
     * @param int $id_pemesanan_sewa
     * @param string $new_status
     * @return bool
     */
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
        $success = PemesananSewaAlat::updateStatus($id_val, $new_status); // Panggil statis
        if (!$success) {
            if (!isset($_SESSION['flash_message'])) {
                set_flash_message('danger', 'Gagal memperbarui status pemesanan sewa.');
            }
            return false;
        }
        // Pesan sukses diset oleh proses_update_status_sewa.php
        return true;
    }

    /**
     * Mengupdate catatan dan denda untuk item sewa.
     * @param array $data Harus berisi 'id', 'catatan_item_sewa', 'denda'.
     * @return bool
     */
    public static function updateCatatanDanDenda($data)
    {
        global $conn;
        if (!$conn || !isset($data['id'])) {
            set_flash_message('danger', 'Data tidak lengkap atau koneksi gagal.');
            return false;
        }
        // Validasi lebih lanjut bisa ditambahkan di sini jika perlu
        return PemesananSewaAlat::update($data); // Panggil metode update statis di Model
    }


    /**
     * Menghapus pemesanan sewa alat.
     * @param int $id_pemesanan_sewa
     * @return bool
     */
    public static function deletePemesananSewa($id_pemesanan_sewa)
    {
        $id_val = filter_var($id_pemesanan_sewa, FILTER_VALIDATE_INT);
        if ($id_val === false || $id_val <= 0) {
            set_flash_message('danger', 'ID Pemesanan Sewa tidak valid untuk dihapus.');
            return false;
        }
        // Model PemesananSewaAlat::delete() akan menghandle pengembalian stok jika perlu
        if (PemesananSewaAlat::delete($id_val)) { // Panggil statis
            set_flash_message('success', 'Pemesanan sewa alat berhasil dihapus.');
            return true;
        } else {
            set_flash_message('danger', 'Gagal menghapus pemesanan sewa alat.');
            return false;
        }
    }

    /**
     * Menghitung jumlah pemesanan sewa berdasarkan status (untuk dashboard).
     * @param string $status_item_sewa
     * @return int
     */
    public static function countByStatus($status_item_sewa)
    {
        if (empty(trim($status_item_sewa))) {
            return 0;
        }
        return PemesananSewaAlat::countByStatus(trim($status_item_sewa)); // Panggil statis
    }
}
