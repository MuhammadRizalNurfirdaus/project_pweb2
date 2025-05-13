<?php
// File: C:\xampp\htdocs\Cilengkrang-Web-Wisata\models\SewaAlat.php

class SewaAlat
{
    private static $table_name = "sewa_alat";
    private static $db; // Properti untuk menyimpan koneksi database
    private static $upload_dir_alat; // Akan diinisialisasi

    // Daftar satuan durasi yang diizinkan
    private const ALLOWED_DURATION_UNITS = ['Jam', 'Hari', 'Peminjaman'];
    // Daftar kondisi alat yang diizinkan
    private const ALLOWED_CONDITIONS = ['Baik', 'Rusak Ringan', 'Rusak Berat', 'Perlu Perbaikan'];


    /**
     * Mengatur koneksi database dan path upload untuk digunakan oleh kelas ini.
     * Metode ini HARUS dipanggil sekali (misalnya dari config.php) sebelum metode lain digunakan.
     * @param mysqli $connection Instance koneksi mysqli.
     * @param string $upload_path Path absolut ke direktori upload alat sewa.
     */
    public static function init(mysqli $connection, $upload_path)
    {
        self::$db = $connection;
        self::$upload_dir_alat = rtrim($upload_path, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        // error_log(get_called_class() . "::init() dipanggil. Upload dir: " . self::$upload_dir_alat); // Untuk debugging
    }

    /**
     * Memeriksa apakah koneksi database dan path upload sudah diinisialisasi.
     * @return bool True jika valid, false jika tidak.
     */
    private static function checkDependencies()
    {
        if (!self::$db || (self::$db instanceof mysqli && self::$db->connect_error)) {
            error_log(get_called_class() . " - Koneksi database tidak tersedia atau gagal: " .
                (self::$db instanceof mysqli ? self::$db->connect_error : (self::$db === null ? 'Koneksi DB (self::$db) belum diset via init().' : 'Koneksi DB bukan objek mysqli.')));
            return false;
        }
        if (empty(self::$upload_dir_alat)) {
            error_log(get_called_class() . " - Path upload direktori alat (self::\$upload_dir_alat) belum diinisialisasi via init().");
            return false;
        }
        return true;
    }

    /**
     * Membuat record alat sewa baru.
     * @param array $data Data alat sewa.
     * @return int|false ID alat baru atau false jika gagal.
     */
    public static function create($data)
    {
        if (!self::checkDependencies()) return false;

        $nama_item = trim($data['nama_item'] ?? '');
        $kategori_alat = isset($data['kategori_alat']) ? trim($data['kategori_alat']) : null;
        $deskripsi = trim($data['deskripsi'] ?? '');
        $harga_sewa = isset($data['harga_sewa']) ? (float)$data['harga_sewa'] : 0.0;
        $durasi_harga_sewa = isset($data['durasi_harga_sewa']) ? (int)$data['durasi_harga_sewa'] : 1;
        $satuan_durasi_harga = trim($data['satuan_durasi_harga'] ?? 'Hari');
        $stok_tersedia = isset($data['stok_tersedia']) ? (int)$data['stok_tersedia'] : 0;
        $gambar_alat = isset($data['gambar_alat']) && !empty($data['gambar_alat']) ? trim($data['gambar_alat']) : null;
        $kondisi_alat = trim($data['kondisi_alat'] ?? 'Baik');

        // Validasi
        if (empty($nama_item)) {
            error_log(get_called_class() . "::create() - Nama item kosong.");
            return false;
        }
        if ($harga_sewa < 0) {
            error_log(get_called_class() . "::create() - Harga sewa tidak boleh negatif.");
            return false;
        }
        if ($durasi_harga_sewa <= 0 && $satuan_durasi_harga !== 'Peminjaman') {
            error_log(get_called_class() . "::create() - Durasi harga sewa harus positif kecuali satuan 'Peminjaman'.");
            return false;
        }
        if (!in_array($satuan_durasi_harga, self::ALLOWED_DURATION_UNITS)) {
            error_log(get_called_class() . "::create() - Satuan durasi harga tidak valid: " . $satuan_durasi_harga);
            return false;
        }
        if ($stok_tersedia < 0) {
            error_log(get_called_class() . "::create() - Stok tersedia tidak boleh negatif.");
            return false;
        }
        if (!in_array($kondisi_alat, self::ALLOWED_CONDITIONS)) {
            error_log(get_called_class() . "::create() - Kondisi alat tidak valid: " . $kondisi_alat);
            return false;
        }


        $sql = "INSERT INTO " . self::$table_name .
            " (nama_item, kategori_alat, deskripsi, harga_sewa, durasi_harga_sewa, satuan_durasi_harga, stok_tersedia, gambar_alat, kondisi_alat, created_at, updated_at) 
               VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";
        $stmt = mysqli_prepare(self::$db, $sql);

        if (!$stmt) {
            error_log(get_called_class() . "::create() - MySQLi Prepare Error: " . mysqli_error(self::$db));
            return false;
        }

        mysqli_stmt_bind_param(
            $stmt,
            "sssdisiss",
            $nama_item,
            $kategori_alat,
            $deskripsi,
            $harga_sewa,
            $durasi_harga_sewa,
            $satuan_durasi_harga,
            $stok_tersedia,
            $gambar_alat,
            $kondisi_alat
        );

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

    /**
     * Mengambil semua item alat sewa.
     * @return array Daftar alat sewa.
     */
    public static function getAll()
    {
        if (!self::checkDependencies()) return [];

        $sql = "SELECT * FROM " . self::$table_name . " ORDER BY nama_item ASC, id ASC";
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

    /**
     * Mencari alat sewa berdasarkan ID.
     * @param int $id ID alat.
     * @return array|null Data alat atau null.
     */
    public static function getById($id)
    {
        if (!self::checkDependencies()) return null;
        $id_val = filter_var($id, FILTER_VALIDATE_INT);
        if ($id_val === false || $id_val <= 0) {
            error_log(get_called_class() . "::getById() - ID tidak valid: " . $id);
            return null;
        }

        $sql = "SELECT * FROM " . self::$table_name . " WHERE id = ?";
        $stmt = mysqli_prepare(self::$db, $sql);
        if (!$stmt) {
            error_log(get_called_class() . "::getById() - MySQLi Prepare Error: " . mysqli_error(self::$db));
            return null;
        }
        mysqli_stmt_bind_param($stmt, "i", $id_val);
        if (mysqli_stmt_execute($stmt)) {
            $result = mysqli_stmt_get_result($stmt);
            $item = mysqli_fetch_assoc($result);
            mysqli_stmt_close($stmt);
            return $item ?: null;
        } else {
            error_log(get_called_class() . "::getById() - MySQLi Execute Error: " . mysqli_stmt_error($stmt));
        }
        mysqli_stmt_close($stmt);
        return null;
    }

    /**
     * Mengupdate data alat sewa.
     * @param array $data Data yang akan diupdate, harus berisi 'id'.
     * @param string|null $new_image_filename Nama file gambar baru (jika ada), atau "REMOVE_IMAGE" untuk menghapus.
     * @param string|null $old_image_filename Nama file gambar lama (untuk dihapus jika gambar diganti).
     * @return bool True jika berhasil.
     */
    public static function update($data, $new_image_filename = null, $old_image_filename = null)
    {
        if (!self::checkDependencies() || !isset($data['id'])) {
            error_log(get_called_class() . "::update() - Koneksi DB/upload_dir gagal atau ID tidak disediakan.");
            return false;
        }

        $id = (int)$data['id'];
        if ($id <= 0) {
            error_log(get_called_class() . "::update() - ID tidak valid: " . $data['id']);
            return false;
        }

        $nama_item = trim($data['nama_item'] ?? '');
        $kategori_alat = isset($data['kategori_alat']) ? trim($data['kategori_alat']) : null;
        $deskripsi = trim($data['deskripsi'] ?? '');
        $harga_sewa = isset($data['harga_sewa']) ? (float)$data['harga_sewa'] : 0.0;
        $durasi_harga_sewa = isset($data['durasi_harga_sewa']) ? (int)$data['durasi_harga_sewa'] : 1;
        $satuan_durasi_harga = trim($data['satuan_durasi_harga'] ?? 'Hari');
        $stok_tersedia = isset($data['stok_tersedia']) ? (int)$data['stok_tersedia'] : 0;
        $kondisi_alat = trim($data['kondisi_alat'] ?? 'Baik');

        // Validasi
        if (empty($nama_item)) {
            error_log(get_called_class() . "::update() - Nama item kosong untuk ID: {$id}.");
            return false;
        }
        if ($harga_sewa < 0) {
            error_log(get_called_class() . "::update() - Harga sewa negatif untuk ID: {$id}.");
            return false;
        }
        if ($durasi_harga_sewa <= 0 && $satuan_durasi_harga !== 'Peminjaman') {
            error_log(get_called_class() . "::update() - Durasi harga sewa tidak valid untuk ID: {$id}.");
            return false;
        }
        if (!in_array($satuan_durasi_harga, self::ALLOWED_DURATION_UNITS)) {
            error_log(get_called_class() . "::update() - Satuan durasi tidak valid untuk ID: {$id}.");
            return false;
        }
        if ($stok_tersedia < 0) {
            error_log(get_called_class() . "::update() - Stok tidak valid untuk ID: {$id}.");
            return false;
        }
        if (!in_array($kondisi_alat, self::ALLOWED_CONDITIONS)) {
            error_log(get_called_class() . "::update() - Kondisi alat tidak valid untuk ID: {$id}.");
            return false;
        }

        $gambar_to_set_in_db = $old_image_filename;

        if ($new_image_filename === "REMOVE_IMAGE") {
            if (!empty($old_image_filename) && file_exists(self::$upload_dir_alat . $old_image_filename)) {
                if (!@unlink(self::$upload_dir_alat . $old_image_filename)) {
                    error_log(get_called_class() . "::update() - Gagal hapus file gambar lama (REMOVE_IMAGE): " . self::$upload_dir_alat . $old_image_filename);
                }
            }
            $gambar_to_set_in_db = null;
        } elseif (!empty($new_image_filename) && $new_image_filename !== $old_image_filename) {
            if (!empty($old_image_filename) && file_exists(self::$upload_dir_alat . $old_image_filename)) {
                if (!@unlink(self::$upload_dir_alat . $old_image_filename)) {
                    error_log(get_called_class() . "::update() - Gagal hapus file gambar lama (ganti baru): " . self::$upload_dir_alat . $old_image_filename);
                }
            }
            $gambar_to_set_in_db = $new_image_filename;
        }

        $sql = "UPDATE " . self::$table_name . " SET
                nama_item = ?, kategori_alat = ?, deskripsi = ?, harga_sewa = ?, 
                durasi_harga_sewa = ?, satuan_durasi_harga = ?, stok_tersedia = ?, 
                gambar_alat = ?, kondisi_alat = ?, updated_at = NOW()
                WHERE id = ?";
        $stmt = mysqli_prepare(self::$db, $sql);

        if (!$stmt) {
            error_log(get_called_class() . "::update() - MySQLi Prepare Error: " . mysqli_error(self::$db));
            return false;
        }

        mysqli_stmt_bind_param(
            $stmt,
            "sssdisissi",
            $nama_item,
            $kategori_alat,
            $deskripsi,
            $harga_sewa,
            $durasi_harga_sewa,
            $satuan_durasi_harga,
            $stok_tersedia,
            $gambar_to_set_in_db,
            $kondisi_alat,
            $id
        );

        if (mysqli_stmt_execute($stmt)) {
            $affected_rows = mysqli_stmt_affected_rows($stmt);
            mysqli_stmt_close($stmt);
            return $affected_rows >= 0;
        } else {
            error_log(get_called_class() . "::update() - MySQLi Execute Error: " . mysqli_stmt_error($stmt));
            mysqli_stmt_close($stmt);
            return false;
        }
    }

    /**
     * Menghapus alat sewa dan file gambar terkait.
     * Mencegah penghapusan jika alat masih terkait dengan pemesanan sewa.
     * @param int $id ID alat.
     * @return bool True jika berhasil.
     */
    public static function delete($id)
    {
        if (!self::checkDependencies()) return false;
        $id_to_delete = filter_var($id, FILTER_VALIDATE_INT);
        if ($id_to_delete === false || $id_to_delete <= 0) {
            error_log(get_called_class() . "::delete() - ID tidak valid: " . $id);
            return false;
        }

        $item = self::getById($id_to_delete); // Ambil info item (termasuk nama_item) sebelum cek

        // Cek apakah alat ini masih digunakan di tabel pemesanan_sewa_alat
        $sql_check = "SELECT COUNT(*) as total_pemesanan FROM pemesanan_sewa_alat WHERE sewa_alat_id = ?";
        $stmt_check = mysqli_prepare(self::$db, $sql_check);
        if (!$stmt_check) {
            error_log(get_called_class() . "::delete() - MySQLi Prepare Error (check pemesanan): " . mysqli_error(self::$db));
            return false;
        }
        mysqli_stmt_bind_param($stmt_check, "i", $id_to_delete);
        if (!mysqli_stmt_execute($stmt_check)) {
            error_log(get_called_class() . "::delete() - MySQLi Execute Error (check pemesanan): " . mysqli_stmt_error($stmt_check));
            mysqli_stmt_close($stmt_check);
            return false;
        }
        $result_check = mysqli_stmt_get_result($stmt_check);
        $row_check = mysqli_fetch_assoc($result_check);
        mysqli_stmt_close($stmt_check);

        $nama_item_display = $item['nama_item'] ?? "ID: " . $id_to_delete;
        if ($row_check && isset($row_check['total_pemesanan']) && $row_check['total_pemesanan'] > 0) {
            error_log(get_called_class() . "::delete() - Gagal: Alat {$nama_item_display} masih digunakan dalam " . $row_check['total_pemesanan'] . " pemesanan.");
            if (function_exists('set_flash_message')) {
                set_flash_message('warning', 'Alat "' . htmlspecialchars($nama_item_display) . '" tidak dapat dihapus karena masih terkait dengan ' . $row_check['total_pemesanan'] . ' data pemesanan sewa.');
            }
            return false;
        }

        $sql_delete = "DELETE FROM " . self::$table_name . " WHERE id = ?";
        $stmt_delete = mysqli_prepare(self::$db, $sql_delete);
        if (!$stmt_delete) {
            error_log(get_called_class() . "::delete() - MySQLi Prepare Error (delete item): " . mysqli_error(self::$db));
            return false;
        }
        mysqli_stmt_bind_param($stmt_delete, "i", $id_to_delete);
        if (mysqli_stmt_execute($stmt_delete)) {
            $affected_rows = mysqli_stmt_affected_rows($stmt_delete);
            mysqli_stmt_close($stmt_delete);
            if ($affected_rows > 0) {
                if ($item && !empty($item['gambar_alat']) && file_exists(self::$upload_dir_alat . $item['gambar_alat'])) {
                    if (!@unlink(self::$upload_dir_alat . $item['gambar_alat'])) {
                        error_log(get_called_class() . "::delete() - Peringatan: Gagal menghapus file gambar " . self::$upload_dir_alat . $item['gambar_alat']);
                    }
                }
                return true;
            } else {
                error_log(get_called_class() . "::delete() - Tidak ada baris yang terhapus untuk ID: " . $id_to_delete);
                return false;
            }
        } else {
            error_log(get_called_class() . "::delete() - MySQLi Execute Error (delete item): " . mysqli_stmt_error($stmt_delete));
            mysqli_stmt_close($stmt_delete);
            return false;
        }
    }

    /**
     * Mengupdate stok tersedia untuk alat tertentu.
     * @param int $alat_id ID alat.
     * @param int $jumlah_perubahan Jumlah perubahan (positif untuk menambah, negatif untuk mengurangi).
     * @return bool True jika berhasil.
     */
    public static function updateStok($alat_id, $jumlah_perubahan)
    {
        if (!self::checkDependencies()) return false; // Gunakan checkDependencies yang juga cek upload_dir
        $alat_id_val = (int)$alat_id;
        $jumlah_val = (int)$jumlah_perubahan;

        if ($alat_id_val <= 0) {
            error_log(get_called_class() . "::updateStok() - ID Alat tidak valid: " . $alat_id);
            return false;
        }

        $sql = "UPDATE " . self::$table_name . " SET stok_tersedia = GREATEST(0, stok_tersedia + ?) WHERE id = ?";
        $stmt = mysqli_prepare(self::$db, $sql);
        if (!$stmt) {
            error_log(get_called_class() . "::updateStok() - MySQLi Prepare Error: " . mysqli_error(self::$db));
            return false;
        }
        mysqli_stmt_bind_param($stmt, "ii", $jumlah_val, $alat_id_val);
        if (mysqli_stmt_execute($stmt)) {
            mysqli_stmt_close($stmt);
            return true;
        } else {
            error_log(get_called_class() . "::updateStok() - MySQLi Execute Error: " . mysqli_stmt_error($stmt));
            mysqli_stmt_close($stmt);
            return false;
        }
    }

    /**
     * Menghitung semua item alat sewa.
     * @return int Total item.
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
        } else {
            error_log(get_called_class() . "::countAll() - MySQLi Query Error: " . mysqli_error(self::$db));
        }
        return 0;
    }
} // End of class SewaAlat