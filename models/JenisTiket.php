<?php
// File: C:\xampp\htdocs\Cilengkrang-Web-Wisata\models\JenisTiket.php

class JenisTiket
{
    private static $table_name = "jenis_tiket";

    /**
     * Membuat jenis tiket baru.
     * @param array $data Array asosiatif data.
     * Kunci: 'nama_layanan_display', 'tipe_hari', 'harga', 'deskripsi' (opsional), 
     * 'aktif' (opsional, default 1), 'wisata_id' (opsional, default null).
     * @return int|false ID record baru jika berhasil, false jika gagal.
     */
    public static function create($data)
    {
        global $conn;
        if (!$conn) {
            error_log("JenisTiket::create() - Koneksi database gagal.");
            return false;
        }

        $nama_layanan = trim($data['nama_layanan_display'] ?? '');
        $tipe_hari = trim($data['tipe_hari'] ?? '');
        $harga = isset($data['harga']) ? (int)$data['harga'] : 0;
        $deskripsi = isset($data['deskripsi']) ? trim($data['deskripsi']) : null;
        $aktif = isset($data['aktif']) ? (int)$data['aktif'] : 1;
        $wisata_id = isset($data['wisata_id']) && !empty($data['wisata_id']) && is_numeric($data['wisata_id']) ? (int)$data['wisata_id'] : null;

        if (empty($nama_layanan) || empty($tipe_hari) || $harga < 0) {
            error_log("JenisTiket::create() - Error: Nama layanan ('{$nama_layanan}'), tipe hari ('{$tipe_hari}'), atau harga ({$harga}) tidak valid.");
            return false;
        }
        $allowed_tipe_hari = ['Hari Kerja', 'Hari Libur', 'Semua Hari'];
        if (!in_array($tipe_hari, $allowed_tipe_hari)) {
            error_log("JenisTiket::create() - Error: Tipe hari '{$tipe_hari}' tidak valid.");
            return false;
        }

        // Cek duplikasi sebelum insert
        $sql_check = "SELECT id FROM " . self::$table_name . " WHERE nama_layanan_display = ? AND tipe_hari = ? AND (wisata_id = ? OR (wisata_id IS NULL AND ? IS NULL)) LIMIT 1";
        $stmt_check = mysqli_prepare($conn, $sql_check);
        if ($stmt_check) {
            mysqli_stmt_bind_param($stmt_check, "ssii", $nama_layanan, $tipe_hari, $wisata_id, $wisata_id);
            mysqli_stmt_execute($stmt_check);
            $result_check = mysqli_stmt_get_result($stmt_check);
            if (mysqli_num_rows($result_check) > 0) {
                error_log("JenisTiket::create() - Error: Duplikasi jenis tiket untuk nama layanan, tipe hari, dan wisata ID yang sama.");
                set_flash_message('danger', 'Jenis tiket dengan nama layanan, tipe hari, dan destinasi terkait yang sama sudah ada.');
                mysqli_stmt_close($stmt_check);
                return false;
            }
            mysqli_stmt_close($stmt_check);
        } else {
            error_log("JenisTiket::create() - Gagal prepare statement untuk cek duplikasi: " . mysqli_error($conn));
            // return false; // Pertimbangkan untuk menghentikan proses jika cek duplikasi gagal
        }

        $sql = "INSERT INTO " . self::$table_name .
            " (nama_layanan_display, tipe_hari, harga, deskripsi, aktif, wisata_id) 
               VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = mysqli_prepare($conn, $sql);

        if (!$stmt) {
            error_log("JenisTiket::create() - MySQLi Prepare Error: " . mysqli_error($conn) . " | SQL: " . $sql);
            return false;
        }
        mysqli_stmt_bind_param($stmt, "ssisii", $nama_layanan, $tipe_hari, $harga, $deskripsi, $aktif, $wisata_id);

        if (mysqli_stmt_execute($stmt)) {
            $new_id = mysqli_insert_id($conn);
            mysqli_stmt_close($stmt);
            return $new_id;
        } else {
            error_log("JenisTiket::create() - MySQLi Execute Error: " . mysqli_stmt_error($stmt) . " | SQL: " . $sql);
            mysqli_stmt_close($stmt);
            return false;
        }
    }

