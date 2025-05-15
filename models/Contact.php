<?php
// File: C:\xampp\htdocs\Cilengkrang-Web-Wisata\models\Contact.php

class Contact
{
    private static $table_name = "contacts";
    private static $db; // Tambahkan properti untuk koneksi
    private static $last_error = null;

    /**
     * Mengatur koneksi database untuk digunakan oleh kelas ini.
     * @param mysqli $connection Instance koneksi mysqli.
     */
    public static function setDbConnection(mysqli $connection)
    {
        self::$db = $connection;
    }

    private static function checkDbConnection(): bool
    {
        self::$last_error = null;
        if (!self::$db || (self::$db instanceof mysqli && self::$db->connect_error)) {
            self::$last_error = (self::$db instanceof mysqli ? self::$db->connect_error : (self::$db === null ? 'Koneksi DB (self::$db) belum diset.' : 'Koneksi DB bukan objek mysqli.'));
            error_log(get_called_class() . " - Koneksi Error: " . self::$last_error);
            return false;
        }
        return true;
    }

    public static function getLastError(): ?string
    {
        if (self::$last_error) return self::$last_error;
        if (self::$db instanceof mysqli && !empty(self::$db->error)) return self::$db->error;
        return null;
    }


    /**
     * Menyisipkan pesan kontak baru ke database.
     * @param array $data Array asosiatif dengan kunci ['nama', 'email', 'pesan'].
     * @return int|false ID dari pesan kontak baru jika berhasil, false jika gagal.
     */
    public static function create($data)
    {
        // global $conn; // HAPUS PENGGUNAAN GLOBAL $conn
        if (!self::checkDbConnection()) { // GUNAKAN KONEKSI DARI self::$db
            // error_log("Contact::create() - Koneksi database gagal."); // Sudah di-log oleh checkDbConnection
            return false;
        }

        $nama = trim($data['nama'] ?? '');
        $email = trim($data['email'] ?? '');
        $pesan = trim($data['pesan'] ?? '');

        if (empty($nama)) {
            self::$last_error = "Nama tidak boleh kosong.";
            error_log(get_called_class() . "::create() - " . self::$last_error);
            return false;
        }
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            self::$last_error = "Email tidak valid: " . $email;
            error_log(get_called_class() . "::create() - " . self::$last_error);
            return false;
        }
        if (empty($pesan)) {
            self::$last_error = "Pesan tidak boleh kosong.";
            error_log(get_called_class() . "::create() - " . self::$last_error);
            return false;
        }

        $sql = "INSERT INTO " . self::$table_name . " (nama, email, pesan, created_at) VALUES (?, ?, ?, NOW())"; // Asumsi created_at ada
        $stmt = mysqli_prepare(self::$db, $sql); // Gunakan self::$db

        if (!$stmt) {
            self::$last_error = "MySQLi Prepare Error: " . mysqli_error(self::$db);
            error_log(get_called_class() . "::create() - " . self::$last_error . " SQL: " . $sql);
            return false;
        }

        mysqli_stmt_bind_param($stmt, "sss", $nama, $email, $pesan);

        if (mysqli_stmt_execute($stmt)) {
            $new_id = mysqli_insert_id(self::$db); // Gunakan self::$db
            mysqli_stmt_close($stmt);
            return $new_id;
        } else {
            self::$last_error = "MySQLi Execute Error: " . mysqli_stmt_error($stmt);
            error_log(get_called_class() . "::create() - " . self::$last_error . " SQL: " . $sql);
            mysqli_stmt_close($stmt);
            return false;
        }
    }

    public static function getAll()
    {
        // global $conn; // HAPUS
        if (!self::checkDbConnection()) return []; // GUNAKAN self::$db

        $sql = "SELECT id, nama, email, pesan, created_at 
                FROM " . self::$table_name . " 
                ORDER BY created_at DESC";
        $result = mysqli_query(self::$db, $sql); // Gunakan self::$db

        if ($result) {
            $data = mysqli_fetch_all($result, MYSQLI_ASSOC);
            mysqli_free_result($result);
            return $data;
        } else {
            self::$last_error = "MySQLi Query Error: " . mysqli_error(self::$db);
            error_log(get_called_class() . "::getAll() - " . self::$last_error . " SQL: " . $sql);
            return [];
        }
    }

    public static function delete($id)
    {
        // global $conn; // HAPUS
        if (!self::checkDbConnection()) return false; // GUNAKAN self::$db

        $id_to_delete = filter_var($id, FILTER_VALIDATE_INT);
        if ($id_to_delete === false || $id_to_delete <= 0) {
            self::$last_error = "ID tidak valid (" . $id . ").";
            error_log(get_called_class() . "::delete() - " . self::$last_error);
            return false;
        }

        $sql = "DELETE FROM " . self::$table_name . " WHERE id = ?";
        $stmt = mysqli_prepare(self::$db, $sql); // Gunakan self::$db

        if (!$stmt) {
            self::$last_error = "MySQLi Prepare Error: " . mysqli_error(self::$db);
            error_log(get_called_class() . "::delete() - " . self::$last_error . " SQL: " . $sql);
            return false;
        }

        mysqli_stmt_bind_param($stmt, "i", $id_to_delete);

        if (mysqli_stmt_execute($stmt)) {
            $affected_rows = mysqli_stmt_affected_rows($stmt);
            mysqli_stmt_close($stmt);
            if ($affected_rows > 0) {
                return true;
            } else {
                // self::$last_error = "Tidak ada baris yang terhapus untuk ID: " . $id_to_delete; // Tidak perlu set error jika memang tidak ada
                // error_log(get_called_class() . "::delete() - " . self::$last_error);
                return false;
            }
        } else {
            self::$last_error = "MySQLi Execute Error: " . mysqli_stmt_error($stmt);
            error_log(get_called_class() . "::delete() - " . self::$last_error . " SQL: " . $sql);
            mysqli_stmt_close($stmt);
            return false;
        }
    }
}
