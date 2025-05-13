<?php
// File: C:\xampp\htdocs\Cilengkrang-Web-Wisata\models\Feedback.php

class Feedback
{
    private static $table_name = "feedback"; // Sesuai dengan output DESCRIBE Anda
    private static $db; // Properti untuk menyimpan koneksi database

    /**
     * Mengatur koneksi database untuk digunakan oleh kelas ini.
     * @param mysqli $connection Instance koneksi mysqli.
     */
    public static function setDbConnection(mysqli $connection)
    {
        self::$db = $connection;
        // error_log(get_called_class() . "::setDbConnection dipanggil."); // Untuk debugging
    }

    /**
     * Memeriksa apakah koneksi database tersedia.
     * @return bool True jika koneksi valid, false jika tidak.
     */
    private static function checkDbConnection()
    {
        if (!self::$db || (self::$db instanceof mysqli && self::$db->connect_error)) {
            error_log(get_called_class() . " - Koneksi database tidak tersedia atau gagal: " .
                (self::$db instanceof mysqli ? self::$db->connect_error : (self::$db === null ? 'Koneksi belum diset.' : 'Koneksi bukan objek mysqli.')));
            return false;
        }
        return true;
    }

    /**
     * Menyisipkan feedback baru ke database.
     * @param array $data Data feedback, kunci yang diharapkan: 'komentar', 'rating'.
     *                    Opsional: 'user_id', 'artikel_id'.
     * @return int|false ID feedback baru jika berhasil, false jika gagal.
     */
    public static function create($data)
    {
        if (!self::checkDbConnection()) return false;

        // user_id dan artikel_id bisa NULL
        $user_id = isset($data['user_id']) && !empty($data['user_id']) ? (int)$data['user_id'] : null;
        $artikel_id = isset($data['artikel_id']) && !empty($data['artikel_id']) ? (int)$data['artikel_id'] : null;

        $komentar = trim($data['komentar'] ?? '');
        // Rating bisa 0 jika diizinkan, atau default ke null jika tidak diberikan dan kolom DB mengizinkan NULL
        $rating = isset($data['rating']) ? (int)$data['rating'] : null;

        // Validasi dasar
        if (empty($komentar)) {
            error_log(get_called_class() . "::create() - Komentar tidak boleh kosong.");
            return false;
        }
        // Validasi rating jika diberikan (misalnya, 1-5). Jika boleh null, hapus pengecekan ini.
        if ($rating !== null && ($rating < 1 || $rating > 5)) {
            error_log(get_called_class() . "::create() - Rating tidak valid (harus antara 1-5 atau null): " . $rating);
            return false;
        }

        // Kolom `created_at` diasumsikan memiliki DEFAULT CURRENT_TIMESTAMP di DB.
        // Jika tidak, Anda perlu menambahkannya ke query dan bind_param: `NOW()` atau $data['created_at']
        $sql = "INSERT INTO " . self::$table_name . " (user_id, artikel_id, komentar, rating, created_at) 
                VALUES (?, ?, ?, ?, NOW())"; // Asumsi created_at dihandle DB atau NOW()

        $stmt = mysqli_prepare(self::$db, $sql);
        if (!$stmt) {
            error_log(get_called_class() . "::create() - MySQLi Prepare Error: " . mysqli_error(self::$db));
            return false;
        }

        // Tipe data: i (user_id), i (artikel_id), s (komentar), i (rating)
        // Jika user_id atau artikel_id bisa NULL, Anda perlu logika bind yang berbeda atau pastikan PHP mengirim NULL dengan benar.
        // Untuk NULL, mysqli_stmt_bind_param akan mengikat NULL jika variabel PHP adalah null.
        mysqli_stmt_bind_param($stmt, "iisi", $user_id, $artikel_id, $komentar, $rating);

        if (mysqli_stmt_execute($stmt)) {
            $new_id = mysqli_insert_id(self::$db);
            mysqli_stmt_close($stmt);
            return $new_id;
        } else {
            error_log(get_called_class() . "::create() - MySQLi Execute Error: " . mysqli_stmt_error($stmt));
            mysqli_stmt_close($stmt);
            return false;
        }
    }