    /**
     * Mengambil semua jenis tiket, di-join dengan nama wisata jika ada.
     * Diurutkan berdasarkan ID jenis tiket secara menaik.
     * @return array Array data jenis tiket, atau array kosong jika gagal/tidak ada.
     */
    public static function getAll()
    {
        global $conn;
        if (!$conn) {
            error_log("JenisTiket::getAll() - Koneksi database gagal.");
            return [];
        }

        // === PERBAIKAN UTAMA ADA DI SINI ===
        $sql = "SELECT jt.*, w.nama AS nama_wisata_terkait 
                FROM " . self::$table_name . " jt
                LEFT JOIN wisata w ON jt.wisata_id = w.id
                ORDER BY jt.id ASC"; // Diurutkan berdasarkan ID Jenis Tiket secara menaik
        $result = mysqli_query($conn, $sql);

        if ($result) {
            return mysqli_fetch_all($result, MYSQLI_ASSOC);
        } else {
            error_log("JenisTiket::getAll() - MySQLi Query Error: " . mysqli_error($conn) . " | SQL: " . $sql);
            return [];
        }
    }

    public static function getById($id)
    {
        global $conn;
        if (!$conn) {
            error_log("JenisTiket::getById({$id}) - Koneksi database gagal.");
            return null;
        }
        $id_val = filter_var($id, FILTER_VALIDATE_INT);
        if ($id_val === false || $id_val <= 0) {
            error_log("JenisTiket::getById() - ID tidak valid: '" . e($id) . "'.");
            return null;
        }

        $sql = "SELECT jt.*, w.nama AS nama_wisata_terkait 
                FROM " . self::$table_name . " jt
                LEFT JOIN wisata w ON jt.wisata_id = w.id
                WHERE jt.id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        if (!$stmt) {
            error_log("JenisTiket::getById({$id_val}) - MySQLi Prepare Error: " . mysqli_error($conn) . " | SQL: " . $sql);
            return null;
        }
        mysqli_stmt_bind_param($stmt, "i", $id_val);
        if (mysqli_stmt_execute($stmt)) {
            $result = mysqli_stmt_get_result($stmt);
            $jenis_tiket = mysqli_fetch_assoc($result);
            mysqli_stmt_close($stmt);
            return $jenis_tiket ?: null;
        } else {
            error_log("JenisTiket::getById({$id_val}) - MySQLi Execute Error: " . mysqli_stmt_error($stmt) . " | SQL: " . $sql);
            mysqli_stmt_close($stmt);
            return null;
        }
    }

    public static function update($data)
    {
        global $conn;
        if (!$conn || !isset($data['id'])) {
            error_log("JenisTiket::update() - Koneksi database gagal atau ID tidak disediakan.");
            return false;
        }

        $id = isset($data['id']) ? (int)$data['id'] : 0;
        $nama_layanan = trim($data['nama_layanan_display'] ?? '');
        $tipe_hari = trim($data['tipe_hari'] ?? '');
        $harga = isset($data['harga']) ? (int)$data['harga'] : 0;
        $deskripsi = isset($data['deskripsi']) ? trim($data['deskripsi']) : null;
        $aktif = isset($data['aktif']) ? (int)$data['aktif'] : 0;
        $wisata_id = isset($data['wisata_id']) && !empty($data['wisata_id']) && is_numeric($data['wisata_id']) ? (int)$data['wisata_id'] : null;

        if ($id <= 0 || empty($nama_layanan) || empty($tipe_hari) || $harga < 0) {
            error_log("JenisTiket::update() - Data input tidak valid. ID: {$id}, Nama: '{$nama_layanan}', Tipe: '{$tipe_hari}', Harga: {$harga}");
            return false;
        }
        $allowed_tipe_hari = ['Hari Kerja', 'Hari Libur', 'Semua Hari'];
        if (!in_array($tipe_hari, $allowed_tipe_hari)) {
            error_log("JenisTiket::update() - Error: Tipe hari '{$tipe_hari}' tidak valid.");
            return false;
        }

        $sql_check = "SELECT id FROM " . self::$table_name . " WHERE nama_layanan_display = ? AND tipe_hari = ? AND (wisata_id = ? OR (wisata_id IS NULL AND ? IS NULL)) AND id != ? LIMIT 1";
        $stmt_check = mysqli_prepare($conn, $sql_check);
        if ($stmt_check) {
            mysqli_stmt_bind_param($stmt_check, "ssiii", $nama_layanan, $tipe_hari, $wisata_id, $wisata_id, $id);
            mysqli_stmt_execute($stmt_check);
            $result_check = mysqli_stmt_get_result($stmt_check);
            if (mysqli_num_rows($result_check) > 0) {
                error_log("JenisTiket::update() - Error: Duplikasi jenis tiket saat update ID {$id}.");
                set_flash_message('danger', 'Jenis tiket dengan nama layanan, tipe hari, dan destinasi terkait yang sama sudah ada (untuk entri lain).');
                mysqli_stmt_close($stmt_check);
                return false;
            }
            mysqli_stmt_close($stmt_check);
        } else {
            error_log("JenisTiket::update() - Gagal prepare statement untuk cek duplikasi: " . mysqli_error($conn));
        }

        $sql = "UPDATE " . self::$table_name . " SET
                nama_layanan_display = ?, tipe_hari = ?, harga = ?, 
                deskripsi = ?, aktif = ?, wisata_id = ?
                WHERE id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        if (!$stmt) {
            error_log("JenisTiket::update() - MySQLi Prepare Error: " . mysqli_error($conn) . " | SQL: " . $sql);
            return false;
        }
        mysqli_stmt_bind_param($stmt, "ssisiii", $nama_layanan, $tipe_hari, $harga, $deskripsi, $aktif, $wisata_id, $id);
        if (mysqli_stmt_execute($stmt)) {
            mysqli_stmt_close($stmt);
            return true;
        } else {
            error_log("JenisTiket::update() - MySQLi Execute Error: " . mysqli_stmt_error($stmt) . " | SQL: " . $sql);
            mysqli_stmt_close($stmt);
            return false;
        }
    }

