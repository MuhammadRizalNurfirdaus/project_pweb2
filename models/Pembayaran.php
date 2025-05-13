<?php
// File: C:\xampp\htdocs\Cilengkrang-Web-Wisata\models\Pembayaran.php

/**
 * Class Pembayaran
 * Mengelola operasi database untuk tabel pembayaran.
 */
class Pembayaran
{
    private static $table_name = 'pembayaran';
    private static $db; // Properti untuk menyimpan koneksi database

    // Daftar status yang diizinkan
    private const ALLOWED_STATUSES = [
        'pending',
        'success',
        'failed',
        'expired',
        'refunded',
        'awaiting_confirmation',
        'paid',
        'confirmed',
        'cancelled'
    ];
    private const SUCCESSFUL_PAYMENT_STATUSES = ['success', 'paid', 'confirmed'];


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
     * Membuat record pembayaran baru.
     * @param array $data Data pembayaran. Kunci yang diharapkan: pemesanan_tiket_id, jumlah_dibayar.
     *                    Opsional: kode_pemesanan, status_pembayaran, metode_pembayaran, waktu_pembayaran,
     *                              bukti_pembayaran, id_transaksi_gateway, nomor_virtual_account, catatan_admin.
     * @return int|false ID pembayaran baru atau false jika gagal.
     */
    public static function create($data)
    {
        if (!self::checkDbConnection()) return false;

        $pemesanan_tiket_id = isset($data['pemesanan_tiket_id']) ? (int)$data['pemesanan_tiket_id'] : 0;
        $jumlah_dibayar = isset($data['jumlah_dibayar']) ? (float)$data['jumlah_dibayar'] : 0.0;
        $status_pembayaran = isset($data['status_pembayaran']) ? strtolower(trim($data['status_pembayaran'])) : 'pending';
        $metode_pembayaran = $data['metode_pembayaran'] ?? null;
        $kode_pemesanan = $data['kode_pemesanan'] ?? null;
        $waktu_pembayaran = $data['waktu_pembayaran'] ?? null;
        $bukti_pembayaran = $data['bukti_pembayaran'] ?? null;
        $id_transaksi_gateway = $data['id_transaksi_gateway'] ?? null;
        $nomor_virtual_account = $data['nomor_virtual_account'] ?? null;
        $catatan_admin = $data['catatan_admin'] ?? null;

        if ($pemesanan_tiket_id <= 0) {
            error_log(get_called_class() . "::create() - Error: pemesanan_tiket_id tidak valid (" . $pemesanan_tiket_id . ").");
            return false;
        }
        if ($jumlah_dibayar < 0) {
            error_log(get_called_class() . "::create() - Error: jumlah_dibayar tidak boleh negatif.");
            return false;
        }
        if (!in_array($status_pembayaran, self::ALLOWED_STATUSES)) {
            error_log(get_called_class() . "::create() - Error: Status awal tidak valid: " . htmlspecialchars($status_pembayaran));
            return false;
        }
        if ($waktu_pembayaran === null && in_array($status_pembayaran, self::SUCCESSFUL_PAYMENT_STATUSES)) {
            $waktu_pembayaran = date('Y-m-d H:i:s');
        }

        $sql = "INSERT INTO " . self::$table_name . " 
                    (pemesanan_tiket_id, kode_pemesanan, jumlah_dibayar, status_pembayaran, metode_pembayaran, 
                     waktu_pembayaran, bukti_pembayaran, id_transaksi_gateway, nomor_virtual_account, catatan_admin, 
                     created_at, updated_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";

        $stmt = mysqli_prepare(self::$db, $sql);
        if (!$stmt) {
            error_log(get_called_class() . "::create() - MySQLi Prepare Error: " . mysqli_error(self::$db));
            return false;
        }

        mysqli_stmt_bind_param(
            $stmt,
            "isdsssssss",
            $pemesanan_tiket_id,
            $kode_pemesanan,
            $jumlah_dibayar,
            $status_pembayaran,
            $metode_pembayaran,
            $waktu_pembayaran,
            $bukti_pembayaran,
            $id_transaksi_gateway,
            $nomor_virtual_account,
            $catatan_admin
        );

        if (mysqli_stmt_execute($stmt)) {
            $newId = mysqli_insert_id(self::$db);
            mysqli_stmt_close($stmt);
            return $newId;
        } else {
            error_log(get_called_class() . "::create() - MySQLi Execute Error: " . mysqli_stmt_error($stmt));
            mysqli_stmt_close($stmt);
            return false;
        }
    }

    /**
     * Mencari pembayaran berdasarkan ID.
     * @param int $id ID pembayaran.
     * @return array|null Data pembayaran atau null.
     */
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
            $pembayaran = mysqli_fetch_assoc($result);
            mysqli_stmt_close($stmt);
            return $pembayaran ?: null;
        } else {
            error_log(get_called_class() . "::findById() - MySQLi Execute Error: " . mysqli_stmt_error($stmt));
        }
        mysqli_stmt_close($stmt);
        return null;
    }

    /**
     * Mencari pembayaran berdasarkan ID pemesanan tiket.
     * @param int $pemesanan_tiket_id ID pemesanan tiket.
     * @return array|null Data pembayaran atau null.
     */
    public static function findByPemesananId($pemesanan_tiket_id)
    {
        if (!self::checkDbConnection()) return null;
        $id_val = filter_var($pemesanan_tiket_id, FILTER_VALIDATE_INT);
        if ($id_val === false || $id_val <= 0) {
            error_log(get_called_class() . "::findByPemesananId() - ID Pemesanan tidak valid: " . $pemesanan_tiket_id);
            return null;
        }

        $sql = "SELECT * FROM " . self::$table_name . " WHERE pemesanan_tiket_id = ? ORDER BY created_at DESC LIMIT 1";
        $stmt = mysqli_prepare(self::$db, $sql);
        if (!$stmt) {
            error_log(get_called_class() . "::findByPemesananId() - MySQLi Prepare Error: " . mysqli_error(self::$db));
            return null;
        }

        mysqli_stmt_bind_param($stmt, "i", $id_val);
        if (mysqli_stmt_execute($stmt)) {
            $result = mysqli_stmt_get_result($stmt);
            $pembayaran = mysqli_fetch_assoc($result);
            mysqli_stmt_close($stmt);
            return $pembayaran ?: null;
        } else {
            error_log(get_called_class() . "::findByPemesananId() - MySQLi Execute Error: " . mysqli_stmt_error($stmt));
        }
        mysqli_stmt_close($stmt);
        return null;
    }

    /**
     * Mencari pembayaran berdasarkan ID pemesanan tiket dan status tertentu.
     * @param int $pemesanan_tiket_id ID pemesanan tiket.
     * @param array $statusArray Array status yang dicari.
     * @return array|null Data pembayaran atau null.
     */
    public static function findByPemesananIdAndStatus($pemesanan_tiket_id, $statusArray)
    {
        if (!self::checkDbConnection() || empty($statusArray) || !is_array($statusArray)) {
            error_log(get_called_class() . "::findByPemesananIdAndStatus() - Input statusArray tidak valid.");
            return null;
        }
        $id_val = filter_var($pemesanan_tiket_id, FILTER_VALIDATE_INT);
        if ($id_val === false || $id_val <= 0) {
            error_log(get_called_class() . "::findByPemesananIdAndStatus() - ID Pemesanan tidak valid: " . $pemesanan_tiket_id);
            return null;
        }

        $valid_statuses = array_filter($statusArray, fn($s) => in_array(strtolower(trim($s)), self::ALLOWED_STATUSES));
        if (empty($valid_statuses)) {
            error_log(get_called_class() . "::findByPemesananIdAndStatus() - Tidak ada status valid yang diberikan.");
            return null;
        }

        $placeholders = implode(',', array_fill(0, count($valid_statuses), '?'));
        $types = 'i' . str_repeat('s', count($valid_statuses));
        $sql = "SELECT * FROM " . self::$table_name . " WHERE pemesanan_tiket_id = ? AND status_pembayaran IN (" . $placeholders . ") ORDER BY created_at DESC LIMIT 1";

        $stmt = mysqli_prepare(self::$db, $sql);
        if (!$stmt) {
            error_log(get_called_class() . "::findByPemesananIdAndStatus() - MySQLi Prepare Error: " . mysqli_error(self::$db));
            return null;
        }

        $params_for_bind = array_merge([$id_val], $valid_statuses);
        mysqli_stmt_bind_param($stmt, $types, ...$params_for_bind);

        if (mysqli_stmt_execute($stmt)) {
            $result = mysqli_stmt_get_result($stmt);
            $pembayaran = mysqli_fetch_assoc($result);
            mysqli_stmt_close($stmt);
            return $pembayaran ?: null;
        } else {
            error_log(get_called_class() . "::findByPemesananIdAndStatus() - MySQLi Execute Error: " . mysqli_stmt_error($stmt));
        }
        mysqli_stmt_close($stmt);
        return null;
    }

    /**
     * Mengambil semua pembayaran dengan kode pemesanan dan info user terkait.
     * @param string $orderBy Kolom dan arah pengurutan.
     * @return array Daftar pembayaran.
     */
    public static function findAllWithKodePemesanan($orderBy = 'p.created_at DESC')
    {
        if (!self::checkDbConnection()) return [];

        $allowed_order_columns = ['p.created_at', 'p.id', 'pt.kode_pemesanan', 'p.status_pembayaran', 'p.waktu_pembayaran', 'p.jumlah_dibayar', 'user_nama_pemesan', 'user_email_pemesan'];
        $order_parts = explode(' ', trim($orderBy));
        $order_column_input = $order_parts[0] ?? 'p.created_at';
        $order_column_base = preg_replace('/^[a-zA-Z_]+\./', '', $order_column_input);
        $order_column = 'p.created_at';
        if (in_array($order_column_input, $allowed_order_columns) || in_array($order_column_base, $allowed_order_columns)) {
            $order_column = $order_column_input;
        }
        $order_direction = (isset($order_parts[1]) && strtoupper($order_parts[1]) === 'ASC') ? 'ASC' : 'DESC';

        $orderBySafe = $order_column . ' ' . $order_direction;
        if ($order_column !== 'user_nama_pemesan' && $order_column !== 'user_email_pemesan') { // Hanya escape jika bukan alias dari COALESCE
            $orderBySafe = "`" . str_replace(".", "`.`", $order_column) . "` " . $order_direction;
        }

        $sql = "SELECT p.*, pt.kode_pemesanan, 
                       COALESCE(u.nama_lengkap, pt.nama_pemesan_tamu) AS user_nama_pemesan, 
                       COALESCE(u.email, pt.email_pemesan_tamu) AS user_email_pemesan
                FROM " . self::$table_name . " p 
                LEFT JOIN pemesanan_tiket pt ON p.pemesanan_tiket_id = pt.id
                LEFT JOIN users u ON pt.user_id = u.id 
                ORDER BY " . $orderBySafe;

        $result = mysqli_query(self::$db, $sql);
        if ($result) {
            $data = mysqli_fetch_all($result, MYSQLI_ASSOC);
            mysqli_free_result($result);
            return $data;
        } else {
            error_log(get_called_class() . "::findAllWithKodePemesanan() - MySQLi Query Error: " . mysqli_error(self::$db));
        }
        return [];
    }

    /**
     * Mengupdate status dan detail pembayaran.
     * @param int $id ID pembayaran.
     * @param string $status Status baru.
     * @param array $details Detail tambahan.
     * @return bool True jika berhasil.
     */
    public static function updateStatusAndDetails($id, $status, $details = [])
    {
        if (!self::checkDbConnection()) return false;
        $id_val = filter_var($id, FILTER_VALIDATE_INT);
        if ($id_val === false || $id_val <= 0) {
            error_log(get_called_class() . "::updateStatusAndDetails() - ID tidak valid: " . $id);
            return false;
        }

        $clean_status = strtolower(trim($status));
        if (empty($clean_status) || !in_array($clean_status, self::ALLOWED_STATUSES)) {
            error_log(get_called_class() . "::updateStatusAndDetails() - Status tidak valid: " . $status);
            return false;
        }

        $fields_to_update = [];
        $params = [];
        $types = "";

        $fields_to_update[] = "status_pembayaran = ?";
        $params[] = $clean_status;
        $types .= "s";

        $possible_fields = [
            'metode_pembayaran' => 's',
            'bukti_pembayaran' => 's',
            'id_transaksi_gateway' => 's',
            'nomor_virtual_account' => 's',
            'catatan_admin' => 's',
            'waktu_pembayaran' => 's',
            'jumlah_dibayar' => 'd'
        ];
        foreach ($possible_fields as $field_name => $type_char) {
            if (array_key_exists($field_name, $details)) {
                $fields_to_update[] = "`{$field_name}` = ?";
                $params[] = ($type_char === 'd') ? (float)$details[$field_name] : $details[$field_name];
                $types .= $type_char;
            }
        }

        if (in_array($clean_status, self::SUCCESSFUL_PAYMENT_STATUSES) && !array_key_exists('waktu_pembayaran', $details)) {
            $waktu_update_exists = false;
            foreach ($fields_to_update as $fld) {
                if (strpos($fld, 'waktu_pembayaran =') !== false) {
                    $waktu_update_exists = true;
                    break;
                }
            }
            if (!$waktu_update_exists) $fields_to_update[] = "waktu_pembayaran = NOW()";
        }
        $fields_to_update[] = "updated_at = NOW()";

        if (empty($params) && count($fields_to_update) <= 1) { // Hanya updated_at
            error_log(get_called_class() . "::updateStatusAndDetails() - Tidak ada field valid untuk diupdate (selain updated_at) untuk ID: " . $id_val);
            return true;
        }

        $sql = "UPDATE " . self::$table_name . " SET " . implode(', ', $fields_to_update) . " WHERE id = ?";
        $params[] = $id_val;
        $types .= "i";

        $stmt = mysqli_prepare(self::$db, $sql);
        if (!$stmt) {
            error_log(get_called_class() . "::updateStatusAndDetails() - MySQLi Prepare Error: " . mysqli_error(self::$db) . " SQL: " . $sql);
            return false;
        }

        mysqli_stmt_bind_param($stmt, $types, ...$params);
        if (mysqli_stmt_execute($stmt)) {
            $affected_rows = mysqli_stmt_affected_rows($stmt);
            mysqli_stmt_close($stmt);
            return $affected_rows >= 0;
        } else {
            error_log(get_called_class() . "::updateStatusAndDetails() - MySQLi Execute Error: " . mysqli_stmt_error($stmt));
        }
        mysqli_stmt_close($stmt);
        return false;
    }

    /**
     * Mengambil total pendapatan berdasarkan array status.
     * @param array|null $status_array Array status yang valid (default ke SUCCESSFUL_PAYMENT_STATUSES).
     * @return float Total pendapatan.
     */
    public static function getTotalRevenue($status_array = null)
    {
        if (!self::checkDbConnection()) return 0.0;
        $valid_statuses = $status_array === null ? self::SUCCESSFUL_PAYMENT_STATUSES : (is_array($status_array) ? array_filter($status_array, fn($s) => in_array(strtolower(trim($s)), self::ALLOWED_STATUSES)) : []);
        if (empty($valid_statuses)) {
            error_log(get_called_class() . "::getTotalRevenue() - Tidak ada status valid yang diberikan.");
            return 0.0;
        }

        $placeholders = implode(',', array_fill(0, count($valid_statuses), '?'));
        $types = str_repeat('s', count($valid_statuses));
        $sql = "SELECT SUM(jumlah_dibayar) AS total_pendapatan FROM " . self::$table_name . " WHERE status_pembayaran IN (" . $placeholders . ")";
        $stmt = mysqli_prepare(self::$db, $sql);
        if (!$stmt) {
            error_log(get_called_class() . "::getTotalRevenue() - MySQLi Prepare Error: " . mysqli_error(self::$db));
            return 0.0;
        }

        mysqli_stmt_bind_param($stmt, $types, ...$valid_statuses);
        if (mysqli_stmt_execute($stmt)) {
            $result = mysqli_stmt_get_result($stmt);
            $row = mysqli_fetch_assoc($result);
            mysqli_stmt_close($stmt);
            return (float)($row['total_pendapatan'] ?? 0.0);
        } else {
            error_log(get_called_class() . "::getTotalRevenue() - MySQLi Execute Error: " . mysqli_stmt_error($stmt));
        }
        mysqli_stmt_close($stmt);
        return 0.0;
    }

    /**
     * Menghitung jumlah pembayaran berdasarkan status (bisa array status).
     * @param string|array $status Status atau array status.
     * @return int Jumlah pembayaran.
     */
    public static function countByStatus($status)
    {
        if (!self::checkDbConnection()) return 0;
        $statuses_to_check = is_array($status) ? $status : [$status];
        if (empty($statuses_to_check)) return 0;
        $valid_statuses = array_filter($statuses_to_check, fn($s) => in_array(strtolower(trim($s)), self::ALLOWED_STATUSES));
        if (empty($valid_statuses)) {
            error_log(get_called_class() . "::countByStatus() - Tidak ada status valid: " . print_r($status, true));
            return 0;
        }

        $placeholders = implode(',', array_fill(0, count($valid_statuses), '?'));
        $types = str_repeat('s', count($valid_statuses));
        $sql = "SELECT COUNT(id) as total FROM " . self::$table_name . " WHERE status_pembayaran IN (" . $placeholders . ")";
        $stmt = mysqli_prepare(self::$db, $sql);
        if (!$stmt) {
            error_log(get_called_class() . "::countByStatus() - MySQLi Prepare Error: " . mysqli_error(self::$db));
            return 0;
        }

        mysqli_stmt_bind_param($stmt, $types, ...$valid_statuses);
        if (mysqli_stmt_execute($stmt)) {
            $result = mysqli_stmt_get_result($stmt);
            $row = mysqli_fetch_assoc($result);
            mysqli_stmt_close($stmt);
            return (int)($row['total'] ?? 0);
        } else {
            error_log(get_called_class() . "::countByStatus() - MySQLi Execute Error: " . mysqli_stmt_error($stmt));
        }
        mysqli_stmt_close($stmt);
        return 0;
    }

    /**
     * Menghitung semua record pembayaran.
     * @return int Total pembayaran.
     */
    public static function countAll()
    {
        if (!self::checkDbConnection()) return 0;
        $sql = "SELECT COUNT(id) as total FROM " . self::$table_name;
        $result = mysqli_query(self::$db, $sql);
        if ($result) {
            $row = mysqli_fetch_assoc($result);
            mysqli_free_result($result);
            return (int)($row['total'] ?? 0);
        } else {
            error_log(get_called_class() . "::countAll() - MySQLi Query Error: " . mysqli_error(self::$db));
        }
        return 0;
    }

    /**
     * Menghapus record pembayaran berdasarkan ID.
     * @param int $id ID pembayaran.
     * @return bool True jika berhasil.
     */
    public static function delete($id)
    {
        if (!self::checkDbConnection()) return false;
        $id_val = filter_var($id, FILTER_VALIDATE_INT);
        if ($id_val === false || $id_val <= 0) {
            error_log(get_called_class() . "::delete() - ID tidak valid: " . $id);
            return false;
        }

        $sql = "DELETE FROM " . self::$table_name . " WHERE id = ?";
        $stmt = mysqli_prepare(self::$db, $sql);
        if (!$stmt) {
            error_log(get_called_class() . "::delete() - MySQLi Prepare Error: " . mysqli_error(self::$db));
            return false;
        }

        mysqli_stmt_bind_param($stmt, "i", $id_val);
        if (mysqli_stmt_execute($stmt)) {
            $affected_rows = mysqli_stmt_affected_rows($stmt);
            mysqli_stmt_close($stmt);
            return $affected_rows > 0;
        } else {
            error_log(get_called_class() . "::delete() - MySQLi Execute Error: " . mysqli_stmt_error($stmt));
        }
        mysqli_stmt_close($stmt);
        return false;
    }
} // End of class Pembayaran