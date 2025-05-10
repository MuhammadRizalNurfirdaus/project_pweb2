<?php
// Controllers should include the main config file
// Path from 'controllers' to 'config' is '../config/'
require_once __DIR__ . '/../config/config.php';

class ArtikelController
{
    /**
     * Tambah artikel baru
     * @param string $judul Judul artikel
     * @param string $isi Isi artikel
     * @param string|null $gambar Nama file gambar (opsional)
     * @return int|false ID artikel baru jika berhasil, false jika gagal
     */
    public static function create($judul, $isi, $gambar = null)
    {
        global $conn; // $conn from config.php

        $sql = "INSERT INTO articles (judul, isi, gambar) VALUES (?, ?, ?)";
        $stmt = mysqli_prepare($conn, $sql);

        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "sss", $judul, $isi, $gambar);
            if (mysqli_stmt_execute($stmt)) {
                $new_id = mysqli_insert_id($conn);
                mysqli_stmt_close($stmt);
                return $new_id;
            } else {
                error_log("ArtikelController::create execute failed: " . mysqli_stmt_error($stmt));
                mysqli_stmt_close($stmt);
                return false;
            }
        } else {
            error_log("ArtikelController::create prepare failed: " . mysqli_error($conn));
            return false;
        }
    }

    /**
     * Ambil semua artikel
     * @return mysqli_result|false Hasil query atau false jika gagal
     */
    public static function getAll()
    {
        global $conn;
        $result = mysqli_query($conn, "SELECT id, judul, isi, gambar, created_at FROM articles ORDER BY created_at DESC");
        return $result;
    }

    /**
     * Ambil satu artikel berdasarkan ID
     * @param int $id ID Artikel
     * @return array|null Data artikel atau null jika tidak ditemukan/error
     */
    public static function getById($id)
    {
        global $conn;
        $sql = "SELECT id, judul, isi, gambar, created_at FROM articles WHERE id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "i", $id);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $artikel = mysqli_fetch_assoc($result);
            mysqli_stmt_close($stmt);
            return $artikel;
        }
        return null;
    }

    /**
     * Update artikel
     * @param int $id ID Artikel
     * @param string $judul Judul baru
     * @param string $isi Isi baru
     * @param string|null $gambar Gambar baru (null jika tidak diubah, "REMOVE" jika ingin dihapus)
     * @return bool True jika berhasil, false jika gagal
     */
    public static function update($id, $judul, $isi, $gambar_new_name = null, $gambar_old_name = null)
    {
        global $conn;

        if ($gambar_new_name === "REMOVE") { // User wants to remove existing image
            $sql = "UPDATE articles SET judul = ?, isi = ?, gambar = NULL WHERE id = ?";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "ssi", $judul, $isi, $id);
        } elseif ($gambar_new_name) { // New image uploaded
            $sql = "UPDATE articles SET judul = ?, isi = ?, gambar = ? WHERE id = ?";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "sssi", $judul, $isi, $gambar_new_name, $id);
        } else { // No change to image
            $sql = "UPDATE articles SET judul = ?, isi = ? WHERE id = ?";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "ssi", $judul, $isi, $id);
        }

        if ($stmt) {
            if (mysqli_stmt_execute($stmt)) {
                mysqli_stmt_close($stmt);
                // If a new image was uploaded and there was an old one, delete old file
                if ($gambar_new_name && $gambar_new_name !== "REMOVE" && $gambar_old_name) {
                    $old_file_path = __DIR__ . '/../../public/uploads/artikel/' . $gambar_old_name;
                    if (file_exists($old_file_path)) {
                        unlink($old_file_path);
                    }
                } elseif ($gambar_new_name === "REMOVE" && $gambar_old_name) {
                    $old_file_path = __DIR__ . '/../../public/uploads/artikel/' . $gambar_old_name;
                    if (file_exists($old_file_path)) {
                        unlink($old_file_path);
                    }
                }
                return true;
            }
            error_log("ArtikelController::update execute failed: " . mysqli_stmt_error($stmt));
            mysqli_stmt_close($stmt);
        } else {
            error_log("ArtikelController::update prepare failed: " . mysqli_error($conn));
        }
        return false;
    }

    /**
     * Hapus artikel berdasarkan ID
     * @param int $id ID Artikel
     * @return bool True jika berhasil, false jika gagal
     */
    public static function delete($id)
    {
        global $conn;
        // Get image filename to delete it
        $artikel = self::getById($id);
        $gambar_to_delete = null;
        if ($artikel && !empty($artikel['gambar'])) {
            $gambar_to_delete = $artikel['gambar'];
        }

        $sql = "DELETE FROM articles WHERE id = ?";
        $stmt = mysqli_prepare($conn, $sql);

        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "i", $id);
            if (mysqli_stmt_execute($stmt)) {
                mysqli_stmt_close($stmt);
                // Delete the image file if it exists
                if ($gambar_to_delete) {
                    $file_path = __DIR__ . '/../../public/uploads/artikel/' . $gambar_to_delete;
                    if (file_exists($file_path)) {
                        unlink($file_path);
                    }
                }
                return true;
            }
            error_log("ArtikelController::delete execute failed: " . mysqli_stmt_error($stmt));
            mysqli_stmt_close($stmt);
        } else {
            error_log("ArtikelController::delete prepare failed: " . mysqli_error($conn));
        }
        return false;
    }
}
