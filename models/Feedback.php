<?php
// File: C:\xampp\htdocs\Cilengkrang-Web-Wisata\models\Feedback.php

class Feedback
{
    private static $table_name = "feedback"; // As per your DESCRIBE output

    /**
     * Inserts new feedback.
     * @param array $data Associative array with keys [user_id (nullable), artikel_id (nullable), komentar, rating].
     * @return int|false ID of the new feedback on success, false on failure.
     */
    public static function create($data)
    {
        global $conn;

        $user_id = isset($data['user_id']) ? (int)$data['user_id'] : null;
        $artikel_id = isset($data['artikel_id']) ? (int)$data['artikel_id'] : null;
        $komentar = trim($data['komentar']);
        $rating = isset($data['rating']) ? (int)$data['rating'] : null; // Rating can be 0, but let's assume 1-5

        // Basic validation
        if (empty($komentar) || $rating === null || $rating < 1 || $rating > 5) {
            error_log("Feedback Create Error: Komentar or rating invalid/missing.");
            return false;
        }

        // 'created_at' is usually handled by database DEFAULT CURRENT_TIMESTAMP
        $sql = "INSERT INTO " . self::$table_name . " (user_id, artikel_id, komentar, rating) 
                VALUES (?, ?, ?, ?)";
        $stmt = mysqli_prepare($conn, $sql);

        if (!$stmt) {
            error_log("MySQLi Prepare Error (Feedback Create): " . mysqli_error($conn));
            return false;
        }

        mysqli_stmt_bind_param($stmt, "iisi", $user_id, $artikel_id, $komentar, $rating);

        if (mysqli_stmt_execute($stmt)) {
            $new_id = mysqli_insert_id($conn);
            mysqli_stmt_close($stmt);
            return $new_id;
        } else {
            error_log("MySQLi Execute Error (Feedback Create): " . mysqli_stmt_error($stmt));
            mysqli_stmt_close($stmt);
            return false;
        }
    }

    /**
     * Retrieves all feedback, joining with user and article data.
     * @return array Array of feedback records, or empty array on failure/no records.
     */
    public static function getAll()
    {
        global $conn;
        $sql = "SELECT f.id, f.komentar, f.rating, f.created_at, 
                       u.nama AS user_nama, u.email AS user_email,
                       a.judul AS artikel_judul
                FROM " . self::$table_name . " f
                LEFT JOIN users u ON f.user_id = u.id
                LEFT JOIN articles a ON f.artikel_id = a.id
                ORDER BY f.created_at DESC";
        $result = mysqli_query($conn, $sql);

        if ($result) {
            return mysqli_fetch_all($result, MYSQLI_ASSOC);
        } else {
            error_log("MySQLi Error (Feedback getAll): " . mysqli_error($conn));
            return [];
        }
    }

    /**
     * Deletes feedback by its ID.
     * @param int $id The ID of the feedback to delete.
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
            error_log("MySQLi Prepare Error (Feedback Delete): " . mysqli_error($conn));
            return false;
        }
        mysqli_stmt_bind_param($stmt, "i", $id_to_delete);
        if (mysqli_stmt_execute($stmt)) {
            $affected_rows = mysqli_stmt_affected_rows($stmt);
            mysqli_stmt_close($stmt);
            return $affected_rows > 0;
        } else {
            error_log("MySQLi Execute Error (Feedback Delete): " . mysqli_stmt_error($stmt));
            mysqli_stmt_close($stmt);
            return false;
        }
    }
}
