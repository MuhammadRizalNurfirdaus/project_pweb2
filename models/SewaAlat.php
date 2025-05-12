<?php
// File: C:\xampp\htdocs\Cilengkrang-Web-Wisata\models\SewaAlat.php

class SewaAlat
{
    private static $table_name = "sewa_alat";
    private static $upload_dir_alat = __DIR__ . "/../../public/uploads/alat_sewa/";

    public static function create($data)
    {
        global $conn;
        if (!$conn) {
            error_log("SewaAlat::create() - Koneksi database gagal.");
            return false;
        }

        $nama_item = trim($data['nama_item'] ?? '');
        $kategori_alat = trim($data['kategori_alat'] ?? null);
        $deskripsi = trim($data['deskripsi'] ?? '');
        $harga_sewa = isset($data['harga_sewa']) ? (int)$data['harga_sewa'] : 0;
        $durasi_harga_sewa = isset($data['durasi_harga_sewa']) ? (int)$data['durasi_harga_sewa'] : 1;
        $satuan_durasi_harga = trim($data['satuan_durasi_harga'] ?? 'Hari');
        $stok_tersedia = isset($data['stok_tersedia']) ? (int)$data['stok_tersedia'] : 0;
        $gambar_alat = isset($data['gambar_alat']) && !empty($data['gambar_alat']) ? trim($data['gambar_alat']) : null;
        $kondisi_alat = trim($data['kondisi_alat'] ?? 'Baik');


        if (empty($nama_item) || $harga_sewa < 0 || $durasi_harga_sewa <= 0 || $stok_tersedia < 0) {
            error_log("SewaAlat::create() - Error: Nama item, harga, durasi, atau stok tidak valid. Nama: '{$nama_item}', Harga: {$harga_sewa}, Durasi: {$durasi_harga_sewa}, Stok: {$stok_tersedia}");
            return false;
        }

        $sql = "INSERT INTO " . self::$table_name .
            " (nama_item, kategori_alat, deskripsi, harga_sewa, durasi_harga_sewa, satuan_durasi_harga, stok_tersedia, gambar_alat, kondisi_alat) 
               VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = mysqli_prepare($conn, $sql);

        if (!$stmt) {
            error_log("SewaAlat::create() - MySQLi Prepare Error: " . mysqli_error($conn) . " | SQL: " . $sql);
            return false;
        }

        mysqli_stmt_bind_param(
            $stmt,
            "sssiisiss",
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
            $new_id = mysqli_insert_id($conn);
            mysqli_stmt_close($stmt);
            return $new_id;
        } else {
            error_log("SewaAlat::create() - MySQLi Execute Error: " . mysqli_stmt_error($stmt) . " | SQL: " . $sql);
            mysqli_stmt_close($stmt);
            return false;
        }
    }

    /**
     * Mengambil semua item alat sewa.
     * Diurutkan berdasarkan ID secara menaik.
     * @return array Array of records, atau array kosong jika gagal/tidak ada records.
     */
    public static function getAll()
    {
        global $conn;
        if (!$conn) {
            error_log("SewaAlat::getAll() - Koneksi database gagal.");
            return [];
        }
        // === PERBAIKAN UTAMA ADA DI SINI ===
        $sql = "SELECT * FROM " . self::$table_name . " ORDER BY id ASC"; // Diurutkan berdasarkan ID ASC
        $result = mysqli_query($conn, $sql);
        if ($result) {
            return mysqli_fetch_all($result, MYSQLI_ASSOC);
        } else {
            error_log("SewaAlat::getAll() - MySQLi Query Error: " . mysqli_error($conn) . " | SQL: " . $sql);
            return [];
        }
    }

