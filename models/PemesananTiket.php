<?php
// File: C:\xampp\htdocs\Cilengkrang-Web-Wisata\models\PemesananTiket.php

class PemesananTiket
{
    // Nama tabel di database
    private static $table_name = "pemesanan_tiket";

    /**
     * Membuat pemesanan tiket baru.
     * @param array $data Array asosiatif data.
     * Kunci yang diharapkan: 'user_id', 'nama_destinasi', 'jenis_pemesanan', 'nama_item', 'jumlah_item', 'tanggal_kunjungan', 'total_harga', 'status'.
     * @return int|false ID dari record baru jika berhasil, false jika gagal.
     */
    public static function create($data)
    {
        global $conn;
        if (!$conn) {
            error_log("Koneksi database gagal di PemesananTiket::create()");
            return false;
        }

        $user_id = isset($data['user_id']) && !empty($data['user_id']) ? (int)$data['user_id'] : null;
        $nama_destinasi = trim($data['nama_destinasi'] ?? '');
        $jenis_pemesanan = trim($data['jenis_pemesanan'] ?? 'Online');
        $nama_item = trim($data['nama_item'] ?? '');
        $jumlah_item = isset($data['jumlah_item']) ? (int)$data['jumlah_item'] : 0;
        $tanggal_kunjungan = trim($data['tanggal_kunjungan'] ?? '');
        $total_harga = isset($data['total_harga']) ? (float)$data['total_harga'] : 0.00;
        $status = trim($data['status'] ?? 'pending');

        if (empty($nama_destinasi) || empty($nama_item) || empty($tanggal_kunjungan) || $jumlah_item <= 0 || $total_harga < 0) {
            error_log("PemesananTiket Create Error: Field wajib (nama_destinasi, nama_item, tanggal_kunjungan, jumlah_item, total_harga) kosong/tidak valid.");
            return false;
        }

        $sql = "INSERT INTO " . self::$table_name . "
                (user_id, nama_destinasi, jenis_pemesanan, nama_item, jumlah_item, tanggal_kunjungan, total_harga, status)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = mysqli_prepare($conn, $sql);

        if (!$stmt) {
            error_log("MySQLi Prepare Error (PemesananTiket Create): " . mysqli_error($conn));
            return false;
        }
        mysqli_stmt_bind_param($stmt, "isssisds", $user_id, $nama_destinasi, $jenis_pemesanan, $nama_item, $jumlah_item, $tanggal_kunjungan, $total_harga, $status);

        if (mysqli_stmt_execute($stmt)) {
            $new_id = mysqli_insert_id($conn);
            mysqli_stmt_close($stmt);
            return $new_id;
        } else {
            error_log("MySQLi Execute Error (PemesananTiket Create): " . mysqli_stmt_error($stmt));
            mysqli_stmt_close($stmt);
            return false;
        }
    }

    /**
     * Mengambil semua pemesanan tiket, bisa join dengan data pengguna.
     * @param mysqli|null $conn_param Koneksi database opsional.
     * @return array Array records, atau array kosong jika gagal/tidak ada records.
     */
    public static function getAll($conn_param = null)
    {
        $db_conn = $conn_param ?? $GLOBALS['conn'];
        if (!$db_conn) {
            error_log("Koneksi database gagal di PemesananTiket::getAll()");
            return [];
        }

        $sql = "SELECT pt.*, u.nama AS user_nama, u.email AS user_email
                FROM " . self::$table_name . " pt
                LEFT JOIN users u ON pt.user_id = u.id
                ORDER BY pt.created_at DESC, pt.id DESC";
        $result = mysqli_query($db_conn, $sql);

        if ($result) {
            return mysqli_fetch_all($result, MYSQLI_ASSOC);
        } else {
            error_log("MySQLi Error (PemesananTiket getAll): " . mysqli_error($db_conn));
            return [];
        }
    }

    /**
     * Mengambil satu pemesanan tiket berdasarkan ID.
     * @param int $id ID pemesanan.
     * @param mysqli|null $conn_param Koneksi database opsional.
     * @return array|null Data pemesanan atau null jika tidak ditemukan/error.
     */
    public static function getById($id, $conn_param = null)
    {
        $db_conn = $conn_param ?? $GLOBALS['conn'];
        if (!$db_conn) {
            error_log("Koneksi database gagal di PemesananTiket::getById()");
            return null;
        }

        $id_val = intval($id);
        if ($id_val <= 0) {
            error_log("PemesananTiket getById Error: ID tidak valid.");
            return null;
        }

        $sql = "SELECT pt.*, u.nama AS user_nama, u.email AS user_email
                FROM " . self::$table_name . " pt
                LEFT JOIN users u ON pt.user_id = u.id
                WHERE pt.id = ?";
        $stmt = mysqli_prepare($db_conn, $sql);

        if (!$stmt) {
            error_log("MySQLi Prepare Error (PemesananTiket getById): " . mysqli_error($db_conn));
            return null;
        }

        mysqli_stmt_bind_param($stmt, "i", $id_val);

        if (mysqli_stmt_execute($stmt)) {
            $result = mysqli_stmt_get_result($stmt);
            $pemesanan = mysqli_fetch_assoc($result);
            mysqli_stmt_close($stmt);
            return $pemesanan ?: null; // Mengembalikan null jika tidak ada hasil
        } else {
            error_log("MySQLi Execute Error (PemesananTiket getById): " . mysqli_stmt_error($stmt));
            mysqli_stmt_close($stmt);
            return null;
        }
    }


