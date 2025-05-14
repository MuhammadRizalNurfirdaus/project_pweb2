<?php
// File: C:\xampp\htdocs\Cilengkrang-Web-Wisata\models\Wisata.php

class Wisata
{
    private static $table_name = "wisata"; // Nama tabel sudah benar
    private static $db;
    private static $upload_dir_wisata;

    public static function init(mysqli $connection, $upload_path)
    {
        self::$db = $connection;
        self::$upload_dir_wisata = rtrim($upload_path, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
    }

    private static function checkDependencies()
    {
        if (!self::$db || (self::$db instanceof mysqli && self::$db->connect_error)) {
            error_log(get_called_class() . " - Koneksi database tidak tersedia atau gagal: " .
                (self::$db instanceof mysqli ? self::$db->connect_error : (self::$db === null ? 'Koneksi DB (self::$db) belum diset via init().' : 'Koneksi DB bukan objek mysqli.')));
            return false;
        }
        if (empty(self::$upload_dir_wisata)) {
            error_log(get_called_class() . " - Path upload direktori wisata (self::\$upload_dir_wisata) belum diinisialisasi via init().");
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
        if (!self::checkDependencies()) return false;

        // $data['nama_wisata'] dari form akan disimpan ke kolom 'nama' di DB
        $nama_untuk_db = trim($data['nama_wisata'] ?? '');
        $deskripsi = trim($data['deskripsi'] ?? '');
        $lokasi = isset($data['lokasi']) ? trim($data['lokasi']) : null;
        $gambar = isset($data['gambar']) && !empty($data['gambar']) ? trim($data['gambar']) : null;

        if (empty($nama_untuk_db) || empty($deskripsi)) {
            error_log(get_called_class() . "::create() - Error: Nama Wisata atau Deskripsi tidak boleh kosong.");
            return false;
        }

        // PERBAIKAN: Menggunakan kolom 'nama'. Kolom 'created_at' diisi otomatis. 'updated_at' tidak ada.
        $sql = "INSERT INTO " . self::$table_name . " (nama, deskripsi, gambar, lokasi) 
                VALUES (?, ?, ?, ?)";
        $stmt = mysqli_prepare(self::$db, $sql);

        if (!$stmt) {
            error_log(get_called_class() . "::create() Prepare Error: " . mysqli_error(self::$db));
            return false;
        }

        mysqli_stmt_bind_param($stmt, "ssss", $nama_untuk_db, $deskripsi, $gambar, $lokasi);

        if (mysqli_stmt_execute($stmt)) {
            $new_id = mysqli_insert_id(self::$db);
            mysqli_stmt_close($stmt);
            return $new_id;
        } else {
            error_log(get_called_class() . "::create() Execute Error: " . mysqli_stmt_error($stmt));
            mysqli_stmt_close($stmt);
            return false;
        }
    }

    public static function getAll($orderBy = 'nama ASC')
    {
        if (!self::checkDependencies()) return [];

        $allowed_order_columns = ['id', 'nama', 'created_at', 'lokasi'];
        $order_parts = explode(' ', trim($orderBy));
        $column = $order_parts[0] ?? 'nama';
        $direction = (isset($order_parts[1]) && strtoupper($order_parts[1]) === 'ASC') ? 'ASC' : 'DESC';
        if (!in_array($column, $allowed_order_columns)) {
            $column = 'nama';
            $direction = 'ASC';
        }
        $orderBySafe = "`" . $column . "` " . $direction;

        // PERBAIKAN: SELECT kolom 'nama' dan alias sebagai 'nama_wisata'. 'updated_at' tidak ada.
        $sql = "SELECT id, nama AS nama_wisata, deskripsi, gambar, lokasi, created_at 
                FROM " . self::$table_name . " 
                ORDER BY " . $orderBySafe;
        $result = mysqli_query(self::$db, $sql);

        if ($result) {
            $data = mysqli_fetch_all($result, MYSQLI_ASSOC);
            mysqli_free_result($result);
            return $data;
        } else {
            error_log(get_called_class() . "::getAll() Query Error: " . mysqli_error(self::$db) . " SQL: " . $sql);
            return [];
        }
    }

    public static function getById($id)
    {
        if (!self::checkDependencies()) return null;
        $id_val = filter_var($id, FILTER_VALIDATE_INT);
        if ($id_val === false || $id_val <= 0) {
            error_log(get_called_class() . "::getById() - ID tidak valid: " . $id);
            return null;
        }

        // PERBAIKAN: SELECT kolom 'nama' dan alias sebagai 'nama_wisata'. 'updated_at' tidak ada.
        $sql = "SELECT id, nama AS nama_wisata, deskripsi, gambar, lokasi, created_at 
                FROM " . self::$table_name . " 
                WHERE id = ?";
        $stmt = mysqli_prepare(self::$db, $sql);
        if (!$stmt) {
            error_log(get_called_class() . "::getById() Prepare Error: " . mysqli_error(self::$db));
            return null;
        }

        mysqli_stmt_bind_param($stmt, "i", $id_val);
        if (mysqli_stmt_execute($stmt)) {
            $result = mysqli_stmt_get_result($stmt);
            $wisata = mysqli_fetch_assoc($result);
            mysqli_stmt_close($stmt);
            return $wisata ?: null;
        } else {
            error_log(get_called_class() . "::getById() Execute Error: " . mysqli_stmt_error($stmt));
        }
        mysqli_stmt_close($stmt);
        return null;
    }

    public static function update($data)
    {
        if (!self::checkDependencies() || !isset($data['id'])) {
            error_log(get_called_class() . "::update() Koneksi/ID Error");
            return false;
        }

        $id = filter_var($data['id'], FILTER_VALIDATE_INT);
        if ($id === false || $id <= 0) {
            error_log(get_called_class() . "::update() ID tidak valid.");
            return false;
        }

        $current_wisata = self::getById($id); // $current_wisata akan punya key 'nama_wisata' karena alias
        if (!$current_wisata) {
            error_log(get_called_class() . "::update() Wisata ID {$id} tidak ditemukan.");
            return false;
        }

        // Terima 'nama_wisata' dari $data, update ke kolom 'nama'
        $nama_input_untuk_db = trim($data['nama_wisata'] ?? $current_wisata['nama_wisata']);
        $deskripsi = trim($data['deskripsi'] ?? $current_wisata['deskripsi']);
        $lokasi = isset($data['lokasi']) ? trim($data['lokasi']) : $current_wisata['lokasi'];

        if (empty($nama_input_untuk_db) || empty($deskripsi)) {
            error_log(get_called_class() . "::update() Nama atau Deskripsi kosong.");
            return false;
        }

        // PERBAIKAN: Update kolom 'nama'
        $fields_to_update = ["nama = ?", "deskripsi = ?", "lokasi = ?"];
        $params = [$nama_input_untuk_db, $deskripsi, $lokasi];
        $types = "sss";

        if (array_key_exists('gambar', $data)) {
            $gambar_baru = !empty($data['gambar']) ? trim($data['gambar']) : null;
            $fields_to_update[] = "gambar = ?";
            $params[] = $gambar_baru;
            $types .= "s";
        }

        // Kolom updated_at tidak ada di tabel Anda.
        // Jika Anda menambahkannya dengan ON UPDATE CURRENT_TIMESTAMP, tidak perlu set manual.

        if (empty($fields_to_update) || (count($fields_to_update) === 3 && !array_key_exists('gambar', $data) && $nama_input_untuk_db === $current_wisata['nama_wisata'] && $deskripsi === $current_wisata['deskripsi'] && $lokasi === $current_wisata['lokasi'])) {
            return true;
        }

        $sql = "UPDATE " . self::$table_name . " SET " . implode(', ', $fields_to_update) . " WHERE id = ?";
        $params[] = $id;
        $types .= "i";

        $stmt = mysqli_prepare(self::$db, $sql);
        if (!$stmt) {
            error_log(get_called_class() . "::update() Prepare Error: " . mysqli_error(self::$db));
            return false;
        }

        mysqli_stmt_bind_param($stmt, $types, ...$params);
        if (mysqli_stmt_execute($stmt)) {
            $affected_rows = mysqli_stmt_affected_rows($stmt);
            mysqli_stmt_close($stmt);
            return $affected_rows >= 0;
        } else {
            error_log(get_called_class() . "::update() Execute Error: " . mysqli_stmt_error($stmt));
            mysqli_stmt_close($stmt);
            return false;
        }
    }

    public static function delete($id)
    {
        if (!self::checkDependencies()) return false;
        $id_val = filter_var($id, FILTER_VALIDATE_INT);
        if ($id_val === false || $id_val <= 0) {
            error_log(get_called_class() . "::delete() ID tidak valid: " . $id);
            return false;
        }
        $wisata = self::getById($id_val);
        $sql = "DELETE FROM " . self::$table_name . " WHERE id = ?";
        $stmt = mysqli_prepare(self::$db, $sql);
        if (!$stmt) {
            error_log(get_called_class() . "::delete() Prepare Error: " . mysqli_error(self::$db));
            return false;
        }
        mysqli_stmt_bind_param($stmt, "i", $id_val);
        if (mysqli_stmt_execute($stmt)) {
            $affected_rows = mysqli_stmt_affected_rows($stmt);
            mysqli_stmt_close($stmt);
            if ($affected_rows > 0) {
                if ($wisata && !empty($wisata['gambar'])) {
                    $file_path = self::$upload_dir_wisata . basename($wisata['gambar']);
                    if (file_exists($file_path) && is_file($file_path)) {
                        if (!@unlink($file_path)) {
                            error_log(get_called_class() . "::delete() Peringatan: Gagal hapus file gambar " . $file_path);
                        }
                    }
                }
                return true;
            }
            error_log(get_called_class() . "::delete() Tidak ada baris terhapus untuk ID: " . $id_val);
            return false;
        } else {
            error_log(get_called_class() . "::delete() Execute Error: " . mysqli_stmt_error($stmt));
            mysqli_stmt_close($stmt);
            return false;
        }
    }

    public static function countAll()
    {
        if (!self::checkDependencies()) return 0;
        $sql = "SELECT COUNT(id) as total FROM " . self::$table_name;
        $result = mysqli_query(self::$db, $sql);
        if ($result) {
            $row = mysqli_fetch_assoc($result);
            mysqli_free_result($result);
            return (int)($row['total'] ?? 0);
        }
        error_log(get_called_class() . "::countAll() Query Error: " . mysqli_error(self::$db));
        return 0;
    }
}