    public static function getById($id)
    {
        global $conn;
        if (!$conn) {
            error_log("SewaAlat::getById() - Koneksi database gagal.");
            return null;
        }
        $id_val = filter_var($id, FILTER_VALIDATE_INT);
        if ($id_val === false || $id_val <= 0) {
            error_log("SewaAlat::getById() - Error: ID tidak valid (" . e($id) . ").");
            return null;
        }

        $sql = "SELECT * FROM " . self::$table_name . " WHERE id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        if (!$stmt) {
            error_log("SewaAlat::getById() - MySQLi Prepare Error: " . mysqli_error($conn) . " | SQL: " . $sql);
            return null;
        }
        mysqli_stmt_bind_param($stmt, "i", $id_val);
        if (mysqli_stmt_execute($stmt)) {
            $result = mysqli_stmt_get_result($stmt);
            $item = mysqli_fetch_assoc($result);
            mysqli_stmt_close($stmt);
            return $item ?: null;
        } else {
            error_log("SewaAlat::getById() - MySQLi Execute Error: " . mysqli_stmt_error($stmt) . " | SQL: " . $sql);
            mysqli_stmt_close($stmt);
            return null;
        }
    }

    public static function update($data, $new_image_filename = null, $old_image_filename = null)
    {
        global $conn;
        if (!$conn || !isset($data['id'])) {
            error_log("SewaAlat::update() - Koneksi database gagal atau ID tidak disediakan.");
            return false;
        }

        $id = (int)$data['id'];
        $nama_item = trim($data['nama_item'] ?? '');
        $kategori_alat = trim($data['kategori_alat'] ?? null);
        $deskripsi = trim($data['deskripsi'] ?? '');
        $harga_sewa = isset($data['harga_sewa']) ? (int)$data['harga_sewa'] : 0;
        $durasi_harga_sewa = isset($data['durasi_harga_sewa']) ? (int)$data['durasi_harga_sewa'] : 1;
        $satuan_durasi_harga = trim($data['satuan_durasi_harga'] ?? 'Hari');
        $stok_tersedia = isset($data['stok_tersedia']) ? (int)$data['stok_tersedia'] : 0;
        $kondisi_alat = trim($data['kondisi_alat'] ?? 'Baik');

        if ($id <= 0 || empty($nama_item) || $harga_sewa < 0 || $durasi_harga_sewa <= 0 || $stok_tersedia < 0) {
            error_log("SewaAlat::update() - Error: Data input tidak valid. ID: {$id}, Nama: '{$nama_item}', Harga: {$harga_sewa}, Durasi: {$durasi_harga_sewa}, Stok: {$stok_tersedia}");
            return false;
        }

        $gambar_to_set_in_db = $old_image_filename;

        if ($new_image_filename === "REMOVE_IMAGE") {
            if (!empty($old_image_filename) && file_exists(self::$upload_dir_alat . $old_image_filename)) {
                if (!@unlink(self::$upload_dir_alat . $old_image_filename)) {
                    error_log("SewaAlat::update() - Gagal menghapus file gambar lama (REMOVE_IMAGE): " . self::$upload_dir_alat . $old_image_filename);
                }
            }
            $gambar_to_set_in_db = null;
        } elseif (!empty($new_image_filename) && $new_image_filename !== $old_image_filename) {
            if (!empty($old_image_filename) && file_exists(self::$upload_dir_alat . $old_image_filename)) {
                if (!@unlink(self::$upload_dir_alat . $old_image_filename)) {
                    error_log("SewaAlat::update() - Gagal menghapus file gambar lama (saat mengganti): " . self::$upload_dir_alat . $old_image_filename);
                }
            }
            $gambar_to_set_in_db = $new_image_filename;
        }

        $sql = "UPDATE " . self::$table_name . " SET
                nama_item = ?, kategori_alat = ?, deskripsi = ?, harga_sewa = ?, 
                durasi_harga_sewa = ?, satuan_durasi_harga = ?, stok_tersedia = ?, 
                gambar_alat = ?, kondisi_alat = ?
                WHERE id = ?";
        $stmt = mysqli_prepare($conn, $sql);

        if (!$stmt) {
            error_log("SewaAlat::update() - MySQLi Prepare Error: " . mysqli_error($conn) . " | SQL: " . $sql);
            return false;
        }

        mysqli_stmt_bind_param(
            $stmt,
            "sssiisissi",
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
            mysqli_stmt_close($stmt);
            return true;
        } else {
            error_log("SewaAlat::update() - MySQLi Execute Error: " . mysqli_stmt_error($stmt) . " | SQL: " . $sql);
            mysqli_stmt_close($stmt);
            return false;
        }
    }

    public static function delete($id)
    { /* ... sama seperti sebelumnya, dengan pengecekan FK aktif ... */
        global $conn;
        if (!$conn) {
            error_log("SewaAlat::delete() - Koneksi database gagal.");
            return false;
        }
        $id_to_delete = filter_var($id, FILTER_VALIDATE_INT);
        if ($id_to_delete === false || $id_to_delete <= 0) {
            error_log("SewaAlat::delete() - Error: ID tidak valid (" . e($id) . ").");
            return false;
        }
        $sql_check = "SELECT COUNT(*) as total_pemesanan FROM pemesanan_sewa_alat WHERE sewa_alat_id = ?";
        $stmt_check = mysqli_prepare($conn, $sql_check);
        if (!$stmt_check) {
            error_log("SewaAlat::delete() - MySQLi Prepare Error (check pemesanan): " . mysqli_error($conn));
            return false;
        }
        mysqli_stmt_bind_param($stmt_check, "i", $id_to_delete);
        if (!mysqli_stmt_execute($stmt_check)) {
            error_log("SewaAlat::delete() - MySQLi Execute Error (check pemesanan): " . mysqli_stmt_error($stmt_check));
            mysqli_stmt_close($stmt_check);
            return false;
        }
        $result_check = mysqli_stmt_get_result($stmt_check);
        $row_check = mysqli_fetch_assoc($result_check);
        mysqli_stmt_close($stmt_check);
        if ($row_check && $row_check['total_pemesanan'] > 0) {
            error_log("SewaAlat::delete() - Gagal: Alat ID " . $id_to_delete . " masih digunakan dalam " . $row_check['total_pemesanan'] . " pemesanan.");
            set_flash_message('warning', 'Alat tidak dapat dihapus karena masih terkait dengan data pemesanan.');
            return false;
        }
        $item = self::getById($id_to_delete);
        $sql_delete = "DELETE FROM " . self::$table_name . " WHERE id = ?";
        $stmt_delete = mysqli_prepare($conn, $sql_delete);
        if (!$stmt_delete) {
            error_log("SewaAlat::delete() - MySQLi Prepare Error (delete item): " . mysqli_error($conn) . " | SQL: " . $sql_delete);
            return false;
        }
        mysqli_stmt_bind_param($stmt_delete, "i", $id_to_delete);
        if (mysqli_stmt_execute($stmt_delete)) {
            $affected_rows = mysqli_stmt_affected_rows($stmt_delete);
            mysqli_stmt_close($stmt_delete);
            if ($affected_rows > 0) {
                if ($item && !empty($item['gambar_alat']) && file_exists(self::$upload_dir_alat . $item['gambar_alat'])) {
                    if (!@unlink(self::$upload_dir_alat . $item['gambar_alat'])) {
                        error_log("SewaAlat::delete() - Warning: Gagal menghapus file gambar " . self::$upload_dir_alat . $item['gambar_alat']);
                    }
                }
                return true;
            } else {
                error_log("SewaAlat::delete() - Tidak ada baris yang terhapus untuk ID: " . $id_to_delete . " (mungkin sudah dihapus atau ID tidak ada).");
                return false;
            }
        } else {
            error_log("SewaAlat::delete() - MySQLi Execute Error (delete item): " . mysqli_stmt_error($stmt_delete) . " | SQL: " . $sql_delete);
            mysqli_stmt_close($stmt_delete);
            return false;
        }
    }
    public static function updateStok($alat_id, $jumlah_perubahan)
    { /* ... sama seperti sebelumnya ... */
        global $conn;
        if (!$conn) {
            error_log("SewaAlat::updateStok() - Koneksi database gagal.");
            return false;
        }
        $alat_id_val = (int)$alat_id;
        $jumlah_val = (int)$jumlah_perubahan;
        if ($alat_id_val <= 0) {
            error_log("SewaAlat::updateStok() - ID Alat tidak valid.");
            return false;
        }
        $sql = "UPDATE " . self::$table_name . " SET stok_tersedia = GREATEST(0, stok_tersedia + ?) WHERE id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        if (!$stmt) {
            error_log("SewaAlat::updateStok() - MySQLi Prepare Error: " . mysqli_error($conn) . " | SQL: " . $sql);
            return false;
        }
        mysqli_stmt_bind_param($stmt, "ii", $jumlah_val, $alat_id_val);
        if (mysqli_stmt_execute($stmt)) {
            mysqli_stmt_close($stmt);
            return true;
        } else {
            error_log("SewaAlat::updateStok() - MySQLi Execute Error: " . mysqli_stmt_error($stmt) . " | SQL: " . $sql);
            mysqli_stmt_close($stmt);
            return false;
        }
    }
}
