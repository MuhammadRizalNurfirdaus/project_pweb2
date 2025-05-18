<?php
// File: C:\xampp\htdocs\Cilengkrang-Web-Wisata\models\Galeri.php

class Galeri
{
    private static $table_name = "galeri"; // Sesuai screenshot tabel Anda
    private static $db;
    private static $upload_dir_galeri; // Path absolut ke direktori upload galeri
    private static $last_error = null;   // Untuk error internal model

    /**
     * Mengatur koneksi database dan path upload.
     * Dipanggil dari config.php.
     * @param mysqli $connection Instance koneksi mysqli.
     * @param string $upload_path Path absolut ke direktori upload galeri.
     */
    public static function init(mysqli $connection, string $upload_path)
    {
        self::$db = $connection;
        self::$upload_dir_galeri = rtrim($upload_path, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        error_log(get_called_class() . "::init() dipanggil. DB: " . (self::$db && !self::$db->connect_error ? "OK" : "GAGAL") . ". Upload Dir: " . self::$upload_dir_galeri);
    }

    /**
     * Memeriksa apakah koneksi database valid.
     * Metode ini bisa dipanggil oleh semua metode yang butuh DB.
     * @return bool True jika koneksi OK.
     */
    private static function checkDbConnection(): bool
    {
        self::$last_error = null;
        if (!self::$db || (self::$db instanceof mysqli && self::$db->connect_error)) {
            self::$last_error = "Koneksi database Galeri belum diset atau gagal. ";
            self::$last_error .= (self::$db instanceof mysqli ? self::$db->connect_error : (self::$db === null ? 'Belum init().' : 'Bukan mysqli.'));
            error_log(get_called_class() . " - DB Error: " . self::$last_error);
            return false;
        }
        return true;
    }

    /**
     * Memeriksa apakah path upload direktori galeri valid dan writable.
     * Dipanggil oleh metode yang melakukan operasi file.
     * @return bool True jika path upload OK.
     */
    private static function checkUploadDir(): bool
    {
        if (empty(self::$upload_dir_galeri) || !is_dir(self::$upload_dir_galeri) || !is_writable(self::$upload_dir_galeri)) {
            self::$last_error = 'Path upload direktori galeri (self::$upload_dir_galeri) tidak valid, tidak ada, atau tidak dapat ditulis: ' . (self::$upload_dir_galeri ?: 'Kosong');
            error_log(get_called_class() . " - Upload Dir Error: " . self::$last_error);
            return false;
        }
        return true;
    }


    public static function getLastError(): ?string
    {
        if (self::$last_error) { // Prioritaskan error internal model
            return self::$last_error;
        }
        if (self::$db instanceof mysqli && !empty(self::$db->error)) { // Kemudian error mysqli
            return "MySQLi Error: " . self::$db->error;
        }
        // Jangan kembalikan "Tidak ada error..." jika memang tidak ada, biarkan null.
        // Jika koneksi gagal di checkDbConnection, self::$last_error akan diset.
        if (!self::$db || !(self::$db instanceof mysqli)) {
            return 'Koneksi database Galeri belum diinisialisasi/tidak valid.';
        }
        return null;
    }

    /**
     * Membuat entri galeri baru.
     * @param array $data Harus berisi 'nama_file'. Opsional: 'keterangan'.
     * @return int|false ID galeri baru jika berhasil, false jika gagal.
     */
    public static function create(array $data)
    {
        if (!self::checkDbConnection()) return false;
        // checkUploadDir() tidak dipanggil di create, karena file diupload oleh controller,
        // model hanya menyimpan nama file.

        $nama_file = trim($data['nama_file'] ?? '');
        $keterangan = isset($data['keterangan']) ? trim($data['keterangan']) : null;

        if (empty($nama_file)) {
            self::$last_error = "Nama file untuk galeri tidak boleh kosong.";
            error_log(get_called_class() . "::create() - " . self::$last_error);
            return false;
        }

        // Kolom 'uploaded_at' di tabel galeri Anda adalah timestamp dan Not Null
        // Jika tidak ada default CURRENT_TIMESTAMP, Anda HARUS menyediakannya.
        // Menggunakan NOW() di query adalah solusi yang baik.
        $sql = "INSERT INTO " . self::$table_name . " (nama_file, keterangan, uploaded_at) 
                VALUES (?, ?, NOW())";
        $stmt = mysqli_prepare(self::$db, $sql);
        if (!$stmt) {
            self::$last_error = mysqli_error(self::$db);
            error_log(get_called_class() . "::create() - MySQLi Prepare Error: " . self::$last_error . " | SQL: " . $sql);
            return false;
        }

        mysqli_stmt_bind_param($stmt, "ss", $nama_file, $keterangan);
        if (mysqli_stmt_execute($stmt)) {
            $new_id = mysqli_insert_id(self::$db);
            mysqli_stmt_close($stmt);
            return $new_id;
        } else {
            self::$last_error = mysqli_stmt_error($stmt);
            error_log(get_called_class() . "::create() - MySQLi Execute Error: " . self::$last_error);
            mysqli_stmt_close($stmt);
            return false;
        }
    }

    /**
     * Mengambil semua item galeri.
     * @param string $orderBy Pengurutan hasil, contoh: 'uploaded_at DESC'.
     * @param int|null $limit Batas jumlah data yang diambil.
     * @return array Array data galeri atau array kosong jika gagal/tidak ada data.
     */
    public static function getAll(string $orderBy = 'uploaded_at DESC', ?int $limit = null): array
    {
        if (!self::checkDbConnection()) return [];

        $allowed_order_columns = ['id', 'nama_file', 'keterangan', 'uploaded_at'];
        $order_parts = explode(' ', trim($orderBy), 2);
        $column = $order_parts[0] ?? 'uploaded_at';
        $direction = (isset($order_parts[1]) && strtoupper($order_parts[1]) === 'ASC') ? 'ASC' : 'DESC';
        if (!in_array(strtolower($column), $allowed_order_columns, true)) {
            $column = 'uploaded_at';
            $direction = 'DESC';
        }
        $orderBySafe = "`" . mysqli_real_escape_string(self::$db, $column) . "` " . $direction;

        $sql = "SELECT id, nama_file, keterangan, uploaded_at FROM " . self::$table_name . " ORDER BY " . $orderBySafe;
        if ($limit !== null && $limit > 0) {
            $sql .= " LIMIT " . (int)$limit;
        }

        $result = mysqli_query(self::$db, $sql);
        if ($result) {
            $data = mysqli_fetch_all($result, MYSQLI_ASSOC);
            mysqli_free_result($result);
            return $data;
        } else {
            self::$last_error = "MySQLi Query Error: " . mysqli_error(self::$db);
            error_log(get_called_class() . "::getAll() - " . self::$last_error . " | SQL: " . $sql);
            return [];
        }
    }

    public static function findById(int $id): ?array
    {
        if (!self::checkDbConnection()) return null;
        if ($id <= 0) {
            self::$last_error = "ID galeri tidak valid: " . $id;
            return null;
        }

        $sql = "SELECT id, nama_file, keterangan, uploaded_at FROM " . self::$table_name . " WHERE id = ? LIMIT 1";
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

    /**
     * Mengupdate data galeri.
     * @param array $data Harus berisi 'id'. Opsional: 'keterangan', 'nama_file_baru' (nama file setelah dihandle controller).
     * @return bool True jika berhasil, false jika gagal.
     */
    public static function update(array $data): bool
    {
        if (!self::checkDbConnection()) return false;
        // checkUploadDir() akan dipanggil oleh controller jika ada operasi file sebelum memanggil update ini

        if (!isset($data['id'])) {
            self::$last_error = "ID galeri harus disertakan untuk update.";
            return false;
        }
        $id = filter_var($data['id'], FILTER_VALIDATE_INT);
        if ($id === false || $id <= 0) {
            self::$last_error = "ID galeri tidak valid untuk update.";
            return false;
        }

        $current_item = self::findById($id);
        if (!$current_item) {
            self::$last_error = "Item galeri dengan ID {$id} tidak ditemukan.";
            return false;
        }

        $update_fields = [];
        $params = [];
        $types = "";

        // Hanya update keterangan jika memang ada di $data dan berbeda
        if (array_key_exists('keterangan', $data) && $data['keterangan'] !== $current_item['keterangan']) {
            $update_fields[] = "keterangan = ?";
            $params[] = trim($data['keterangan']);
            $types .= "s";
        }

        // Hanya update nama_file jika memang ada di $data dan berbeda
        // Controller bertanggung jawab untuk mengurus file fisik (upload baru, hapus lama)
        // Model hanya mengupdate nama file di DB.
        if (array_key_exists('nama_file', $data) && $data['nama_file'] !== $current_item['nama_file']) {
            $update_fields[] = "nama_file = ?";
            $params[] = !empty($data['nama_file']) ? trim($data['nama_file']) : null; // Simpan null jika dikosongkan
            $types .= "s";
        }

        // Jika tidak ada field yang perlu diupdate di DB
        if (empty($update_fields)) {
            return true; // Dianggap berhasil karena tidak ada perubahan data
        }

        // Kolom 'uploaded_at' biasanya tidak diubah saat edit.
        // Jika ingin `updated_at` otomatis, tambahkan kolom itu di tabel dengan ON UPDATE CURRENT_TIMESTAMP
        // atau tambahkan manual di sini: $update_fields[] = "kolom_updated_at = NOW()";

        $sql = "UPDATE " . self::$table_name . " SET " . implode(', ', $update_fields) . " WHERE id = ?";
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
            return true; // Berhasil meskipun affected_rows bisa 0 jika data sama
        } else {
            self::$last_error = mysqli_stmt_error($stmt);
            mysqli_stmt_close($stmt);
            return false;
        }
    }


    /**
     * Menghapus item galeri berdasarkan ID.
     * Ini juga akan menghapus file gambar terkait.
     * @param int $id ID item galeri.
     * @return bool True jika berhasil, false jika gagal.
     */
    public static function delete(int $id): bool
    {
        if (!self::checkDbConnection()) return false;
        if (!self::checkUploadDir()) return false; // Butuh upload_dir untuk hapus file

        if ($id <= 0) {
            self::$last_error = "ID galeri tidak valid untuk dihapus.";
            return false;
        }

        $item = self::findById($id);
        if (!$item) {
            // self::$last_error sudah diset oleh findById jika tidak ditemukan
            return false; // Item tidak ada, anggap gagal menghapus yang tidak ada
        }

        $sql = "DELETE FROM " . self::$table_name . " WHERE id = ?";
        $stmt = mysqli_prepare(self::$db, $sql);
        if (!$stmt) {
            self::$last_error = mysqli_error(self::$db);
            return false;
        }
        mysqli_stmt_bind_param($stmt, "i", $id);
        if (mysqli_stmt_execute($stmt)) {
            $affected_rows = mysqli_stmt_affected_rows($stmt);
            mysqli_stmt_close($stmt);
            if ($affected_rows > 0) {
                if (!empty($item['nama_file'])) {
                    $file_path = self::$upload_dir_galeri . basename($item['nama_file']);
                    if (file_exists($file_path) && is_file($file_path)) {
                        if (!@unlink($file_path)) {
                            // Ini adalah peringatan, data DB sudah terhapus
                            self::$last_error = "Peringatan: Data galeri dihapus dari DB, tetapi gagal menghapus file fisik: " . $file_path;
                            error_log(get_called_class() . "::delete() - " . self::$last_error);
                        }
                    } else {
                        error_log(get_called_class() . "::delete() Info: File galeri tidak ditemukan di server untuk dihapus: " . $file_path);
                    }
                }
                return true;
            } else {
                self::$last_error = "Tidak ada baris galeri yang terhapus untuk ID {$id}. Mungkin sudah dihapus sebelumnya.";
                return false;
            }
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