    /**
     * Menghapus pemesanan tiket berdasarkan ID.
     * @param int $id ID record yang akan dihapus.
     * @param mysqli|null $conn_param Koneksi database opsional.
     * @return bool True jika berhasil, false jika gagal.
     */
    public static function delete($id, $conn_param = null)
    {
        $db_conn = $conn_param ?? $GLOBALS['conn'];
        if (!$db_conn) return false;

        $id_to_delete = intval($id);
        if ($id_to_delete <= 0) return false;

        $sql = "DELETE FROM " . self::$table_name . " WHERE id = ?";
        $stmt = mysqli_prepare($db_conn, $sql);

        if (!$stmt) {
            error_log("MySQLi Prepare Error (PemesananTiket Delete): " . mysqli_error($db_conn));
            return false;
        }
        mysqli_stmt_bind_param($stmt, "i", $id_to_delete);

        if (mysqli_stmt_execute($stmt)) {
            $affected_rows = mysqli_stmt_affected_rows($stmt);
            mysqli_stmt_close($stmt);
            return $affected_rows > 0;
        } else {
            error_log("MySQLi Execute Error (PemesananTiket Delete): " . mysqli_stmt_error($stmt));
            mysqli_stmt_close($stmt);
            return false;
        }
    }

    /**
     * Memperbarui status pemesanan tiket.
     * @param int $id ID record.
     * @param string $status Status baru.
     * @param mysqli|null $conn_param Koneksi database opsional.
     * @return bool True jika berhasil, false jika gagal.
     */
    public static function updateStatus($id, $status, $conn_param = null)
    {
        $db_conn = $conn_param ?? $GLOBALS['conn'];
        if (!$db_conn) return false;

        $id_to_update = intval($id);
        if ($id_to_update <= 0 || empty(trim($status))) return false;

        $sql = "UPDATE " . self::$table_name . " SET status = ? WHERE id = ?";
        $stmt = mysqli_prepare($db_conn, $sql);

        if (!$stmt) {
            error_log("MySQLi Prepare Error (PemesananTiket UpdateStatus): " . mysqli_error($db_conn));
            return false;
        }
        mysqli_stmt_bind_param($stmt, "si", $status, $id_to_update);
        if (mysqli_stmt_execute($stmt)) {
            mysqli_stmt_close($stmt);
            return true;
        } else {
            error_log("MySQLi Execute Error (PemesananTiket UpdateStatus): " . mysqli_stmt_error($stmt));
            mysqli_stmt_close($stmt);
            return false;
        }
    }

    /**
     * Menghitung pemesanan tiket berdasarkan status.
     * @param string $status Status pemesanan (misal, 'pending').
     * @param mysqli|null $conn_param Koneksi database opsional.
     * @return int Jumlah total.
     */
    public static function countByStatus($status, $conn_param = null)
    {
        $db_conn = $conn_param ?? $GLOBALS['conn'];
        if (!$db_conn) return 0;

        $sql = "SELECT COUNT(id) as total FROM " . self::$table_name . " WHERE status = ?";
        $stmt = mysqli_prepare($db_conn, $sql);
        if (!$stmt) {
            error_log("MySQLi Prepare Error (PemesananTiket countByStatus): " . mysqli_error($db_conn));
            return 0;
        }
        mysqli_stmt_bind_param($stmt, "s", $status);
        if (mysqli_stmt_execute($stmt)) {
            $result = mysqli_stmt_get_result($stmt);
            $row = mysqli_fetch_assoc($result);
            mysqli_stmt_close($stmt);
            return $row['total'] ?? 0;
        }
        mysqli_stmt_close($stmt);
        return 0;
    }

    /**
     * Mengambil pemesanan tiket berdasarkan User ID.
     * @param int $user_id ID Pengguna.
     * @param mysqli|null $conn_param Koneksi database opsional.
     * @param int|null $limit Batas jumlah record yang diambil.
     * @return array Array records, atau array kosong jika gagal/tidak ada records.
     */
    public static function getByUserId($user_id, $conn_param = null, $limit = null)
    {
        $db_conn = $conn_param ?? $GLOBALS['conn'];
        if (!$db_conn) {
            error_log("Koneksi database gagal di PemesananTiket::getByUserId()");
            return [];
        }

        $user_id_val = intval($user_id);
        if ($user_id_val <= 0) {
            return [];
        }

        $sql = "SELECT pt.*, u.nama AS user_nama, u.email AS user_email
                FROM " . self::$table_name . " pt
                LEFT JOIN users u ON pt.user_id = u.id
                WHERE pt.user_id = ? ORDER BY pt.created_at DESC, pt.id DESC";
        if (is_numeric($limit) && $limit > 0) {
            $sql .= " LIMIT " . intval($limit);
        }

        $stmt = mysqli_prepare($db_conn, $sql);
        if (!$stmt) {
            error_log("MySQLi Prepare Error (PemesananTiket getByUserId): " . mysqli_error($db_conn));
            return [];
        }

        mysqli_stmt_bind_param($stmt, "i", $user_id_val);
        if (mysqli_stmt_execute($stmt)) {
            $result = mysqli_stmt_get_result($stmt);
            $data = mysqli_fetch_all($result, MYSQLI_ASSOC);
            mysqli_stmt_close($stmt);
            return $data;
        } else {
            error_log("MySQLi Execute Error (PemesananTiket getByUserId): " . mysqli_stmt_error($stmt));
            mysqli_stmt_close($stmt);
            return [];
        }
    }
}
