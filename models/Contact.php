<?php
// File: C:\xampp\htdocs\Cilengkrang-Web-Wisata\models\Contact.php

class Contact
{
    private static $table_name = "contacts"; // Sesuai dengan tabel di database Anda

    /**
     * Menyisipkan pesan kontak baru ke database.
     * @param array $data Array asosiatif dengan kunci ['nama', 'email', 'pesan'].
     * @return int|false ID dari pesan kontak baru jika berhasil, false jika gagal.
     */
    public static function create($data)
    {
        global $conn; // Menggunakan koneksi global dari config.php
        if (!$conn) {
            error_log("Contact::create() - Koneksi database gagal.");
            return false;
        }

        // Ambil dan trim data, berikan default string kosong jika tidak ada
        $nama = trim($data['nama'] ?? '');
        $email = trim($data['email'] ?? '');
        $pesan = trim($data['pesan'] ?? '');

        // Validasi dasar di Model
        if (empty($nama)) {
            error_log("Contact::create() - Error: Nama tidak boleh kosong.");
            return false;
        }
        if (empty($email)) {
            error_log("Contact::create() - Error: Email tidak boleh kosong.");
            return false;
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            error_log("Contact::create() - Error: Format email tidak valid untuk email: " . $email);
            return false;
        }
        if (empty($pesan)) {
            error_log("Contact::create() - Error: Pesan tidak boleh kosong.");
            return false;
        }

        // Kolom 'created_at' diasumsikan memiliki DEFAULT CURRENT_TIMESTAMP di database
        $sql = "INSERT INTO " . self::$table_name . " (nama, email, pesan) VALUES (?, ?, ?)";
        $stmt = mysqli_prepare($conn, $sql);

        if (!$stmt) {
            error_log("Contact::create() - MySQLi Prepare Error: " . mysqli_error($conn) . " SQL: " . $sql);
            return false;
        }

        mysqli_stmt_bind_param($stmt, "sss", $nama, $email, $pesan);

        if (mysqli_stmt_execute($stmt)) {
            $new_id = mysqli_insert_id($conn);
            mysqli_stmt_close($stmt);
            return $new_id; // Kembalikan ID jika berhasil
        } else {
            error_log("Contact::create() - MySQLi Execute Error: " . mysqli_stmt_error($stmt) . " SQL: " . $sql);
            mysqli_stmt_close($stmt);
            return false;
        }
    }

    /**
     * Mengambil semua pesan kontak dari database.
     * @return array Array data pesan kontak, atau array kosong jika tidak ada data atau terjadi error.
     */
    public static function getAll()
    {
        global $conn;
        if (!$conn) {
            error_log("Contact::getAll() - Koneksi database gagal.");
            return [];
        }

        $sql = "SELECT id, nama, email, pesan, created_at 
                FROM " . self::$table_name . " 
                ORDER BY created_at DESC";
        $result = mysqli_query($conn, $sql);

        if ($result) {
            return mysqli_fetch_all($result, MYSQLI_ASSOC);
        } else {
            error_log("Contact::getAll() - MySQLi Query Error: " . mysqli_error($conn) . " SQL: " . $sql);
            return []; // Kembalikan array kosong jika error
        }
    }

    /**
     * Menghapus pesan kontak berdasarkan ID.
     * @param int $id ID pesan kontak yang akan dihapus.
     * @return bool True jika berhasil menghapus, false jika gagal atau ID tidak valid/ditemukan.
     */
    public static function delete($id)
    {
        global $conn;
        if (!$conn) {
            error_log("Contact::delete() - Koneksi database gagal.");
            return false;
        }

        $id_to_delete = filter_var($id, FILTER_VALIDATE_INT);
        if ($id_to_delete === false || $id_to_delete <= 0) {
            error_log("Contact::delete() - Error: ID tidak valid (" . $id . ").");
            return false;
        }

        $sql = "DELETE FROM " . self::$table_name . " WHERE id = ?";
        $stmt = mysqli_prepare($conn, $sql);

        if (!$stmt) {
            error_log("Contact::delete() - MySQLi Prepare Error: " . mysqli_error($conn) . " SQL: " . $sql);
            return false;
        }

        mysqli_stmt_bind_param($stmt, "i", $id_to_delete);

        if (mysqli_stmt_execute($stmt)) {
            $affected_rows = mysqli_stmt_affected_rows($stmt);
            mysqli_stmt_close($stmt);
            if ($affected_rows > 0) {
                return true; // Berhasil menghapus
            } else {
                error_log("Contact::delete() - Tidak ada baris yang terhapus untuk ID: " . $id_to_delete . " (mungkin ID tidak ditemukan).");
                return false; // Tidak ada baris yang terpengaruh
            }
        } else {
            error_log("Contact::delete() - MySQLi Execute Error: " . mysqli_stmt_error($stmt) . " SQL: " . $sql);
            mysqli_stmt_close($stmt);
            return false;
        }
    }

    // Jika Anda memerlukan method getById() di masa depan:
    // public static function getById($id) { ... }
}
