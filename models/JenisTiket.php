<?php
// File: C:\xampp\htdocs\Cilengkrang-Web-Wisata\models\JenisTiket.php

class JenisTiket
{
    private static $table_name = "jenis_tiket";
    private static $db;

    // Daftar tipe hari yang diizinkan (bisa juga dari konstanta global atau config)
    public const ALLOWED_TIPE_HARI = ['Hari Kerja', 'Hari Libur', 'Semua Hari'];

    /**
     * Mengatur koneksi database untuk digunakan oleh kelas ini.
     * @param mysqli $connection Instance koneksi mysqli.
     */
    public static function setDbConnection(mysqli $connection)
    {
        self::$db = $connection;
    }

    /**
     * Memeriksa apakah koneksi database tersedia.
     * @return bool True jika koneksi valid, false jika tidak.
     */
    private static function checkDbConnection()
    {
        if (!self::$db || (self::$db instanceof mysqli && self::$db->connect_error)) {
            error_log(get_called_class() . " - Koneksi database tidak tersedia atau gagal: " .
                (self::$db instanceof mysqli ? self::$db->connect_error : (self::$db === null ? 'Koneksi belum diset.' : 'Koneksi bukan objek mysqli.')));
            return false;
        }
        return true;
    }

    /**
     * Mengambil pesan error terakhir dari koneksi database model ini.
     * @return string Pesan error.
     */
    public static function getLastError()
    {
        if (self::$db instanceof mysqli && !empty(self::$db->error)) {
            return self::$db->error;
        } elseif (!self::$db || !(self::$db instanceof mysqli)) {
            return 'Koneksi database belum diinisialisasi atau tidak valid untuk kelas ' . get_called_class() . '.';
        }
        return 'Tidak ada error database spesifik yang dilaporkan dari model ' . get_called_class() . '.';
    }

    /**
     * Mengecek apakah sudah ada entri jenis tiket yang sama (nama, tipe hari, wisata_id).
     * @param string $nama_layanan
     * @param string $tipe_hari
     * @param int|null $wisata_id
     * @param int|null $exclude_id ID jenis tiket yang dikecualikan (untuk operasi update).
     * @return bool True jika duplikat, false jika tidak.
     */
    private static function isDuplicateEntry($nama_layanan, $tipe_hari, $wisata_id = null, $exclude_id = null)
    {
        if (!self::checkDbConnection()) return true;
        $sql = "SELECT id FROM " . self::$table_name . " WHERE nama_layanan_display = ? AND tipe_hari = ? AND ";
        $params = [$nama_layanan, $tipe_hari];
        $types = "ss";
        if ($wisata_id === null) {
            $sql .= "wisata_id IS NULL";
        } else {
            $sql .= "wisata_id = ?";
            $params[] = $wisata_id;
            $types .= "i";
        }
        if ($exclude_id !== null && (int)$exclude_id > 0) {
            $sql .= " AND id != ?";
            $params[] = (int)$exclude_id;
            $types .= "i";
        }
        $sql .= " LIMIT 1";
        $stmt = mysqli_prepare(self::$db, $sql);
        if (!$stmt) {
            error_log(get_called_class() . "::isDuplicateEntry() Prepare Error: " . mysqli_error(self::$db));
            return true;
        }
        if (!empty($params)) {
            mysqli_stmt_bind_param($stmt, $types, ...$params);
        }
        if (!mysqli_stmt_execute($stmt)) {
            error_log(get_called_class() . "::isDuplicateEntry() Execute Error: " . mysqli_stmt_error($stmt));
            mysqli_stmt_close($stmt);
            return true;
        }
        $result = mysqli_stmt_get_result($stmt);
        $exists = mysqli_num_rows($result) > 0;
        mysqli_stmt_close($stmt);
        return $exists;
    }

