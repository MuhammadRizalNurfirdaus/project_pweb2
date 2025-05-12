<?php
// File: C:\xampp\htdocs\Cilengkrang-Web-Wisata\models\JadwalKetersediaanTiket.php

class JadwalKetersediaanTiket
{
    private static $table_name = "jadwal_ketersediaan_tiket";

    /**
     * Mengecek apakah sudah ada jadwal untuk jenis tiket dan tanggal tertentu.
     * @param int $jenis_tiket_id
     * @param string $tanggal Format YYYY-MM-DD
     * @param int|null $exclude_id ID jadwal yang dikecualikan (untuk operasi update)
     * @return bool True jika sudah ada (duplikat), false jika tidak.
     */
    private static function isDuplicateEntry($jenis_tiket_id, $tanggal, $exclude_id = null)
    {
        global $conn;
        if (!$conn) return true; // Anggap duplikat jika koneksi gagal untuk mencegah error lebih lanjut

        $sql = "SELECT id FROM " . self::$table_name . " WHERE jenis_tiket_id = ? AND tanggal = ?";
        $params = [$jenis_tiket_id, $tanggal];
        $types = "is";

        if ($exclude_id !== null && (int)$exclude_id > 0) {
            $sql .= " AND id != ?";
            $params[] = (int)$exclude_id;
            $types .= "i";
        }
        $sql .= " LIMIT 1";

        $stmt = mysqli_prepare($conn, $sql);
        if (!$stmt) {
            error_log("JadwalKetersediaanTiket::isDuplicateEntry() - Prepare Error: " . mysqli_error($conn));
            return true; // Anggap duplikat untuk keamanan
        }
        mysqli_stmt_bind_param($stmt, $types, ...$params);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $exists = mysqli_num_rows($result) > 0;
        mysqli_stmt_close($stmt);
        return $exists;
    }

    public static function create($data)
    {
        global $conn;
        if (!$conn) {
            error_log("JadwalKetersediaanTiket::create() - Koneksi database gagal.");
            return false;
        }

        $jenis_tiket_id = isset($data['jenis_tiket_id']) ? (int)$data['jenis_tiket_id'] : 0;
        $tanggal = trim($data['tanggal'] ?? '');
        $jumlah_total_tersedia = isset($data['jumlah_total_tersedia']) ? (int)$data['jumlah_total_tersedia'] : 0;
        $jumlah_saat_ini_tersedia = isset($data['jumlah_saat_ini_tersedia']) ? (int)$data['jumlah_saat_ini_tersedia'] : $jumlah_total_tersedia;
        $aktif = isset($data['aktif']) ? (int)$data['aktif'] : 1;

        if ($jenis_tiket_id <= 0 || empty($tanggal) || !DateTime::createFromFormat('Y-m-d', $tanggal) || $jumlah_total_tersedia < 0 || $jumlah_saat_ini_tersedia < 0 || $jumlah_saat_ini_tersedia > $jumlah_total_tersedia) {
            error_log("JadwalKetersediaanTiket::create() - Error: Data input tidak valid.");
            // Pesan flash yang lebih spesifik sebaiknya di-set di Controller
            return false;
        }

        // Validasi duplikasi
        if (self::isDuplicateEntry($jenis_tiket_id, $tanggal)) {
            error_log("JadwalKetersediaanTiket::create() - Error: Duplikasi jadwal untuk jenis tiket ID {$jenis_tiket_id} dan tanggal {$tanggal}.");
            // Pesan flash akan di-set oleh Controller
            return false;
        }

        $sql = "INSERT INTO " . self::$table_name .
            " (jenis_tiket_id, tanggal, jumlah_total_tersedia, jumlah_saat_ini_tersedia, aktif) 
               VALUES (?, ?, ?, ?, ?)";
        $stmt = mysqli_prepare($conn, $sql);

        if (!$stmt) {
            error_log("JadwalKetersediaanTiket::create() - MySQLi Prepare Error: " . mysqli_error($conn));
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
            $new_id = mysqli_insert_id($conn);
            mysqli_stmt_close($stmt);
            return $new_id;
        } else {
            error_log("JadwalKetersediaanTiket::create() - MySQLi Execute Error: " . mysqli_stmt_error($stmt));
            mysqli_stmt_close($stmt);
            return false;
        }
    }

