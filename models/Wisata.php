<?php
// File: C:\xampp\htdocs\Cilengkrang-Web-Wisata\models\Wisata.php

class Wisata
{
    private static $table_name = "wisata";
    private static $db;
    private static $upload_dir_wisata; // Path upload untuk gambar wisata
    private static $last_error = null;

    public static function init(mysqli $connection, string $upload_path)
    {
        self::$db = $connection;
        self::$upload_dir_wisata = rtrim($upload_path, '/\\') . DIRECTORY_SEPARATOR; // Pastikan ada separator di akhir
        // error_log(get_called_class() . "::init() dipanggil. DB: " . (self::$db && !self::$db->connect_error ? "OK" : "GAGAL") . ". Upload Dir Wisata: " . self::$upload_dir_wisata);
    }

    private static function checkDbConnection(): bool
    {
        self::$last_error = null; // Selalu reset error di awal pengecekan
        if (!self::$db || !self::$db instanceof mysqli || (self::$db instanceof mysqli && self::$db->connect_error)) {
            self::$last_error = "Koneksi database untuk Model Wisata gagal atau belum diinisialisasi.";
            $db_conn_error = self::$db instanceof mysqli ? self::$db->connect_error : 'Objek DB null atau bukan instance mysqli.';
            error_log(get_called_class() . " - DB Error: " . self::$last_error . " Detail: " . $db_conn_error);
            return false;
        }
        return true;
    }

    private static function checkUploadDir(): bool
    {
        // Pengecekan upload_dir hanya relevan jika akan melakukan operasi file (seperti delete)
        if (empty(self::$upload_dir_wisata) || !is_dir(self::$upload_dir_wisata)) {
            self::$last_error = 'Path upload direktori wisata (self::$upload_dir_wisata) tidak valid atau bukan direktori. Path: ' . (self::$upload_dir_wisata ?: 'Kosong/Tidak Diset');
            error_log(get_called_class() . " - Upload Dir Error: " . self::$last_error);
            return false;
        }
        // Pengecekan is_writable bisa dilakukan tepat sebelum operasi unlink/move_uploaded_file
        return true;
    }

    public static function getLastError(): ?string
    {
        $error = self::$last_error;
        self::$last_error = null; // Reset error setelah diambil (opsional, tapi baik untuk operasi berikutnya)
        if ($error) {
            return $error;
        }
        if (self::$db instanceof mysqli && !empty(self::$db->error)) {
            return "MySQLi Error: " . self::$db->error;
        }
        // Hapus pesan "Tidak ada error..." agar null jika memang tidak ada
        return null;
    }

    /**
     * Mencari data wisata berdasarkan ID.
     * @param int $id ID Wisata.
     * @return array|null Data wisata jika ditemukan, atau null jika tidak atau error.
     */
    public static function findById(int $id): ?array
    {
        if (!self::checkDbConnection()) {
            return null; // self::$last_error sudah diset
        }
        if ($id <= 0) {
            self::$last_error = "ID Wisata tidak valid: " . $id;
            error_log(get_called_class() . "::findById() - " . self::$last_error);
            return null;
        }

        $sql = "SELECT id, nama, deskripsi, gambar, lokasi, created_at, updated_at 
                FROM " . self::$table_name . " 
                WHERE id = ? LIMIT 1";
        $stmt = mysqli_prepare(self::$db, $sql);

        if (!$stmt) {
            self::$last_error = "MySQLi Prepare Error (findById): " . mysqli_error(self::$db);
            error_log(get_called_class() . "::findById() - " . self::$last_error . " | SQL: " . $sql);
            return null;
        }

        mysqli_stmt_bind_param($stmt, "i", $id);

        if (mysqli_stmt_execute($stmt)) {
            $result = mysqli_stmt_get_result($stmt);
            $item = mysqli_fetch_assoc($result);
            mysqli_stmt_close($stmt);
            if (!$item) {
                // Ini bukan error, hanya data tidak ditemukan. Tidak perlu set self::$last_error.
                // Controller bisa menangani item null.
                // error_log(get_called_class() . "::findById() - Info: Data tidak ditemukan untuk ID: " . $id);
            }
            return $item ?: null;
        } else {
            self::$last_error = "MySQLi Execute Error (findById): " . mysqli_stmt_error($stmt);
            error_log(get_called_class() . "::findById() - " . self::$last_error);
            mysqli_stmt_close($stmt);
            return null;
        }
    }

