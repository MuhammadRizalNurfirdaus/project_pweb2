<?php
// File: C:\xampp\htdocs\Cilengkrang-Web-Wisata\models\DetailPemesananTiket.php

class DetailPemesananTiket
{
    private static $table_name = "detail_pemesanan_tiket";

    /**
     * Membuat (menyimpan) satu item detail pemesanan tiket baru ke database.
     * @param array $data Array asosiatif data untuk satu item tiket.
     *                    Kunci yang diharapkan: 
     *                    'pemesanan_tiket_id' (int),
     *                    'jenis_tiket_id' (int),
     *                    'jumlah' (int),
     *                    'harga_satuan_saat_pesan' (int),
     *                    'subtotal_item' (int).
     *                    Kolom 'created_at' akan diisi otomatis oleh database.
     * @return int|false ID dari detail pemesanan tiket baru jika berhasil, false jika gagal.
     */
    public static function create($data)
    {
        global $conn;
        if (!$conn) {
            error_log("DetailPemesananTiket::create() - Koneksi database gagal.");
            return false;
        }

        // Validasi dan ambil data
        $pemesanan_tiket_id = isset($data['pemesanan_tiket_id']) ? filter_var($data['pemesanan_tiket_id'], FILTER_VALIDATE_INT) : null;
        $jenis_tiket_id = isset($data['jenis_tiket_id']) ? filter_var($data['jenis_tiket_id'], FILTER_VALIDATE_INT) : null;
        $jumlah = isset($data['jumlah']) ? filter_var($data['jumlah'], FILTER_VALIDATE_INT) : null;
        $harga_satuan_saat_pesan = isset($data['harga_satuan_saat_pesan']) ? filter_var($data['harga_satuan_saat_pesan'], FILTER_VALIDATE_INT) : null;
        $subtotal_item = isset($data['subtotal_item']) ? filter_var($data['subtotal_item'], FILTER_VALIDATE_INT) : null;

        if (
            $pemesanan_tiket_id === null || $pemesanan_tiket_id <= 0 ||
            $jenis_tiket_id === null || $jenis_tiket_id <= 0 ||
            $jumlah === null || $jumlah <= 0 ||
            $harga_satuan_saat_pesan === null || $harga_satuan_saat_pesan < 0 ||
            $subtotal_item === null || $subtotal_item < 0
        ) {
            error_log("DetailPemesananTiket::create() - Data input tidak valid atau tidak lengkap. PTID: {$pemesanan_tiket_id}, JTID: {$jenis_tiket_id}, JML: {$jumlah}, Harga: {$harga_satuan_saat_pesan}, Subtotal: {$subtotal_item}");
            return false;
        }

        $sql = "INSERT INTO " . self::$table_name .
            " (pemesanan_tiket_id, jenis_tiket_id, jumlah, harga_satuan_saat_pesan, subtotal_item) 
               VALUES (?, ?, ?, ?, ?)";

        $stmt = mysqli_prepare($conn, $sql);
        if (!$stmt) {
            error_log("DetailPemesananTiket::create() - MySQLi Prepare Error: " . mysqli_error($conn) . " | SQL: " . $sql);
            return false;
        }

        // Bind parameter: i = integer
        mysqli_stmt_bind_param(
            $stmt,
            "iiiii",
            $pemesanan_tiket_id,
            $jenis_tiket_id,
            $jumlah,
            $harga_satuan_saat_pesan,
            $subtotal_item
        );

        if (mysqli_stmt_execute($stmt)) {
            $new_id = mysqli_insert_id($conn);
            mysqli_stmt_close($stmt);
            return $new_id;
        } else {
            error_log("DetailPemesananTiket::create() - MySQLi Execute Error: " . mysqli_stmt_error($stmt) . " | SQL: " . $sql);
            mysqli_stmt_close($stmt);
            return false;
        }
    }

