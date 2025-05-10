<?php
// File: C:\xampp\htdocs\Cilengkrang-Web-Wisata\models\Booking.php

class Booking
{
    private static $table_name = "bookings"; // As per your DESCRIBE output

    /**
     * Creates a new booking.
     * @param array $data Associative array of booking data.
     * Expected keys: 'user_id' (optional, int), 'nama_wisata' (string),
     * 'tanggal_kunjungan' (string YYYY-MM-DD), 'jumlah_orang' (int), 'status' (optional, string).
     * @return int|false ID of the new booking on success, false on failure.
     */
    public static function create($data)
    {
        global $conn;

        $user_id = isset($data['user_id']) ? (int)$data['user_id'] : null;
        $nama_wisata = trim($data['nama_wisata']);
        $tanggal_kunjungan = $data['tanggal_kunjungan']; // Ensure valid date format before passing
        $jumlah_orang = (int)$data['jumlah_orang'];
        $status = trim($data['status'] ?? 'pending'); // Default status

        if (empty($nama_wisata) || empty($tanggal_kunjungan) || $jumlah_orang <= 0) {
            error_log("Booking Create Error: Required fields missing or invalid.");
            return false;
        }

        $sql = "INSERT INTO " . self::$table_name . " (user_id, nama_wisata, tanggal_kunjungan, jumlah_orang, status)
                VALUES (?, ?, ?, ?, ?)";
        $stmt = mysqli_prepare($conn, $sql);

        if (!$stmt) {
            error_log("MySQLi Prepare Error (Booking Create): " . mysqli_error($conn));
            return false;
        }

        mysqli_stmt_bind_param($stmt, "issis", $user_id, $nama_wisata, $tanggal_kunjungan, $jumlah_orang, $status);

        if (mysqli_stmt_execute($stmt)) {
            $new_id = mysqli_insert_id($conn);
            mysqli_stmt_close($stmt);
            return $new_id;
        } else {
            error_log("MySQLi Execute Error (Booking Create): " . mysqli_stmt_error($stmt));
            mysqli_stmt_close($stmt);
            return false;
        }
    }

    /**
     * Retrieves all bookings, optionally joining with user data.
     * @return array Array of booking records, or empty array on failure/no records.
     */
    public static function getAll()
    {
        global $conn;
        $sql = "SELECT b.id, b.user_id, b.nama_wisata, b.tanggal_kunjungan, b.jumlah_orang, b.status, 
                       u.nama AS user_nama, u.email AS user_email 
                FROM " . self::$table_name . " b
                LEFT JOIN users u ON b.user_id = u.id
                ORDER BY b.tanggal_kunjungan DESC, b.id DESC";
        $result = mysqli_query($conn, $sql);

        if ($result) {
            return mysqli_fetch_all($result, MYSQLI_ASSOC);
        } else {
            error_log("MySQLi Error (Booking getAll): " . mysqli_error($conn));
            return [];
        }
    }

    /**
     * Deletes a booking by its ID.
     * @param int $id The ID of the booking to delete.
     * @return bool True on success, false on failure.
     */
    public static function delete($id)
    {
        global $conn;
        $id_to_delete = intval($id);
        if ($id_to_delete <= 0) return false;

        $sql = "DELETE FROM " . self::$table_name . " WHERE id = ?";
        $stmt = mysqli_prepare($conn, $sql);

        if (!$stmt) {
            error_log("MySQLi Prepare Error (Booking Delete): " . mysqli_error($conn));
            return false;
        }

        mysqli_stmt_bind_param($stmt, "i", $id_to_delete);

        if (mysqli_stmt_execute($stmt)) {
            $affected_rows = mysqli_stmt_affected_rows($stmt);
            mysqli_stmt_close($stmt);
            return $affected_rows > 0;
        } else {
            error_log("MySQLi Execute Error (Booking Delete): " . mysqli_stmt_error($stmt));
            mysqli_stmt_close($stmt);
            return false;
        }
    }

    /**
     * Updates the status of a booking.
     * @param int $id The ID of the booking.
     * @param string $status The new status (e.g., 'konfirmasi', 'selesai', 'dibatalkan').
     * @return bool True on success, false on failure.
     */
    public static function updateStatus($id, $status)
    {
        global $conn;
        $id_to_update = intval($id);
        if ($id_to_update <= 0 || empty(trim($status))) return false;

        $sql = "UPDATE " . self::$table_name . " SET status = ? WHERE id = ?";
        $stmt = mysqli_prepare($conn, $sql);

        if (!$stmt) {
            error_log("MySQLi Prepare Error (Booking UpdateStatus): " . mysqli_error($conn));
            return false;
        }
        mysqli_stmt_bind_param($stmt, "si", $status, $id_to_update);
        if (mysqli_stmt_execute($stmt)) {
            mysqli_stmt_close($stmt);
            return true;
        } else {
            error_log("MySQLi Execute Error (Booking UpdateStatus): " . mysqli_stmt_error($stmt));
            mysqli_stmt_close($stmt);
            return false;
        }
    }
}
