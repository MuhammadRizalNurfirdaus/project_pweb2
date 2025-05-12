<?php
// File: C:\xampp\htdocs\Cilengkrang-Web-Wisata\models\PemesananTiket.php

class PemesananTiket
{
    private static $table_name = "pemesanan_tiket";

    /**
     * Membuat header pemesanan tiket baru.
     * @param array $data Data pemesanan.
     * @return int|false ID pemesanan baru atau false jika gagal.
     */
    public static function create($data)
    {
        global $conn;
        if (!$conn) {
            error_log("PemesananTiket::create() - Koneksi database gagal.");
            return false;
        }

        $user_id = isset($data['user_id']) && !empty($data['user_id']) ? (int)$data['user_id'] : null;
        $nama_pemesan_tamu = $user_id === null ? trim($data['nama_pemesan_tamu'] ?? '') : null;
        $email_pemesan_tamu = $user_id === null ? trim($data['email_pemesan_tamu'] ?? '') : null;
        $nohp_pemesan_tamu = $user_id === null ? trim($data['nohp_pemesan_tamu'] ?? '') : null;

        $kode_pemesanan = trim($data['kode_pemesanan'] ?? ('PT' . strtoupper(uniqid() . dechex(time()))));
        $tanggal_kunjungan = trim($data['tanggal_kunjungan'] ?? '');
        $total_harga_akhir = 0;
        // PERBAIKAN: Gunakan 'status' jika itu nama kolom di DB dan key di $data
        $status_input = trim($data['status'] ?? ($data['status_pemesanan'] ?? 'pending')); // Handle kedua kemungkinan key dari input
        $catatan_umum_pemesanan = isset($data['catatan_umum_pemesanan']) ? trim($data['catatan_umum_pemesanan']) : null;

        if (empty($kode_pemesanan) || empty($tanggal_kunjungan) || !DateTime::createFromFormat('Y-m-d', $tanggal_kunjungan) || ($user_id === null && (empty($nama_pemesan_tamu) || empty($email_pemesan_tamu) || empty($nohp_pemesan_tamu) || !filter_var($email_pemesan_tamu, FILTER_VALIDATE_EMAIL)))) {
            error_log("PemesananTiket::create() - Validasi input gagal.");
            return false;
        }
        $allowed_statuses = ['pending', 'waiting_payment', 'paid', 'confirmed', 'completed', 'cancelled', 'expired'];
        if (!in_array($status_input, $allowed_statuses)) {
            error_log("PemesananTiket::create() - Status pemesanan tidak valid: " . e($status_input));
            return false;
        }

        // PERBAIKAN: Gunakan kolom 'status' di SQL
        $sql = "INSERT INTO " . self::$table_name .
            " (user_id, nama_pemesan_tamu, email_pemesan_tamu, nohp_pemesan_tamu, kode_pemesanan, tanggal_kunjungan, total_harga_akhir, status, catatan_umum_pemesanan, created_at, updated_at) 
               VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";
        $stmt = mysqli_prepare($conn, $sql);

        if (!$stmt) {
            error_log("PemesananTiket::create() - MySQLi Prepare Error: " . mysqli_error($conn) . " | SQL: " . $sql);
            return false;
        }

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
            $status_input, // Kirim status yang sudah divalidasi
            $catatan_umum_pemesanan
        );

