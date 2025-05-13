<?php
// File: C:\xampp\htdocs\Cilengkrang-Web-Wisata\models\PemesananTiket.php

class PemesananTiket
{
    private static $table_name = "pemesanan_tiket";
    private static $db; // Properti untuk menyimpan koneksi database

    // Daftar status yang diizinkan
    private const ALLOWED_STATUSES = [
        'pending',
        'waiting_payment',
        'paid',
        'confirmed',
        'completed',
        'cancelled',
        'expired',
        'refunded'
    ];

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
     * Membuat header pemesanan tiket baru.
     * @param array $data Data pemesanan.
     * @return int|false ID pemesanan baru atau false jika gagal.
     */
    public static function create($data)
    {
        if (!self::checkDbConnection()) return false;

        $user_id = isset($data['user_id']) && !empty($data['user_id']) ? (int)$data['user_id'] : null;
        $nama_pemesan_tamu = ($user_id === null && isset($data['nama_pemesan_tamu'])) ? trim($data['nama_pemesan_tamu']) : null;
        $email_pemesan_tamu = ($user_id === null && isset($data['email_pemesan_tamu'])) ? trim($data['email_pemesan_tamu']) : null;
        $nohp_pemesan_tamu = ($user_id === null && isset($data['nohp_pemesan_tamu'])) ? trim($data['nohp_pemesan_tamu']) : null;
        $kode_pemesanan = trim($data['kode_pemesanan'] ?? ('PT' . date('Ymd') . strtoupper(bin2hex(random_bytes(4)))));
        $tanggal_kunjungan = trim($data['tanggal_kunjungan'] ?? '');
        $total_harga_akhir = isset($data['total_harga_akhir']) ? (float)$data['total_harga_akhir'] : 0.0;
        $status_pemesanan = strtolower(trim($data['status'] ?? 'pending'));
        $catatan_umum_pemesanan = isset($data['catatan_umum_pemesanan']) ? trim($data['catatan_umum_pemesanan']) : null;

        if (empty($kode_pemesanan)) {
            error_log(get_called_class() . "::create() - Kode pemesanan kosong.");
            return false;
        }
        if (empty($tanggal_kunjungan) || !DateTime::createFromFormat('Y-m-d', $tanggal_kunjungan)) {
            error_log(get_called_class() . "::create() - Tanggal kunjungan tidak valid: " . $tanggal_kunjungan);
            return false;
        }
        if ($user_id === null) {
            if (empty($nama_pemesan_tamu)) {
                error_log(get_called_class() . "::create() - Nama tamu kosong.");
                return false;
            }
            if (empty($email_pemesan_tamu) || !filter_var($email_pemesan_tamu, FILTER_VALIDATE_EMAIL)) {
                error_log(get_called_class() . "::create() - Email tamu tidak valid: " . $email_pemesan_tamu);
                return false;
            }
            if (empty($nohp_pemesan_tamu)) {
                error_log(get_called_class() . "::create() - No HP tamu kosong.");
                return false;
            }
        }
        if (!in_array($status_pemesanan, self::ALLOWED_STATUSES)) {
            error_log(get_called_class() . "::create() - Status pemesanan tidak valid: " . $status_pemesanan);
            return false;
        }

        $sql = "INSERT INTO " . self::$table_name .
            " (user_id, nama_pemesan_tamu, email_pemesan_tamu, nohp_pemesan_tamu, kode_pemesanan, tanggal_kunjungan, total_harga_akhir, status, catatan_umum_pemesanan, created_at, updated_at) 
               VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";

        $stmt = mysqli_prepare(self::$db, $sql);
        if (!$stmt) {
            error_log(get_called_class() . "::create() - MySQLi Prepare Error: " . mysqli_error(self::$db));
            return false;
        }

        mysqli_stmt_bind_param($stmt, "isssssdss", $user_id, $nama_pemesan_tamu, $email_pemesan_tamu, $nohp_pemesan_tamu, $kode_pemesanan, $tanggal_kunjungan, $total_harga_akhir, $status_pemesanan, $catatan_umum_pemesanan);

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

    // ... (Metode getAll, findById, getByKodePemesanan, getByUserId, updateStatusPemesanan, updateTotalHargaAkhir, delete, countByStatus, countAll) ...
    // SEMUA METODE LAIN DI SINI TETAP SAMA SEPERTI REVISI SEBELUMNYA,
    // PASTIKAN MEREKA MENGGUNAKAN self::$db dan self::checkDbConnection()
    // Contoh untuk findById:
    public static function findById($id)
    {
        if (!self::checkDbConnection()) return null;
        $id_val = filter_var($id, FILTER_VALIDATE_INT);
        if ($id_val === false || $id_val <= 0) {
            error_log(get_called_class() . "::findById() - ID tidak valid: " . $id);
            return null;
        }

        $sql = "SELECT pt.*, u.nama_lengkap AS user_nama_lengkap, u.email AS user_email, u.no_hp as user_no_hp 
                FROM " . self::$table_name . " pt
                LEFT JOIN users u ON pt.user_id = u.id
                WHERE pt.id = ?";
        $stmt = mysqli_prepare(self::$db, $sql);
        if (!$stmt) {
            error_log(get_called_class() . "::findById() Prepare Error: " . mysqli_error(self::$db));
            return null;
        }
        mysqli_stmt_bind_param($stmt, "i", $id_val);
        if (mysqli_stmt_execute($stmt)) {
            $result = mysqli_stmt_get_result($stmt);
            $pemesanan = mysqli_fetch_assoc($result);
            mysqli_stmt_close($stmt);
            return $pemesanan ?: null;
        } else {
            error_log(get_called_class() . "::findById() Execute Error: " . mysqli_stmt_error($stmt));
        }
        mysqli_stmt_close($stmt);
        return null;
    }
    // ... (Implementasikan SEMUA metode lain dengan pola yang sama, menggunakan self::$db) ...
    // PASTIkan semua metode yang Anda sertakan dari versi sebelumnya dimasukkan kembali ke sini
    // dengan `if (!self::checkDbConnection()) return ...;` di awal dan penggunaan `self::$db`
    // untuk semua operasi database.

    public static function getAll()
    {
        if (!self::checkDbConnection()) return [];
        $sql = "SELECT pt.*, u.nama_lengkap AS user_nama_lengkap, u.email AS user_email FROM " . self::$table_name . " pt LEFT JOIN users u ON pt.user_id = u.id ORDER BY pt.created_at DESC, pt.id DESC";
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
    public static function getByKodePemesanan($kode_pemesanan)
    {
        if (!self::checkDbConnection()) return null;
        $kode = trim($kode_pemesanan);
        if (empty($kode)) {
            error_log(get_called_class() . "::getByKodePemesanan() - Kode pemesanan kosong.");
            return null;
        }
        $sql = "SELECT pt.*, u.nama_lengkap AS user_nama_lengkap, u.email AS user_email, u.no_hp as user_no_hp FROM " . self::$table_name . " pt LEFT JOIN users u ON pt.user_id = u.id WHERE pt.kode_pemesanan = ?";
        $stmt = mysqli_prepare(self::$db, $sql);
        if (!$stmt) {
            error_log(get_called_class() . "::getByKodePemesanan() Prepare Error: " . mysqli_error(self::$db));
            return null;
        }
        mysqli_stmt_bind_param($stmt, "s", $kode);
        if (mysqli_stmt_execute($stmt)) {
            $result = mysqli_stmt_get_result($stmt);
            $data = mysqli_fetch_assoc($result);
            mysqli_stmt_close($stmt);
            return $data ?: null;
        } else {
            error_log(get_called_class() . "::getByKodePemesanan() Execute Error: " . mysqli_stmt_error($stmt));
        }
        mysqli_stmt_close($stmt);
        return null;
    }
    public static function getByUserId($user_id, $limit = null)
    {
        if (!self::checkDbConnection()) return [];
        $user_id_val = filter_var($user_id, FILTER_VALIDATE_INT);
        if ($user_id_val === false || $user_id_val <= 0) {
            error_log(get_called_class() . "::getByUserId() - User ID tidak valid: " . $user_id);
            return [];
        }
        $sql = "SELECT pt.*, u.nama_lengkap AS user_nama_lengkap, u.email AS user_email FROM " . self::$table_name . " pt LEFT JOIN users u ON pt.user_id = u.id WHERE pt.user_id = ? ORDER BY pt.created_at DESC, pt.id DESC";
        $params = [$user_id_val];
        $types = "i";
        if (is_numeric($limit) && $limit > 0) {
            $sql .= " LIMIT ?";
            $params[] = (int)$limit;
            $types .= "i";
        }
        $stmt = mysqli_prepare(self::$db, $sql);
        if (!$stmt) {
            error_log(get_called_class() . "::getByUserId() Prepare Error: " . mysqli_error(self::$db));
            return [];
        }
        mysqli_stmt_bind_param($stmt, $types, ...$params);
        if (mysqli_stmt_execute($stmt)) {
            $result = mysqli_stmt_get_result($stmt);
            $data = mysqli_fetch_all($result, MYSQLI_ASSOC);
            mysqli_stmt_close($stmt);
            return $data;
        } else {
            error_log(get_called_class() . "::getByUserId() Execute Error: " . mysqli_stmt_error($stmt));
        }
        mysqli_stmt_close($stmt);
        return [];
    }
    public static function updateStatusPemesanan($id, $new_status)
    {
        if (!self::checkDbConnection()) return false;
        $id_val = filter_var($id, FILTER_VALIDATE_INT);
        $status_val = strtolower(trim($new_status));
        if ($id_val === false || $id_val <= 0 || empty($status_val) || !in_array($status_val, self::ALLOWED_STATUSES)) {
            error_log(get_called_class() . "::updateStatusPemesanan() - Input tidak valid. ID: " . $id . ", Status: '" . $status_val . "'");
            return false;
        }
        $sql = "UPDATE " . self::$table_name . " SET status = ?, updated_at = NOW() WHERE id = ?";
        $stmt = mysqli_prepare(self::$db, $sql);
        if (!$stmt) {
            error_log(get_called_class() . "::updateStatusPemesanan() Prepare Error: " . mysqli_error(self::$db));
            return false;
        }
        mysqli_stmt_bind_param($stmt, "si", $status_val, $id_val);
        if (mysqli_stmt_execute($stmt)) {
            $affected_rows = mysqli_stmt_affected_rows($stmt);
            mysqli_stmt_close($stmt);
            return $affected_rows >= 0;
        } else {
            error_log(get_called_class() . "::updateStatusPemesanan() Execute Error: " . mysqli_stmt_error($stmt));
        }
        mysqli_stmt_close($stmt);
        return false;
    }
    public static function updateTotalHargaAkhir($pemesanan_tiket_id, $total_harga_baru)
    {
        if (!self::checkDbConnection()) return false;
        $id_val = filter_var($pemesanan_tiket_id, FILTER_VALIDATE_INT);
        $harga_val = filter_var($total_harga_baru, FILTER_VALIDATE_FLOAT, ['options' => ['min_range' => 0]]);
        if ($id_val === false || $id_val <= 0 || $harga_val === false) {
            error_log(get_called_class() . "::updateTotalHargaAkhir() - Input tidak valid. ID: " . $pemesanan_tiket_id . ", Harga: " . $total_harga_baru);
            return false;
        }
        $sql = "UPDATE " . self::$table_name . " SET total_harga_akhir = ?, updated_at = NOW() WHERE id = ?";
        $stmt = mysqli_prepare(self::$db, $sql);
        if (!$stmt) {
            error_log(get_called_class() . "::updateTotalHargaAkhir() Prepare Error: " . mysqli_error(self::$db));
            return false;
        }
        mysqli_stmt_bind_param($stmt, "di", $harga_val, $id_val);
        if (mysqli_stmt_execute($stmt)) {
            $affected_rows = mysqli_stmt_affected_rows($stmt);
            mysqli_stmt_close($stmt);
            return $affected_rows >= 0;
        } else {
            error_log(get_called_class() . "::updateTotalHargaAkhir() Execute Error: " . mysqli_stmt_error($stmt));
        }
        mysqli_stmt_close($stmt);
        return false;
    }
    public static function delete($id)
    {
        if (!self::checkDbConnection()) return false;
        $id_val = filter_var($id, FILTER_VALIDATE_INT);
        if ($id_val === false || $id_val <= 0) {
            error_log(get_called_class() . "::delete() - ID tidak valid: " . $id);
            return false;
        }
        mysqli_begin_transaction(self::$db);
        try {
            $tables_to_delete_from = ["detail_pemesanan_tiket" => "pemesanan_tiket_id", "detail_pemesanan_sewa" => "pemesanan_tiket_id", "pembayaran" => "pemesanan_tiket_id"];
            foreach ($tables_to_delete_from as $table_relasi => $foreign_key_column) {
                $sql_delete_related = "DELETE FROM `{$table_relasi}` WHERE `{$foreign_key_column}` = ?";
                $stmt_related = mysqli_prepare(self::$db, $sql_delete_related);
                if ($stmt_related) {
                    mysqli_stmt_bind_param($stmt_related, "i", $id_val);
                    if (!mysqli_stmt_execute($stmt_related)) {
                        throw new Exception("Gagal execute hapus dari {$table_relasi}: " . mysqli_stmt_error($stmt_related));
                    }
                    mysqli_stmt_close($stmt_related);
                } else {
                    throw new Exception("Gagal prepare statement hapus dari {$table_relasi}: " . mysqli_error(self::$db));
                }
            }
            $sql_delete_header = "DELETE FROM " . self::$table_name . " WHERE id = ?";
            $stmt_header = mysqli_prepare(self::$db, $sql_delete_header);
            if (!$stmt_header) {
                throw new Exception("Gagal prepare statement hapus header pemesanan: " . mysqli_error(self::$db));
            }
            mysqli_stmt_bind_param($stmt_header, "i", $id_val);
            if (mysqli_stmt_execute($stmt_header)) {
                $affected_rows = mysqli_stmt_affected_rows($stmt_header);
                mysqli_stmt_close($stmt_header);
                mysqli_commit(self::$db);
                return $affected_rows > 0;
            } else {
                throw new Exception("Gagal execute statement hapus header pemesanan: " . mysqli_stmt_error($stmt_header));
            }
        } catch (Exception $e) {
            mysqli_rollback(self::$db);
            error_log(get_called_class() . "::delete() Exception: " . $e->getMessage());
            return false;
        }
    }
    public static function countByStatus($status_filter)
    {
        if (!self::checkDbConnection()) return 0;
        $statuses_to_check = is_array($status_filter) ? $status_filter : [$status_filter];
        if (empty($statuses_to_check)) return 0;
        $valid_statuses = array_filter($statuses_to_check, fn($s) => in_array(strtolower(trim($s)), self::ALLOWED_STATUSES));
        if (empty($valid_statuses)) {
            error_log(get_called_class() . "::countByStatus() - Tidak ada status valid yang diberikan: " . print_r($status_filter, true));
            return 0;
        }
        $placeholders = implode(',', array_fill(0, count($valid_statuses), '?'));
        $types = str_repeat('s', count($valid_statuses));
        $sql = "SELECT COUNT(id) as total FROM " . self::$table_name . " WHERE status IN (" . $placeholders . ")";
        $stmt = mysqli_prepare(self::$db, $sql);
        if (!$stmt) {
            error_log(get_called_class() . "::countByStatus() - MySQLi Prepare Error: " . mysqli_error(self::$db));
            return 0;
        }
        mysqli_stmt_bind_param($stmt, $types, ...$valid_statuses);
        if (mysqli_stmt_execute($stmt)) {
            $result = mysqli_stmt_get_result($stmt);
            $row = mysqli_fetch_assoc($result);
            mysqli_stmt_close($stmt);
            return (int)($row['total'] ?? 0);
        } else {
            error_log(get_called_class() . "::countByStatus() - MySQLi Execute Error: " . mysqli_stmt_error($stmt));
        }
        mysqli_stmt_close($stmt);
        return 0;
    }
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
} // End of class PemesananTiket