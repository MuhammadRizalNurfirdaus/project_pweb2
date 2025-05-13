<?php
// File: C:\xampp\htdocs\Cilengkrang-Web-Wisata\models\JadwalKetersediaanTiket.php

class JadwalKetersediaanTiket
{
    private static $table_name = "jadwal_ketersediaan_tiket";
    private static $db; // Properti untuk menyimpan koneksi database

    /**
     * Mengatur koneksi database untuk digunakan oleh kelas ini.
     * @param mysqli $connection Instance koneksi mysqli.
     */
    public static function setDbConnection(mysqli $connection)
    {
        self::$db = $connection;
        // error_log(get_called_class() . "::setDbConnection dipanggil."); // Untuk debugging
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
    public static function getLastError() // <<< METODE INI DITAMBAHKAN
    {
        if (self::$db instanceof mysqli && !empty(self::$db->error)) {
            return self::$db->error;
        } elseif (!self::$db || !(self::$db instanceof mysqli)) {
            return 'Koneksi database belum diinisialisasi atau tidak valid untuk kelas ' . get_called_class() . '.';
        }
        return 'Tidak ada error database spesifik yang dilaporkan dari model ' . get_called_class() . '.';
    }

    /**
     * Mengecek apakah sudah ada jadwal untuk jenis tiket dan tanggal tertentu.
     * @param int $jenis_tiket_id
     * @param string $tanggal Format YYYY-MM-DD
     * @param int|null $exclude_id ID jadwal yang dikecualikan (untuk operasi update)
     * @return bool True jika sudah ada (duplikat), false jika tidak.
     */
    private static function isDuplicateEntry($jenis_tiket_id, $tanggal, $exclude_id = null)
    {
        if (!self::checkDbConnection()) return true;

        $jenis_tiket_id_val = (int)$jenis_tiket_id;
        $tanggal_val = trim($tanggal);

        $sql = "SELECT id FROM " . self::$table_name . " WHERE jenis_tiket_id = ? AND tanggal = ?";
        $params = [$jenis_tiket_id_val, $tanggal_val];
        $types = "is";

        if ($exclude_id !== null && (int)$exclude_id > 0) {
            $sql .= " AND id != ?";
            $params[] = (int)$exclude_id;
            $types .= "i";
        }
        $sql .= " LIMIT 1";

        $stmt = mysqli_prepare(self::$db, $sql);
        if (!$stmt) {
            error_log(get_called_class() . "::isDuplicateEntry() - Prepare Error: " . mysqli_error(self::$db));
            return true;
        }
        mysqli_stmt_bind_param($stmt, $types, ...$params);
        if (!mysqli_stmt_execute($stmt)) {
            error_log(get_called_class() . "::isDuplicateEntry() - Execute Error: " . mysqli_stmt_error($stmt));
            mysqli_stmt_close($stmt);
            return true;
        }
        $result = mysqli_stmt_get_result($stmt);
        $exists = mysqli_num_rows($result) > 0;
        mysqli_stmt_close($stmt);
        return $exists;
    }

    /**
     * Membuat jadwal ketersediaan tiket baru.
     * @param array $data Data jadwal.
     * @return int|string|false ID jadwal baru, string 'duplicate' jika duplikat, atau false jika gagal.
     */
    public static function create($data)
    {
        if (!self::checkDbConnection()) return false;

        $jenis_tiket_id = isset($data['jenis_tiket_id']) ? (int)$data['jenis_tiket_id'] : 0;
        $tanggal = trim($data['tanggal'] ?? '');
        $jumlah_total_tersedia = isset($data['jumlah_total_tersedia']) ? (int)$data['jumlah_total_tersedia'] : 0;
        $jumlah_saat_ini_tersedia = isset($data['jumlah_saat_ini_tersedia']) ?
            (int)$data['jumlah_saat_ini_tersedia'] :
            $jumlah_total_tersedia;
        $aktif = isset($data['aktif']) ? (int)$data['aktif'] : 1;

        if ($jenis_tiket_id <= 0) {
            error_log(get_called_class() . "::create() - jenis_tiket_id tidak valid.");
            return false;
        }
        if (empty($tanggal) || !DateTime::createFromFormat('Y-m-d', $tanggal)) {
            error_log(get_called_class() . "::create() - tanggal tidak valid: " . $tanggal);
            return false;
        }
        if ($jumlah_total_tersedia < 0) {
            error_log(get_called_class() . "::create() - jumlah_total_tersedia tidak boleh negatif.");
            return false;
        }
        if ($jumlah_saat_ini_tersedia < 0) {
            error_log(get_called_class() . "::create() - jumlah_saat_ini_tersedia tidak boleh negatif.");
            return false;
        }
        if ($jumlah_saat_ini_tersedia > $jumlah_total_tersedia) {
            error_log(get_called_class() . "::create() - jumlah_saat_ini_tersedia tidak boleh lebih besar dari jumlah_total_tersedia. Dikoreksi.");
            $jumlah_saat_ini_tersedia = $jumlah_total_tersedia;
        }
        if (!in_array($aktif, [0, 1])) {
            $aktif = 1;
        }

        if (self::isDuplicateEntry($jenis_tiket_id, $tanggal)) {
            error_log(get_called_class() . "::create() - Error: Duplikasi jadwal untuk jenis tiket ID {$jenis_tiket_id} dan tanggal {$tanggal}.");
            return 'duplicate';
        }

        // created_at dan updated_at dihandle oleh DB atau NOW()
        $sql = "INSERT INTO " . self::$table_name .
            " (jenis_tiket_id, tanggal, jumlah_total_tersedia, jumlah_saat_ini_tersedia, aktif, created_at, updated_at) 
               VALUES (?, ?, ?, ?, ?, NOW(), NOW())";
        $stmt = mysqli_prepare(self::$db, $sql);

        if (!$stmt) {
            error_log(get_called_class() . "::create() - MySQLi Prepare Error: " . mysqli_error(self::$db));
            return false;
        }

        mysqli_stmt_bind_param(
            $stmt,
            "isiii",
            $jenis_tiket_id,
            $tanggal,
            $jumlah_total_tersedia,
            $jumlah_saat_ini_tersedia,
            $aktif
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
     * Mengambil semua jadwal ketersediaan dengan info jenis tiket.
     * @return array Daftar jadwal.
     */
    public static function getAll()
    {
        if (!self::checkDbConnection()) return [];

        $sql = "SELECT jkt.*, 
                       jt.nama_layanan_display, jt.tipe_hari, jt.harga AS harga_jenis_tiket
                FROM " . self::$table_name . " jkt
                LEFT JOIN jenis_tiket jt ON jkt.jenis_tiket_id = jt.id
                ORDER BY jkt.tanggal DESC, jt.nama_layanan_display ASC, jt.tipe_hari ASC";
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
     * Mencari jadwal berdasarkan ID.
     * @param int $id ID Jadwal.
     * @return array|null Data jadwal atau null.
     */
    public static function findById($id)
    {
        if (!self::checkDbConnection()) return null;
        $id_val = filter_var($id, FILTER_VALIDATE_INT);
        if ($id_val === false || $id_val <= 0) {
            error_log(get_called_class() . "::findById() - ID tidak valid: " . $id);
            return null;
        }

        $sql = "SELECT jkt.*, jt.nama_layanan_display, jt.tipe_hari, jt.harga AS harga_jenis_tiket
                FROM " . self::$table_name . " jkt
                LEFT JOIN jenis_tiket jt ON jkt.jenis_tiket_id = jt.id
                WHERE jkt.id = ?";
        $stmt = mysqli_prepare(self::$db, $sql);
        if (!$stmt) {
            error_log(get_called_class() . "::findById() - Prepare Error: " . mysqli_error(self::$db));
            return null;
        }
        mysqli_stmt_bind_param($stmt, "i", $id_val);
        if (mysqli_stmt_execute($stmt)) {
            $result = mysqli_stmt_get_result($stmt);
            $jadwal = mysqli_fetch_assoc($result);
            mysqli_stmt_close($stmt);
            return $jadwal ?: null;
        } else {
            error_log(get_called_class() . "::findById() - Execute Error: " . mysqli_stmt_error($stmt));
        }
        mysqli_stmt_close($stmt);
        return null;
    }

    /**
     * Mengupdate data jadwal ketersediaan.
     * @param array $data Data jadwal, harus ada 'id'.
     * @return bool|string True jika berhasil, string 'duplicate', atau false jika gagal.
     */
    public static function update($data)
    {
        if (!self::checkDbConnection() || !isset($data['id'])) {
            error_log(get_called_class() . "::update() - Koneksi DB gagal atau ID tidak disediakan.");
            return false;
        }
        $id = (int)$data['id'];
        if ($id <= 0) {
            error_log(get_called_class() . "::update() - ID tidak valid: " . $data['id']);
            return false;
        }

        $jenis_tiket_id = isset($data['jenis_tiket_id']) ? (int)$data['jenis_tiket_id'] : 0;
        $tanggal = trim($data['tanggal'] ?? '');
        $jumlah_total_tersedia = isset($data['jumlah_total_tersedia']) ? (int)$data['jumlah_total_tersedia'] : 0;
        $jumlah_saat_ini_tersedia = isset($data['jumlah_saat_ini_tersedia']) ? (int)$data['jumlah_saat_ini_tersedia'] : 0;
        $aktif = isset($data['aktif']) ? (int)$data['aktif'] : 0;

        if ($jenis_tiket_id <= 0) {
            error_log(get_called_class() . "::update() - jenis_tiket_id tidak valid.");
            return false;
        }
        if (empty($tanggal) || !DateTime::createFromFormat('Y-m-d', $tanggal)) {
            error_log(get_called_class() . "::update() - tanggal tidak valid: " . $tanggal);
            return false;
        }
        if ($jumlah_total_tersedia < 0) {
            error_log(get_called_class() . "::update() - jumlah_total_tersedia tidak boleh negatif.");
            return false;
        }
        if ($jumlah_saat_ini_tersedia < 0) {
            error_log(get_called_class() . "::update() - jumlah_saat_ini_tersedia tidak boleh negatif.");
            return false;
        }
        if ($jumlah_saat_ini_tersedia > $jumlah_total_tersedia) {
            error_log(get_called_class() . "::update() - jumlah_saat_ini_tersedia tidak boleh > jumlah_total_tersedia. Dikoreksi.");
            $jumlah_saat_ini_tersedia = $jumlah_total_tersedia;
        }
        if (!in_array($aktif, [0, 1])) {
            $aktif = 0;
        }

        if (self::isDuplicateEntry($jenis_tiket_id, $tanggal, $id)) {
            error_log(get_called_class() . "::update() - Error: Duplikasi jadwal untuk jenis tiket ID {$jenis_tiket_id}, tanggal {$tanggal} (kecuali ID {$id}).");
            return 'duplicate';
        }

        $sql = "UPDATE " . self::$table_name . " SET
                jenis_tiket_id = ?, tanggal = ?, jumlah_total_tersedia = ?, 
                jumlah_saat_ini_tersedia = ?, aktif = ?, updated_at = NOW()
                WHERE id = ?";
        $stmt = mysqli_prepare(self::$db, $sql);
        if (!$stmt) {
            error_log(get_called_class() . "::update() - Prepare Error: " . mysqli_error(self::$db));
            return false;
        }
        mysqli_stmt_bind_param($stmt, "isiiii", $jenis_tiket_id, $tanggal, $jumlah_total_tersedia, $jumlah_saat_ini_tersedia, $aktif, $id);
        if (mysqli_stmt_execute($stmt)) {
            $affected_rows = mysqli_stmt_affected_rows($stmt);
            mysqli_stmt_close($stmt);
            return $affected_rows >= 0;
        } else {
            error_log(get_called_class() . "::update() - Execute Error: " . mysqli_stmt_error($stmt));
            mysqli_stmt_close($stmt);
            return false;
        }
    }

    /**
     * Mengambil ketersediaan aktif untuk jenis tiket dan tanggal tertentu.
     * @param int $jenis_tiket_id
     * @param string $tanggal (Y-m-d)
     * @return array|null Data ketersediaan atau null.
     */
    public static function getActiveKetersediaan($jenis_tiket_id, $tanggal)
    {
        if (!self::checkDbConnection()) return null;
        $id_val = (int)$jenis_tiket_id;
        $tanggal_val = trim($tanggal);
        if ($id_val <= 0 || empty($tanggal_val) || !DateTime::createFromFormat('Y-m-d', $tanggal_val)) {
            error_log(get_called_class() . "::getActiveKetersediaan - Input tidak valid. JTID: {$id_val}, Tgl: {$tanggal_val}");
            return null;
        }

        $sql = "SELECT * FROM " . self::$table_name . " WHERE jenis_tiket_id = ? AND tanggal = ? AND aktif = 1 LIMIT 1";
        $stmt = mysqli_prepare(self::$db, $sql);
        if (!$stmt) {
            error_log(get_called_class() . "::getActiveKetersediaan - Prepare Gagal: " . mysqli_error(self::$db));
            return null;
        }
        mysqli_stmt_bind_param($stmt, "is", $id_val, $tanggal_val);
        if (mysqli_stmt_execute($stmt)) {
            $result = mysqli_stmt_get_result($stmt);
            $data = mysqli_fetch_assoc($result);
            mysqli_stmt_close($stmt);
            return $data ?: null;
        } else {
            error_log(get_called_class() . "::getActiveKetersediaan - Execute Gagal: " . mysqli_stmt_error($stmt));
        }
        mysqli_stmt_close($stmt);
        return null;
    }

    /**
     * Mengupdate jumlah tiket yang saat ini tersedia untuk jadwal tertentu.
     * @param int $jadwal_id ID jadwal.
     * @param int $jumlah_perubahan Bisa positif (menambah) atau negatif (mengurangi).
     * @return bool True jika berhasil.
     */
    public static function updateJumlahSaatIniTersedia($jadwal_id, $jumlah_perubahan)
    {
        if (!self::checkDbConnection()) return false;
        $id_val = (int)$jadwal_id;
        $perubahan_val = (int)$jumlah_perubahan;
        if ($id_val <= 0) {
            error_log(get_called_class() . "::updateJumlahSaatIniTersedia - ID Jadwal tidak valid: " . $jadwal_id);
            return false;
        }
        if ($perubahan_val == 0) return true;

        $current_jadwal = self::findById($id_val);
        if (!$current_jadwal) {
            error_log(get_called_class() . "::updateJumlahSaatIniTersedia - Jadwal ID {$id_val} tidak ditemukan.");
            return false;
        }
        $jumlah_total_dari_db = (int)$current_jadwal['jumlah_total_tersedia'];

        $sql = "UPDATE " . self::$table_name . " 
                SET jumlah_saat_ini_tersedia = LEAST(?, GREATEST(0, jumlah_saat_ini_tersedia + ?))
                WHERE id = ?";
        $stmt = mysqli_prepare(self::$db, $sql);
        if (!$stmt) {
            error_log(get_called_class() . "::updateJumlahSaatIniTersedia - Prepare Gagal: " . mysqli_error(self::$db));
            return false;
        }
        mysqli_stmt_bind_param($stmt, "iii", $jumlah_total_dari_db, $perubahan_val, $id_val);

        if (mysqli_stmt_execute($stmt)) {
            mysqli_stmt_close($stmt);
            return true;
        } else {
            error_log(get_called_class() . "::updateJumlahSaatIniTersedia - Execute Gagal: " . mysqli_stmt_error($stmt));
            mysqli_stmt_close($stmt);
            return false;
        }
    }

    /**
     * Menghapus jadwal ketersediaan.
     * @param int $id ID jadwal.
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

        // Pertimbangkan cek FK ke detail_pemesanan_tiket jika jadwal_ketersediaan_id disimpan di sana
        // Jika ada, cegah penghapusan jika masih digunakan.

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
     * Menghitung semua jadwal ketersediaan.
     * @return int Total jadwal.
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
} // End of class JadwalKetersediaanTiket