    public static function getAll()
    {
        global $conn;
        if (!$conn) {
            error_log("JadwalKetersediaanTiket::getAll() - Koneksi DB gagal.");
            return [];
        }
        $sql = "SELECT jkt.*, jt.nama_layanan_display, jt.tipe_hari, jt.harga AS harga_jenis_tiket
                FROM " . self::$table_name . " jkt
                LEFT JOIN jenis_tiket jt ON jkt.jenis_tiket_id = jt.id
                ORDER BY jkt.tanggal DESC, jt.nama_layanan_display ASC, jt.tipe_hari ASC";
        $result = mysqli_query($conn, $sql);
        if ($result) {
            return mysqli_fetch_all($result, MYSQLI_ASSOC);
        } else {
            error_log("JadwalKetersediaanTiket::getAll() - MySQLi Query Error: " . mysqli_error($conn));
            return [];
        }
    }

    public static function getById($id)
    {
        global $conn;
        if (!$conn) {
            error_log("JadwalKetersediaanTiket::getById() - Koneksi DB gagal.");
            return null;
        }
        $id_val = filter_var($id, FILTER_VALIDATE_INT);
        if ($id_val === false || $id_val <= 0) {
            error_log("JadwalKetersediaanTiket::getById() - ID tidak valid: " . e($id));
            return null;
        }

        $sql = "SELECT jkt.*, jt.nama_layanan_display, jt.tipe_hari, jt.harga AS harga_jenis_tiket
                FROM " . self::$table_name . " jkt
                LEFT JOIN jenis_tiket jt ON jkt.jenis_tiket_id = jt.id
                WHERE jkt.id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        if (!$stmt) {
            error_log("JadwalKetersediaanTiket::getById() - Prepare Error: " . mysqli_error($conn));
            return null;
        }
        mysqli_stmt_bind_param($stmt, "i", $id_val);
        if (mysqli_stmt_execute($stmt)) {
            $result = mysqli_stmt_get_result($stmt);
            $jadwal = mysqli_fetch_assoc($result);
            mysqli_stmt_close($stmt);
            return $jadwal ?: null;
        } else {
            error_log("JadwalKetersediaanTiket::getById() - Execute Error: " . mysqli_stmt_error($stmt));
            mysqli_stmt_close($stmt);
            return null;
        }
    }

    public static function update($data)
    {
        global $conn;
        if (!$conn || !isset($data['id'])) {
            error_log("JadwalKetersediaanTiket::update() - Koneksi DB/ID tidak ada.");
            return false;
        }

        $id = (int)$data['id'];
        $jenis_tiket_id = isset($data['jenis_tiket_id']) ? (int)$data['jenis_tiket_id'] : 0;
        $tanggal = trim($data['tanggal'] ?? '');
        $jumlah_total_tersedia = isset($data['jumlah_total_tersedia']) ? (int)$data['jumlah_total_tersedia'] : 0;
        $jumlah_saat_ini_tersedia = isset($data['jumlah_saat_ini_tersedia']) ? (int)$data['jumlah_saat_ini_tersedia'] : 0;
        $aktif = isset($data['aktif']) ? (int)$data['aktif'] : 0;

        if ($id <= 0 || $jenis_tiket_id <= 0 || empty($tanggal) || !DateTime::createFromFormat('Y-m-d', $tanggal) || $jumlah_total_tersedia < 0 || $jumlah_saat_ini_tersedia < 0 || $jumlah_saat_ini_tersedia > $jumlah_total_tersedia) {
            error_log("JadwalKetersediaanTiket::update() - Data input tidak valid.");
            return false;
        }

        // Validasi duplikasi, kecuali untuk ID yang sedang diedit
        if (self::isDuplicateEntry($jenis_tiket_id, $tanggal, $id)) {
            error_log("JadwalKetersediaanTiket::update() - Error: Duplikasi jadwal untuk jenis tiket ID {$jenis_tiket_id} dan tanggal {$tanggal} (saat update ID {$id}).");
            // Pesan flash akan di-set oleh Controller
            return false;
        }

        $sql = "UPDATE " . self::$table_name . " SET
                jenis_tiket_id = ?, tanggal = ?, jumlah_total_tersedia = ?, 
                jumlah_saat_ini_tersedia = ?, aktif = ?
                WHERE id = ?";
        $stmt = mysqli_prepare($conn, $sql);

        if (!$stmt) {
            error_log("JadwalKetersediaanTiket::update() - Prepare Error: " . mysqli_error($conn));
            return false;
        }
        mysqli_stmt_bind_param(
            $stmt,
            "isiiii",
            $jenis_tiket_id,
            $tanggal,
            $jumlah_total_tersedia,
            $jumlah_saat_ini_tersedia,
            $aktif,
            $id
        );

        if (mysqli_stmt_execute($stmt)) {
            mysqli_stmt_close($stmt);
            return true;
        } else {
            error_log("JadwalKetersediaanTiket::update() - Execute Error: " . mysqli_stmt_error($stmt));
            mysqli_stmt_close($stmt);
            return false;
        }
    }

