<?php
// File: C:\xampp\htdocs\Cilengkrang-Web-Wisata\models\Feedback.php

class Feedback
{
    private static $table_name = "feedback";
    private static $db;

    public static function setDbConnection(mysqli $connection)
    {
        self::$db = $connection;
    }

    private static function checkDbConnection()
    {
        if (!self::$db || (self::$db instanceof mysqli && self::$db->connect_error)) {
            error_log(get_called_class() . " - Koneksi database tidak tersedia atau gagal: " .
                (self::$db instanceof mysqli ? self::$db->connect_error : (self::$db === null ? 'Koneksi belum diset.' : 'Koneksi bukan objek mysqli.')));
            return false;
        }
        return true;
    }

    public static function getLastError()
    {
        if (self::$db instanceof mysqli && !empty(self::$db->error)) {
            return self::$db->error;
        } elseif (!self::$db || !(self::$db instanceof mysqli)) {
            return 'Koneksi database belum diinisialisasi atau tidak valid untuk kelas ' . get_called_class() . '.';
        }
        return 'Tidak ada error database spesifik yang dilaporkan dari model ' . get_called_class() . '.';
    }

    public static function create($data)
    {
        if (!self::checkDbConnection()) return false;
        $user_id = isset($data['user_id']) && !empty($data['user_id']) ? (int)$data['user_id'] : null;
        $artikel_id = isset($data['artikel_id']) && !empty($data['artikel_id']) ? (int)$data['artikel_id'] : null;
        $komentar = trim($data['komentar'] ?? '');
        $rating = isset($data['rating']) ? (int)$data['rating'] : null;

        if (empty($komentar)) {
            error_log(get_called_class() . "::create() - Komentar kosong.");
            return false;
        }
        if ($rating !== null && ($rating < 1 || $rating > 5)) {
            error_log(get_called_class() . "::create() - Rating tidak valid: " . $rating);
            return false;
        }

        $sql = "INSERT INTO " . self::$table_name . " (user_id, artikel_id, komentar, rating, created_at) 
                VALUES (?, ?, ?, ?, NOW())";
        $stmt = mysqli_prepare(self::$db, $sql);
        if (!$stmt) {
            error_log(get_called_class() . "::create() - MySQLi Prepare Error: " . mysqli_error(self::$db));
            return false;
        }
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

    public static function getAll()
    {
        if (!self::checkDbConnection()) return [];
        $sql = "SELECT f.id, f.komentar, f.rating, f.created_at, 
                       u.nama_lengkap AS user_nama, u.email AS user_email,
                       a.judul AS artikel_judul
                FROM " . self::$table_name . " f
                LEFT JOIN users u ON f.user_id = u.id
                LEFT JOIN artikel a ON f.artikel_id = a.id
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

    public static function delete($id)
    {
        if (!self::checkDbConnection()) return false;
        $id_to_delete = filter_var($id, FILTER_VALIDATE_INT);
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
     * Menghapus semua feedback yang terkait dengan artikel_id tertentu.
     * @param int $artikel_id ID artikel.
     * @return bool True jika berhasil (atau tidak ada feedback untuk dihapus), false jika query error.
     */
    public static function deleteByArtikelId($artikel_id) // <<< METODE BARU DITAMBAHKAN
    {
        if (!self::checkDbConnection()) return false;
        $artikel_id_val = filter_var($artikel_id, FILTER_VALIDATE_INT);
        if ($artikel_id_val === false || $artikel_id_val <= 0) {
            error_log(get_called_class() . "::deleteByArtikelId() - artikel_id tidak valid: " . $artikel_id);
            return false;
        }

        $sql = "DELETE FROM " . self::$table_name . " WHERE artikel_id = ?";
        $stmt = mysqli_prepare(self::$db, $sql);
        if (!$stmt) {
            error_log(get_called_class() . "::deleteByArtikelId() - MySQLi Prepare Error: " . mysqli_error(self::$db));
            return false;
        }
        mysqli_stmt_bind_param($stmt, "i", $artikel_id_val);
        if (mysqli_stmt_execute($stmt)) {
            // $affected_rows = mysqli_stmt_affected_rows($stmt); // Bisa dicek jika perlu tahu berapa baris terhapus
            mysqli_stmt_close($stmt);
            return true; // Anggap berhasil jika query tereksekusi tanpa error
        } else {
            error_log(get_called_class() . "::deleteByArtikelId() - MySQLi Execute Error: " . mysqli_stmt_error($stmt));
            mysqli_stmt_close($stmt);
            return false;
        }
    }

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