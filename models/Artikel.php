<?php
// File: C:\xampp\htdocs\Cilengkrang-Web-Wisata\models\Artikel.php

class Artikel
{
    // ==================================================================
    // PASTIKAN NAMA TABEL INI SESUAI DENGAN DATABASE ANDA
    // Berdasarkan screenshot phpMyAdmin Anda, nama tabelnya adalah 'articles'
    // ==================================================================
    private static $table_name = "articles"; // PERBAIKAN DI SINI JIKA PERLU
    private static $db;
    private static $upload_dir_artikel;

    public static function init(mysqli $connection, $upload_path)
    {
        self::$db = $connection;
        self::$upload_dir_artikel = rtrim($upload_path, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        // error_log(get_called_class() . "::init() dipanggil. DB: " . (self::$db ? 'OK' : 'FAIL') . ", Upload dir: " . self::$upload_dir_artikel);
    }

    private static function checkDependencies()
    {
        if (!self::$db || (self::$db instanceof mysqli && self::$db->connect_error)) {
            error_log(get_called_class() . " - Koneksi database tidak tersedia atau gagal: " .
                (self::$db instanceof mysqli ? self::$db->connect_error : (self::$db === null ? 'Koneksi DB (self::$db) belum diset via init().' : 'Koneksi DB bukan objek mysqli.')));
            return false;
        }
        // Pengecekan upload_dir_artikel bisa opsional untuk metode yang hanya baca DB
        // if (empty(self::$upload_dir_artikel) && method_requires_upload_path) {
        //     error_log(get_called_class() . " - Path upload direktori artikel (self::\$upload_dir_artikel) belum diinisialisasi via init().");
        //     return false;
        // }
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
        $judul = trim($data['judul'] ?? '');
        $isi = trim($data['isi'] ?? '');
        $gambar = isset($data['gambar']) && !empty($data['gambar']) ? trim($data['gambar']) : null;

        if (empty($judul) || empty($isi)) {
            error_log(get_called_class() . "::create() - Judul dan Isi tidak boleh kosong.");
            return false;
        }

        // created_at diisi NOW(), updated_at tidak ada di tabel Anda
        $sql = "INSERT INTO " . self::$table_name . " (judul, isi, gambar, created_at) 
                VALUES (?, ?, ?, NOW())";
        $stmt = mysqli_prepare(self::$db, $sql);
        if (!$stmt) {
            error_log(get_called_class() . "::create() Prepare Error: " . mysqli_error(self::$db));
            return false;
        }
        mysqli_stmt_bind_param($stmt, "sss", $judul, $isi, $gambar);
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

    public static function getAll($orderBy = 'created_at DESC')
    {
        if (!self::checkDependencies()) return [];
        $allowed_order_columns = ['id', 'judul', 'created_at'];
        $order_parts = explode(' ', trim($orderBy));
        $column = $order_parts[0];
        $direction = (isset($order_parts[1]) && strtoupper($order_parts[1]) === 'ASC') ? 'ASC' : 'DESC';
        if (!in_array($column, $allowed_order_columns)) {
            $column = 'created_at';
            $direction = 'DESC';
        }
        $orderBySafe = "`" . $column . "` " . $direction;

        // Pastikan nama kolom sesuai dengan tabel Anda
        $sql = "SELECT id, judul, isi, gambar, created_at FROM " . self::$table_name . " ORDER BY " . $orderBySafe;
        $result = mysqli_query(self::$db, $sql);
        if ($result) {
            $data = mysqli_fetch_all($result, MYSQLI_ASSOC);
            mysqli_free_result($result);
            return $data;
        } else {
            error_log(get_called_class() . "::getAll() Query Error: " . mysqli_error(self::$db) . " SQL: " . $sql); // Tambahkan SQL ke log
            return []; // Kembalikan array kosong jika query gagal
        }
    }

    public static function getById($id)
    {
        if (!self::checkDependencies()) return null;
        $id_val = filter_var($id, FILTER_VALIDATE_INT);
        if ($id_val === false || $id_val <= 0) {
            error_log(get_called_class() . "::getById() ID tidak valid: " . $id);
            return null;
        }

        $sql = "SELECT id, judul, isi, gambar, created_at FROM " . self::$table_name . " WHERE id = ? LIMIT 1";
        $stmt = mysqli_prepare(self::$db, $sql);
        if (!$stmt) {
            error_log(get_called_class() . "::getById() Prepare Error: " . mysqli_error(self::$db));
            return null;
        }
        mysqli_stmt_bind_param($stmt, "i", $id_val);
        if (mysqli_stmt_execute($stmt)) {
            $result = mysqli_stmt_get_result($stmt);
            $artikel = mysqli_fetch_assoc($result);
            mysqli_stmt_close($stmt);
            return $artikel ?: null;
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
            error_log(get_called_class() . "::update() ID artikel tidak valid.");
            return false;
        }
        $current_artikel = self::getById($id);
        if (!$current_artikel) {
            error_log(get_called_class() . "::update() Artikel ID {$id} tidak ditemukan.");
            return false;
        }
        $judul = trim($data['judul'] ?? $current_artikel['judul']);
        $isi = trim($data['isi'] ?? $current_artikel['isi']);
        if (empty($judul) || empty($isi)) {
            error_log(get_called_class() . "::update() Judul dan Isi kosong.");
            return false;
        }
        $fields_to_update = ["judul = ?", "isi = ?"];
        $params = [$judul, $isi];
        $types = "ss";
        if (array_key_exists('gambar', $data)) {
            $gambar_baru = !empty($data['gambar']) ? trim($data['gambar']) : null;
            $fields_to_update[] = "gambar = ?";
            $params[] = $gambar_baru;
            $types .= "s";
        }
        // Hapus updated_at jika tidak ada di tabel
        // Jika ada dan ON UPDATE CURRENT_TIMESTAMP, ini tidak perlu
        // $fields_to_update[] = "updated_at = NOW()"; 

        if (empty($fields_to_update)) {
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
        $artikel = self::getById($id_val);
        mysqli_begin_transaction(self::$db);
        try {
            if (class_exists('Feedback') && method_exists('Feedback', 'deleteByArtikelId')) {
                if (!Feedback::deleteByArtikelId($id_val)) {
                    error_log(get_called_class() . "::delete() - Peringatan: Gagal hapus feedback artikel ID {$id_val}. " . Feedback::getLastError());
                }
            }
            $sql_delete_article = "DELETE FROM " . self::$table_name . " WHERE id = ?";
            $stmt_article = mysqli_prepare(self::$db, $sql_delete_article);
            if (!$stmt_article) {
                throw new Exception("Gagal prepare hapus artikel: " . mysqli_error(self::$db));
            }
            mysqli_stmt_bind_param($stmt_article, "i", $id_val);
            if (!mysqli_stmt_execute($stmt_article)) {
                throw new Exception("Gagal execute hapus artikel: " . mysqli_stmt_error($stmt_article));
            }
            $affected_rows = mysqli_stmt_affected_rows($stmt_article);
            mysqli_stmt_close($stmt_article);
            if ($affected_rows > 0) {
                if ($artikel && !empty($artikel['gambar'])) {
                    $file_path = self::$upload_dir_artikel . basename($artikel['gambar']);
                    if (file_exists($file_path) && is_file($file_path)) {
                        if (!@unlink($file_path)) {
                            error_log(get_called_class() . "::delete() Peringatan: Gagal hapus file gambar " . $file_path);
                        }
                    }
                }
                mysqli_commit(self::$db);
                return true;
            } else {
                mysqli_rollback(self::$db);
                error_log(get_called_class() . "::delete() Tidak ada artikel terhapus ID: " . $id_val);
                return false;
            }
        } catch (Exception $e) {
            mysqli_rollback(self::$db);
            error_log(get_called_class() . "::delete() Exception: " . $e->getMessage());
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
        if (!self::checkDependencies()) return [];
        $limit_val = filter_var($limit, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($limit_val === false) $limit_val = 3; // Default jika input tidak valid

        $sql = "SELECT id, judul, isi, gambar, created_at FROM " . self::$table_name . " ORDER BY created_at DESC LIMIT ?";
        $stmt = mysqli_prepare(self::$db, $sql);
        if (!$stmt) {
            error_log(get_called_class() . "::getLatest() Prepare Error: " . mysqli_error(self::$db));
            return [];
        }

        mysqli_stmt_bind_param($stmt, "i", $limit_val);
        if (mysqli_stmt_execute($stmt)) {
            $result = mysqli_stmt_get_result($stmt);
            $articles = mysqli_fetch_all($result, MYSQLI_ASSOC);
            mysqli_stmt_close($stmt);
            return $articles;
        }
        error_log(get_called_class() . "::getLatest() Execute Error: " . mysqli_stmt_error($stmt));
        mysqli_stmt_close($stmt);
        return [];
    }

    /**
     * Menghitung semua artikel.
     * @return int Jumlah artikel atau 0 jika error.
     */
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
} // End of class Artikel