    /**
     * Mengambil semua item detail tiket untuk satu pemesanan_tiket_id.
     * Melakukan JOIN dengan tabel jenis_tiket dan wisata untuk informasi tambahan.
     * @param int $pemesanan_tiket_id ID dari pemesanan tiket utama.
     * @return array Array data detail tiket, atau array kosong jika tidak ada/error.
     */
    public static function getByPemesananTiketId($pemesanan_tiket_id)
    {
        global $conn;
        if (!$conn) {
            error_log("DetailPemesananTiket::getByPemesananTiketId() - Koneksi database gagal.");
            return [];
        }

        $id_val = filter_var($pemesanan_tiket_id, FILTER_VALIDATE_INT);
        if ($id_val === false || $id_val <= 0) {
            error_log("DetailPemesananTiket::getByPemesananTiketId() - pemesanan_tiket_id tidak valid: " . e($pemesanan_tiket_id));
            return [];
        }

        $sql = "SELECT dpt.*, jt.nama_layanan_display, jt.tipe_hari, jt.deskripsi AS deskripsi_jenis_tiket, 
                       w.nama AS nama_wisata_terkait 
                FROM " . self::$table_name . " dpt
                JOIN jenis_tiket jt ON dpt.jenis_tiket_id = jt.id
                LEFT JOIN wisata w ON jt.wisata_id = w.id
                WHERE dpt.pemesanan_tiket_id = ?
                ORDER BY dpt.id ASC";

        $stmt = mysqli_prepare($conn, $sql);
        if (!$stmt) {
            error_log("DetailPemesananTiket::getByPemesananTiketId() - MySQLi Prepare Error: " . mysqli_error($conn) . " | SQL: " . $sql);
            return [];
        }

        mysqli_stmt_bind_param($stmt, "i", $id_val);

        if (mysqli_stmt_execute($stmt)) {
            $result = mysqli_stmt_get_result($stmt);
            $items = mysqli_fetch_all($result, MYSQLI_ASSOC);
            mysqli_stmt_close($stmt);
            return $items;
        } else {
            error_log("DetailPemesananTiket::getByPemesananTiketId() - MySQLi Execute Error: " . mysqli_stmt_error($stmt) . " | SQL: " . $sql);
            mysqli_stmt_close($stmt);
            return [];
        }
    }

    /**
     * Menghapus semua detail item tiket berdasarkan pemesanan_tiket_id.
     * Berguna jika pemesanan tiket utama dibatalkan/dihapus dan tidak ada ON DELETE CASCADE di DB,
     * atau jika ingin menghapus detail sebelum menghapus header pemesanan (jika ON DELETE CASCADE tidak ada).
     * PENTING: Fungsi ini TIDAK mengembalikan stok tiket.
     *
     * @param int $pemesanan_tiket_id ID dari pemesanan tiket utama.
     * @return bool True jika berhasil menghapus (atau tidak ada yang dihapus), false jika terjadi error query.
     */
    public static function deleteByPemesananTiketId($pemesanan_tiket_id)
    {
        global $conn;
        if (!$conn) {
            error_log("DetailPemesananTiket::deleteByPemesananTiketId() - Koneksi database gagal.");
            return false;
        }

        $id_val = filter_var($pemesanan_tiket_id, FILTER_VALIDATE_INT);
        if ($id_val === false || $id_val <= 0) {
            error_log("DetailPemesananTiket::deleteByPemesananTiketId() - pemesanan_tiket_id tidak valid: " . e($pemesanan_tiket_id));
            return false;
        }

        $sql = "DELETE FROM " . self::$table_name . " WHERE pemesanan_tiket_id = ?";
        $stmt = mysqli_prepare($conn, $sql);

        if (!$stmt) {
            error_log("DetailPemesananTiket::deleteByPemesananTiketId() - MySQLi Prepare Error: " . mysqli_error($conn) . " | SQL: " . $sql);
            return false;
        }

        mysqli_stmt_bind_param($stmt, "i", $id_val);

        if (mysqli_stmt_execute($stmt)) {
            // $affected_rows = mysqli_stmt_affected_rows($stmt); // Bisa dicek jika perlu
            mysqli_stmt_close($stmt);
            return true; // Anggap berhasil jika query tereksekusi tanpa error
        } else {
            error_log("DetailPemesananTiket::deleteByPemesananTiketId() - MySQLi Execute Error: " . mysqli_stmt_error($stmt) . " | SQL: " . $sql);
            mysqli_stmt_close($stmt);
            return false;
        }
    }
}