    public static function getActiveKetersediaan($jenis_tiket_id, $tanggal)
    {
        global $conn;
        if (!$conn) {
            return null;
        }
        $id_val = (int)$jenis_tiket_id;
        if ($id_val <= 0 || empty($tanggal) || !DateTime::createFromFormat('Y-m-d', $tanggal)) {
            error_log("JadwalKetersediaanTiket::getActiveKetersediaan - Input tidak valid. Jenis Tiket ID: {$id_val}, Tanggal: {$tanggal}");
            return null;
        }

        $sql = "SELECT * FROM " . self::$table_name . " WHERE jenis_tiket_id = ? AND tanggal = ? AND aktif = TRUE LIMIT 1";
        $stmt = mysqli_prepare($conn, $sql);
        if (!$stmt) {
            error_log("JadwalKetersediaanTiket::getActiveKetersediaan - Prepare Gagal: " . mysqli_error($conn));
            return null;
        }
        mysqli_stmt_bind_param($stmt, "is", $id_val, $tanggal);
        if (mysqli_stmt_execute($stmt)) {
            $result = mysqli_stmt_get_result($stmt);
            $data = mysqli_fetch_assoc($result);
            mysqli_stmt_close($stmt);
            return $data ?: null;
        }
        error_log("JadwalKetersediaanTiket::getActiveKetersediaan - Execute Gagal: " . mysqli_stmt_error($stmt));
        mysqli_stmt_close($stmt);
        return null;
    }

    public static function updateJumlahSaatIniTersedia($jadwal_id, $jumlah_perubahan)
    {
        global $conn;
        if (!$conn) {
            error_log("JadwalKetersediaanTiket::updateJumlahSaatIniTersedia - Koneksi DB gagal.");
            return false;
        }
        $id_val = (int)$jadwal_id;
        $perubahan_val = (int)$jumlah_perubahan;

        if ($id_val <= 0) {
            error_log("JadwalKetersediaanTiket::updateJumlahSaatIniTersedia - ID Jadwal tidak valid.");
            return false;
        }

        $sql = "UPDATE " . self::$table_name . " 
                SET jumlah_saat_ini_tersedia = GREATEST(0, LEAST(jumlah_total_tersedia, jumlah_saat_ini_tersedia + ?))
                WHERE id = ?";

        $stmt = mysqli_prepare($conn, $sql);
        if (!$stmt) {
            error_log("JadwalKetersediaanTiket::updateJumlahSaatIniTersedia - Prepare Gagal: " . mysqli_error($conn));
            return false;
        }
        mysqli_stmt_bind_param($stmt, "ii", $perubahan_val, $id_val);

        if (mysqli_stmt_execute($stmt)) {
            mysqli_stmt_close($stmt);
            return true;
        }
        error_log("JadwalKetersediaanTiket::updateJumlahSaatIniTersedia - Execute Gagal: " . mysqli_stmt_error($stmt));
        mysqli_stmt_close($stmt);
        return false;
    }
}