    /**
     * Membuat jenis tiket baru.
     * @param array $data Data jenis tiket.
     * @return int|string|false ID record baru, string 'duplicate', atau false jika gagal.
     */
    public static function create($data)
    {
        if (!self::checkDbConnection()) return false;
        $nama_layanan = trim($data['nama_layanan_display'] ?? '');
        $tipe_hari = trim($data['tipe_hari'] ?? '');
        $harga = isset($data['harga']) ? (float)$data['harga'] : 0.0;
        $deskripsi = isset($data['deskripsi']) ? trim($data['deskripsi']) : null;
        $aktif = isset($data['aktif']) ? (int)$data['aktif'] : 1;
        $wisata_id = isset($data['wisata_id']) && !empty($data['wisata_id']) && is_numeric($data['wisata_id']) ? (int)$data['wisata_id'] : null;

        if (empty($nama_layanan)) {
            error_log(get_called_class() . "::create() Nama layanan kosong.");
            return false;
        }
        if (empty($tipe_hari) || !in_array($tipe_hari, self::ALLOWED_TIPE_HARI)) {
            error_log(get_called_class() . "::create() Tipe hari tidak valid: " . $tipe_hari);
            return false;
        }
        if ($harga < 0) {
            error_log(get_called_class() . "::create() Harga tidak boleh negatif.");
            return false;
        }
        if (!in_array($aktif, [0, 1])) {
            $aktif = 1;
        }

        if (self::isDuplicateEntry($nama_layanan, $tipe_hari, $wisata_id)) {
            error_log(get_called_class() . "::create() - Error: Duplikasi jenis tiket terdeteksi.");
            return 'duplicate';
        }

        // Asumsi created_at dan updated_at (jika ada) dihandle oleh DB (DEFAULT CURRENT_TIMESTAMP / ON UPDATE CURRENT_TIMESTAMP)
        $sql = "INSERT INTO " . self::$table_name .
            " (nama_layanan_display, tipe_hari, harga, deskripsi, aktif, wisata_id) 
               VALUES (?, ?, ?, ?, ?, ?)"; // Kolom timestamp tidak di-list
        $stmt = mysqli_prepare(self::$db, $sql);
        if (!$stmt) {
            error_log(get_called_class() . "::create() Prepare Error: " . mysqli_error(self::$db));
            return false;
        }

        mysqli_stmt_bind_param($stmt, "ssdsii", $nama_layanan, $tipe_hari, $harga, $deskripsi, $aktif, $wisata_id);
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

    /**
     * Mengambil semua jenis tiket dengan nama wisata terkait.
     * @return array Daftar jenis tiket.
     */
    public static function getAll()
    {
        if (!self::checkDbConnection()) return [];

        // Pastikan nama kolom di tabel wisata adalah 'nama'. Jika 'nama_wisata', ubah w.nama menjadi w.nama_wisata
        $sql = "SELECT jt.*, w.nama AS nama_wisata_terkait 
                FROM " . self::$table_name . " jt
                LEFT JOIN wisata w ON jt.wisata_id = w.id
                ORDER BY jt.nama_layanan_display ASC, jt.tipe_hari ASC, jt.id ASC";
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

    /**
     * Mencari jenis tiket berdasarkan ID.
     * @param int $id ID Jenis Tiket.
     * @return array|null Data jenis tiket atau null.
     */
    public static function findById($id)
    {
        if (!self::checkDbConnection()) return null;
        $id_val = filter_var($id, FILTER_VALIDATE_INT);
        if ($id_val === false || $id_val <= 0) {
            error_log(get_called_class() . "::findById() ID tidak valid: " . $id);
            return null;
        }

        $sql = "SELECT jt.*, w.nama AS nama_wisata_terkait 
                FROM " . self::$table_name . " jt
                LEFT JOIN wisata w ON jt.wisata_id = w.id
                WHERE jt.id = ?";
        $stmt = mysqli_prepare(self::$db, $sql);
        if (!$stmt) {
            error_log(get_called_class() . "::findById() Prepare Error: " . mysqli_error(self::$db));
            return null;
        }
        mysqli_stmt_bind_param($stmt, "i", $id_val);
        if (mysqli_stmt_execute($stmt)) {
            $result = mysqli_stmt_get_result($stmt);
            $jenis_tiket = mysqli_fetch_assoc($result);
            mysqli_stmt_close($stmt);
            return $jenis_tiket ?: null;
        } else {
            error_log(get_called_class() . "::findById() Execute Error: " . mysqli_stmt_error($stmt));
        }
        mysqli_stmt_close($stmt);
        return null;
    }

    /**
     * Mengupdate data jenis tiket.
     * @param array $data Data jenis tiket, harus ada 'id'.
     * @return bool|string True jika berhasil, string 'duplicate', atau false jika gagal.
     */
    public static function update($data)
    {
        if (!self::checkDbConnection() || !isset($data['id'])) {
            error_log(get_called_class() . "::update() Koneksi/ID Error");
            return false;
        }
        $id = (int)$data['id'];
        if ($id <= 0) {
            error_log(get_called_class() . "::update() ID tidak valid: " . $data['id']);
            return false;
        }

        $nama_layanan = trim($data['nama_layanan_display'] ?? '');
        $tipe_hari = trim($data['tipe_hari'] ?? '');
        $harga = isset($data['harga']) ? (float)$data['harga'] : 0.0;
        $deskripsi = isset($data['deskripsi']) ? trim($data['deskripsi']) : null;
        $aktif = isset($data['aktif']) ? (int)$data['aktif'] : 0;
        $wisata_id = isset($data['wisata_id']) && !empty($data['wisata_id']) && is_numeric($data['wisata_id']) ? (int)$data['wisata_id'] : null;

        if (empty($nama_layanan)) {
            error_log(get_called_class() . "::update() Nama layanan kosong ID {$id}.");
            return false;
        }
        if (empty($tipe_hari) || !in_array($tipe_hari, self::ALLOWED_TIPE_HARI)) {
            error_log(get_called_class() . "::update() Tipe hari tidak valid ID {$id}.");
            return false;
        }
        if ($harga < 0) {
            error_log(get_called_class() . "::update() Harga negatif ID {$id}.");
            return false;
        }
        if (!in_array($aktif, [0, 1])) {
            $aktif = 0;
        }

        if (self::isDuplicateEntry($nama_layanan, $tipe_hari, $wisata_id, $id)) {
            error_log(get_called_class() . "::update() - Error: Duplikasi jenis tiket saat update ID {$id}.");
            return 'duplicate';
        }

        // Asumsi tabel memiliki kolom updated_at dengan ON UPDATE CURRENT_TIMESTAMP, jadi tidak perlu set manual.
        // Jika tidak, tambahkan `updated_at = NOW()` ke SET clause.
        $sql = "UPDATE " . self::$table_name . " SET
                nama_layanan_display = ?, tipe_hari = ?, harga = ?, 
                deskripsi = ?, aktif = ?, wisata_id = ? 
                WHERE id = ?"; // updated_at dihapus dari SET
        $stmt = mysqli_prepare(self::$db, $sql);
        if (!$stmt) {
            error_log(get_called_class() . "::update() Prepare Error: " . mysqli_error(self::$db));
            return false;
        }
        // Tipe: s, s, d, s, i, i, i (untuk id di WHERE)
        mysqli_stmt_bind_param($stmt, "ssdsiii", $nama_layanan, $tipe_hari, $harga, $deskripsi, $aktif, $wisata_id, $id);
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

    /**
     * Menghapus jenis tiket.
     * Mencegah penghapusan jika masih terkait dengan detail pemesanan.
     * @param int $id ID Jenis Tiket.
     * @return bool True jika berhasil.
     */
    public static function delete($id)
    {
        if (!self::checkDbConnection()) return false;
        $id_val = filter_var($id, FILTER_VALIDATE_INT);
        if ($id_val === false || $id_val <= 0) {
            error_log(get_called_class() . "::delete() - ID tidak valid: " . $id);
            return false;
        }

        // Cek keterkaitan dengan detail_pemesanan_tiket
        $sql_check_detail = "SELECT COUNT(*) as total FROM detail_pemesanan_tiket WHERE jenis_tiket_id = ?";
        $stmt_check_detail = mysqli_prepare(self::$db, $sql_check_detail);
        if ($stmt_check_detail) {
            mysqli_stmt_bind_param($stmt_check_detail, "i", $id_val);
            mysqli_stmt_execute($stmt_check_detail);
            $result_check_detail = mysqli_stmt_get_result($stmt_check_detail);
            $row_check_detail = mysqli_fetch_assoc($result_check_detail);
            mysqli_stmt_close($stmt_check_detail);
            if ($row_check_detail && $row_check_detail['total'] > 0) {
                error_log(get_called_class() . "::delete() - Gagal: Jenis tiket ID {$id_val} masih digunakan dalam " . $row_check_detail['total'] . " detail pemesanan.");
                if (function_exists('set_flash_message')) set_flash_message('danger', 'Jenis tiket tidak dapat dihapus karena masih digunakan dalam data pemesanan.');
                return false;
            }
        } else {
            error_log(get_called_class() . "::delete() - Gagal prepare statement untuk cek detail pemesanan.");
            return false; // Anggap tidak aman untuk delete
        }

        // Cek keterkaitan dengan jadwal_ketersediaan_tiket
        $sql_check_jadwal = "SELECT COUNT(*) as total FROM jadwal_ketersediaan_tiket WHERE jenis_tiket_id = ?";
        $stmt_check_jadwal = mysqli_prepare(self::$db, $sql_check_jadwal);
        if ($stmt_check_jadwal) {
            mysqli_stmt_bind_param($stmt_check_jadwal, "i", $id_val);
            mysqli_stmt_execute($stmt_check_jadwal);
            $result_check_jadwal = mysqli_stmt_get_result($stmt_check_jadwal);
            $row_check_jadwal = mysqli_fetch_assoc($result_check_jadwal);
            mysqli_stmt_close($stmt_check_jadwal);
            if ($row_check_jadwal && $row_check_jadwal['total'] > 0) {
                error_log(get_called_class() . "::delete() - Gagal: Jenis tiket ID {$id_val} masih digunakan dalam " . $row_check_jadwal['total'] . " jadwal ketersediaan.");
                if (function_exists('set_flash_message')) set_flash_message('danger', 'Jenis tiket tidak dapat dihapus karena masih memiliki data jadwal ketersediaan terkait.');
                return false;
            }
        } else {
            error_log(get_called_class() . "::delete() - Gagal prepare statement untuk cek jadwal ketersediaan.");
            return false;
        }


        $sql = "DELETE FROM " . self::$table_name . " WHERE id = ?";
        $stmt = mysqli_prepare(self::$db, $sql);
        if (!$stmt) {
            error_log(get_called_class() . "::delete() - MySQLi Prepare Error: " . mysqli_error(self::$db));
            return false;
        }
        mysqli_stmt_bind_param($stmt, "i", $id_val);
        if (mysqli_stmt_execute($stmt)) {
            $affected_rows = mysqli_stmt_affected_rows($stmt);
            mysqli_stmt_close($stmt);
            return $affected_rows > 0;
        } else {
            error_log(get_called_class() . "::delete() - MySQLi Execute Error: " . mysqli_stmt_error($stmt));
            mysqli_stmt_close($stmt);
            return false;
        }
    }

    /**
     * Mengambil jenis tiket yang aktif berdasarkan nama layanan dan tipe hari.
     * @param string $nama_layanan
     * @param string $tipe_hari
     * @return array|null Data jenis tiket atau null.
     */
    public static function getActiveByLayananAndTipeHari($nama_layanan, $tipe_hari)
    {
        if (!self::checkDbConnection()) return null;
        if (empty($nama_layanan) || empty($tipe_hari)) {
            error_log(get_called_class() . "::getActiveByLayananAndTipeHari() - Nama layanan atau tipe hari kosong.");
            return null;
        }
        $sql = "SELECT * FROM " . self::$table_name . " WHERE nama_layanan_display = ? AND tipe_hari = ? AND aktif = 1 LIMIT 1";
        $stmt = mysqli_prepare(self::$db, $sql);
        if (!$stmt) {
            error_log(get_called_class() . "::getActiveByLayananAndTipeHari() - Prepare Error: " . mysqli_error(self::$db));
            return null;
        }
        mysqli_stmt_bind_param($stmt, "ss", $nama_layanan, $tipe_hari);
        if (mysqli_stmt_execute($stmt)) {
            $result = mysqli_stmt_get_result($stmt);
            $jenis_tiket = mysqli_fetch_assoc($result);
            mysqli_stmt_close($stmt);
            return $jenis_tiket ?: null;
        } else {
            error_log(get_called_class() . "::getActiveByLayananAndTipeHari() - Execute Error: " . mysqli_stmt_error($stmt));
        }
        mysqli_stmt_close($stmt);
        return null;
    }

    /**
     * Menghitung semua jenis tiket.
     * @return int Total jenis tiket.
     */
    public static function countAll()
    {
        if (!self::checkDbConnection()) return 0;
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
} // End of class JenisTiket