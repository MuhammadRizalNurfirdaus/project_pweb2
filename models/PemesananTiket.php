<?php
// File: C:\xampp\htdocs\Cilengkrang-Web-Wisata\models\PemesananTiket.php

class PemesananTiket
{
    private static $table_name = "pemesanan_tiket"; // Sesuai struktur tabel baru

    /**
     * Membuat header pemesanan tiket baru.
     * Detail item tiket dan sewa akan disimpan terpisah.
     * @param array $data Array asosiatif data.
     * Kunci yang diharapkan: 'user_id' (opsional), 'nama_pemesan_tamu' (jika user_id null),
     * 'email_pemesan_tamu' (jika user_id null), 'nohp_pemesan_tamu' (jika user_id null),
     * 'kode_pemesanan', 'tanggal_kunjungan',
     * 'status_pemesanan' (opsional, default 'pending'), 'catatan_umum_pemesanan' (opsional).
     * 'total_harga_akhir' akan diupdate terpisah setelah semua detail ditambahkan.
     * @return int|false ID dari record pemesanan tiket baru jika berhasil, false jika gagal.
     */
    public static function create($data)
    {
        global $conn;
        if (!$conn) {
            error_log("PemesananTiket::create() - Koneksi database gagal.");
            return false;
        }

        $user_id = isset($data['user_id']) && !empty($data['user_id']) ? (int)$data['user_id'] : null;
        // Ambil data tamu hanya jika user_id null
        $nama_pemesan_tamu = $user_id === null ? trim($data['nama_pemesan_tamu'] ?? null) : null;
        $email_pemesan_tamu = $user_id === null ? trim($data['email_pemesan_tamu'] ?? null) : null;
        $nohp_pemesan_tamu = $user_id === null ? trim($data['nohp_pemesan_tamu'] ?? null) : null;

        // Generate kode pemesanan jika tidak disediakan
        $kode_pemesanan = trim($data['kode_pemesanan'] ?? ('PT' . strtoupper(uniqid() . dechex(time())))); // Contoh kode unik
        $tanggal_kunjungan = trim($data['tanggal_kunjungan'] ?? '');
        $total_harga_akhir = 0; // Akan diupdate nanti setelah semua item ditambahkan
        $status_pemesanan = trim($data['status_pemesanan'] ?? 'pending');
        $catatan_umum_pemesanan = isset($data['catatan_umum_pemesanan']) ? trim($data['catatan_umum_pemesanan']) : null;

        // Validasi dasar
        if (empty($kode_pemesanan) || empty($tanggal_kunjungan) || !DateTime::createFromFormat('Y-m-d', $tanggal_kunjungan)) {
            error_log("PemesananTiket::create() - Error: Kode pemesanan atau tanggal kunjungan tidak valid.");
            return false;
        }
        if ($user_id === null && (empty($nama_pemesan_tamu) || empty($email_pemesan_tamu) || empty($nohp_pemesan_tamu) || !filter_var($email_pemesan_tamu, FILTER_VALIDATE_EMAIL))) {
            error_log("PemesananTiket::create() - Error: Untuk tamu, nama, email (valid), dan no. HP wajib diisi.");
            return false;
        }

        $sql = "INSERT INTO " . self::$table_name .
            " (user_id, nama_pemesan_tamu, email_pemesan_tamu, nohp_pemesan_tamu, kode_pemesanan, tanggal_kunjungan, total_harga_akhir, status_pemesanan, catatan_umum_pemesanan) 
               VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = mysqli_prepare($conn, $sql);

        if (!$stmt) {
            error_log("PemesananTiket::create() - MySQLi Prepare Error: " . mysqli_error($conn) . " | SQL: " . $sql);
            return false;
        }

        // Tipe data: i (user_id), s, s, s, s, s, i (total_harga_akhir), s, s
        mysqli_stmt_bind_param(
            $stmt,
            "isssssiss",
            $user_id,
            $nama_pemesan_tamu,
            $email_pemesan_tamu,
            $nohp_pemesan_tamu,
            $kode_pemesanan,
            $tanggal_kunjungan,
            $total_harga_akhir,
            $status_pemesanan,
            $catatan_umum_pemesanan
        );

        if (mysqli_stmt_execute($stmt)) {
            $new_id = mysqli_insert_id($conn);
            mysqli_stmt_close($stmt);
            return $new_id;
        } else {
            error_log("PemesananTiket::create() - MySQLi Execute Error: " . mysqli_stmt_error($stmt) . " | SQL: " . $sql);
            if (mysqli_errno($conn) == 1062) { // Error duplicate entry untuk kode_pemesanan
                error_log("PemesananTiket::create() - Kode Pemesanan Duplikat: " . $kode_pemesanan);
                // Anda bisa mencoba generate kode baru atau memberi tahu user
            }
            mysqli_stmt_close($stmt);
            return false;
        }
    }

