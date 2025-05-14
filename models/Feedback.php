<?php
// File: C:\xampp\htdocs\Cilengkrang-Web-Wisata\models\Feedback.php

class Feedback
{
    private static $table_name = "feedback";
    private static $db;
    private static $last_error = null; // Untuk menyimpan pesan error terakhir dari operasi model

    /**
     * Mengatur koneksi database untuk model ini.
     * @param mysqli $connection Objek koneksi mysqli.
     */
    public static function setDbConnection(mysqli $connection)
    {
        self::$db = $connection;
        // Baris error_log di bawah ini berguna saat development, bisa dikomentari untuk produksi.
        // error_log(get_called_class() . "::setDbConnection dipanggil. Koneksi " . (self::$db && !self::$db->connect_error ? "BERHASIL" : "GAGAL atau belum diset"));
    }

    /**
     * Memeriksa apakah koneksi database valid dan siap digunakan.
     * @return bool True jika koneksi OK, false jika ada masalah.
     */
    private static function checkDbConnection()
    {
        if (!self::$db || (self::$db instanceof mysqli && self::$db->connect_error)) {
            self::$last_error = (self::$db instanceof mysqli ? self::$db->connect_error : (self::$db === null ? 'Koneksi database belum diset.' : 'Koneksi bukan objek mysqli.'));
            error_log(get_called_class() . " - Koneksi database tidak tersedia atau gagal: " . self::$last_error);
            return false;
        }
        self::$last_error = null; // Reset error jika koneksi OK
        return true;
    }

    /**
     * Mengambil pesan error terakhir yang terjadi di model ini.
     * @return string|null Pesan error atau null jika tidak ada.
     */
    public static function getLastError()
    {
        if (self::$last_error) {
            return self::$last_error;
        }
        if (self::$db instanceof mysqli && !empty(self::$db->error)) {
            return self::$db->error; // Error dari operasi mysqli terakhir
        } elseif (!self::$db || !(self::$db instanceof mysqli)) {
            return 'Koneksi database belum diinisialisasi atau tidak valid untuk kelas ' . get_called_class() . '.';
        }
        return null; // Tidak ada error spesifik yang dilaporkan
    }

    /**
     * Membuat feedback baru di database.
     * @param array $data Data feedback, harus berisi 'artikel_id', 'komentar', 'rating'.
     *                    'user_id' opsional (jika null, diasumsikan feedback tamu).
     * @return int|false ID feedback baru jika berhasil, false jika gagal. Error di-set di self::$last_error.
     */
    public static function create(array $data)
    {
        if (!self::checkDbConnection()) return false;

        $user_id = isset($data['user_id']) && !empty($data['user_id']) ? (int)$data['user_id'] : null;
        $artikel_id = isset($data['artikel_id']) && !empty($data['artikel_id']) ? (int)$data['artikel_id'] : null;
        $komentar = trim($data['komentar'] ?? '');
        // Pastikan rating tidak string kosong sebelum di-cast ke int, agar null tetap null
        $rating_input = $data['rating'] ?? null;
        $rating = ($rating_input !== null && $rating_input !== '') ? (int)$rating_input : null;


        // Validasi input
        if (!$artikel_id) {
            self::$last_error = "ID Artikel harus disertakan untuk membuat feedback.";
            error_log(get_called_class() . "::create() - " . self::$last_error);
            return false;
        }
        if (empty($komentar)) {
            self::$last_error = "Komentar tidak boleh kosong.";
            error_log(get_called_class() . "::create() - " . self::$last_error);
            return false;
        }
        if ($rating === null || $rating < 1 || $rating > 5) {
            self::$last_error = "Rating tidak valid atau kosong (harus antara 1 dan 5). Diberikan: '" . print_r($rating_input, true) . "'";
            error_log(get_called_class() . "::create() - " . self::$last_error);
            return false;
        }

        $sql = "INSERT INTO " . self::$table_name . " (user_id, artikel_id, komentar, rating, created_at) 
                VALUES (?, ?, ?, ?, NOW())";
        $stmt = mysqli_prepare(self::$db, $sql);
        if (!$stmt) {
            self::$last_error = "MySQLi Prepare Error: " . mysqli_error(self::$db);
            error_log(get_called_class() . "::create() - " . self::$last_error . " | SQL: " . $sql);
            return false;
        }

        // Tipe 'i' untuk user_id akan menangani PHP null dengan benar jika kolom DB adalah NULLABLE.
        mysqli_stmt_bind_param($stmt, "iisi", $user_id, $artikel_id, $komentar, $rating);

        if (mysqli_stmt_execute($stmt)) {
            $new_id = mysqli_insert_id(self::$db);
            mysqli_stmt_close($stmt);
            return $new_id;
        } else {
            self::$last_error = "MySQLi Execute Error: " . mysqli_stmt_error($stmt);
            error_log(get_called_class() . "::create() - " . self::$last_error);
            mysqli_stmt_close($stmt);
            return false;
        }
    }