    public static function delete($id)
    { /* ... sama seperti sebelumnya ... */
        global $conn;
        if (!$conn) {
            error_log("JenisTiket::delete() - Koneksi DB gagal.");
            return false;
        }
        $id_val = filter_var($id, FILTER_VALIDATE_INT);
        if ($id_val === false || $id_val <= 0) {
            error_log("JenisTiket::delete() - ID tidak valid: " . e($id));
            return false;
        }
        $sql_check = "SELECT COUNT(*) as total FROM detail_pemesanan_tiket WHERE jenis_tiket_id = ?";
        $stmt_check = mysqli_prepare($conn, $sql_check);
        if ($stmt_check) {
            mysqli_stmt_bind_param($stmt_check, "i", $id_val);
            mysqli_stmt_execute($stmt_check);
            $result_check = mysqli_stmt_get_result($stmt_check);
            $row_check = mysqli_fetch_assoc($result_check);
            mysqli_stmt_close($stmt_check);
            if ($row_check && $row_check['total'] > 0) {
                error_log("JenisTiket::delete() - Gagal: Jenis tiket ID {$id_val} masih digunakan dalam " . $row_check['total'] . " detail pemesanan.");
                set_flash_message('danger', 'Jenis tiket tidak dapat dihapus karena masih digunakan dalam pemesanan.');
                return false;
            }
        } else {
            error_log("JenisTiket::delete() - Gagal melakukan prepare statement untuk pengecekan foreign key.");
            return false;
        }
        $sql = "DELETE FROM " . self::$table_name . " WHERE id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        if (!$stmt) {
            error_log("JenisTiket::delete() - MySQLi Prepare Error: " . mysqli_error($conn) . " | SQL: " . $sql);
            return false;
        }
        mysqli_stmt_bind_param($stmt, "i", $id_val);
        if (mysqli_stmt_execute($stmt)) {
            $affected_rows = mysqli_stmt_affected_rows($stmt);
            mysqli_stmt_close($stmt);
            return $affected_rows > 0;
        } else {
            error_log("JenisTiket::delete() - MySQLi Execute Error: " . mysqli_stmt_error($stmt) . " | SQL: " . $sql);
            mysqli_stmt_close($stmt);
            return false;
        }
    }
    public static function getActiveByLayananAndTipeHari($nama_layanan, $tipe_hari)
    { /* ... sama seperti sebelumnya ... */
        global $conn;
        if (!$conn) {
            error_log("JenisTiket::getActiveByLayananAndTipeHari() - Koneksi DB gagal.");
            return null;
        }
        if (empty($nama_layanan) || empty($tipe_hari)) {
            error_log("JenisTiket::getActiveByLayananAndTipeHari() - Nama layanan atau tipe hari kosong.");
            return null;
        }
        $sql = "SELECT * FROM " . self::$table_name . " WHERE nama_layanan_display = ? AND tipe_hari = ? AND aktif = TRUE LIMIT 1";
        $stmt = mysqli_prepare($conn, $sql);
        if (!$stmt) {
            error_log("JenisTiket::getActiveByLayananAndTipeHari() - Prepare Error: " . mysqli_error($conn) . " | SQL: " . $sql);
            return null;
        }
        mysqli_stmt_bind_param($stmt, "ss", $nama_layanan, $tipe_hari);
        if (mysqli_stmt_execute($stmt)) {
            $result = mysqli_stmt_get_result($stmt);
            $jenis_tiket = mysqli_fetch_assoc($result);
            mysqli_stmt_close($stmt);
            return $jenis_tiket ?: null;
        } else {
            error_log("JenisTiket::getActiveByLayananAndTipeHari() - Execute Error: " . mysqli_stmt_error($stmt) . " | SQL: " . $sql);
            mysqli_stmt_close($stmt);
            return null;
        }
    }
}