    public static function create(array $data): int|false
    {
        if (!self::checkDbConnection()) return false;
        self::$last_error = null;

        $nama = trim($data['nama'] ?? '');
        $deskripsi = trim($data['deskripsi'] ?? '');
        $gambar = isset($data['gambar']) && !empty(trim($data['gambar'])) ? trim($data['gambar']) : null;
        $lokasi = isset($data['lokasi']) && !empty(trim($data['lokasi'])) ? trim($data['lokasi']) : null;

        if (empty($nama)) {
            self::$last_error = "Nama destinasi wisata tidak boleh kosong.";
            error_log(get_called_class() . "::create() - " . self::$last_error);
            return false;
        }
        if (empty($deskripsi)) {
            self::$last_error = "Deskripsi wisata tidak boleh kosong.";
            error_log(get_called_class() . "::create() - " . self::$last_error);
            return false;
        }
        // Gambar bisa opsional saat create, tergantung Controller. Jika dikirim null, akan disimpan null.

        // created_at dan updated_at dihandle otomatis oleh DB (DEFAULT CURRENT_TIMESTAMP / ON UPDATE)
        $sql = "INSERT INTO " . self::$table_name . " (nama, deskripsi, gambar, lokasi) 
                VALUES (?, ?, ?, ?)";
        $stmt = mysqli_prepare(self::$db, $sql);

        if (!$stmt) {
            self::$last_error = "MySQLi Prepare Error (create): " . mysqli_error(self::$db);
            error_log(get_called_class() . "::create() - " . self::$last_error . " | SQL: " . $sql);
            return false;
        }

        mysqli_stmt_bind_param($stmt, "ssss", $nama, $deskripsi, $gambar, $lokasi);

        if (mysqli_stmt_execute($stmt)) {
            $new_id = mysqli_insert_id(self::$db);
            mysqli_stmt_close($stmt);
            return $new_id > 0 ? $new_id : false; // Pastikan ID valid
        } else {
            self::$last_error = "MySQLi Execute Error (create): " . mysqli_stmt_error($stmt);
            error_log(get_called_class() . "::create() - " . self::$last_error);
            mysqli_stmt_close($stmt);
            return false;
        }
    }

    public static function getAll(string $orderBy = 'nama ASC', ?int $limit = null): array
    {
        if (!self::checkDbConnection()) return [];
        self::$last_error = null;

        $allowed_order_columns = ['id', 'nama', 'created_at', 'updated_at', 'lokasi'];
        $order_parts = explode(' ', trim($orderBy), 2);
        $column = $order_parts[0] ?? 'nama';
        $direction = (isset($order_parts[1]) && strtoupper($order_parts[1]) === 'DESC') ? 'DESC' : 'ASC';
        if (!in_array(strtolower($column), $allowed_order_columns, true)) {
            $column = 'nama';
            $direction = 'ASC';
        }
        $orderBySafe = "`" . mysqli_real_escape_string(self::$db, $column) . "` " . $direction;

        $sql = "SELECT id, nama, deskripsi, gambar, lokasi, created_at, updated_at 
                FROM " . self::$table_name . " 
                ORDER BY " . $orderBySafe;
        if ($limit !== null && $limit > 0) {
            $sql .= " LIMIT " . (int)$limit; // Ini penting untuk menerapkan limit
        }

        $result = mysqli_query(self::$db, $sql);
        if ($result) {
            $data = mysqli_fetch_all($result, MYSQLI_ASSOC);
            mysqli_free_result($result);
            return $data;
        } else {
            self::$last_error = "MySQLi Query Error (getAll): " . mysqli_error(self::$db);
            error_log(get_called_class() . "::getAll() - " . self::$last_error . " | SQL: " . $sql);
            return [];
        }
    }