    /**
     * Mengambil semua feedback, di-join dengan nama user dan judul artikel.
     * Diurutkan berdasarkan tanggal pembuatan terbaru.
     * @return array Array data feedback, atau array kosong jika gagal/tidak ada data.
     */
    public static function getAll()
    {
        if (!self::checkDbConnection()) return [];

        $nama_tabel_artikel = 'articles'; // Sesuai struktur tabel Anda

        $sql = "SELECT f.id, f.user_id, f.artikel_id, f.komentar, f.rating, f.created_at, 
                       COALESCE(u.nama_lengkap, 'Pengunjung') AS user_nama, 
                       a.judul AS artikel_judul
                FROM " . self::$table_name . " f
                LEFT JOIN users u ON f.user_id = u.id
                LEFT JOIN " . $nama_tabel_artikel . " a ON f.artikel_id = a.id 
                ORDER BY f.created_at DESC";

        $result = mysqli_query(self::$db, $sql);
        if ($result) {
            $data = mysqli_fetch_all($result, MYSQLI_ASSOC);
            mysqli_free_result($result);
            return $data;
        } else {
            self::$last_error = "Query getAll GAGAL. MySQLi Error: " . mysqli_error(self::$db);
            error_log(get_called_class() . "::getAll() - " . self::$last_error . " | SQL: " . $sql);
            return [];
        }
    }

    /**
     * Mencari feedback berdasarkan ID feedback.
     * @param int $id ID dari feedback yang dicari.
     * @return array|null Data feedback jika ditemukan, atau null jika tidak ada/error.
     */
    public static function findById($id)
    {
        if (!self::checkDbConnection()) return null;

        $id_val = filter_var($id, FILTER_VALIDATE_INT);
        if ($id_val === false || $id_val <= 0) {
            self::$last_error = "ID feedback tidak valid: '" . print_r($id, true) . "'";
            error_log(get_called_class() . "::findById() - " . self::$last_error);
            return null;
        }

        $nama_tabel_artikel = 'articles';

        $sql = "SELECT f.*, 
                       COALESCE(u.nama_lengkap, 'Pengunjung') AS user_nama, 
                       a.judul AS artikel_judul 
                FROM " . self::$table_name . " f
                LEFT JOIN users u ON f.user_id = u.id
                LEFT JOIN " . $nama_tabel_artikel . " a ON f.artikel_id = a.id
                WHERE f.id = ? LIMIT 1";

        $stmt = mysqli_prepare(self::$db, $sql);
        if (!$stmt) {
            self::$last_error = "MySQLi Prepare Error: " . mysqli_error(self::$db);
            error_log(get_called_class() . "::findById() - " . self::$last_error . " | SQL: " . $sql);
            return null;
        }

        mysqli_stmt_bind_param($stmt, "i", $id_val);
        if (mysqli_stmt_execute($stmt)) {
            $result_set = mysqli_stmt_get_result($stmt);
            $feedback_item = mysqli_fetch_assoc($result_set);
            mysqli_stmt_close($stmt);
            return $feedback_item ?: null; // Mengembalikan null jika fetch_assoc tidak menghasilkan data
        } else {
            self::$last_error = "MySQLi Execute Error: " . mysqli_stmt_error($stmt);
            error_log(get_called_class() . "::findById() - " . self::$last_error);
            mysqli_stmt_close($stmt);
            return null;
        }
    }

    /**
     * Mengambil semua feedback untuk artikel tertentu, diurutkan berdasarkan tanggal terbaru.
     * @param int $artikel_id ID dari artikel.
     * @return array Array berisi feedback atau array kosong jika tidak ada/error.
     */
    public static function getByArtikelId($artikel_id)
    {
        if (!self::checkDbConnection()) return [];

        $artikel_id_val = filter_var($artikel_id, FILTER_VALIDATE_INT);
        if ($artikel_id_val === false || $artikel_id_val <= 0) {
            self::$last_error = "ID Artikel tidak valid untuk getByArtikelId: '" . print_r($artikel_id, true) . "'";
            error_log(get_called_class() . "::getByArtikelId() - " . self::$last_error);
            return [];
        }

        $sql = "SELECT f.id, f.user_id, f.artikel_id, f.komentar, f.rating, f.created_at,
                       COALESCE(u.nama_lengkap, 'Pengunjung') AS user_nama 
                FROM " . self::$table_name . " f
                LEFT JOIN users u ON f.user_id = u.id
                WHERE f.artikel_id = ? 
                ORDER BY f.created_at DESC";

        $stmt = mysqli_prepare(self::$db, $sql);
        if (!$stmt) {
            self::$last_error = "MySQLi Prepare Error: " . mysqli_error(self::$db);
            error_log(get_called_class() . "::getByArtikelId() - " . self::$last_error . " | SQL: " . $sql);
            return [];
        }

        mysqli_stmt_bind_param($stmt, "i", $artikel_id_val);
        if (mysqli_stmt_execute($stmt)) {
            $result_set = mysqli_stmt_get_result($stmt);
            $feedbacks = mysqli_fetch_all($result_set, MYSQLI_ASSOC);
            mysqli_stmt_close($stmt);
            return $feedbacks;
        } else {
            self::$last_error = "MySQLi Execute Error: " . mysqli_stmt_error($stmt);
            error_log(get_called_class() . "::getByArtikelId() - " . self::$last_error);
            mysqli_stmt_close($stmt);
            return [];
        }
    }