    /**
     * Mengambil semua feedback, dengan join ke tabel users dan articles.
     * @return array Array berisi record feedback, atau array kosong jika gagal/tidak ada.
     */
    public static function getAll()
    {
        if (!self::checkDbConnection()) return [];

        // Pastikan nama tabel 'articles' dan kolom 'nama_lengkap' di 'users' sudah benar
        $sql = "SELECT f.id, f.komentar, f.rating, f.created_at, 
                       u.nama_lengkap AS user_nama, u.email AS user_email,
                       a.judul AS artikel_judul
                FROM " . self::$table_name . " f
                LEFT JOIN users u ON f.user_id = u.id
                LEFT JOIN artikel a ON f.artikel_id = a.id  -- Ganti 'artikel' jika nama tabel artikel berbeda
                ORDER BY f.created_at DESC";

        $result = mysqli_query(self::$db, $sql);
        if ($result) {
            $data = mysqli_fetch_all($result, MYSQLI_ASSOC);
            mysqli_free_result($result);
            return $data;
        } else {
            error_log(get_called_class() . "::getAll() - MySQLi Query Error: " . mysqli_error(self::$db));
            return [];
        }
    }

    /**
     * Mencari feedback berdasarkan ID.
     * @param int $id ID feedback.
     * @return array|null Data feedback atau null.
     */
    public static function findById($id)
    {
        if (!self::checkDbConnection()) return null;
        $id_val = filter_var($id, FILTER_VALIDATE_INT);
        if ($id_val === false || $id_val <= 0) {
            error_log(get_called_class() . "::findById() - ID tidak valid: " . $id);
            return null;
        }

        $sql = "SELECT f.*, u.nama_lengkap AS user_nama, a.judul AS artikel_judul 
                FROM " . self::$table_name . " f
                LEFT JOIN users u ON f.user_id = u.id
                LEFT JOIN artikel a ON f.artikel_id = a.id
                WHERE f.id = ? LIMIT 1";
        $stmt = mysqli_prepare(self::$db, $sql);
        if (!$stmt) {
            error_log(get_called_class() . "::findById() - MySQLi Prepare Error: " . mysqli_error(self::$db));
            return null;
        }

        mysqli_stmt_bind_param($stmt, "i", $id_val);
        if (mysqli_stmt_execute($stmt)) {
            $result = mysqli_stmt_get_result($stmt);
            $feedback_item = mysqli_fetch_assoc($result);
            mysqli_stmt_close($stmt);
            return $feedback_item ?: null;
        } else {
            error_log(get_called_class() . "::findById() - MySQLi Execute Error: " . mysqli_stmt_error($stmt));
        }
        mysqli_stmt_close($stmt);
        return null;
    }


    /**
     * Menghapus feedback berdasarkan ID-nya.
     * @param int $id ID feedback yang akan dihapus.
     * @return bool True jika berhasil, false jika gagal.
     */
    public static function delete($id)
    {
        if (!self::checkDbConnection()) return false;
        $id_to_delete = filter_var($id, FILTER_VALIDATE_INT); // Menggunakan filter_var untuk sanitasi dan validasi

        if ($id_to_delete === false || $id_to_delete <= 0) {
            error_log(get_called_class() . "::delete() - ID tidak valid: " . $id);
            return false;
        }

        $sql = "DELETE FROM " . self::$table_name . " WHERE id = ?";
        $stmt = mysqli_prepare(self::$db, $sql);

        if (!$stmt) {
            error_log(get_called_class() . "::delete() - MySQLi Prepare Error: " . mysqli_error(self::$db));
            return false;
        }
        mysqli_stmt_bind_param($stmt, "i", $id_to_delete);
        if (mysqli_stmt_execute($stmt)) {
            $affected_rows = mysqli_stmt_affected_rows($stmt);
            mysqli_stmt_close($stmt);
            return $affected_rows > 0;
        } else {
            error_log(get_called_class() . "::delete() - MySQLi Execute Error: " . mysqli_stmt_error($stmt));
            mysqli_stmt_close($stmt);
            return false;
        }
    }

    /**
     * Menghitung semua record feedback.
     * @return int Total feedback.
     */
    public static function countAll()
    {
        if (!self::checkDbConnection()) return 0;
        $sql = "SELECT COUNT(id) as total FROM " . self::$table_name;
        $result = mysqli_query(self::$db, $sql);
        if ($result) {
            $row = mysqli_fetch_assoc($result);
            mysqli_free_result($result);
            return (int)($row['total'] ?? 0);
        } else {
            error_log(get_called_class() . "::countAll() - MySQLi Query Error: " . mysqli_error(self::$db));
        }
        return 0;
    }
} // End of class Feedback