    public static function update(array $data): bool
    {
        if (!self::checkDbConnection()) return false;
        self::$last_error = null;

        if (!isset($data['id'])) {
            self::$last_error = "ID wisata wajib disertakan untuk proses update.";
            error_log(get_called_class() . "::update() - " . self::$last_error);
            return false;
        }
        $id = filter_var($data['id'], FILTER_VALIDATE_INT);
        if (!$id || $id <= 0) {
            self::$last_error = "ID wisata tidak valid untuk update.";
            error_log(get_called_class() . "::update() - " . self::$last_error . " ID: " . ($data['id'] ?? 'Tidak ada'));
            return false;
        }

        // Validasi input yang diterima (Controller seharusnya sudah melakukan ini, tapi baik untuk double check)
        if (isset($data['nama']) && empty(trim($data['nama']))) {
            self::$last_error = "Nama tidak boleh kosong saat update.";
            return false;
        }
        if (isset($data['deskripsi']) && empty(trim($data['deskripsi']))) {
            self::$last_error = "Deskripsi tidak boleh kosong saat update.";
            return false;
        }

        $fields_to_set_sql = [];
        $params_for_bind = [];
        $types_for_bind = "";

        $allowed_fields_for_update = [
            'nama' => 's',
            'deskripsi' => 's',
            'gambar' => 's',
            'lokasi' => 's'
        ];

        foreach ($allowed_fields_for_update as $field_key => $type_char) {
            if (array_key_exists($field_key, $data)) { // Hanya proses field yang dikirim di $data
                $fields_to_set_sql[] = "`" . $field_key . "` = ?";
                $value_to_bind = $data[$field_key];
                // Jika string kosong untuk field yang boleh null (seperti gambar, lokasi), set ke NULL
                if ($type_char === 's' && $value_to_bind === '') {
                    $value_to_bind = null;
                }
                $params_for_bind[] = $value_to_bind;
                $types_for_bind .= $type_char;
            }
        }

        if (empty($fields_to_set_sql)) {
            // Tidak ada field yang valid untuk diupdate, atau nilainya sama dengan yang di DB.
            // Dianggap berhasil karena tidak ada perubahan yang diminta/diperlukan.
            return true;
        }

        // `updated_at` akan dihandle otomatis oleh MySQL karena `ON UPDATE CURRENT_TIMESTAMP()`
        $sql_update = "UPDATE " . self::$table_name . " SET " . implode(', ', $fields_to_set_sql) . " WHERE id = ?";
        $params_for_bind[] = $id; // Tambahkan ID untuk klausa WHERE
        $types_for_bind .= "i";   // Tipe untuk ID

        $stmt = mysqli_prepare(self::$db, $sql_update);
        if (!$stmt) {
            self::$last_error = "MySQLi Prepare Error (update): " . mysqli_error(self::$db);
            error_log(get_called_class() . "::update() - " . self::$last_error . " | SQL: " . $sql_update);
            return false;
        }

        mysqli_stmt_bind_param($stmt, $types_for_bind, ...$params_for_bind);

        if (mysqli_stmt_execute($stmt)) {
            mysqli_stmt_close($stmt);
            return true; // Sukses meskipun affected_rows bisa 0 jika data tidak berubah
        } else {
            self::$last_error = "MySQLi Execute Error (update): " . mysqli_stmt_error($stmt);
            error_log(get_called_class() . "::update() - " . self::$last_error);
            mysqli_stmt_close($stmt);
            return false;
        }
    }