    /**
     * Menghapus feedback berdasarkan ID feedback.
     * @param int $id ID feedback yang akan dihapus.
     * @return bool True jika berhasil menghapus, false jika gagal.
     */
    public static function delete($id)
    {
        if (!self::checkDbConnection()) return false;

        $id_to_delete = filter_var($id, FILTER_VALIDATE_INT);
        if ($id_to_delete === false || $id_to_delete <= 0) {
            self::$last_error = "ID feedback tidak valid untuk dihapus: '" . print_r($id, true) . "'";
            error_log(get_called_class() . "::delete() - " . self::$last_error);
            return false;
        }

        $sql = "DELETE FROM " . self::$table_name . " WHERE id = ?";
        $stmt = mysqli_prepare(self::$db, $sql);
        if (!$stmt) {
            self::$last_error = "MySQLi Prepare Error: " . mysqli_error(self::$db);
            error_log(get_called_class() . "::delete() - " . self::$last_error . " | SQL: " . $sql);
            return false;
        }

        mysqli_stmt_bind_param($stmt, "i", $id_to_delete);
        if (mysqli_stmt_execute($stmt)) {
            $affected_rows = mysqli_stmt_affected_rows($stmt);
            mysqli_stmt_close($stmt);
            return $affected_rows > 0; // Berhasil jika setidaknya satu baris terpengaruh
        } else {
            self::$last_error = "MySQLi Execute Error: " . mysqli_stmt_error($stmt);
            error_log(get_called_class() . "::delete() - " . self::$last_error);
            mysqli_stmt_close($stmt);
            return false;
        }
    }

    /**
     * Menghapus semua feedback yang terkait dengan ID artikel tertentu.
     * Berguna saat artikel dihapus.
     * @param int $artikel_id ID artikel.
     * @return bool True jika query berhasil dieksekusi (bahkan jika tidak ada baris yang dihapus), false jika query gagal.
     */
    public static function deleteByArtikelId($artikel_id)
    {
        if (!self::checkDbConnection()) return false;

        $artikel_id_val = filter_var($artikel_id, FILTER_VALIDATE_INT);
        if ($artikel_id_val === false || $artikel_id_val <= 0) {
            self::$last_error = "ID Artikel tidak valid untuk deleteByArtikelId: '" . print_r($artikel_id, true) . "'";
            error_log(get_called_class() . "::deleteByArtikelId() - " . self::$last_error);
            return false;
        }

        $sql = "DELETE FROM " . self::$table_name . " WHERE artikel_id = ?";
        $stmt = mysqli_prepare(self::$db, $sql);
        if (!$stmt) {
            self::$last_error = "MySQLi Prepare Error: " . mysqli_error(self::$db);
            error_log(get_called_class() . "::deleteByArtikelId() - " . self::$last_error . " | SQL: " . $sql);
            return false;
        }

        mysqli_stmt_bind_param($stmt, "i", $artikel_id_val);
        if (mysqli_stmt_execute($stmt)) {
            mysqli_stmt_close($stmt);
            return true; // Dianggap berhasil jika query berjalan tanpa error
        } else {
            self::$last_error = "MySQLi Execute Error: " . mysqli_stmt_error($stmt);
            error_log(get_called_class() . "::deleteByArtikelId() - " . self::$last_error);
            mysqli_stmt_close($stmt);
            return false;
        }
    }

    /**
     * Menghitung jumlah total feedback dalam database.
     * @return int Jumlah feedback, atau 0 jika terjadi error.
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
            self::$last_error = "Query countAll GAGAL. MySQLi Error: " . mysqli_error(self::$db);
            error_log(get_called_class() . "::countAll() - " . self::$last_error . " | SQL: " . $sql);
            return 0;
        }
    }
}