    /**
     * Mengambil semua header pemesanan tiket, di-join dengan data pengguna.
     * @return array Array data pemesanan tiket, atau array kosong jika gagal/tidak ada.
     */
    public static function getAll()
    {
        global $conn;
        if (!$conn) {
            error_log("PemesananTiket::getAll() - Koneksi database gagal.");
            return [];
        }

        $sql = "SELECT pt.*, u.nama AS user_nama, u.email AS user_email 
                FROM " . self::$table_name . " pt
                LEFT JOIN users u ON pt.user_id = u.id
                ORDER BY pt.created_at DESC, pt.id DESC";
        $result = mysqli_query($conn, $sql);

        if ($result) {
            return mysqli_fetch_all($result, MYSQLI_ASSOC);
        } else {
            error_log("PemesananTiket::getAll() - MySQLi Query Error: " . mysqli_error($conn) . " | SQL: " . $sql);
            return [];
        }
    }

    /**
     * Mengambil satu header pemesanan tiket berdasarkan ID, di-join dengan data pengguna.
     * @param int $id ID pemesanan tiket.
     * @return array|null Data pemesanan tiket atau null jika tidak ditemukan/error.
     */
    public static function getById($id)
    {
        global $conn;
        if (!$conn) {
            error_log("PemesananTiket::getById({$id}) - Koneksi database gagal.");
            return null;
        }
        $id_val = filter_var($id, FILTER_VALIDATE_INT);
        if ($id_val === false || $id_val <= 0) {
            error_log("PemesananTiket::getById() - ID tidak valid: '" . e($id) . "'.");
            return null;
        }

        $sql = "SELECT pt.*, u.nama AS user_nama, u.email AS user_email 
                FROM " . self::$table_name . " pt
                LEFT JOIN users u ON pt.user_id = u.id
                WHERE pt.id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        if (!$stmt) {
            error_log("PemesananTiket::getById({$id_val}) - MySQLi Prepare Error: " . mysqli_error($conn) . " | SQL: " . $sql);
            return null;
        }
        mysqli_stmt_bind_param($stmt, "i", $id_val);
        if (mysqli_stmt_execute($stmt)) {
            $result = mysqli_stmt_get_result($stmt);
            $pemesanan = mysqli_fetch_assoc($result);
            mysqli_stmt_close($stmt);
            return $pemesanan ?: null;
        } else {
            error_log("PemesananTiket::getById({$id_val}) - MySQLi Execute Error: " . mysqli_stmt_error($stmt) . " | SQL: " . $sql);
            mysqli_stmt_close($stmt);
            return null;
        }
    }

