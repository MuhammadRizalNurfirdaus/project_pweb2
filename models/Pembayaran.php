<?php
// File: C:\xampp\htdocs\Cilengkrang-Web-Wisata\models\Pembayaran.php

/**
 * Class Pembayaran
 * Mengelola operasi database untuk tabel pembayaran.
 * Menggunakan pendekatan statis dan koneksi global $conn.
 */
class Pembayaran
{
    private static $table_name = 'pembayaran';

    /**
     * Membuat record pembayaran baru.
     * @param array $data Harus berisi pemesanan_tiket_id, jumlah_dibayar. Opsional: status_pembayaran, metode_pembayaran.
     * @return int|false ID pembayaran baru atau false jika gagal.
     */
    public static function create($data)
    {
        global $conn;
        if (!$conn) {
            error_log("Pembayaran::create() - Koneksi database gagal.");
            return false;
        }

        $pemesanan_tiket_id = isset($data['pemesanan_tiket_id']) ? (int)$data['pemesanan_tiket_id'] : 0;
        $jumlah_dibayar = isset($data['jumlah_dibayar']) ? (float)$data['jumlah_dibayar'] : 0.0; // Gunakan float jika jumlah bisa desimal
        $status = $data['status_pembayaran'] ?? 'pending';
        $metode = $data['metode_pembayaran'] ?? null;

        // Validasi dasar
        if ($pemesanan_tiket_id <= 0) {
            error_log("Pembayaran::create() - Error: pemesanan_tiket_id tidak valid.");
            return false;
        }
        if ($jumlah_dibayar < 0) {
            error_log("Pembayaran::create() - Error: jumlah_dibayar tidak boleh negatif.");
            return false;
        }

        $allowed_status = ['pending', 'success', 'failed', 'expired', 'refunded', 'awaiting_confirmation', 'paid', 'confirmed'];
        if (!in_array($status, $allowed_status)) {
            error_log("Pembayaran::create() - Error: Status awal tidak valid: " . htmlspecialchars($status));
            return false;
        }

        $sql = "INSERT INTO " . self::$table_name . " 
                    (pemesanan_tiket_id, jumlah_dibayar, status_pembayaran, metode_pembayaran, created_at, updated_at) 
                VALUES (?, ?, ?, ?, NOW(), NOW())";

        $stmt = mysqli_prepare($conn, $sql);
        if (!$stmt) {
            error_log("Pembayaran::create() - MySQLi Prepare Error: " . mysqli_error($conn));
            return false;
        }

        mysqli_stmt_bind_param($stmt, "idss", $pemesanan_tiket_id, $jumlah_dibayar, $status, $metode); // 'd' untuk double/float jika jumlah_dibayar desimal

        if (mysqli_stmt_execute($stmt)) {
            $newId = mysqli_insert_id($conn);
            mysqli_stmt_close($stmt);
            return $newId;
        } else {
            error_log("Pembayaran::create() - MySQLi Execute Error: " . mysqli_stmt_error($stmt));
            mysqli_stmt_close($stmt);
            return false;
        }
    }

    public static function findById($id)
    {
        global $conn;
        if (!$conn) { /* ... */
            return null;
        }
        $id_val = filter_var($id, FILTER_VALIDATE_INT);
        if ($id_val === false || $id_val <= 0) { /* ... */
            return null;
        }
        $sql = "SELECT * FROM " . self::$table_name . " WHERE id = ? LIMIT 1";
        $stmt = mysqli_prepare($conn, $sql);
        if (!$stmt) { /* ... */
            return null;
        }
        mysqli_stmt_bind_param($stmt, "i", $id_val);
        if (mysqli_stmt_execute($stmt)) {
            $result = mysqli_stmt_get_result($stmt);
            $pembayaran = mysqli_fetch_assoc($result);
            mysqli_stmt_close($stmt);
            return $pembayaran ?: null;
        }
        mysqli_stmt_close($stmt);
        return null;
    }

    public static function findByPemesananTiketId($pemesanan_tiket_id)
    {
        global $conn;
        if (!$conn) { /* ... */
            return null;
        }
        $id_val = filter_var($pemesanan_tiket_id, FILTER_VALIDATE_INT);
        if ($id_val === false || $id_val <= 0) { /* ... */
            return null;
        }
        $sql = "SELECT * FROM " . self::$table_name . " WHERE pemesanan_tiket_id = ? LIMIT 1";
        $stmt = mysqli_prepare($conn, $sql);
        if (!$stmt) { /* ... */
            return null;
        }
        mysqli_stmt_bind_param($stmt, "i", $id_val);
        if (mysqli_stmt_execute($stmt)) {
            $result = mysqli_stmt_get_result($stmt);
            $pembayaran = mysqli_fetch_assoc($result);
            mysqli_stmt_close($stmt);
            return $pembayaran ?: null;
        }
        mysqli_stmt_close($stmt);
        return null;
    }

