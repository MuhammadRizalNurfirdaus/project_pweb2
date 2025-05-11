<?php
// File: C:\xampp\htdocs\Cilengkrang-Web-Wisata\models\Wisata.php

class Wisata
{
    private static $table_name = "wisata";
    private static $upload_dir = __DIR__ . "/../../public/uploads/wisata/"; // Path absolut ke folder upload

    /**
     * Membuat data destinasi wisata baru.
     * @param array $data Array asosiatif data wisata.
     * Kunci yang diharapkan: 'nama_wisata', 'deskripsi', dan opsional 'lokasi', 'gambar'.
     * @return int|false ID record baru jika berhasil, false jika gagal.
     */
    public static function create($data)
    {
        global $conn; // Menggunakan koneksi global dari config.php
        if (!$conn) {
            error_log("Wisata::create() - Koneksi database gagal.");
            return false;
        }

        // Ambil data dan berikan nilai default jika tidak ada
        $nama_wisata = trim($data['nama_wisata'] ?? ''); // Ini akan disimpan ke kolom 'nama' di DB
        $deskripsi = trim($data['deskripsi'] ?? '');
        $lokasi = trim($data['lokasi'] ?? '');
        $gambar = isset($data['gambar']) && !empty($data['gambar']) ? trim($data['gambar']) : null;

        // Validasi dasar
        if (empty($nama_wisata) || empty($deskripsi)) {
            error_log("Wisata::create() - Error: Nama Wisata atau Deskripsi tidak boleh kosong.");
            return false;
        }

        // Kolom di DB adalah 'nama', 'deskripsi', 'gambar', 'lokasi'
        $sql = "INSERT INTO " . self::$table_name . " (nama, deskripsi, gambar, lokasi) VALUES (?, ?, ?, ?)";
        $stmt = mysqli_prepare($conn, $sql);

        if (!$stmt) {
            error_log("Wisata::create() - MySQLi Prepare Error: " . mysqli_error($conn));
            return false;
        }

        // Bind parameter: s = string
        mysqli_stmt_bind_param(
            $stmt,
            "ssss",
            $nama_wisata, // Variabel $nama_wisata dari PHP akan dimasukkan ke kolom 'nama'
            $deskripsi,
            $gambar,
            $lokasi
        );

        if (mysqli_stmt_execute($stmt)) {
            $new_id = mysqli_insert_id($conn);
            mysqli_stmt_close($stmt);
            return $new_id;
        } else {
            error_log("Wisata::create() - MySQLi Execute Error: " . mysqli_stmt_error($stmt));
            mysqli_stmt_close($stmt);
            return false;
        }
    }

    /**
     * Mengambil semua data destinasi wisata.
     * @return array Array of records, atau array kosong jika gagal/tidak ada records.
     */
    public static function getAll()
    {
        global $conn;
        if (!$conn) {
            error_log("Wisata::getAll() - Koneksi database gagal.");
            return [];
        }

        // Ambil semua kolom yang ada dan alias 'nama' menjadi 'nama_wisata' untuk konsistensi di kode PHP
        // Diurutkan berdasarkan ID ASC (menaik)
        $sql = "SELECT id, nama AS nama_wisata, deskripsi, gambar, lokasi, created_at 
                FROM " . self::$table_name . " 
                ORDER BY id ASC";
        $result = mysqli_query($conn, $sql);

        if ($result) {
            return mysqli_fetch_all($result, MYSQLI_ASSOC);
        } else {
            error_log("Wisata::getAll() - MySQLi Query Error: " . mysqli_error($conn));
            return [];
        }
    }

