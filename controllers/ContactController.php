<?php
require_once __DIR__ . '/../config/config.php';

class ContactController
{
    /**
     * Buat pesan kontak baru
     * @param array $data [nama, email, pesan]
     * @return bool
     */
    public static function create($data)
    {
        global $conn;
        $nama = $data['nama'];
        $email = $data['email'];
        $pesan = $data['pesan'];

        // DESCRIBE contacts: id, nama, email, pesan, created_at
        // 'created_at' is likely auto by DB
        $sql = "INSERT INTO contacts (nama, email, pesan) VALUES (?, ?, ?)";
        $stmt = mysqli_prepare($conn, $sql);

        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "sss", $nama, $email, $pesan);
            if (mysqli_stmt_execute($stmt)) {
                mysqli_stmt_close($stmt);
                return true;
            }
            mysqli_stmt_close($stmt);
        }
        return false;
    }

    /**
     * Ambil semua pesan kontak
     * @return mysqli_result|false
     */
    public static function getAll()
    {
        global $conn;
        // Assuming 'contacts' table and 'created_at' for ordering
        $result = mysqli_query($conn, "SELECT id, nama, email, pesan, created_at FROM contacts ORDER BY created_at DESC");
        return $result;
    }

    /**
     * Hapus pesan kontak
     * @param int $id
     * @return bool
     */
    public static function delete($id)
    {
        global $conn;
        $sql = "DELETE FROM contacts WHERE id = ?";
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