    public static function findAllWithKodePemesanan($orderBy = 'p.created_at DESC')
    {
        global $conn;
        if (!$conn) { /* ... */
            return [];
        }
        $allowed_order_columns = ['p.created_at', 'p.id', 'pt.kode_pemesanan', 'p.status_pembayaran', 'p.waktu_pembayaran', 'p.jumlah_dibayar'];
        $order_parts = explode(' ', trim($orderBy));
        $order_column = $order_parts[0] ?? 'p.created_at';
        $order_direction = isset($order_parts[1]) && strtoupper($order_parts[1]) === 'ASC' ? 'ASC' : 'DESC';
        if (!in_array($order_column, $allowed_order_columns)) {
            $order_column = 'p.created_at';
            $order_direction = 'DESC';
        }
        $orderBySafe = $order_column . ' ' . $order_direction;
        $sql = "SELECT p.*, pt.kode_pemesanan FROM " . self::$table_name . " p LEFT JOIN pemesanan_tiket pt ON p.pemesanan_tiket_id = pt.id ORDER BY " . $orderBySafe;
        $result = mysqli_query($conn, $sql);
        if ($result) {
            $data = mysqli_fetch_all($result, MYSQLI_ASSOC);
            mysqli_free_result($result);
            return $data;
        }
        return [];
    }

    public static function updateStatusAndDetails($id, $status, $details = [])
    {
        global $conn;
        if (!$conn) { /* ... */
            return false;
        }
        $id_val = filter_var($id, FILTER_VALIDATE_INT);
        if ($id_val === false || $id_val <= 0) { /* ... */
            return false;
        }
        $allowed_status = ['pending', 'success', 'failed', 'expired', 'refunded', 'awaiting_confirmation', 'paid', 'confirmed'];
        if (empty($status) || !in_array($status, $allowed_status)) { /* ... */
            return false;
        }

        $fields_to_update = [];
        $params = [];
        $types = "";
        $fields_to_update[] = "status_pembayaran = ?";
        $params[] = $status;
        $types .= "s";
        if (isset($details['metode_pembayaran'])) {
            $fields_to_update[] = "metode_pembayaran = ?";
            $params[] = $details['metode_pembayaran'];
            $types .= "s";
        }
        if (isset($details['bukti_pembayaran'])) {
            $fields_to_update[] = "bukti_pembayaran = ?";
            $params[] = $details['bukti_pembayaran'];
            $types .= "s";
        }
        if (isset($details['id_transaksi_gateway'])) {
            $fields_to_update[] = "id_transaksi_gateway = ?";
            $params[] = $details['id_transaksi_gateway'];
            $types .= "s";
        }
        if (isset($details['nomor_virtual_account'])) {
            $fields_to_update[] = "nomor_virtual_account = ?";
            $params[] = $details['nomor_virtual_account'];
            $types .= "s";
        }
        if (isset($details['jumlah_dibayar'])) {
            $fields_to_update[] = "jumlah_dibayar = ?";
            $params[] = (float)$details['jumlah_dibayar'];
            $types .= "d";
        } // 'd' untuk double/float
        if (in_array($status, ['success', 'paid', 'confirmed']) && !isset($details['waktu_pembayaran'])) {
            $fields_to_update[] = "waktu_pembayaran = NOW()";
        } elseif (isset($details['waktu_pembayaran'])) {
            $fields_to_update[] = "waktu_pembayaran = ?";
            $params[] = $details['waktu_pembayaran'];
            $types .= "s";
        }
        $fields_to_update[] = "updated_at = NOW()";
        if (empty($params) && count($fields_to_update) <= 1) { /* Cek jika hanya updated_at */
            return true;
        } // Jika hanya updated_at yang berubah

        $sql = "UPDATE " . self::$table_name . " SET " . implode(', ', $fields_to_update) . " WHERE id = ?";
        $params[] = $id_val;
        $types .= "i";
        $stmt = mysqli_prepare($conn, $sql);
        if (!$stmt) { /* ... log error ... */
            return false;
        }
        mysqli_stmt_bind_param($stmt, $types, ...$params);
        if (mysqli_stmt_execute($stmt)) {
            mysqli_stmt_close($stmt);
            return true;
        }
        mysqli_stmt_close($stmt);
        return false;
    }

