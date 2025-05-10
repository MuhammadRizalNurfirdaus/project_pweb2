<?php
// File: C:\xampp\htdocs\Cilengkrang-Web-Wisata\models\Contact.php

class Contact
{
    private static $table_name = "contacts"; // As per your DESCRIBE output

    /**
     * Inserts a new contact message.
     * @param array $data Associative array with keys [nama, email, pesan].
     * @return int|false ID of the new contact message on success, false on failure.
     */
    public static function create($data)
    {
        global $conn;

        $nama = trim($data['nama']);
        $email = trim($data['email']);
        $pesan = trim($data['pesan']);

        if (empty($nama) || empty($email) || empty($pesan)) {
            error_log("Contact Create Error: Required fields missing.");
            return false;
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            error_log("Contact Create Error: Invalid email format.");
            return false;
        }

        // 'created_at' is usually handled by database DEFAULT CURRENT_TIMESTAMP
        $sql = "INSERT INTO " . self::$table_name . " (nama, email, pesan) VALUES (?, ?, ?)";
        $stmt = mysqli_prepare($conn, $sql);

        if (!$stmt) {
            error_log("MySQLi Prepare Error (Contact Create): " . mysqli_error($conn));
            return false;
        }

        mysqli_stmt_bind_param($stmt, "sss", $nama, $email, $pesan);

        if (mysqli_stmt_execute($stmt)) {
            $new_id = mysqli_insert_id($conn);
            mysqli_stmt_close($stmt);
            return $new_id;
        } else {
            error_log("MySQLi Execute Error (Contact Create): " . mysqli_stmt_error($stmt));
            mysqli_stmt_close($stmt);
            return false;
        }
    }

    /**
     * Retrieves all contact messages.
     * @return array Array of contact messages, or empty array on failure/no records.
     */
    public static function getAll()
    {
        global $conn;
        $sql = "SELECT id, nama, email, pesan, created_at FROM " . self::$table_name . " ORDER BY created_at DESC";
        $result = mysqli_query($conn, $sql);
        if ($result) {
            return mysqli_fetch_all($result, MYSQLI_ASSOC);
        } else {
            error_log("MySQLi Error (Contact getAll): " . mysqli_error($conn));
            return [];
        }
    }

    /**
     * Deletes a contact message by its ID.
     * @param int $id The ID of the contact message to delete.
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
            error_log("MySQLi Prepare Error (Contact Delete): " . mysqli_error($conn));
            return false;
        }
        mysqli_stmt_bind_param($stmt, "i", $id_to_delete);
        if (mysqli_stmt_execute($stmt)) {
            $affected_rows = mysqli_stmt_affected_rows($stmt);
            mysqli_stmt_close($stmt);
            return $affected_rows > 0;
        } else {
            error_log("MySQLi Execute Error (Contact Delete): " . mysqli_stmt_error($stmt));
            mysqli_stmt_close($stmt);
            return false;
        }
    }
}