        if (mysqli_stmt_execute($stmt)) {
            $new_id = mysqli_insert_id($conn);
            mysqli_stmt_close($stmt);
            return $new_id;
        } else {
            error_log("PemesananTiket::create() - MySQLi Execute Error: " . mysqli_stmt_error($stmt) . " | SQL: " . $sql);
            mysqli_stmt_close($stmt);
            return false;
        }
    }

    public static function getAll()
    {
        global $conn;
        if (!$conn) {
            error_log("PemesananTiket::getAll() - Koneksi database gagal.");
            return [];
        }
        // Pastikan query SELECT mengambil kolom 'status' dengan benar
        $sql = "SELECT pt.*, u.nama AS user_nama, u.email AS user_email 
                FROM " . self::$table_name . " pt
                LEFT JOIN users u ON pt.user_id = u.id
                ORDER BY pt.created_at DESC, pt.id DESC";
        $result = mysqli_query($conn, $sql);
        if ($result) {
            $data = mysqli_fetch_all($result, MYSQLI_ASSOC);
            mysqli_free_result($result);
            return $data;
        } else {
            error_log("PemesananTiket::getAll() - MySQLi Query Error: " . mysqli_error($conn));
            return [];
        }
    }

    public static function getById($id)
    {
        global $conn;
        if (!$conn) {
            error_log("PemesananTiket::getById() - Koneksi gagal.");
            return null;
        }
        $id_val = filter_var($id, FILTER_VALIDATE_INT);
        if ($id_val === false || $id_val <= 0) {
            error_log("PemesananTiket::getById() - ID tidak valid: " . e($id));
            return null;
        }
        // Pastikan query SELECT mengambil kolom 'status'
        $sql = "SELECT pt.*, u.nama AS user_nama, u.email AS user_email 
                FROM " . self::$table_name . " pt
                LEFT JOIN users u ON pt.user_id = u.id
                WHERE pt.id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        if (!$stmt) {
            error_log("PemesananTiket::getById() Prepare Error: " . mysqli_error($conn));
            return null;
        }
        mysqli_stmt_bind_param($stmt, "i", $id_val);
        if (mysqli_stmt_execute($stmt)) {
            $result = mysqli_stmt_get_result($stmt);
            $pemesanan = mysqli_fetch_assoc($result);
            mysqli_stmt_close($stmt);
            return $pemesanan ?: null;
        } else {
            error_log("PemesananTiket::getById() Execute Error: " . mysqli_stmt_error($stmt));
            mysqli_stmt_close($stmt);
            return null;
        }
    }

    public static function getByKodePemesanan($kode_pemesanan)
    {
        global $conn;
        if (!$conn) return null;
        $kode = trim($kode_pemesanan);
        if (empty($kode)) return null;
        $sql = "SELECT pt.*, u.nama AS user_nama, u.email AS user_email FROM " . self::$table_name . " pt LEFT JOIN users u ON pt.user_id = u.id WHERE pt.kode_pemesanan = ?";
        $stmt = mysqli_prepare($conn, $sql);
        if (!$stmt) {
            error_log("PemesananTiket::getByKodePemesanan() Prepare Error: " . mysqli_error($conn));
            return null;
        }
        mysqli_stmt_bind_param($stmt, "s", $kode);
        if (mysqli_stmt_execute($stmt)) {
            $result = mysqli_stmt_get_result($stmt);
            $data = mysqli_fetch_assoc($result);
            mysqli_stmt_close($stmt);
            return $data ?: null;
        }
        error_log("PemesananTiket::getByKodePemesanan() Execute Error: " . mysqli_stmt_error($stmt));
        mysqli_stmt_close($stmt);
        return null;
    }

    public static function getByUserId($user_id, $limit = null)
    {
        global $conn;
        if (!$conn) return [];
        $user_id_val = filter_var($user_id, FILTER_VALIDATE_INT);
        if ($user_id_val === false || $user_id_val <= 0) return [];
        $sql = "SELECT pt.*, u.nama AS user_nama, u.email AS user_email FROM " . self::$table_name . " pt LEFT JOIN users u ON pt.user_id = u.id WHERE pt.user_id = ? ORDER BY pt.created_at DESC, pt.id DESC";
        $params = [$user_id_val];
        $types = "i";
        if (is_numeric($limit) && $limit > 0) {
            $sql .= " LIMIT ?";
            $params[] = (int)$limit;
            $types .= "i";
        }
        $stmt = mysqli_prepare($conn, $sql);
        if (!$stmt) {
            error_log("PemesananTiket::getByUserId() Prepare Error: " . mysqli_error($conn));
            return [];
        }
        mysqli_stmt_bind_param($stmt, $types, ...$params);
        if (mysqli_stmt_execute($stmt)) {
            $result = mysqli_stmt_get_result($stmt);
            $data = mysqli_fetch_all($result, MYSQLI_ASSOC);
            mysqli_stmt_close($stmt);
            return $data;
        }
        error_log("PemesananTiket::getByUserId() Execute Error: " . mysqli_stmt_error($stmt));
        mysqli_stmt_close($stmt);
        return [];
    }

    /**
     * Memperbarui status pemesanan tiket.
     * @param int $id ID pemesanan tiket.
     * @param string $new_status Status baru.
     * @return bool True jika berhasil, false jika gagal.
     */
    public static function updateStatus($id, $new_status)
    {
        global $conn;
        if (!$conn) {
            return false;
        }
        $id_val = filter_var($id, FILTER_VALIDATE_INT);
        $status_val = trim($new_status);
        $allowed_statuses = ['pending', 'waiting_payment', 'paid', 'confirmed', 'completed', 'cancelled', 'expired'];
        if ($id_val === false || $id_val <= 0 || empty($status_val) || !in_array($status_val, $allowed_statuses)) {
            error_log("PemesananTiket::updateStatus() - Input tidak valid. ID: " . e($id) . ", Status: '" . e($status_val) . "'");
            return false;
        }
        // PERBAIKAN: Update kolom 'status'
        $sql = "UPDATE " . self::$table_name . " SET status = ?, updated_at = NOW() WHERE id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        if (!$stmt) {
            error_log("PemesananTiket::updateStatus() Prepare Error: " . mysqli_error($conn));
            return false;
        }
        mysqli_stmt_bind_param($stmt, "si", $status_val, $id_val);
        if (mysqli_stmt_execute($stmt)) {
            mysqli_stmt_close($stmt);
            return true;
        }
        error_log("PemesananTiket::updateStatus() Execute Error: " . mysqli_stmt_error($stmt));
        mysqli_stmt_close($stmt);
        return false;
    }

    public static function updateTotalHargaAkhir($pemesanan_tiket_id, $total_harga_baru)
    {
        global $conn;
        if (!$conn) {
            return false;
        }
        $id_val = filter_var($pemesanan_tiket_id, FILTER_VALIDATE_INT);
        $harga_val = filter_var($total_harga_baru, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]);
        if ($id_val === false || $id_val <= 0 || $harga_val === false) {
            error_log("PemesananTiket::updateTotalHargaAkhir() - Input tidak valid.");
            return false;
        }
        $sql = "UPDATE " . self::$table_name . " SET total_harga_akhir = ?, updated_at = NOW() WHERE id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        if (!$stmt) {
            error_log("PemesananTiket::updateTotalHargaAkhir() Prepare Error: " . mysqli_error($conn));
            return false;
        }
        mysqli_stmt_bind_param($stmt, "ii", $harga_val, $id_val);
        if (mysqli_stmt_execute($stmt)) {
            mysqli_stmt_close($stmt);
            return true;
        }
        error_log("PemesananTiket::updateTotalHargaAkhir() Execute Error: " . mysqli_stmt_error($stmt));
        mysqli_stmt_close($stmt);
        return false;
    }

    public static function delete($id)
    {
        global $conn;
        if (!$conn) {
            return false;
        }
        $id_val = filter_var($id, FILTER_VALIDATE_INT);
        if ($id_val === false || $id_val <= 0) {
            return false;
        }
        $tables_to_delete_from = ["detail_pemesanan_tiket" => "pemesanan_tiket_id", "pembayaran" => "pemesanan_tiket_id"];
        foreach ($tables_to_delete_from as $table => $foreign_key) {
            $sql_delete_related = "DELETE FROM {$table} WHERE {$foreign_key} = ?";
            $stmt_related = mysqli_prepare($conn, $sql_delete_related);
            if ($stmt_related) {
                mysqli_stmt_bind_param($stmt_related, "i", $id_val);
                mysqli_stmt_execute($stmt_related);
                mysqli_stmt_close($stmt_related);
            }
        }
        $sql = "DELETE FROM " . self::$table_name . " WHERE id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        if (!$stmt) {
            error_log("PemesananTiket::delete() Prepare Error: " . mysqli_error($conn));
            return false;
        }
        mysqli_stmt_bind_param($stmt, "i", $id_val);
        if (mysqli_stmt_execute($stmt)) {
            $affected_rows = mysqli_stmt_affected_rows($stmt);
            mysqli_stmt_close($stmt);
            return $affected_rows > 0;
        }
        error_log("PemesananTiket::delete() Execute Error: " . mysqli_stmt_error($stmt));
        mysqli_stmt_close($stmt);
        return false;
    }

    /**
     * Menghitung jumlah pemesanan tiket berdasarkan status.
     * @param string $status_filter Status yang ingin dihitung (sesuai nama kolom di DB).
     * @return int Jumlah pemesanan.
     */
    public static function countByStatus($status_filter)
    {
        global $conn;
        if (!$conn || empty($status_filter)) {
            error_log("PemesananTiket::countByStatus() - Koneksi atau status tidak valid.");
            return 0;
        }
        // PERBAIKAN: Gunakan kolom 'status' di query
        $sql = "SELECT COUNT(id) as total FROM " . self::$table_name . " WHERE status = ?";
        $stmt = mysqli_prepare($conn, $sql);
        if (!$stmt) {
            error_log("PemesananTiket::countByStatus() - MySQLi Prepare Error: " . mysqli_error($conn));
            return 0;
        }
        mysqli_stmt_bind_param($stmt, "s", $status_filter);
        if (mysqli_stmt_execute($stmt)) {
            $result = mysqli_stmt_get_result($stmt);
            $row = mysqli_fetch_assoc($result);
            mysqli_stmt_close($stmt);
            return (int)($row['total'] ?? 0);
        }
        error_log("PemesananTiket::countByStatus() - MySQLi Execute Error: " . mysqli_stmt_error($stmt));
        mysqli_stmt_close($stmt);
        return 0;
    }

    public static function countAll()
    {
        global $conn;
        if (!$conn) {
            error_log("PemesananTiket::countAll() - Koneksi DB gagal.");
            return 0;
        }
        $sql = "SELECT COUNT(id) as total FROM " . self::$table_name;
        $result = mysqli_query($conn, $sql);
        if ($result) {
            $row = mysqli_fetch_assoc($result);
            mysqli_free_result($result);
            return (int)($row['total'] ?? 0);
        } else {
            error_log("PemesananTiket::countAll() - MySQLi Query Error: " . mysqli_error($conn));
            return 0;
        }
    }
}
