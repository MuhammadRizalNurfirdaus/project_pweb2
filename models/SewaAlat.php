<?php
// File: C:\xampp\htdocs\Cilengkrang-Web-Wisata\models\SewaAlat.php

class SewaAlat
{
    private static $table_name = "sewa_alat";
    private static $db;
    private static $upload_dir_alat; // Path fisik server untuk upload
    private static $last_error = null;

    public const ALLOWED_DURATION_UNITS = ['Jam', 'Hari', 'Peminjaman'];
    public const ALLOWED_CONDITIONS = ['Baik', 'Rusak Ringan', 'Rusak Berat', 'Perlu Perbaikan'];

    public static function init(mysqli $connection, $upload_path)
    {
        self::$db = $connection;
        // Pastikan path upload diakhiri dengan DIRECTORY_SEPARATOR
        self::$upload_dir_alat = rtrim($upload_path, '/\\') . DIRECTORY_SEPARATOR;
    }

    private static function checkDependencies()
    {
        self::$last_error = null; // Reset error di awal
        if (!self::$db || (self::$db instanceof mysqli && self::$db->connect_error)) {
            self::$last_error = "Koneksi database Model SewaAlat gagal atau belum diinisialisasi.";
            error_log(get_called_class() . " - " . self::$last_error . " Detail: " . (self::$db instanceof mysqli ? self::$db->connect_error : 'Objek DB null atau bukan mysqli.'));
            return false;
        }
        if (empty(self::$upload_dir_alat) || !is_dir(self::$upload_dir_alat)) { // Cukup cek is_dir, writable dicek saat operasi file
            self::$last_error = "Path upload direktori alat (self::\$upload_dir_alat) belum diinisialisasi atau bukan direktori valid.";
            error_log(get_called_class() . " - " . self::$last_error . " Path: '" . (self::$upload_dir_alat ?? 'Kosong') . "'");
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
        // Jangan tampilkan "Tidak ada error..." jika memang null.
        return null;
    }

    public static function create($data)
    {
        if (!self::checkDependencies()) {
            // self::$last_error sudah di-set oleh checkDependencies
            return false;
        }
        self::$last_error = null;

        $nama_item = trim($data['nama_item'] ?? '');
        $kategori_alat = isset($data['kategori_alat']) ? trim($data['kategori_alat']) : null;
        $deskripsi = trim($data['deskripsi'] ?? '');
        $harga_sewa = isset($data['harga_sewa']) ? (float)$data['harga_sewa'] : 0.0;
        $durasi_harga_sewa = isset($data['durasi_harga_sewa']) ? (int)$data['durasi_harga_sewa'] : 1;
        $satuan_durasi_harga = trim($data['satuan_durasi_harga'] ?? 'Hari');
        $stok_tersedia = isset($data['stok_tersedia']) ? (int)$data['stok_tersedia'] : 0;
        // Controller akan mengirim nama file yang sudah diproses (unik) atau null
        $gambar_alat_db = isset($data['gambar_alat']) ? trim($data['gambar_alat']) : null;
        if (empty($gambar_alat_db)) $gambar_alat_db = null; // Pastikan null jika string kosong

        $kondisi_alat = trim($data['kondisi_alat'] ?? 'Baik');

        // Validasi di Model (lapisan terakhir)
        if (empty($nama_item)) {
            self::$last_error = "Nama item wajib diisi.";
            return false;
        }
        if ($harga_sewa < 1) {
            self::$last_error = "Harga sewa minimal Rp 1.";
            return false;
        }
        if ($satuan_durasi_harga !== 'Peminjaman' && $durasi_harga_sewa < 1) {
            self::$last_error = "Durasi harga sewa minimal 1 (kecuali Peminjaman).";
            return false;
        }
        if ($stok_tersedia < 1) {
            self::$last_error = "Stok tersedia minimal 1 unit.";
            return false;
        }
        if (!in_array($satuan_durasi_harga, self::ALLOWED_DURATION_UNITS)) {
            self::$last_error = "Satuan durasi harga tidak valid: " . $satuan_durasi_harga;
            return false;
        }
        if (!in_array($kondisi_alat, self::ALLOWED_CONDITIONS)) {
            self::$last_error = "Kondisi alat tidak valid: " . $kondisi_alat;
            return false;
        }

        $sql = "INSERT INTO " . self::$table_name .
            " (nama_item, kategori_alat, deskripsi, harga_sewa, durasi_harga_sewa, satuan_durasi_harga, stok_tersedia, gambar_alat, kondisi_alat, created_at, updated_at) 
               VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";
        $stmt = mysqli_prepare(self::$db, $sql);

        if (!$stmt) {
            self::$last_error = "MySQLi Prepare Error (create): " . mysqli_error(self::$db);
            error_log(get_called_class() . "::create() - " . self::$last_error . " | SQL: " . $sql);
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
            $gambar_alat_db,
            $kondisi_alat
        );

        if (mysqli_stmt_execute($stmt)) {
            $new_id = mysqli_insert_id(self::$db);
            mysqli_stmt_close($stmt);
            return $new_id;
        } else {
            self::$last_error = "MySQLi Execute Error (create): " . mysqli_stmt_error($stmt);
            error_log(get_called_class() . "::create() - " . self::$last_error);
            mysqli_stmt_close($stmt);
            return false;
        }
    }

    public static function getAll()
    {
        if (!self::checkDependencies()) return [];
        self::$last_error = null;
        $sql = "SELECT * FROM " . self::$table_name . " ORDER BY nama_item ASC, id ASC";
        $result = mysqli_query(self::$db, $sql);
        if ($result) {
            return mysqli_fetch_all($result, MYSQLI_ASSOC);
        } else {
            self::$last_error = "MySQLi Query Error (getAll): " . mysqli_error(self::$db);
            error_log(get_called_class() . "::getAll() - " . self::$last_error);
            return [];
        }
    }

    public static function getById($id)
    {
        if (!self::checkDependencies()) return null;
        self::$last_error = null;
        $id_val = filter_var($id, FILTER_VALIDATE_INT);
        if ($id_val === false || $id_val <= 0) {
            self::$last_error = "ID tidak valid untuk getById: " . $id;
            error_log(get_called_class() . "::getById() - " . self::$last_error);
            return null;
        }
        $sql = "SELECT * FROM " . self::$table_name . " WHERE id = ?";
        $stmt = mysqli_prepare(self::$db, $sql);
        if (!$stmt) {
            self::$last_error = "MySQLi Prepare Error (getById): " . mysqli_error(self::$db);
            error_log(get_called_class() . "::getById() - " . self::$last_error);
            return null;
        }
        mysqli_stmt_bind_param($stmt, "i", $id_val);
        if (mysqli_stmt_execute($stmt)) {
            $result = mysqli_stmt_get_result($stmt);
            $item = mysqli_fetch_assoc($result);
            mysqli_stmt_close($stmt);
            return $item ?: null;
        } else {
            self::$last_error = "MySQLi Execute Error (getById): " . mysqli_stmt_error($stmt);
            error_log(get_called_class() . "::getById() - " . self::$last_error);
            mysqli_stmt_close($stmt);
            return null;
        }
    }

    /**
     * Mengupdate data alat sewa.
     * Menerima array $data yang berisi semua field yang mungkin diupdate.
     * Jika 'gambar_alat' ada di $data, kolom gambar akan diupdate.
     * Jika 'gambar_alat' tidak ada di $data, kolom gambar TIDAK akan diubah.
     * Penghapusan file fisik lama harus dihandle oleh Controller.
     */
    public static function update(array $data): bool // Hanya satu parameter array
    {
        if (!self::checkDependencies() || !isset($data['id'])) {
            self::$last_error = "Koneksi DB/upload_dir gagal atau ID tidak disediakan.";
            error_log(get_called_class() . "::update() - " . self::$last_error);
            return false;
        }
        $id = (int)$data['id'];
        if ($id <= 0) {
            self::$last_error = "ID tidak valid untuk update: " . ($data['id'] ?? 'Tidak ada');
            error_log(get_called_class() . "::update() - " . self::$last_error);
            return false;
        }

        // Validasi input dasar yang ada di $data
        if (isset($data['nama_item']) && empty(trim($data['nama_item']))) {
            self::$last_error = "Nama item tidak boleh kosong.";
            return false;
        }
        if (isset($data['harga_sewa']) && ((float)$data['harga_sewa'] < 1)) {
            self::$last_error = "Harga sewa minimal Rp 1.";
            return false;
        }
        if (isset($data['durasi_harga_sewa']) && ($data['satuan_durasi_harga'] ?? '') !== 'Peminjaman' && (int)$data['durasi_harga_sewa'] < 1) {
            self::$last_error = "Durasi harga minimal 1.";
            return false;
        }
        if (isset($data['stok_tersedia']) && (int)$data['stok_tersedia'] < 1) {
            self::$last_error = "Stok minimal 1 unit.";
            return false;
        }
        if (isset($data['satuan_durasi_harga']) && !in_array($data['satuan_durasi_harga'], self::ALLOWED_DURATION_UNITS)) {
            self::$last_error = "Satuan durasi tidak valid.";
            return false;
        }
        if (isset($data['kondisi_alat']) && !in_array($data['kondisi_alat'], self::ALLOWED_CONDITIONS)) {
            self::$last_error = "Kondisi alat tidak valid.";
            return false;
        }

        $fields_to_set_sql = [];
        $params_for_bind = [];
        $types_for_bind = "";

        $allowed_fields_to_update = [
            'nama_item' => 's',
            'kategori_alat' => 's',
            'deskripsi' => 's',
            'harga_sewa' => 'd',
            'durasi_harga_sewa' => 'i',
            'satuan_durasi_harga' => 's',
            'stok_tersedia' => 'i',
            'kondisi_alat' => 's',
            'gambar_alat' => 's' // gambar_alat sekarang di sini
        ];

        foreach ($allowed_fields_to_update as $field_key => $type_char) {
            // Hanya tambahkan ke query jika field tersebut ada di array $data yang dikirim
            if (array_key_exists($field_key, $data)) {
                $fields_to_set_sql[] = "`" . $field_key . "` = ?";
                $params_for_bind[] = $data[$field_key]; // Nilai dari $data, bisa jadi null untuk gambar_alat
                $types_for_bind .= $type_char;
            }
        }

        if (empty($fields_to_set_sql)) {
            // Tidak ada field yang diubah dan dikirim oleh controller
            return true;
        }

        $fields_to_set_sql[] = "`updated_at` = NOW()";

        $sql_update = "UPDATE `" . self::$table_name . "` SET " . implode(', ', $fields_to_set_sql) . " WHERE `id` = ?";
        $params_for_bind[] = $id;
        $types_for_bind .= "i";

        $stmt = mysqli_prepare(self::$db, $sql_update);
        if (!$stmt) {
            self::$last_error = "MySQLi Prepare Error (update): " . mysqli_error(self::$db);
            error_log(get_called_class() . "::update() - " . self::$last_error . " | SQL: " . $sql_update);
            return false;
        }

        mysqli_stmt_bind_param($stmt, $types_for_bind, ...$params_for_bind);

        if (mysqli_stmt_execute($stmt)) {
            mysqli_stmt_close($stmt);
            return true;
        } else {
            self::$last_error = "MySQLi Execute Error (update): " . mysqli_stmt_error($stmt);
            error_log(get_called_class() . "::update() - " . self::$last_error);
            mysqli_stmt_close($stmt);
            return false;
        }
    }
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