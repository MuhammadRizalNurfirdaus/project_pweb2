<?php
require_once __DIR__ . '/../config/config.php';

class GaleriController
{
    /**
     * Tambah item galeri baru
     * @param string $nama_file Nama file gambar
     * @param string $keterangan Keterangan/judul gambar
     * @return bool
     */
    public static function create($nama_file, $keterangan)
    {
        global $conn;

        // DESCRIBE galeri: id, nama_file, keterangan
        // Assuming 'created_at' is not present or not managed by controller.
        $sql = "INSERT INTO galeri (nama_file, keterangan) VALUES (?, ?)";
        $stmt = mysqli_prepare($conn, $sql);

        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "ss", $nama_file, $keterangan);
            if (mysqli_stmt_execute($stmt)) {
                mysqli_stmt_close($stmt);
                return true;
            }
            mysqli_stmt_close($stmt);
        }
        return false;
    }

    /**
     * Ambil semua item galeri
     * @return mysqli_result|false
     */
    public static function getAll()
    {
        global $conn;
        // Assuming 'galeri' table and 'id' for ordering if no 'created_at'
        $result = mysqli_query($conn, "SELECT id, nama_file, keterangan FROM galeri ORDER BY id DESC");
        return $result;
    }

    /**
     * Ambil satu item galeri berdasarkan ID
     * @param int $id ID Galeri
     * @return array|null Data galeri atau null jika tidak ditemukan/error
     */
    public static function getById($id)
    {
        global $conn;
        $sql = "SELECT id, nama_file, keterangan FROM galeri WHERE id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "i", $id);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $item = mysqli_fetch_assoc($result);
            mysqli_stmt_close($stmt);
            return $item;
        }
        return null;
    }

    /**
     * Hapus item galeri
     * @param int $id
     * @return bool
     */
    public static function delete($id)
    {
        global $conn;
        // Important: The calling script should handle deleting the actual image file from the server
        // before calling this method, or this method should also retrieve filename and delete.
        // For simplicity here, it only deletes DB record.

        $sql = "DELETE FROM galeri WHERE id = ?";
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