    /**
     * Mengambil satu data destinasi wisata berdasarkan ID.
     * @param int $id ID destinasi wisata.
     * @return array|null Data wisata atau null jika tidak ditemukan/error.
     */
    public static function getById($id)
    {
        global $conn;
        if (!$conn) {
            error_log("Wisata::getById() - Koneksi database gagal.");
            return null;
        }

        $id_val = intval($id);
        if ($id_val <= 0) {
            error_log("Wisata::getById() - Error: ID tidak valid (" . $id . ").");
            return null;
        }

        // Ambil semua kolom yang ada dan alias 'nama' menjadi 'nama_wisata'
        $sql = "SELECT id, nama AS nama_wisata, deskripsi, gambar, lokasi, created_at 
                FROM " . self::$table_name . " 
                WHERE id = ?";
        $stmt = mysqli_prepare($conn, $sql);

        if (!$stmt) {
            error_log("Wisata::getById() - MySQLi Prepare Error: " . mysqli_error($conn));
            return null;
        }

        mysqli_stmt_bind_param($stmt, "i", $id_val);

        if (mysqli_stmt_execute($stmt)) {
            $result = mysqli_stmt_get_result($stmt);
            $wisata = mysqli_fetch_assoc($result);
            mysqli_stmt_close($stmt);
            return $wisata ?: null; // Mengembalikan null jika tidak ada hasil (ID tidak ditemukan)
        } else {
            error_log("Wisata::getById() - MySQLi Execute Error: " . mysqli_stmt_error($stmt));
            mysqli_stmt_close($stmt);
            return null;
        }
    }

    /**
     * Memperbarui data destinasi wisata.
     * @param array $data Array data wisata yang akan diupdate (harus ada 'id').
     *                    Kunci yang diharapkan: 'id', 'nama_wisata', 'deskripsi', 'lokasi'.
     * @param string|null $new_image_filename Nama file gambar baru, atau "REMOVE_IMAGE", atau null (tidak ada perubahan gambar).
     * @param string|null $old_image_filename Nama file gambar lama yang ada di database.
     * @return bool True jika berhasil, false jika gagal.
     */
    public static function update($data, $new_image_filename = null, $old_image_filename = null)
    {
        global $conn;
        if (!$conn || !isset($data['id'])) {
            error_log("Wisata::update() - Koneksi database gagal atau ID tidak disediakan.");
            return false;
        }

        $id = (int)$data['id'];
        // Ambil data dari array, gunakan 'nama_wisata' untuk konsistensi di PHP
        $nama_wisata = trim($data['nama_wisata'] ?? ''); // Ini akan diupdate ke kolom 'nama' di DB
        $deskripsi = trim($data['deskripsi'] ?? '');
        $lokasi = trim($data['lokasi'] ?? '');

        // Validasi
        if ($id <= 0 || empty($nama_wisata) || empty($deskripsi)) {
            error_log("Wisata::update() - Error: ID, Nama Wisata, atau Deskripsi tidak valid.");
            return false;
        }

        $gambar_to_set_in_db = $old_image_filename; // Default: pertahankan gambar lama di DB

        // Logika untuk menangani file gambar
        if ($new_image_filename === "REMOVE_IMAGE") {
            // Jika ada gambar lama dan file-nya ada, hapus file dari server
            if (!empty($old_image_filename) && file_exists(self::$upload_dir . $old_image_filename)) {
                if (!@unlink(self::$upload_dir . $old_image_filename)) {
                    error_log("Wisata::update() - Gagal menghapus file gambar lama: " . self::$upload_dir . $old_image_filename);
                }
            }
            $gambar_to_set_in_db = null; // Set kolom gambar di DB menjadi NULL
        } elseif (!empty($new_image_filename) && $new_image_filename !== $old_image_filename) {
            // Ada gambar baru yang valid dan berbeda dari yang lama
            // Hapus gambar lama dari server jika ada
            if (!empty($old_image_filename) && file_exists(self::$upload_dir . $old_image_filename)) {
                if (!@unlink(self::$upload_dir . $old_image_filename)) {
                    error_log("Wisata::update() - Gagal menghapus file gambar lama (saat mengganti): " . self::$upload_dir . $old_image_filename);
                }
            }
            $gambar_to_set_in_db = $new_image_filename; // Gunakan nama file gambar baru untuk DB
        }
        // Jika $new_image_filename null atau sama dengan $old_image_filename,
        // maka $gambar_to_set_in_db akan tetap berisi $old_image_filename (tidak ada perubahan pada kolom gambar di DB).

        // Kolom di DB adalah 'nama', 'deskripsi', 'gambar', 'lokasi'
        $sql = "UPDATE " . self::$table_name . " SET
                nama = ?,
                deskripsi = ?,
                gambar = ?,
                lokasi = ?
                WHERE id = ?";
        $stmt = mysqli_prepare($conn, $sql);

        if (!$stmt) {
            error_log("Wisata::update() - MySQLi Prepare Error: " . mysqli_error($conn));
            return false;
        }

        // Bind parameter
        mysqli_stmt_bind_param(
            $stmt,
            "ssssi",
            $nama_wisata, // $nama_wisata dari PHP akan diupdate ke kolom 'nama' di DB
            $deskripsi,
            $gambar_to_set_in_db, // Nilai ini bisa null, nama file baru, atau nama file lama
            $lokasi,
            $id
        );

        if (mysqli_stmt_execute($stmt)) {
            mysqli_stmt_close($stmt);
            return true; // Berhasil meskipun mungkin tidak ada baris yang terpengaruh (data sama)
        } else {
            error_log("Wisata::update() - MySQLi Execute Error: " . mysqli_stmt_error($stmt));
            mysqli_stmt_close($stmt);
            return false;
        }
    }

