<?php
// File: C:\xampp\htdocs\Cilengkrang-Web-Wisata\models\DetailPemesananTiket.php

class DetailPemesananTiket
{
    private static $table_name = "detail_pemesanan_tiket";
    private static $db; // Properti untuk menyimpan koneksi database

    /**
     * Mengatur koneksi database untuk digunakan oleh kelas ini.
     * @param mysqli $connection Instance koneksi mysqli.
     */
    public static function setDbConnection(mysqli $connection)
    {
        self::$db = $connection;
        // error_log(get_called_class() . "::setDbConnection dipanggil."); // Untuk debugging
    }

    /**
     * Memeriksa apakah koneksi database tersedia.
     * @return bool True jika koneksi valid, false jika tidak.
     */
    private static function checkDbConnection()
    {
        if (!self::$db || (self::$db instanceof mysqli && self::$db->connect_error)) {
            error_log(get_called_class() . " - Koneksi database tidak tersedia atau gagal: " .
                (self::$db instanceof mysqli ? self::$db->connect_error : (self::$db === null ? 'Koneksi belum diset.' : 'Koneksi bukan objek mysqli.')));
            return false;
        }
        return true;
    }

    /**
     * Mengambil pesan error terakhir dari koneksi database model ini.
     * @return string Pesan error.
     */
    public static function getLastError()
    {
        if (self::$db instanceof mysqli && !empty(self::$db->error)) {
            return self::$db->error;
        } elseif (!self::$db || !(self::$db instanceof mysqli)) {
            return 'Koneksi database belum diinisialisasi atau tidak valid untuk kelas ' . get_called_class() . '.';
        }
        return 'Tidak ada error database spesifik yang dilaporkan dari model ' . get_called_class() . '.';
    }

    /**
     * Membuat (menyimpan) satu item detail pemesanan tiket baru ke database.
     * @param array $data Array asosiatif data untuk satu item tiket.
     *                    Kunci yang diharapkan: 
     *                    'pemesanan_tiket_id' (int), 'jenis_tiket_id' (int), 'jumlah' (int),
     *                    'harga_satuan_saat_pesan' (float), 'subtotal_item' (float).
     *                    Opsional: 'jadwal_ketersediaan_id' (int).
     * @return int|false ID dari detail pemesanan tiket baru jika berhasil, false jika gagal.
     */
    public static function create($data)
    {
        if (!self::checkDbConnection()) return false;

        $pemesanan_tiket_id = isset($data['pemesanan_tiket_id']) ? filter_var($data['pemesanan_tiket_id'], FILTER_VALIDATE_INT) : null;
        $jenis_tiket_id = isset($data['jenis_tiket_id']) ? filter_var($data['jenis_tiket_id'], FILTER_VALIDATE_INT) : null;
        $jumlah = isset($data['jumlah']) ? filter_var($data['jumlah'], FILTER_VALIDATE_INT) : null;
        $harga_satuan_saat_pesan = isset($data['harga_satuan_saat_pesan']) ? filter_var($data['harga_satuan_saat_pesan'], FILTER_VALIDATE_FLOAT) : null;
        $subtotal_item = isset($data['subtotal_item']) ? filter_var($data['subtotal_item'], FILTER_VALIDATE_FLOAT) : null;
        $jadwal_ketersediaan_id = isset($data['jadwal_ketersediaan_id']) ? filter_var($data['jadwal_ketersediaan_id'], FILTER_VALIDATE_INT) : null; // Bisa NULL

        if (
            $pemesanan_tiket_id === null || $pemesanan_tiket_id <= 0 ||
            $jenis_tiket_id === null || $jenis_tiket_id <= 0 ||
            $jumlah === null || $jumlah <= 0 ||
            $harga_satuan_saat_pesan === null || $harga_satuan_saat_pesan < 0 ||
            $subtotal_item === null || $subtotal_item < 0
        ) {
            error_log(get_called_class() . "::create() - Data input tidak valid atau tidak lengkap.");
            return false;
        }
        if ($jadwal_ketersediaan_id !== null && ($jadwal_ketersediaan_id === false || $jadwal_ketersediaan_id <= 0)) { // Cek jika false dari filter_var
            error_log(get_called_class() . "::create() - jadwal_ketersediaan_id tidak valid jika diset: " . $data['jadwal_ketersediaan_id']);
            return false;
        }

        // Kolom `created_at` dan `updated_at` diasumsikan memiliki DEFAULT atau diisi dengan NOW()
        $sql = "INSERT INTO " . self::$table_name .
            " (pemesanan_tiket_id, jenis_tiket_id, jumlah, harga_satuan_saat_pesan, subtotal_item, jadwal_ketersediaan_id, created_at, updated_at) 
               VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())";

        $stmt = mysqli_prepare(self::$db, $sql);
        if (!$stmt) {
            error_log(get_called_class() . "::create() - MySQLi Prepare Error: " . mysqli_error(self::$db));
            return false;
        }

        mysqli_stmt_bind_param(
            $stmt,
            "iiiddi",
            $pemesanan_tiket_id,
            $jenis_tiket_id,
            $jumlah,
            $harga_satuan_saat_pesan,
            $subtotal_item,
            $jadwal_ketersediaan_id
        );