    public static function getTotalRevenue($status_array = ['success', 'paid', 'confirmed']) // Diubah agar menerima array
    {
        global $conn;
        if (!$conn) { /* ... */
            return 0;
        }
        if (empty($status_array) || !is_array($status_array)) {
            error_log("Pembayaran::getTotalRevenue() - Array status tidak valid.");
            return 0;
        }
        $allowed_status_enum = ['pending', 'success', 'failed', 'expired', 'refunded', 'awaiting_confirmation', 'paid', 'confirmed'];
        $valid_statuses = array_filter($status_array, fn($s) => in_array(strtolower($s), $allowed_status_enum));
        if (empty($valid_statuses)) { /* ... */
            return 0;
        }
        $placeholders = implode(',', array_fill(0, count($valid_statuses), '?'));
        $types = str_repeat('s', count($valid_statuses));
        $sql = "SELECT SUM(jumlah_dibayar) AS total_pendapatan FROM " . self::$table_name . " WHERE status_pembayaran IN (" . $placeholders . ")";
        $stmt = mysqli_prepare($conn, $sql);
        if (!$stmt) { /* ... */
            return 0;
        }
        mysqli_stmt_bind_param($stmt, $types, ...$valid_statuses);
        if (mysqli_stmt_execute($stmt)) {
            $result = mysqli_stmt_get_result($stmt);
            $row = mysqli_fetch_assoc($result);
            mysqli_stmt_close($stmt);
            return (float)($row['total_pendapatan'] ?? 0); // Kembalikan sebagai float
        }
        mysqli_stmt_close($stmt);
        return 0;
    }

    public static function countByStatus($status)
    {
        global $conn;
        if (!$conn) { /* ... */
            return 0;
        }
        $statuses = is_array($status) ? $status : [$status];
        if (empty($statuses)) return 0;
        $allowed_status_enum = ['pending', 'success', 'failed', 'expired', 'refunded', 'awaiting_confirmation', 'paid', 'confirmed'];
        $valid_statuses = array_filter($statuses, fn($s) => in_array(strtolower($s), $allowed_status_enum));
        if (empty($valid_statuses)) return 0;
        $placeholders = implode(',', array_fill(0, count($valid_statuses), '?'));
        $types = str_repeat('s', count($valid_statuses));
        $sql = "SELECT COUNT(id) as total FROM " . self::$table_name . " WHERE status_pembayaran IN (" . $placeholders . ")";
        $stmt = mysqli_prepare($conn, $sql);
        if (!$stmt) { /* ... */
            return 0;
        }
        mysqli_stmt_bind_param($stmt, $types, ...$valid_statuses);
        if (mysqli_stmt_execute($stmt)) {
            $result = mysqli_stmt_get_result($stmt);
            $row = mysqli_fetch_assoc($result);
            mysqli_stmt_close($stmt);
            return (int)($row['total'] ?? 0);
        }
        mysqli_stmt_close($stmt);
        return 0;
    }

    public static function countAll()
    {
        global $conn;
        if (!$conn) { /* ... */
            return 0;
        }
        $sql = "SELECT COUNT(id) as total FROM " . self::$table_name;
        $result = mysqli_query($conn, $sql);
        if ($result) {
            $row = mysqli_fetch_assoc($result);
            mysqli_free_result($result);
            return (int)($row['total'] ?? 0);
        }
        return 0;
    }

    public static function delete($id)
    {
        global $conn;
        if (!$conn) { /* ... */
            return false;
        }
        $id_val = filter_var($id, FILTER_VALIDATE_INT);
        if ($id_val === false || $id_val <= 0) { /* ... */
            return false;
        }
        // Tidak perlu error_log peringatan di sini jika memang fitur delete ada
        $sql = "DELETE FROM " . self::$table_name . " WHERE id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        if (!$stmt) { /* ... */
            return false;
        }
        mysqli_stmt_bind_param($stmt, "i", $id_val);
        if (mysqli_stmt_execute($stmt)) {
            $affected_rows = mysqli_stmt_affected_rows($stmt);
            mysqli_stmt_close($stmt);
            return $affected_rows > 0;
        }
        mysqli_stmt_close($stmt);
        return false;
    }
}
