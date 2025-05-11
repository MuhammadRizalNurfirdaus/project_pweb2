<?php
// File: C:\xampp\htdocs\Cilengkrang-Web-Wisata\controllers\ArtikelController.php

require_once __DIR__ . '/../config/config.php';
// Tidak perlu require Model di sini jika Controller ini tidak langsung menggunakan Model Artikel
// Namun, jika ada logika yang memanggil Model Artikel, maka perlu.
// Untuk saat ini, semua operasi DB langsung di Controller ini.

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
        global $conn;

        $sql = "INSERT INTO articles (judul, isi, gambar) VALUES (?, ?, ?)";
        $stmt = mysqli_prepare($conn, $sql);

        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "sss", $judul, $isi, $gambar);
            if (mysqli_stmt_execute($stmt)) {
                $new_id = mysqli_insert_id($conn);
                mysqli_stmt_close($stmt);
                return $new_id;
            } else {
                error_log("ArtikelController::create execute failed: " . mysqli_stmt_error($stmt) . " SQL: " . $sql);
                mysqli_stmt_close($stmt);
                return false;
            }
        } else {
            error_log("ArtikelController::create prepare failed: " . mysqli_error($conn) . " SQL: " . $sql);
            return false;
        }
    }

    /**
     * Ambil semua artikel
     * @return array|false Array data artikel atau false jika gagal
     */
    public static function getAll()
    {
        global $conn;
        $result = mysqli_query($conn, "SELECT id, judul, isi, gambar, created_at FROM articles ORDER BY created_at DESC");
        if ($result) {
            return mysqli_fetch_all($result, MYSQLI_ASSOC);
        }
        error_log("ArtikelController::getAll query failed: " . mysqli_error($conn));
        return false;
    }

    /**
     * Ambil satu artikel berdasarkan ID
     * @param int $id ID Artikel
     * @return array|null Data artikel atau null jika tidak ditemukan/error
     */
    public static function getById($id)
    {
        global $conn;
        $id_val = (int)$id;
        $sql = "SELECT id, judul, isi, gambar, created_at FROM articles WHERE id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "i", $id_val);
            if (mysqli_stmt_execute($stmt)) {
                $result = mysqli_stmt_get_result($stmt);
                $artikel = mysqli_fetch_assoc($result);
                mysqli_stmt_close($stmt);
                return $artikel ?: null; // Mengembalikan null jika tidak ada hasil
            } else {
                error_log("ArtikelController::getById execute failed for ID $id_val: " . mysqli_stmt_error($stmt));
                mysqli_stmt_close($stmt);
                return null;
            }
        }
        error_log("ArtikelController::getById prepare failed for ID $id_val: " . mysqli_error($conn));
        return null;
    }

    /**
     * Update artikel
     * @param int $id ID Artikel
     * @param string $judul Judul baru
     * @param string $isi Isi baru
     * @param string|null $gambar_new_name Nama file gambar baru (null jika tidak diubah, "REMOVE_IMAGE" jika ingin dihapus)
     * @param string|null $gambar_old_name Nama file gambar lama (untuk dihapus dari server jika diganti/dihapus)
     * @return bool True jika berhasil, false jika gagal
     */
    public static function update($id, $judul, $isi, $gambar_new_name = null, $gambar_old_name = null)
    {
        global $conn;
        $id_val = (int)$id;

        // Tentukan path absolut untuk upload direktori
        $upload_dir_artikel = __DIR__ . '/../../public/uploads/artikel/';

        if ($gambar_new_name === "REMOVE_IMAGE") { // User wants to remove existing image
            $sql = "UPDATE articles SET judul = ?, isi = ?, gambar = NULL WHERE id = ?";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "ssi", $judul, $isi, $id_val);
        } elseif ($gambar_new_name) { // New image uploaded
            $sql = "UPDATE articles SET judul = ?, isi = ?, gambar = ? WHERE id = ?";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "sssi", $judul, $isi, $gambar_new_name, $id_val);
        } else { // No change to image, gambar_new_name adalah null
            $sql = "UPDATE articles SET judul = ?, isi = ? WHERE id = ?";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "ssi", $judul, $isi, $id_val);
        }

        if ($stmt) {
            if (mysqli_stmt_execute($stmt)) {
                mysqli_stmt_close($stmt);

                // Logika penghapusan file lama setelah update DB berhasil
                if ($gambar_new_name === "REMOVE_IMAGE" && !empty($gambar_old_name)) {
                    $old_file_path = $upload_dir_artikel . $gambar_old_name;
                    if (file_exists($old_file_path)) {
                        @unlink($old_file_path);
                    }
                } elseif ($gambar_new_name && $gambar_new_name !== "REMOVE_IMAGE" && !empty($gambar_old_name) && $gambar_new_name !== $gambar_old_name) {
                    // Hapus gambar lama hanya jika gambar baru BERBEDA dan ada gambar lama
                    $old_file_path = $upload_dir_artikel . $gambar_old_name;
                    if (file_exists($old_file_path)) {
                        @unlink($old_file_path);
                    }
                }
                return true;
            }
            error_log("ArtikelController::update execute failed for ID $id_val: " . mysqli_stmt_error($stmt));
            mysqli_stmt_close($stmt);
        } else {
            error_log("ArtikelController::update prepare failed for ID $id_val: " . mysqli_error($conn));
        }
        return false;
    }

    /**
     * Hapus artikel berdasarkan ID.
     * Ini juga akan menghapus semua feedback terkait dengan artikel tersebut terlebih dahulu.
     * @param int $id ID Artikel
     * @return bool True jika berhasil, false jika gagal
     */
    public static function delete($id)
    {
        global $conn;
        $id_val = (int)$id;
        if ($id_val <= 0) {
            error_log("ArtikelController::delete Error: ID artikel tidak valid ($id).");
            return false;
        }

        // Ambil nama file gambar artikel untuk dihapus nanti
        $artikel = self::getById($id_val); // Gunakan getById yang sudah ada
        $gambar_to_delete_on_server = null;
        if ($artikel && !empty($artikel['gambar'])) {
            $gambar_to_delete_on_server = $artikel['gambar'];
        }

        // Tentukan path absolut untuk upload direktori
        $upload_dir_artikel = __DIR__ . '/../../public/uploads/artikel/';

        // LANGKAH 1: Hapus semua feedback yang terkait dengan artikel_id ini
        $sql_delete_feedback = "DELETE FROM feedback WHERE artikel_id = ?";
        $stmt_feedback = mysqli_prepare($conn, $sql_delete_feedback);

        if (!$stmt_feedback) {
            error_log("ArtikelController::delete prepare failed for deleting feedback related to artikel ID $id_val: " . mysqli_error($conn));
            return false; // Gagal persiapan, hentikan proses
        }

        mysqli_stmt_bind_param($stmt_feedback, "i", $id_val);
        if (!mysqli_stmt_execute($stmt_feedback)) {
            error_log("ArtikelController::delete execute failed for deleting feedback related to artikel ID $id_val: " . mysqli_stmt_error($stmt_feedback));
            mysqli_stmt_close($stmt_feedback);
            return false; // Gagal eksekusi, hentikan proses
        }
        mysqli_stmt_close($stmt_feedback);
        // Feedback terkait berhasil dihapus (atau tidak ada feedback terkait)

        // LANGKAH 2: Hapus artikel itu sendiri
        $sql_delete_article = "DELETE FROM articles WHERE id = ?";
        $stmt_article = mysqli_prepare($conn, $sql_delete_article);

        if (!$stmt_article) {
            error_log("ArtikelController::delete prepare failed for article ID $id_val: " . mysqli_error($conn));
            return false;
        }

        mysqli_stmt_bind_param($stmt_article, "i", $id_val);
        if (mysqli_stmt_execute($stmt_article)) {
            $affected_rows = mysqli_stmt_affected_rows($stmt_article);
            mysqli_stmt_close($stmt_article);

            if ($affected_rows > 0) {
                // LANGKAH 3: Hapus file gambar artikel jika ada dan record DB berhasil dihapus
                if ($gambar_to_delete_on_server) {
                    $file_path = $upload_dir_artikel . $gambar_to_delete_on_server;
                    if (file_exists($file_path)) {
                        if (!@unlink($file_path)) {
                            error_log("ArtikelController::delete Warning: Gagal menghapus file gambar artikel " . $file_path);
                        }
                    }
                }
                return true; // Artikel dan feedback terkait berhasil dihapus
            } else {
                // Tidak ada artikel yang terhapus (mungkin ID tidak ditemukan setelah feedback dihapus)
                error_log("ArtikelController::delete Warning: Tidak ada artikel yang terhapus untuk ID $id_val (mungkin sudah dihapus atau ID tidak ada).");
                return false; // Atau true jika menganggap operasi "berhasil" karena artikel memang sudah tidak ada
            }
        } else {
            error_log("ArtikelController::delete execute failed for article ID $id_val: " . mysqli_stmt_error($stmt_article));
            mysqli_stmt_close($stmt_article);
            return false;
        }
    }
}
