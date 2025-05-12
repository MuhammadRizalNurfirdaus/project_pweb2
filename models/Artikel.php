<?php
// File: C:\xampp\htdocs\Cilengkrang-Web-Wisata\models\Artikel.php

/**
 * Class Artikel
 * Mengelola operasi database untuk tabel artikel.
 * Menggunakan pendekatan statis dan koneksi global $conn.
 */
class Artikel
{
    private static $table_name = "articles"; // Pastikan nama tabel ini benar

    /**
     * Membuat artikel baru.
     * @param array $data Harus berisi 'judul', 'isi'. Opsional: 'gambar'.
     * @return int|false ID artikel baru atau false jika gagal.
     */
    public static function create($data)
    {
        global $conn;
        if (!$conn) {
            error_log("Artikel::create() - Koneksi database gagal.");
            return false;
        }

        $judul = isset($data['judul']) ? htmlspecialchars(strip_tags(trim($data['judul']))) : '';
        $isi = $data['isi'] ?? '';
        $gambar = isset($data['gambar']) && !empty($data['gambar']) ? htmlspecialchars(strip_tags($data['gambar'])) : null;

        if (empty($judul) || empty($isi)) {
            error_log("Artikel::create() - Judul dan Isi tidak boleh kosong.");
            // set_flash_message di Controller
            return false;
        }

        $sql = "INSERT INTO " . self::$table_name . " (judul, isi, gambar, created_at) VALUES (?, ?, ?, NOW())";
        $stmt = mysqli_prepare($conn, $sql);
        if (!$stmt) {
            error_log("Artikel::create() - MySQLi Prepare Error: " . mysqli_error($conn));
            return false;
        }
        mysqli_stmt_bind_param($stmt, "sss", $judul, $isi, $gambar);
        if (mysqli_stmt_execute($stmt)) {
            $new_id = mysqli_insert_id($conn);
            mysqli_stmt_close($stmt);
            return $new_id;
        } else {
            error_log("Artikel::create() - MySQLi Execute Error: " . mysqli_stmt_error($stmt));
            mysqli_stmt_close($stmt);
            return false;
        }
    }

    /**
     * Mengambil semua artikel.
     * @return array Array data artikel atau array kosong jika gagal.
     */
    public static function getAll()
    {
        global $conn;
        if (!$conn) {
            error_log("Artikel::getAll() - Koneksi DB gagal.");
            return [];
        }
        // PERUBAHAN DI SINI: Mengurutkan berdasarkan ID ASC
        $sql = "SELECT id, judul, isi, gambar, created_at FROM " . self::$table_name . " ORDER BY id ASC";
        $result = mysqli_query($conn, $sql);
        if ($result) {
            $data = mysqli_fetch_all($result, MYSQLI_ASSOC);
            mysqli_free_result($result);
            return $data;
        } else {
            error_log("Artikel::getAll() - MySQLi Query Error: " . mysqli_error($conn));
            return [];
        }
    }

    /**
     * Mengambil satu artikel berdasarkan ID.
     * @param int $id ID artikel.
     * @return array|null Data artikel atau null jika tidak ditemukan/error.
     */
    public static function getById($id)
    {
        global $conn;
        if (!$conn) {
            error_log("Artikel::getById() - Koneksi DB gagal.");
            return null;
        }
        $id_val = filter_var($id, FILTER_VALIDATE_INT);
        if ($id_val === false || $id_val <= 0) {
            error_log("Artikel::getById() - ID tidak valid: " . e($id));
            return null;
        }
        $sql = "SELECT id, judul, isi, gambar, created_at FROM " . self::$table_name . " WHERE id = ? LIMIT 1";
        $stmt = mysqli_prepare($conn, $sql);
        if (!$stmt) {
            error_log("Artikel::getById() - Prepare Error: " . mysqli_error($conn));
            return null;
        }
        mysqli_stmt_bind_param($stmt, "i", $id_val);
        if (mysqli_stmt_execute($stmt)) {
            $result = mysqli_stmt_get_result($stmt);
            $artikel = mysqli_fetch_assoc($result);
            mysqli_stmt_close($stmt);
            return $artikel ?: null;
        } else {
            error_log("Artikel::getById() - Execute Error: " . mysqli_stmt_error($stmt));
            mysqli_stmt_close($stmt);
            return null;
        }
    }

