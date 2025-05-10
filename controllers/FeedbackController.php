<?php
require_once __DIR__ . '/../config/config.php';

class FeedbackController
{
    /**
     * Kirim feedback baru
     * @param array $data [user_id, artikel_id, komentar, rating]
     *             Note: Your original controller took nama, email. The DB schema has user_id, artikel_id.
     *                   This needs to be consistent. I'll assume the DB schema is the target.
     * @return bool
     */
    public static function create($data)
    {
        global $conn;

        // DESCRIBE feedback: id, user_id, artikel_id, komentar, rating, created_at
        // 'created_at' is likely auto
        $user_id = isset($data['user_id']) ? (int)$data['user_id'] : null; // User sending feedback
        $artikel_id = isset($data['artikel_id']) ? (int)$data['artikel_id'] : null; // Article being reviewed
        $komentar = $data['komentar'];
        $rating = (int)$data['rating'];


        $sql = "INSERT INTO feedback (user_id, artikel_id, komentar, rating) 
                  VALUES (?, ?, ?, ?)";
        $stmt = mysqli_prepare($conn, $sql);

        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "iisi", $user_id, $artikel_id, $komentar, $rating);
            if (mysqli_stmt_execute($stmt)) {
                mysqli_stmt_close($stmt);
                return true;
            }
            mysqli_stmt_close($stmt);
        }
        return false;
    }

    /**
     * Ambil semua feedback
     * @return mysqli_result|false
     */
    public static function getAll()
    {
        global $conn;
        // Join with users and articles table for more meaningful display
        $query = "SELECT f.id, f.komentar, f.rating, f.created_at, 
                         u.nama as user_nama, u.email as user_email,
                         a.judul as artikel_judul
                  FROM feedback f
                  LEFT JOIN users u ON f.user_id = u.id
                  LEFT JOIN articles a ON f.artikel_id = a.id
                  ORDER BY f.created_at DESC";
        return mysqli_query($conn, $query);
    }

    /**
     * Hapus feedback
     * @param int $id
     * @return bool
     */
    public static function delete($id)
    {
        global $conn;
        $sql = "DELETE FROM feedback WHERE id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "i", $id);
            if (mysqli_stmt_execute($stmt)) {
                mysqli_stmt_close($stmt);
                return true;
            }
            mysqli_stmt_close($stmt);
        }
        return false;
    }
}
