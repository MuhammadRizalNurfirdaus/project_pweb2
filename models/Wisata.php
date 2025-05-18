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
        self::$upload_dir_wisata = rtrim($upload_path, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        error_log(get_called_class() . "::init() dipanggil. DB: " . (self::$db && !self::$db->connect_error ? "OK" : "GAGAL") . ". Upload Dir Wisata: " . self::$upload_dir_wisata);
    }

    private static function checkDbConnection(): bool
    {
        self::$last_error = null;
        if (!self::$db || (self::$db instanceof mysqli && self::$db->connect_error)) {
            self::$last_error = "Koneksi database Wisata belum diset atau gagal. ";
            self::$last_error .= (self::$db instanceof mysqli ? self::$db->connect_error : (self::$db === null ? 'Belum init().' : 'Bukan mysqli.'));
            error_log(get_called_class() . " - DB Error: " . self::$last_error);
            return false;
        }
        return true;
    }

    private static function checkUploadDir(): bool
    {
        if (empty(self::$upload_dir_wisata) || !is_dir(self::$upload_dir_wisata) || !is_writable(self::$upload_dir_wisata)) {
            self::$last_error = 'Path upload direktori wisata (self::$upload_dir_wisata) tidak valid, tidak ada, atau tidak dapat ditulis: ' . (self::$upload_dir_wisata ?: 'Kosong');
            error_log(get_called_class() . " - Upload Dir Error: " . self::$last_error);
            return false;
        }
        return true;
    }

    public static function getLastError(): ?string
    {
        if (self::$last_error) {
            return self::$last_error;
        }
        if (self::$db instanceof mysqli && !empty(self::$db->error)) {
            return "MySQLi Error: " . self::$db->error;
        }
        if (!self::$db || !(self::$db instanceof mysqli)) {
            return 'Koneksi database Wisata belum diinisialisasi/tidak valid.';
        }
        return null;
    }
    public static function getById(int $id): ?array
    {
        if (!self::checkDbConnection()) {
            // self::$last_error sudah diset oleh checkDbConnection()
            return null;
        }
        if ($id <= 0) {
            self::$last_error = "ID Wisata tidak valid: " . $id;
            error_log(get_called_class() . "::getById() - " . self::$last_error);
            return null;
        }

        // Mengambil semua kolom termasuk created_at dan updated_at dari tabel 'wisata'
        $sql = "SELECT id, nama, deskripsi, gambar, lokasi, created_at, updated_at 
                FROM " . self::$table_name . " 
                WHERE id = ? LIMIT 1";
        $stmt = mysqli_prepare(self::$db, $sql);

        if (!$stmt) {
            self::$last_error = "MySQLi Prepare Error: " . mysqli_error(self::$db);
            error_log(get_called_class() . "::getById() Prepare Error: " . self::$last_error . " | SQL: " . $sql);
            return null;
        }

        mysqli_stmt_bind_param($stmt, "i", $id);

        if (mysqli_stmt_execute($stmt)) {
            $result = mysqli_stmt_get_result($stmt);
            $item = mysqli_fetch_assoc($result);
            mysqli_stmt_close($stmt);
            if (!$item) {
                // Tidak perlu set self::$last_error di sini jika item tidak ditemukan,
                // karena itu bukan error DB, tapi memang datanya tidak ada.
                // Controller yang akan menangani jika item null.
                error_log(get_called_class() . "::getById() - Info: Data tidak ditemukan untuk ID: " . $id);
            }
            return $item ?: null; // Mengembalikan null jika fetch_assoc tidak menghasilkan data
        } else {
            self::$last_error = "MySQLi Execute Error: " . mysqli_stmt_error($stmt);
            error_log(get_called_class() . "::getById() Execute Error: " . self::$last_error);
            mysqli_stmt_close($stmt);
            return null;
        }
    }
    public static function create(array $data)
    {
        if (!self::checkDbConnection()) return false;

        $nama = trim($data['nama'] ?? '');
        $deskripsi = trim($data['deskripsi'] ?? '');
        $gambar = isset($data['gambar']) && !empty($data['gambar']) ? trim($data['gambar']) : null;
        $lokasi = isset($data['lokasi']) ? trim($data['lokasi']) : null;

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

        $sql = "INSERT INTO " . self::$table_name . " (nama, deskripsi, gambar, lokasi) 
                VALUES (?, ?, ?, ?)";
        $stmt = mysqli_prepare(self::$db, $sql);

        if (!$stmt) {
            self::$last_error = mysqli_error(self::$db);
            error_log(get_called_class() . "::create() Prepare Error: " . self::$last_error . " | SQL: " . $sql);
            return false;
        }

        mysqli_stmt_bind_param($stmt, "ssss", $nama, $deskripsi, $gambar, $lokasi);

        if (mysqli_stmt_execute($stmt)) {
            $new_id = mysqli_insert_id(self::$db);
            mysqli_stmt_close($stmt);
            return $new_id;
        } else {
            self::$last_error = mysqli_stmt_error($stmt);
            error_log(get_called_class() . "::create() Execute Error: " . self::$last_error);
            mysqli_stmt_close($stmt);
            return false;
        }
    }

    public static function getAll(string $orderBy = 'nama ASC', ?int $limit = null): array
    {
        if (!self::checkDbConnection()) return [];
        $allowed_order_columns = ['id', 'nama', 'created_at', 'updated_at', 'lokasi']; // updated_at ada di tabel
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
            $sql .= " LIMIT " . (int)$limit;
        }

        $result = mysqli_query(self::$db, $sql);
        if ($result) {
            $data = mysqli_fetch_all($result, MYSQLI_ASSOC);
            mysqli_free_result($result);
            return $data;
        } else {
            self::$last_error = mysqli_error(self::$db);
            error_log(get_called_class() . "::getAll() Query Error: " . self::$last_error . " | SQL: " . $sql);
            return [];
        }
    }

    public static function findById(int $id): ?array
    {
        if (!self::checkDbConnection()) return null;
        if ($id <= 0) {
            self::$last_error = "ID Wisata tidak valid: " . $id;
            return null;
        }
        $sql = "SELECT id, nama, deskripsi, gambar, lokasi, created_at, updated_at 
                FROM " . self::$table_name . " 
                WHERE id = ? LIMIT 1";
        $stmt = mysqli_prepare(self::$db, $sql);
        if (!$stmt) {
            self::$last_error = mysqli_error(self::$db);
            return null;
        }
        mysqli_stmt_bind_param($stmt, "i", $id);
        if (mysqli_stmt_execute($stmt)) {
            $result = mysqli_stmt_get_result($stmt);
            $item = mysqli_fetch_assoc($result);
            mysqli_stmt_close($stmt);
            return $item ?: null;
        } else {
            self::$last_error = mysqli_stmt_error($stmt);
            mysqli_stmt_close($stmt);
            return null;
        }
    }

    public static function update(array $data): bool
    {
        if (!self::checkDbConnection()) return false;
        if (!isset($data['id'])) {
            self::$last_error = "ID wisata wajib untuk update.";
            return false;
        }
        $id = filter_var($data['id'], FILTER_VALIDATE_INT);
        if (!$id || $id <= 0) {
            self::$last_error = "ID wisata tidak valid.";
            return false;
        }

        $current_data = self::findById($id);
        if (!$current_data) {
            self::$last_error = "Wisata ID {$id} tidak ditemukan.";
            return false;
        }

        $fields = [];
        $params = [];
        $types = "";

        $nama_input = trim($data['nama'] ?? '');
        if (!empty($nama_input) && $nama_input !== $current_data['nama']) {
            $fields[] = "nama = ?";
            $params[] = $nama_input;
            $types .= "s";
        } elseif (empty($nama_input) && isset($data['nama'])) {
            self::$last_error = "Nama tidak boleh kosong saat update.";
            return false;
        }

        if (isset($data['deskripsi']) && trim($data['deskripsi']) !== $current_data['deskripsi']) {
            $deskripsi_input = trim($data['deskripsi']);
            if (empty($deskripsi_input)) {
                self::$last_error = "Deskripsi tidak boleh kosong saat update.";
                return false;
            }
            $fields[] = "deskripsi = ?";
            $params[] = $deskripsi_input;
            $types .= "s";
        }

        if (array_key_exists('gambar', $data) && $data['gambar'] !== $current_data['gambar']) {
            $fields[] = "gambar = ?";
            $params[] = !empty($data['gambar']) ? trim($data['gambar']) : null;
            $types .= "s";
        }

        if (isset($data['lokasi']) && trim($data['lokasi']) !== $current_data['lokasi']) {
            $fields[] = "lokasi = ?";
            $params[] = trim($data['lokasi']);
            $types .= "s";
        }

        if (empty($fields)) return true;

        // updated_at akan dihandle otomatis oleh MySQL
        $sql = "UPDATE " . self::$table_name . " SET " . implode(', ', $fields) . " WHERE id = ?";
        $params[] = $id;
        $types .= "i";

        $stmt = mysqli_prepare(self::$db, $sql);
        if (!$stmt) {
            self::$last_error = mysqli_error(self::$db);
            return false;
        }
        mysqli_stmt_bind_param($stmt, $types, ...$params);
        if (mysqli_stmt_execute($stmt)) {
            mysqli_stmt_close($stmt);
            return true;
        } else {
            self::$last_error = mysqli_stmt_error($stmt);
            mysqli_stmt_close($stmt);
            return false;
        }
    }

    public static function delete(int $id): bool
    {
        if (!self::checkDbConnection()) return false;
        if (!self::checkUploadDir()) return false;
        if ($id <= 0) {
            self::$last_error = "ID wisata tidak valid.";
            return false;
        }

        $item = self::findById($id);
        if (!$item) {
            self::$last_error = "Wisata ID {$id} tidak ditemukan.";
            return false;
        }


        $sql = "DELETE FROM " . self::$table_name . " WHERE id = ?";
        $stmt = mysqli_prepare(self::$db, $sql);
        if (!$stmt) {
            self::$last_error = mysqli_error(self::$db);
            return false;
        }
        mysqli_stmt_bind_param($stmt, "i", $id);
        if (mysqli_stmt_execute($stmt)) {
            if (mysqli_stmt_affected_rows($stmt) > 0) {
                if (!empty($item['gambar'])) {
                    $file_path = self::$upload_dir_wisata . basename($item['gambar']);
                    if (file_exists($file_path) && is_file($file_path) && !@unlink($file_path)) {
                        self::$last_error = "Peringatan: Data wisata dihapus, tapi gagal hapus file gambar: " . $file_path;
                        error_log(get_called_class() . "::delete() - " . self::$last_error);
                    }
                }
                mysqli_stmt_close($stmt);
                return true;
            }
            self::$last_error = "Tidak ada data wisata yang dihapus untuk ID: {$id}.";
            mysqli_stmt_close($stmt);
            return false;
        } else {
            self::$last_error = mysqli_stmt_error($stmt);
            mysqli_stmt_close($stmt);
            return false;
        }
    }

    public static function countAll(): int
    {
        if (!self::checkDbConnection()) return 0;
        $sql = "SELECT COUNT(id) as total FROM " . self::$table_name;
        $result = mysqli_query(self::$db, $sql);
        if ($result) {
            $row = mysqli_fetch_assoc($result);
            mysqli_free_result($result);
            return (int)($row['total'] ?? 0);
        } else {
            self::$last_error = mysqli_error(self::$db);
            return 0;
        }
    }
}