    public static function delete(int $id): bool
    {
        if (!self::checkDbConnection()) return false;
        // checkUploadDir() penting di sini karena kita akan menghapus file
        if (!self::checkUploadDir() || !is_writable(self::$upload_dir_wisata)) { // Tambah cek is_writable
            self::$last_error = self::$last_error ?: "Direktori upload wisata tidak dapat ditulis untuk menghapus file.";
            error_log(get_called_class() . "::delete() - " . self::$last_error);
            return false;
        }
        self::$last_error = null;

        if ($id <= 0) {
            self::$last_error = "ID wisata tidak valid untuk dihapus.";
            error_log(get_called_class() . "::delete() - " . self::$last_error . " ID: " . $id);
            return false;
        }

        // Ambil nama file gambar sebelum menghapus data dari DB
        $item_to_delete = self::findById($id);
        if (!$item_to_delete) {
            self::$last_error = "Data wisata dengan ID {$id} tidak ditemukan untuk dihapus.";
            // error_log sudah dilakukan oleh findById jika tidak ketemu
            return false; // Atau true jika dianggap "sudah terhapus"
        }
        $gambar_file_to_delete = $item_to_delete['gambar'];

        $sql = "DELETE FROM " . self::$table_name . " WHERE id = ?";
        $stmt = mysqli_prepare(self::$db, $sql);
        if (!$stmt) {
            self::$last_error = "MySQLi Prepare Error (delete): " . mysqli_error(self::$db);
            error_log(get_called_class() . "::delete() - " . self::$last_error);
            return false;
        }
        mysqli_stmt_bind_param($stmt, "i", $id);
        if (mysqli_stmt_execute($stmt)) {
            $affected_rows = mysqli_stmt_affected_rows($stmt);
            mysqli_stmt_close($stmt);

            if ($affected_rows > 0) {
                // Hapus file gambar fisik jika ada
                if (!empty($gambar_file_to_delete)) {
                    $file_path = self::$upload_dir_wisata . basename($gambar_file_to_delete); // Gunakan basename
                    if (file_exists($file_path) && is_file($file_path)) {
                        if (!@unlink($file_path)) {
                            // Ini hanya peringatan, data DB sudah terhapus
                            self::$last_error = "Peringatan: Data wisata berhasil dihapus dari DB, tetapi gagal menghapus file gambar fisik: " . $file_path;
                            error_log(get_called_class() . "::delete() - " . self::$last_error);
                            // Jangan return false di sini, karena operasi DB utama berhasil
                        }
                    } else {
                        error_log(get_called_class() . "::delete() - Info: File gambar '{$gambar_file_to_delete}' tidak ditemukan di server untuk dihapus. Path: {$file_path}");
                    }
                }
                return true; // Sukses menghapus dari DB
            } else {
                self::$last_error = "Tidak ada data wisata yang terhapus untuk ID: {$id}. Mungkin sudah dihapus sebelumnya.";
                error_log(get_called_class() . "::delete() - " . self::$last_error);
                return false; // Tidak ada baris yang terpengaruh
            }
        } else {
            self::$last_error = "MySQLi Execute Error (delete): " . mysqli_stmt_error($stmt);
            error_log(get_called_class() . "::delete() - " . self::$last_error);
            mysqli_stmt_close($stmt);
            return false;
        }
    }

    public static function countAll(): int
    {
        if (!self::checkDbConnection()) return 0;
        self::$last_error = null;
        $sql = "SELECT COUNT(id) as total FROM " . self::$table_name;
        $result = mysqli_query(self::$db, $sql);
        if ($result) {
            $row = mysqli_fetch_assoc($result);
            mysqli_free_result($result);
            return (int)($row['total'] ?? 0);
        } else {
            self::$last_error = "MySQLi Query Error (countAll): " . mysqli_error(self::$db);
            error_log(get_called_class() . "::countAll() - " . self::$last_error);
            return 0;
        }
    }
}
