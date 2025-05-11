<?php
// File: C:\xampp\htdocs\Cilengkrang-Web-Wisata\models\PemesananTiket.php
// Nama class dan file diubah dari Booking menjadi PemesananTiket

class PemesananTiket
{
    // Nama tabel di database. Sesuaikan jika Anda mengubah nama tabel di DB.
    private static $table_name = "pemesanan_tiket"; // Pastikan nama tabel sesuai dengan yang ada di database

    /**
     * Creates a new pemesanan tiket.
     * @param array $data Associative array of data.
     * Expected keys: 'user_id' (optional, int), 'nama_wisata' (string),
     * 'tanggal_kunjungan' (string YYYY-MM-DD), 'jumlah_orang' (int), 'status' (optional, string),
     * 'catatan' (optional, string), 'nama_lengkap_tamu', 'email_tamu', 'no_hp_tamu' (jika untuk tamu).
     * @return int|false ID of the new record on success, false on failure.
     */
    public static function create($data)
    {
        global $conn;
        if (!$conn) {
            error_log("Koneksi database gagal di PemesananTiket::create()");
            return false;
        }

        // Ambil dan sanitasi data
        $user_id = isset($data['user_id']) && !empty($data['user_id']) ? (int)$data['user_id'] : null;
        $nama_wisata = trim($data['nama_wisata'] ?? '');
        $tanggal_kunjungan = trim($data['tanggal_kunjungan'] ?? '');
        $jumlah_orang = isset($data['jumlah_orang']) ? (int)$data['jumlah_orang'] : 0;
        $status = trim($data['status'] ?? 'pending');
        $catatan = trim($data['catatan'] ?? '');

        // Kolom tambahan untuk tamu (jika user_id null)
        $nama_lengkap_tamu = trim($data['nama_lengkap_tamu'] ?? null);
        $email_tamu = trim($data['email_tamu'] ?? null);
        $no_hp_tamu = trim($data['no_hp_tamu'] ?? null);


        // Validasi dasar
        if (empty($nama_wisata) || empty($tanggal_kunjungan) || $jumlah_orang <= 0) {
            error_log("PemesananTiket Create Error: Field wajib nama_wisata, tanggal_kunjungan, atau jumlah_orang kosong/tidak valid.");
            return false;
        }
        // Jika user_id tidak ada (tamu), maka nama, email, no_hp tamu menjadi wajib
        if (is_null($user_id) && (empty($nama_lengkap_tamu) || empty($email_tamu) || empty($no_hp_tamu))) {
            error_log("PemesananTiket Create Error: Untuk tamu, nama, email, dan no hp wajib diisi.");
            return false;
        }
        if (!is_null($email_tamu) && !empty($email_tamu) && !filter_var($email_tamu, FILTER_VALIDATE_EMAIL)) {
            error_log("PemesananTiket Create Error: Format email tamu tidak valid.");
            return false;
        }


        // Sesuaikan query SQL dengan kolom yang ada di tabel 'bookings' Anda
        // Jika Anda menambahkan kolom untuk tamu, masukkan di sini
        $sql = "INSERT INTO " . self::$table_name . " 
                (user_id, nama_wisata, tanggal_kunjungan, jumlah_orang, status, catatan, nama_lengkap_tamu, email_tamu, no_hp_tamu)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = mysqli_prepare($conn, $sql);

        if (!$stmt) {
            error_log("MySQLi Prepare Error (PemesananTiket Create): " . mysqli_error($conn));
            return false;
        }

        mysqli_stmt_bind_param($stmt, "ississsss", $user_id, $nama_wisata, $tanggal_kunjungan, $jumlah_orang, $status, $catatan, $nama_lengkap_tamu, $email_tamu, $no_hp_tamu);

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
     * Retrieves all pemesanan tiket, optionally joining with user data.
     * @param mysqli|null $conn_param Koneksi database opsional.
     * @return array Array of records, or empty array on failure/no records.
     */
    public static function getAll($conn_param = null)
    {
        $db_conn = $conn_param ?? $GLOBALS['conn'];
        if (!$db_conn) {
            error_log("Koneksi database gagal di PemesananTiket::getAll()");
            return [];
        }

        // Sesuaikan query jika Anda menambahkan kolom tamu
        $sql = "SELECT b.*, u.nama AS user_nama, u.email AS user_email 
                FROM " . self::$table_name . " b
                LEFT JOIN users u ON b.user_id = u.id
                ORDER BY b.tanggal_kunjungan DESC, b.created_at DESC, b.id DESC"; // Tambah created_at untuk urutan sekunder
        $result = mysqli_query($db_conn, $sql);

        if ($result) {
            return mysqli_fetch_all($result, MYSQLI_ASSOC);
        } else {
            error_log("MySQLi Error (PemesananTiket getAll): " . mysqli_error($db_conn));
            return [];
        }
    }

    /**
     * Deletes a pemesanan tiket by its ID.
     * @param int $id The ID of the record to delete.
     * @param mysqli|null $conn_param Koneksi database opsional.
     * @return bool True on success, false on failure.
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
     * Updates the status of a pemesanan tiket.
     * @param int $id The ID of the record.
     * @param string $status The new status.
     * @param mysqli|null $conn_param Koneksi database opsional.
     * @return bool True on success, false on failure.
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
     * Counts pemesanan tiket by status.
     * @param string $status Status pemesanan (e.g., 'pending').
     * @param mysqli|null $conn_param Koneksi database opsional.
     * @return int Total count.
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
}