    /**
     * Mengambil satu header pemesanan tiket berdasarkan KODE PEMESANAN.
     * @param string $kode_pemesanan Kode unik pemesanan.
     * @return array|null Data pemesanan tiket atau null jika tidak ditemukan/error.
     */
    public static function getByKodePemesanan($kode_pemesanan)
    {
        global $conn;
        if (!$conn) {
            error_log("PemesananTiket::getByKodePemesanan() - Koneksi database gagal.");
            return null;
        }
        $kode = trim($kode_pemesanan);
        if (empty($kode)) {
            error_log("PemesananTiket::getByKodePemesanan() - Kode pemesanan kosong.");
            return null;
        }

        $sql = "SELECT pt.*, u.nama AS user_nama, u.email AS user_email 
                FROM " . self::$table_name . " pt
                LEFT JOIN users u ON pt.user_id = u.id
                WHERE pt.kode_pemesanan = ?";
        $stmt = mysqli_prepare($conn, $sql);
        if (!$stmt) {
            error_log("PemesananTiket::getByKodePemesanan() - MySQLi Prepare Error: " . mysqli_error($conn));
            return null;
        }
        mysqli_stmt_bind_param($stmt, "s", $kode);
        if (mysqli_stmt_execute($stmt)) {
            $result = mysqli_stmt_get_result($stmt);
            $pemesanan = mysqli_fetch_assoc($result);
            mysqli_stmt_close($stmt);
            return $pemesanan ?: null;
        } else {
            error_log("PemesananTiket::getByKodePemesanan() - MySQLi Execute Error: " . mysqli_stmt_error($stmt));
            mysqli_stmt_close($stmt);
            return null;
        }
    }


