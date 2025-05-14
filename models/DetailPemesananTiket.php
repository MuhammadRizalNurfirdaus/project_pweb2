<?php
// File: C:\xampp\htdocs\Cilengkrang-Web-Wisata\models\DetailPemesananTiket.php

class DetailPemesananTiket
{
    private static $table_name = "detail_pemesanan_tiket";
    private static $db;

    public static function setDbConnection(mysqli $connection)
    {
        self::$db = $connection;
    }

    private static function checkDbConnection()
    {
        if (!self::$db || (self::$db instanceof mysqli && self::$db->connect_error)) {
            error_log(get_called_class() . " - Koneksi database tidak tersedia atau gagal.");
            return false;
        }
        return true;
    }

    public static function getLastError()
    {
        if (self::$db instanceof mysqli && !empty(self::$db->error)) {
            return self::$db->error;
        }
        return 'Tidak ada error database spesifik dari model ' . get_called_class() . '.';
    }

    public static function create($data)
    {
        if (!self::checkDbConnection()) return false;

        $pemesanan_tiket_id = isset($data['pemesanan_tiket_id']) ? filter_var($data['pemesanan_tiket_id'], FILTER_VALIDATE_INT) : null;
        $jenis_tiket_id = isset($data['jenis_tiket_id']) ? filter_var($data['jenis_tiket_id'], FILTER_VALIDATE_INT) : null;
        $jumlah = isset($data['jumlah']) ? filter_var($data['jumlah'], FILTER_VALIDATE_INT) : null;
        $harga_satuan_saat_pesan = isset($data['harga_satuan_saat_pesan']) ? filter_var($data['harga_satuan_saat_pesan'], FILTER_VALIDATE_FLOAT) : null;
        $subtotal_item = isset($data['subtotal_item']) ? filter_var($data['subtotal_item'], FILTER_VALIDATE_FLOAT) : null;
        // jadwal_ketersediaan_id tidak ada di screenshot tabel detail_pemesanan_tiket
        // Jika memang tidak ada, hapus atau sesuaikan. Untuk sekarang, saya biarkan dengan asumsi mungkin ada di skema lengkap.
        // $jadwal_ketersediaan_id = isset($data['jadwal_ketersediaan_id']) ? filter_var($data['jadwal_ketersediaan_id'], FILTER_VALIDATE_INT) : null;

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
        // if ($jadwal_ketersediaan_id !== null && ($jadwal_ketersediaan_id === false || $jadwal_ketersediaan_id <= 0)) {
        //     error_log(get_called_class() . "::create() - jadwal_ketersediaan_id tidak valid jika diset: " . $data['jadwal_ketersediaan_id']);
        //     return false;
        // }

        // Sesuaikan query INSERT jika kolom jadwal_ketersediaan_id tidak ada
        $sql = "INSERT INTO " . self::$table_name .
            " (pemesanan_tiket_id, jenis_tiket_id, jumlah, harga_satuan_saat_pesan, subtotal_item, created_at) 
               VALUES (?, ?, ?, ?, ?, NOW())"; // Hapus updated_at jika tidak ada, atau NOW() juga

        $stmt = mysqli_prepare(self::$db, $sql);
        if (!$stmt) {
            error_log(get_called_class() . "::create() - MySQLi Prepare Error: " . mysqli_error(self::$db));
            return false;
        }

        // Sesuaikan tipe data jika kolom jadwal_ketersediaan_id dihapus
        mysqli_stmt_bind_param(
            $stmt,
            "iiidd", // Tipe data disesuaikan
            $pemesanan_tiket_id,
            $jenis_tiket_id,
            $jumlah,
            $harga_satuan_saat_pesan,
            $subtotal_item
            // $jadwal_ketersediaan_id // Hapus jika kolom tidak ada
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

    public static function getByPemesananTiketId($pemesanan_tiket_id)
    {
        if (!self::checkDbConnection()) return [];

        $id_val = filter_var($pemesanan_tiket_id, FILTER_VALIDATE_INT);
        if ($id_val === false || $id_val <= 0) {
            error_log(get_called_class() . "::getByPemesananTiketId() - pemesanan_tiket_id tidak valid: " . $pemesanan_tiket_id);
            return [];
        }

        // PERBAIKAN DI SINI: Menggunakan w.nama sebagai nama_wisata_terkait
        $sql = "SELECT dpt.*, 
                       jt.nama_layanan_display, jt.tipe_hari, jt.deskripsi AS deskripsi_jenis_tiket, 
                       w.nama AS nama_wisata_terkait  -- Menggunakan kolom 'nama' dari tabel 'wisata'
                FROM " . self::$table_name . " dpt
                INNER JOIN jenis_tiket jt ON dpt.jenis_tiket_id = jt.id
                LEFT JOIN wisata w ON jt.wisata_id = w.id -- Pastikan jt.wisata_id adalah FK yang benar ke w.id
                WHERE dpt.pemesanan_tiket_id = ?
                ORDER BY dpt.id ASC";

        $stmt = mysqli_prepare(self::$db, $sql);
        if (!$stmt) {
            error_log(get_called_class() . "::getByPemesananTiketId() - MySQLi Prepare Error: " . mysqli_error(self::$db) . " SQL: " . $sql);
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

    public static function findById($id)
    {
        if (!self::checkDbConnection()) return null;
        $id_val = filter_var($id, FILTER_VALIDATE_INT);
        if ($id_val === false || $id_val <= 0) {
            error_log(get_called_class() . "::findById() - ID tidak valid: " . $id);
            return null;
        }

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
            mysqli_stmt_close($stmt);
            return true;
        } else {
            error_log(get_called_class() . "::deleteByPemesananTiketId() - MySQLi Execute Error: " . mysqli_stmt_error($stmt));
            mysqli_stmt_close($stmt);
            return false;
        }
    }
}
