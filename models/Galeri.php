<?php
// File: C:\xampp\htdocs\Cilengkrang-Web-Wisata\models\Galeri.php

class Galeri
{
    private static $table_name = "galeri";
    private static $db;
    private static $upload_dir_galeri;

    public static function init(mysqli $connection, $upload_path)
    {
        self::$db = $connection;
        self::$upload_dir_galeri = rtrim($upload_path, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
    }

    private static function checkDependencies()
    {
        if (!self::$db || (self::$db instanceof mysqli && self::$db->connect_error)) {
            error_log(get_called_class() . " - Koneksi database tidak tersedia atau gagal: " .
                (self::$db instanceof mysqli ? self::$db->connect_error : (self::$db === null ? 'Koneksi DB (self::$db) belum diset via init().' : 'Koneksi DB bukan objek mysqli.')));
            return false;
        }
        if (empty(self::$upload_dir_galeri)) {
            error_log(get_called_class() . " - Path upload direktori galeri (self::\$upload_dir_galeri) belum diinisialisasi via init().");
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
        // Metode create mungkin hanya butuh koneksi, bukan upload_dir karena nama file dari controller
        // Namun, untuk konsistensi, kita bisa tetap panggil checkDependencies()
        // atau buat checkDbConnection() terpisah jika ada metode yang TIDAK butuh upload_dir.
        // Untuk sekarang, kita asumsikan checkDependencies() yang memeriksa keduanya.
        if (!self::checkDependencies()) return false;


        $nama_file = trim($data['nama_file'] ?? '');
        $keterangan = isset($data['keterangan']) ? trim($data['keterangan']) : null;

        if (empty($nama_file)) {
            error_log(get_called_class() . "::create() - Nama file tidak boleh kosong.");
            return false;
        }

        $sql = "INSERT INTO " . self::$table_name . " (nama_file, keterangan, uploaded_at) VALUES (?, ?, NOW())"; // Asumsi uploaded_at diisi NOW()
        $stmt = mysqli_prepare(self::$db, $sql);
        if (!$stmt) {
            error_log(get_called_class() . "::create() - MySQLi Prepare Error: " . mysqli_error(self::$db));
            return false;
        }
        mysqli_stmt_bind_param($stmt, "ss", $nama_file, $keterangan);
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

    public static function getAll($orderBy = 'uploaded_at DESC')
    {
        // Metode ini hanya butuh koneksi DB, bukan path upload.
        // Jika Anda ingin lebih granural, buat checkDbConnection() terpisah dan panggil di sini.
        // Untuk sekarang, checkDependencies() akan memeriksa keduanya.
        if (!self::checkDependencies()) return [];

        $allowed_order_columns = ['id', 'nama_file', 'keterangan', 'uploaded_at'];
        $order_parts = explode(' ', trim($orderBy));
        $column = $order_parts[0] ?? 'uploaded_at';
        $direction = (isset($order_parts[1]) && strtoupper($order_parts[1]) === 'ASC') ? 'ASC' : 'DESC';
        if (!in_array($column, $allowed_order_columns)) {
            $column = 'uploaded_at';
            $direction = 'DESC';
        }
        $orderBySafe = "`" . $column . "` " . $direction;

        $sql = "SELECT id, nama_file, keterangan, uploaded_at FROM " . self::$table_name . " ORDER BY " . $orderBySafe;
        $result = mysqli_query(self::$db, $sql);
        if ($result) {
            $data = mysqli_fetch_all($result, MYSQLI_ASSOC);
            mysqli_free_result($result);
            return $data;
        } else {
            error_log(get_called_class() . "::getAll() - MySQLi Query Error: " . mysqli_error(self::$db) . " | SQL: " . $sql);
            return [];
        }
    }

    public static function getById($id)
    {
        if (!self::checkDependencies()) return null; // Atau if (!self::checkDbConnection()) jika dibuat terpisah
        $id_val = filter_var($id, FILTER_VALIDATE_INT);
        if ($id_val === false || $id_val <= 0) {
            error_log(get_called_class() . "::getById() ID tidak valid: " . $id);
            return null;
        }

        $sql = "SELECT id, nama_file, keterangan, uploaded_at FROM " . self::$table_name . " WHERE id = ? LIMIT 1";
        $stmt = mysqli_prepare(self::$db, $sql);
        if (!$stmt) {
            error_log(get_called_class() . "::getById() Prepare Error: " . mysqli_error(self::$db));
            return null;
        }

        mysqli_stmt_bind_param($stmt, "i", $id_val);
        if (mysqli_stmt_execute($stmt)) {
            $result = mysqli_stmt_get_result($stmt);
            $foto = mysqli_fetch_assoc($result);
            mysqli_stmt_close($stmt);
            return $foto ?: null;
        } else {
            error_log(get_called_class() . "::getById() Execute Error: " . mysqli_stmt_error($stmt));
        }
        mysqli_stmt_close($stmt);
        return null;
    }

    public static function update(array $data)
    {
        // Metode ini mungkin butuh upload_dir jika menangani pemindahan file, tapi saat ini tidak
        if (!self::checkDependencies()) return false;
        if (!isset($data['id'])) {
            error_log(get_called_class() . "::update() ID tidak ada.");
            return false;
        }

        $id = filter_var($data['id'], FILTER_VALIDATE_INT);
        if ($id === false || $id <= 0) {
            error_log(get_called_class() . "::update() ID tidak valid.");
            return false;
        }

        $current_foto = self::getById($id);
        if (!$current_foto) {
            error_log(get_called_class() . "::update() Foto ID {$id} tidak ditemukan.");
            return false;
        }

        $keterangan = trim($data['keterangan'] ?? $current_foto['keterangan']);
        $fields_to_update = ["keterangan = ?"];
        $params = [$keterangan];
        $types = "s";

        if (array_key_exists('nama_file', $data)) {
            $nama_file_baru = !empty($data['nama_file']) ? trim($data['nama_file']) : null;
            $fields_to_update[] = "nama_file = ?";
            $params[] = $nama_file_baru;
            $types .= "s";
        }
        // Asumsi tabel galeri TIDAK punya kolom updated_at dengan ON UPDATE CURRENT_TIMESTAMP.
        // Jika ada, atau ingin ditambahkan manual:
        // $fields_to_update[] = "updated_at = NOW()";


        if (count($fields_to_update) === 1 && !array_key_exists('nama_file', $data) && $keterangan === $current_foto['keterangan']) {
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
        // Metode ini butuh self::$upload_dir_galeri untuk hapus file, jadi checkDependencies() sudah tepat.
        if (!self::checkDependencies()) return false;
        $id_val = filter_var($id, FILTER_VALIDATE_INT);
        if ($id_val === false || $id_val <= 0) {
            error_log(get_called_class() . "::delete() ID tidak valid: " . $id);
            return false;
        }

        $foto = self::getById($id_val);

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
                if ($foto && !empty($foto['nama_file'])) {
                    $file_path = self::$upload_dir_galeri . basename($foto['nama_file']);
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
        if (!self::checkDependencies()) return 0; // Atau if (!self::checkDbConnection())
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
} // End of class Galeri