    /**
     * Menghapus data destinasi wisata beserta gambarnya.
     * @param int $id ID destinasi wisata yang akan dihapus.
     * @return bool True jika berhasil, false jika gagal.
     */
    public static function delete($id)
    {
        global $conn;
        if (!$conn) {
            error_log("Wisata::delete() - Koneksi database gagal.");
            return false;
        }

        $id_val = intval($id);
        if ($id_val <= 0) {
            error_log("Wisata::delete() - Error: ID tidak valid (" . $id . ").");
            return false;
        }

        // 1. Ambil nama file gambar sebelum menghapus record dari DB
        $wisata = self::getById($id_val);
        $gambar_filename_to_delete_on_server = null;
        if ($wisata && !empty($wisata['gambar'])) {
            $gambar_filename_to_delete_on_server = $wisata['gambar'];
        }

        // 2. Hapus record dari database
        $sql = "DELETE FROM " . self::$table_name . " WHERE id = ?";
        $stmt = mysqli_prepare($conn, $sql);

        if (!$stmt) {
            error_log("Wisata::delete() - MySQLi Prepare Error: " . mysqli_error($conn));
            return false;
        }
        mysqli_stmt_bind_param($stmt, "i", $id_val);

        if (mysqli_stmt_execute($stmt)) {
            $affected_rows = mysqli_stmt_affected_rows($stmt);
            mysqli_stmt_close($stmt);

            if ($affected_rows > 0) {
                // 3. Jika record DB berhasil dihapus, hapus file gambar dari server
                if (!empty($gambar_filename_to_delete_on_server) && file_exists(self::$upload_dir . $gambar_filename_to_delete_on_server)) {
                    if (!@unlink(self::$upload_dir . $gambar_filename_to_delete_on_server)) {
                        error_log("Wisata::delete() - Warning: Gagal menghapus file gambar " . $gambar_filename_to_delete_on_server . " dari server.");
                    }
                }
                return true;
            } else {
                // Tidak ada baris yang terpengaruh (kemungkinan ID tidak ada atau sudah dihapus)
                error_log("Wisata::delete() - Tidak ada baris yang terhapus untuk ID: " . $id_val);
                return false;
            }
        } else {
            error_log("Wisata::delete() - MySQLi Execute Error: " . mysqli_stmt_error($stmt));
            mysqli_stmt_close($stmt);
            return false;
        }
    }
}