    /**
     * Memperbarui status pemesanan tiket.
     * @param int $id ID pemesanan tiket.
     * @param string $status Status baru.
     * @return bool True jika berhasil, false jika gagal.
     */
    public static function updateStatus($id, $status)
    {
        global $conn;
        if (!$conn) {
            error_log("PemesananTiket::updateStatus() - Koneksi database gagal.");
            return false;
        }
        $id_val = filter_var($id, FILTER_VALIDATE_INT);
        $status_val = trim($status);
        // Sesuaikan ENUM status ini dengan yang ada di tabel pemesanan_tiket Anda
        $allowed_statuses = ['pending', 'waiting_payment', 'paid', 'confirmed', 'completed', 'cancelled', 'expired'];

        if ($id_val === false || $id_val <= 0 || empty($status_val) || !in_array($status_val, $allowed_statuses)) {
            error_log("PemesananTiket::updateStatus() - Input tidak valid. ID: {$id_val}, Status: '{$status_val}'");
            return false;
        }

        $sql = "UPDATE " . self::$table_name . " SET status_pemesanan = ?, updated_at = NOW() WHERE id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        if (!$stmt) {
            error_log("PemesananTiket::updateStatus() - Prepare Error: " . mysqli_error($conn));
            return false;
        }
        mysqli_stmt_bind_param($stmt, "si", $status_val, $id_val);
        if (mysqli_stmt_execute($stmt)) {
            mysqli_stmt_close($stmt);
            return true;
        } else {
            error_log("PemesananTiket::updateStatus() - Execute Error: " . mysqli_stmt_error($stmt));
            mysqli_stmt_close($stmt);
            return false;
        }
    }

    /**
     * Memperbarui total harga akhir pemesanan tiket.
     * Ini akan dipanggil setelah semua item tiket dan sewa dihitung.
     * @param int $pemesanan_tiket_id
     * @param int $total_harga_baru
     * @return bool
     */
    public static function updateTotalHargaAkhir($pemesanan_tiket_id, $total_harga_baru)
    {
        global $conn;
        if (!$conn) {
            error_log("PemesananTiket::updateTotalHargaAkhir() - Koneksi DB gagal.");
            return false;
        }
        $id_val = filter_var($pemesanan_tiket_id, FILTER_VALIDATE_INT);
        $harga_val = filter_var($total_harga_baru, FILTER_VALIDATE_INT);

        if ($id_val === false || $id_val <= 0 || $harga_val === false || $harga_val < 0) {
            error_log("PemesananTiket::updateTotalHargaAkhir() - Input tidak valid. ID: {$id_val}, Harga: {$harga_val}");
            return false;
        }

        $sql = "UPDATE " . self::$table_name . " SET total_harga_akhir = ?, updated_at = NOW() WHERE id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        if (!$stmt) {
            error_log("PemesananTiket::updateTotalHargaAkhir() - Prepare Error: " . mysqli_error($conn));
            return false;
        }
        mysqli_stmt_bind_param($stmt, "ii", $harga_val, $id_val);
        if (mysqli_stmt_execute($stmt)) {
            mysqli_stmt_close($stmt);
            return true;
        }
        error_log("PemesananTiket::updateTotalHargaAkhir() - Execute Error: " . mysqli_stmt_error($stmt));
        mysqli_stmt_close($stmt);
        return false;
    }

    /**
     * Menghapus pemesanan tiket berdasarkan ID.
     * Ini juga akan menghapus detail terkait (detail_pemesanan_tiket, pemesanan_sewa_alat, pembayaran)
     * JIKA foreign key constraint di database di-set dengan ON DELETE CASCADE.
     * Jika tidak, Anda perlu menghapusnya secara manual di sini atau di Controller.
     * @param int $id ID pemesanan tiket.
     * @return bool True jika berhasil, false jika gagal.
     */
    public static function delete($id)
    {
        global $conn;
        if (!$conn) {
            error_log("PemesananTiket::delete() - Koneksi database gagal.");
            return false;
        }
        $id_val = filter_var($id, FILTER_VALIDATE_INT);
        if ($id_val === false || $id_val <= 0) {
            error_log("PemesananTiket::delete() - ID tidak valid: " . e($id));
            return false;
        }

        // Jika tidak ada ON DELETE CASCADE di DB untuk tabel detail:
        // require_once __DIR__ . '/DetailPemesananTiket.php';
        // DetailPemesananTiket::deleteByPemesananTiketId($id_val);
        // require_once __DIR__ . '/PemesananSewaAlat.php';
        // PemesananSewaAlat::deleteByPemesananTiketId($id_val); // Pastikan stok dikembalikan
        // require_once __DIR__ . '/Pembayaran.php';
        // Pembayaran::deleteByPemesananTiketId($id_val);


        $sql = "DELETE FROM " . self::$table_name . " WHERE id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        if (!$stmt) {
            error_log("PemesananTiket::delete() - Prepare Error: " . mysqli_error($conn));
            return false;
        }
        mysqli_stmt_bind_param($stmt, "i", $id_val);
        if (mysqli_stmt_execute($stmt)) {
            $affected_rows = mysqli_stmt_affected_rows($stmt);
            mysqli_stmt_close($stmt);
            return $affected_rows > 0;
        } else {
            error_log("PemesananTiket::delete() - Execute Error: " . mysqli_stmt_error($stmt));
            mysqli_stmt_close($stmt);
            return false;
        }
    }

    public static function countByStatus($status, $conn_param = null)
    { /* ... sama seperti sebelumnya ... */
        global $conn;
        $db_conn = $conn_param ?? $conn;
        if (!$db_conn) return 0;
        $sql = "SELECT COUNT(id) as total FROM " . self::$table_name . " WHERE status_pemesanan = ?";
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
    public static function getByUserId($user_id, $conn_param = null, $limit = null)
    { /* ... sama seperti sebelumnya ... */
        global $conn;
        $db_conn = $conn_param ?? $conn;
        if (!$db_conn) {
            return [];
        }
        $user_id_val = intval($user_id);
        if ($user_id_val <= 0) {
            return [];
        }
        $sql = "SELECT pt.*, u.nama AS user_nama, u.email AS user_email FROM " . self::$table_name . " pt LEFT JOIN users u ON pt.user_id = u.id WHERE pt.user_id = ? ORDER BY pt.created_at DESC, pt.id DESC";
        if (is_numeric($limit) && $limit > 0) {
            $sql .= " LIMIT " . intval($limit);
        }
        $stmt = mysqli_prepare($db_conn, $sql);
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
}