    /**
     * Mengupdate artikel.
     * @param array $data Harus berisi 'id'. Opsional: 'judul', 'isi', 'gambar'.
     * @return bool True jika berhasil, false jika gagal.
     */
    public static function update($data)
    {
        global $conn;
        if (!$conn || !isset($data['id'])) {
            error_log("Artikel::update() - Koneksi DB gagal atau ID tidak ada.");
            return false;
        }
        $id = filter_var($data['id'], FILTER_VALIDATE_INT);
        if ($id === false || $id <= 0) {
            error_log("Artikel::update() - ID artikel tidak valid.");
            return false;
        }

        $current_artikel = self::getById($id); // Panggil metode statis
        if (!$current_artikel) {
            error_log("Artikel::update() - Artikel dengan ID {$id} tidak ditemukan.");
            return false;
        }

        $judul = isset($data['judul']) ? htmlspecialchars(strip_tags(trim($data['judul']))) : $current_artikel['judul'];
        $isi = $data['isi'] ?? $current_artikel['isi'];

        $fields_to_update = ["judul = ?", "isi = ?"];
        $params = [$judul, $isi];
        $types = "ss";

        // Handle gambar: hanya update jika 'gambar' ada di $data
        // Jika 'gambar' di $data adalah string kosong, itu berarti menghapus gambar.
        // Jika 'gambar' tidak ada di $data, gambar tidak diubah.
        if (array_key_exists('gambar', $data)) {
            $gambar_baru = !empty($data['gambar']) ? htmlspecialchars(strip_tags($data['gambar'])) : null;
            $fields_to_update[] = "gambar = ?";
            $params[] = $gambar_baru;
            $types .= "s";
        }

        if (empty($judul) || empty($isi)) {
            error_log("Artikel::update() - Judul dan Isi tidak boleh kosong.");
            return false;
        }

        $sql = "UPDATE " . self::$table_name . " SET " . implode(', ', $fields_to_update) . " WHERE id = ?";
        $params[] = $id;
        $types .= "i";

        $stmt = mysqli_prepare($conn, $sql);
        if (!$stmt) {
            error_log("Artikel::update() - Prepare Error: " . mysqli_error($conn));
            return false;
        }
        mysqli_stmt_bind_param($stmt, $types, ...$params);
        if (mysqli_stmt_execute($stmt)) {
            mysqli_stmt_close($stmt);
            return true;
        } else {
            error_log("Artikel::update() - Execute Error: " . mysqli_stmt_error($stmt));
            mysqli_stmt_close($stmt);
            return false;
        }
    }

    /**
     * Menghapus artikel berdasarkan ID.
     * Juga menghapus feedback terkait.
     * @param int $id ID artikel.
     * @return bool True jika berhasil, false jika gagal.
     */
    public static function delete($id)
    {
        global $conn;
        if (!$conn) {
            error_log("Artikel::delete() - Koneksi DB gagal.");
            return false;
        }
        $id_val = filter_var($id, FILTER_VALIDATE_INT);
        if ($id_val === false || $id_val <= 0) {
            error_log("Artikel::delete() - ID tidak valid: " . e($id));
            return false;
        }

        $artikel = self::getById($id_val); // Ambil info gambar
        $gambar_to_delete_on_server = null;
        if ($artikel && !empty($artikel['gambar'])) {
            $gambar_to_delete_on_server = $artikel['gambar'];
        }

        // Hapus feedback terkait
        $sql_delete_feedback = "DELETE FROM feedback WHERE artikel_id = ?";
        $stmt_feedback = mysqli_prepare($conn, $sql_delete_feedback);
        if ($stmt_feedback) {
            mysqli_stmt_bind_param($stmt_feedback, "i", $id_val);
            mysqli_stmt_execute($stmt_feedback); // Tidak krusial jika gagal, tapi log akan bagus
            mysqli_stmt_close($stmt_feedback);
        }

        // Hapus artikel
        $sql_delete_article = "DELETE FROM " . self::$table_name . " WHERE id = ?";
        $stmt_article = mysqli_prepare($conn, $sql_delete_article);
        if (!$stmt_article) {
            error_log("Artikel::delete() - Prepare Error (artikel): " . mysqli_error($conn));
            return false;
        }
        mysqli_stmt_bind_param($stmt_article, "i", $id_val);
        if (mysqli_stmt_execute($stmt_article)) {
            $affected_rows = mysqli_stmt_affected_rows($stmt_article);
            mysqli_stmt_close($stmt_article);
            if ($affected_rows > 0) {
                if ($gambar_to_delete_on_server && defined('UPLOADS_ARTIKEL_PATH')) {
                    $file_path = UPLOADS_ARTIKEL_PATH . DIRECTORY_SEPARATOR . basename($gambar_to_delete_on_server);
                    if (file_exists($file_path) && is_file($file_path)) {
                        @unlink($file_path);
                    }
                }
                return true;
            }
            return false; // Tidak ada baris yang terhapus
        } else {
            error_log("Artikel::delete() - Execute Error (artikel): " . mysqli_stmt_error($stmt_article));
            mysqli_stmt_close($stmt_article);
            return false;
        }
    }

    /**
     * Mengambil sejumlah artikel terbaru.
     * @param int $limit Jumlah artikel yang ingin diambil.
     * @return array Array data artikel atau array kosong.
     */
    public static function getLatest($limit = 3)
    {
        global $conn;
        if (!$conn) {
            return [];
        }
        $limit_val = filter_var($limit, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($limit_val === false) $limit_val = 3;
        $sql = "SELECT id, judul, isi, gambar, created_at FROM " . self::$table_name . " ORDER BY created_at DESC LIMIT ?";
        $stmt = mysqli_prepare($conn, $sql);
        if (!$stmt) {
            return [];
        }
        mysqli_stmt_bind_param($stmt, "i", $limit_val);
        if (mysqli_stmt_execute($stmt)) {
            $result = mysqli_stmt_get_result($stmt);
            $articles = mysqli_fetch_all($result, MYSQLI_ASSOC);
            mysqli_stmt_close($stmt);
            return $articles;
        }
        mysqli_stmt_close($stmt);
        return [];
    }

    /**
     * Menghitung semua artikel.
     * @return int Jumlah artikel atau 0 jika error.
     */
    public static function countAll()
    {
        global $conn;
        if (!$conn) {
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
}