        if (mysqli_stmt_execute($stmt)) {
            $new_id = mysqli_insert_id(self::$db);
            mysqli_stmt_close($stmt);
            return $new_id;
        } else {
            error_log(get_called_class() . "::create() - MySQLi Execute Error: " . mysqli_stmt_error($stmt));
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
        if (!self::checkDbConnection()) return [];

        $id_val = filter_var($pemesanan_tiket_id, FILTER_VALIDATE_INT);
        if ($id_val === false || $id_val <= 0) {
            error_log(get_called_class() . "::getByPemesananTiketId() - pemesanan_tiket_id tidak valid: " . $pemesanan_tiket_id);
            return [];
        }

        // Pastikan nama tabel `jenis_tiket`, `wisata` dan kolomnya (`nama_wisata`) sesuai dengan DB Anda
        $sql = "SELECT dpt.*, 
                       jt.nama_layanan_display, jt.tipe_hari, jt.deskripsi AS deskripsi_jenis_tiket, 
                       w.nama_wisata AS nama_wisata_terkait 
                FROM " . self::$table_name . " dpt
                INNER JOIN jenis_tiket jt ON dpt.jenis_tiket_id = jt.id
                LEFT JOIN wisata w ON jt.wisata_id = w.id
                WHERE dpt.pemesanan_tiket_id = ?
                ORDER BY dpt.id ASC";

        $stmt = mysqli_prepare(self::$db, $sql);
        if (!$stmt) {
            error_log(get_called_class() . "::getByPemesananTiketId() - MySQLi Prepare Error: " . mysqli_error(self::$db));
            return [];
        }

        mysqli_stmt_bind_param($stmt, "i", $id_val);

        if (mysqli_stmt_execute($stmt)) {
            $result = mysqli_stmt_get_result($stmt);
            $items = mysqli_fetch_all($result, MYSQLI_ASSOC);
            mysqli_stmt_close($stmt);
            return $items;
        } else {
            error_log(get_called_class() . "::getByPemesananTiketId() - MySQLi Execute Error: " . mysqli_stmt_error($stmt));
            mysqli_stmt_close($stmt);
            return [];
        }
    }

    /**
     * Mencari detail pemesanan tiket berdasarkan ID detail.
     * @param int $id ID detail pemesanan tiket.
     * @return array|null Data detail atau null.
     */
    public static function findById($id)
    {
        if (!self::checkDbConnection()) return null;
        $id_val = filter_var($id, FILTER_VALIDATE_INT);
        if ($id_val === false || $id_val <= 0) {
            error_log(get_called_class() . "::findById() - ID tidak valid: " . $id);
            return null;
        }

        // Query ini bisa di-JOIN dengan jenis_tiket jika perlu info lebih detail di sini
        $sql = "SELECT * FROM " . self::$table_name . " WHERE id = ? LIMIT 1";
        $stmt = mysqli_prepare(self::$db, $sql);
        if (!$stmt) {
            error_log(get_called_class() . "::findById() - MySQLi Prepare Error: " . mysqli_error(self::$db));
            return null;
        }

        mysqli_stmt_bind_param($stmt, "i", $id_val);
        if (mysqli_stmt_execute($stmt)) {
            $result = mysqli_stmt_get_result($stmt);
            $item = mysqli_fetch_assoc($result);
            mysqli_stmt_close($stmt);
            return $item ?: null;
        } else {
            error_log(get_called_class() . "::findById() - MySQLi Execute Error: " . mysqli_stmt_error($stmt));
        }
        mysqli_stmt_close($stmt);
        return null;
    }


    /**
     * Menghapus semua detail item tiket berdasarkan pemesanan_tiket_id.
     * PENTING: Fungsi ini TIDAK mengembalikan stok tiket ke jadwal ketersediaan.
     * Pengembalian stok harus ditangani oleh logika bisnis yang lebih tinggi (misal di Controller).
     *
     * @param int $pemesanan_tiket_id ID dari pemesanan tiket utama.
     * @return bool True jika berhasil menghapus (atau tidak ada yang dihapus), false jika terjadi error query.
     */
    public static function deleteByPemesananTiketId($pemesanan_tiket_id)
    {
        if (!self::checkDbConnection()) return false;

        $id_val = filter_var($pemesanan_tiket_id, FILTER_VALIDATE_INT);
        if ($id_val === false || $id_val <= 0) {
            error_log(get_called_class() . "::deleteByPemesananTiketId() - pemesanan_tiket_id tidak valid: " . $pemesanan_tiket_id);
            return false;
        }

        $sql = "DELETE FROM " . self::$table_name . " WHERE pemesanan_tiket_id = ?";
        $stmt = mysqli_prepare(self::$db, $sql);

        if (!$stmt) {
            error_log(get_called_class() . "::deleteByPemesananTiketId() - MySQLi Prepare Error: " . mysqli_error(self::$db));
            return false;
        }

        mysqli_stmt_bind_param($stmt, "i", $id_val);

        if (mysqli_stmt_execute($stmt)) {
            // $affected_rows = mysqli_stmt_affected_rows($stmt); // Bisa digunakan jika perlu tahu berapa baris terhapus
            mysqli_stmt_close($stmt);
            return true;
        } else {
            error_log(get_called_class() . "::deleteByPemesananTiketId() - MySQLi Execute Error: " . mysqli_stmt_error($stmt));
            mysqli_stmt_close($stmt);
            return false;
        }
    }
} // End of class DetailPemesananTiket