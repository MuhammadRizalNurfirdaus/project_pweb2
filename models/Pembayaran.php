<?php
// File: C:\xampp\htdocs\Cilengkrang-Web-Wisata\models\Pembayaran.php

class Pembayaran
{
    private static $table_name = 'pembayaran';
    private static $db;
    private static $last_internal_error = null;

    public const ALLOWED_STATUSES = [
        'pending',
        'success',
        'failed',
        'expired',
        'refunded',
        'awaiting_confirmation',
        'paid',
        'confirmed',
        'cancelled'
    ];
    public const SUCCESSFUL_PAYMENT_STATUSES = ['success', 'paid', 'confirmed'];

    public static function setDbConnection(mysqli $connection)
    {
        self::$db = $connection;
    }

    private static function checkDbConnection()
    {
        if (!self::$db || (self::$db instanceof mysqli && self::$db->connect_error)) {
            $error_msg = get_called_class() . " - Koneksi database tidak tersedia atau gagal: " .
                (self::$db instanceof mysqli ? self::$db->connect_error : (self::$db === null ? 'Koneksi belum diset.' : 'Koneksi bukan objek mysqli.'));
            error_log($error_msg);
            self::$last_internal_error = "Masalah koneksi database pada model Pembayaran.";
            return false;
        }
        self::$last_internal_error = null;
        return true;
    }

    public static function getLastError()
    {
        if (self::$last_internal_error) {
            $temp_error = self::$last_internal_error;
            return $temp_error;
        }
        if (self::$db instanceof mysqli && !empty(self::$db->error)) {
            return "MySQL Error: " . self::$db->error;
        } elseif (!self::$db || !(self::$db instanceof mysqli)) {
            return 'Koneksi database belum diinisialisasi atau tidak valid untuk kelas ' . get_called_class() . '.';
        }
        return 'Tidak ada error database spesifik yang dilaporkan dari model ' . get_called_class() . '.';
    }

    public static function create($data)
    {
        self::$last_internal_error = null;
        if (!self::checkDbConnection()) {
            error_log(get_called_class() . "::create() - Gagal: Koneksi DB tidak valid.");
            return false;
        }

        $pemesanan_tiket_id = isset($data['pemesanan_tiket_id']) ? (int)$data['pemesanan_tiket_id'] : 0;
        $kode_pemesanan_to_bind = $data['kode_pemesanan'] ?? null;
        $jumlah_dibayar = isset($data['jumlah_dibayar']) ? (float)$data['jumlah_dibayar'] : 0.0;
        $status_pembayaran = isset($data['status_pembayaran']) ? strtolower(trim($data['status_pembayaran'])) : 'pending';
        $metode_pembayaran = $data['metode_pembayaran'] ?? null;
        $waktu_pembayaran = $data['waktu_pembayaran'] ?? null;
        $bukti_pembayaran = $data['bukti_pembayaran'] ?? null;
        $id_transaksi_gateway = $data['id_transaksi_gateway'] ?? null;
        $nomor_virtual_account = $data['nomor_virtual_account'] ?? null;
        $catatan_admin = $data['catatan_admin'] ?? null;

        if ($pemesanan_tiket_id <= 0) {
            self::$last_internal_error = "ID Pemesanan Tiket tidak valid untuk membuat pembayaran.";
            error_log(get_called_class() . "::create() - Error: " . self::$last_internal_error . " ID: " . $pemesanan_tiket_id);
            return false;
        }
        if (empty($kode_pemesanan_to_bind) && $pemesanan_tiket_id > 0) {
            if (class_exists('PemesananTiket') && method_exists('PemesananTiket', 'findById')) {
                $pemesananHeader = PemesananTiket::findById($pemesanan_tiket_id);
                if ($pemesananHeader && isset($pemesananHeader['kode_pemesanan'])) {
                    $kode_pemesanan_to_bind = $pemesananHeader['kode_pemesanan'];
                } else {
                    error_log(get_called_class() . "::create() - Peringatan: Gagal mengambil kode_pemesanan dari PemesananTiket ID {$pemesanan_tiket_id}. Kolom kode_pemesanan akan diisi NULL jika diizinkan tabel.");
                }
            }
        }
        // Jika kolom 'kode_pemesanan' di tabel 'pembayaran' adalah NOT NULL, tambahkan validasi:
        // if (empty($kode_pemesanan_to_bind)) {
        //    self::$last_internal_error = "Kode Pemesanan wajib diisi untuk pembayaran.";
        //    error_log(get_called_class() . "::create() - Error: " . self::$last_internal_error);
        //    return false;
        // }

        if ($jumlah_dibayar < 0) {
            self::$last_internal_error = "Jumlah dibayar tidak boleh negatif.";
            error_log(get_called_class() . "::create() - Error: " . self::$last_internal_error);
            return false;
        }
        if (!in_array($status_pembayaran, self::ALLOWED_STATUSES)) {
            self::$last_internal_error = "Status pembayaran awal tidak valid: " . htmlspecialchars($status_pembayaran);
            error_log(get_called_class() . "::create() - Error: " . self::$last_internal_error);
            return false;
        }
        if ($waktu_pembayaran === null && in_array($status_pembayaran, self::SUCCESSFUL_PAYMENT_STATUSES)) {
            $waktu_pembayaran = date('Y-m-d H:i:s');
        } elseif ($waktu_pembayaran !== null) {
            $dtWaktu = DateTime::createFromFormat('Y-m-d H:i:s', $waktu_pembayaran) ?: (DateTime::createFromFormat('Y-m-d\TH:i:s', $waktu_pembayaran) ?: DateTime::createFromFormat('Y-m-d\TH:i', $waktu_pembayaran));
            if (!$dtWaktu) {
                self::$last_internal_error = "Format waktu pembayaran tidak valid: " . htmlspecialchars($waktu_pembayaran);
                error_log(get_called_class() . "::create() - Error: " . self::$last_internal_error);
                return false;
            }
            $waktu_pembayaran = $dtWaktu->format('Y-m-d H:i:s');
        }

        $sql = "INSERT INTO `" . self::$table_name . "` " .
            " (`pemesanan_tiket_id`, `kode_pemesanan`, `jumlah_dibayar`, `status_pembayaran`, `metode_pembayaran`, 
             `waktu_pembayaran`, `bukti_pembayaran`, `id_transaksi_gateway`, `nomor_virtual_account`, `catatan_admin`, 
             `created_at`, `updated_at`) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";

        $stmt = mysqli_prepare(self::$db, $sql);
        if (!$stmt) {
            self::$last_internal_error = "MySQLi Prepare Error: " . mysqli_error(self::$db);
            error_log(get_called_class() . "::create() - " . self::$last_internal_error . " | Attempted SQL: " . $sql);
            return false;
        }

        mysqli_stmt_bind_param($stmt, "isdsssssss", $pemesanan_tiket_id, $kode_pemesanan_to_bind, $jumlah_dibayar, $status_pembayaran, $metode_pembayaran, $waktu_pembayaran, $bukti_pembayaran, $id_transaksi_gateway, $nomor_virtual_account, $catatan_admin);

        if (mysqli_stmt_execute($stmt)) {
            $newId = mysqli_insert_id(self::$db);
            mysqli_stmt_close($stmt);
            return $newId;
        } else {
            self::$last_internal_error = "MySQLi Execute Error: " . mysqli_stmt_error($stmt);
            error_log(get_called_class() . "::create() - " . self::$last_internal_error . " | SQL (structure): " . $sql);
            mysqli_stmt_close($stmt);
            return false;
        }
    }

    public static function findById($id)
    {
        self::$last_internal_error = null;
        if (!self::checkDbConnection()) return null;
        $id_val = filter_var($id, FILTER_VALIDATE_INT);
        if ($id_val === false || $id_val <= 0) {
            self::$last_internal_error = "ID pembayaran tidak valid.";
            error_log(get_called_class() . "::findById() - " . self::$last_internal_error . " ID: " . $id);
            return null;
        }
        $sql = "SELECT * FROM `" . self::$table_name . "` WHERE `id` = ? LIMIT 1";
        $stmt = mysqli_prepare(self::$db, $sql);
        if (!$stmt) {
            self::$last_internal_error = "MySQLi Prepare Error (findById): " . mysqli_error(self::$db);
            error_log(get_called_class() . "::findById() - " . self::$last_internal_error . " | SQL: " . $sql);
            return null;
        }
        mysqli_stmt_bind_param($stmt, "i", $id_val);
        if (mysqli_stmt_execute($stmt)) {
            $result = mysqli_stmt_get_result($stmt);
            $pembayaran = mysqli_fetch_assoc($result);
            mysqli_stmt_close($stmt);
            return $pembayaran ?: null;
        } else {
            self::$last_internal_error = "MySQLi Execute Error (findById): " . mysqli_stmt_error($stmt);
            error_log(get_called_class() . "::findById() - " . self::$last_internal_error);
        }
        mysqli_stmt_close($stmt);
        return null;
    }

    public static function findByPemesananId($pemesanan_tiket_id)
    {
        self::$last_internal_error = null;
        if (!self::checkDbConnection()) return null;
        $id_val = filter_var($pemesanan_tiket_id, FILTER_VALIDATE_INT);
        if ($id_val === false || $id_val <= 0) {
            self::$last_internal_error = "ID Pemesanan Tiket tidak valid.";
            error_log(get_called_class() . "::findByPemesananId() - " . self::$last_internal_error . " ID: " . $pemesanan_tiket_id);
            return null;
        }
        $sql = "SELECT * FROM `" . self::$table_name . "` WHERE `pemesanan_tiket_id` = ? ORDER BY `created_at` DESC LIMIT 1";
        $stmt = mysqli_prepare(self::$db, $sql);
        if (!$stmt) {
            self::$last_internal_error = "MySQLi Prepare Error (findByPemesananId): " . mysqli_error(self::$db);
            error_log(get_called_class() . "::findByPemesananId() - " . self::$last_internal_error . " | SQL: " . $sql);
            return null;
        }
        mysqli_stmt_bind_param($stmt, "i", $id_val);
        if (mysqli_stmt_execute($stmt)) {
            $result = mysqli_stmt_get_result($stmt);
            $pembayaran = mysqli_fetch_assoc($result);
            mysqli_stmt_close($stmt);
            return $pembayaran ?: null;
        } else {
            self::$last_internal_error = "MySQLi Execute Error (findByPemesananId): " . mysqli_stmt_error($stmt);
            error_log(get_called_class() . "::findByPemesananId() - " . self::$last_internal_error);
        }
        mysqli_stmt_close($stmt);
        return null;
    }

    public static function findByPemesananIdAndStatus($pemesanan_tiket_id, $statusArray)
    {
        self::$last_internal_error = null;
        if (!self::checkDbConnection()) return null;
        $id_val = filter_var($pemesanan_tiket_id, FILTER_VALIDATE_INT);
        if ($id_val === false || $id_val <= 0) {
            self::$last_internal_error = "ID Pemesanan Tiket tidak valid.";
            error_log(get_called_class() . "::findByPemesananIdAndStatus() - " . self::$last_internal_error . " ID: " . $pemesanan_tiket_id);
            return null;
        }
        if (empty($statusArray) || !is_array($statusArray)) {
            self::$last_internal_error = "Input statusArray tidak valid untuk findByPemesananIdAndStatus.";
            error_log(get_called_class() . "::findByPemesananIdAndStatus() - " . self::$last_internal_error);
            return null;
        }
        $valid_statuses = array_filter($statusArray, fn($s) => in_array(strtolower(trim($s)), self::ALLOWED_STATUSES));
        if (empty($valid_statuses)) {
            self::$last_internal_error = "Tidak ada status valid yang diberikan untuk findByPemesananIdAndStatus.";
            error_log(get_called_class() . "::findByPemesananIdAndStatus() - " . self::$last_internal_error);
            return null;
        }

        $placeholders = implode(',', array_fill(0, count($valid_statuses), '?'));
        $types = 'i' . str_repeat('s', count($valid_statuses));
        $sql = "SELECT * FROM `" . self::$table_name . "` WHERE `pemesanan_tiket_id` = ? AND `status_pembayaran` IN (" . $placeholders . ") ORDER BY `created_at` DESC LIMIT 1";

        $stmt = mysqli_prepare(self::$db, $sql);
        if (!$stmt) {
            self::$last_internal_error = "MySQLi Prepare Error (findByPemesananIdAndStatus): " . mysqli_error(self::$db);
            error_log(get_called_class() . "::findByPemesananIdAndStatus() - " . self::$last_internal_error . " | SQL: " . $sql);
            return null;
        }
        $params_for_bind = array_merge([$id_val], $valid_statuses);
        mysqli_stmt_bind_param($stmt, $types, ...$params_for_bind);

        if (mysqli_stmt_execute($stmt)) {
            $result = mysqli_stmt_get_result($stmt);
            $pembayaran = mysqli_fetch_assoc($result);
            mysqli_stmt_close($stmt);
            return $pembayaran ?: null;
        } else {
            self::$last_internal_error = "MySQLi Execute Error (findByPemesananIdAndStatus): " . mysqli_stmt_error($stmt);
            error_log(get_called_class() . "::findByPemesananIdAndStatus() - " . self::$last_internal_error);
        }
        mysqli_stmt_close($stmt);
        return null;
    }

    public static function findAllWithKodePemesanan($orderBy = 'p.created_at DESC')
    {
        self::$last_internal_error = null;
        if (!self::checkDbConnection()) return [];

        $allowed_order_columns = ['p.id', 'p.pemesanan_tiket_id', 'p.kode_pemesanan', 'p.jumlah_dibayar', 'p.status_pembayaran', 'p.metode_pembayaran', 'p.waktu_pembayaran', 'p.created_at', 'pt.kode_pemesanan', 'user_nama_pemesan', 'user_email_pemesan'];
        $order_parts = explode(' ', trim($orderBy), 2);
        $column_candidate = $order_parts[0] ?? 'p.created_at';
        $direction = (isset($order_parts[1]) && strtoupper($order_parts[1]) === 'ASC') ? 'ASC' : 'DESC';
        $column = 'p.created_at';
        if (in_array($column_candidate, $allowed_order_columns)) {
            $column = $column_candidate;
        } else {
            $base_column_candidate = preg_replace('/^[a-zA-Z0-9_]+\./', '', $column_candidate);
            if (in_array($base_column_candidate, $allowed_order_columns)) {
                $column = $column_candidate;
            }
        }
        if ($column !== 'user_nama_pemesan' && $column !== 'user_email_pemesan') {
            $orderBySafe = "`" . str_replace(".", "`.`", $column) . "` " . $direction;
        } else {
            $orderBySafe = $column . ' ' . $direction;
        }

        $sql = "SELECT p.*, pt.`kode_pemesanan` AS kode_pemesanan_tiket, 
                       COALESCE(u.`nama_lengkap`, pt.`nama_pemesan_tamu`) AS user_nama_pemesan, 
                       COALESCE(u.`email`, pt.`email_pemesan_tamu`) AS user_email_pemesan
                FROM `" . self::$table_name . "` p 
                LEFT JOIN `pemesanan_tiket` pt ON p.`pemesanan_tiket_id` = pt.`id`
                LEFT JOIN `users` u ON pt.`user_id` = u.`id` 
                ORDER BY " . $orderBySafe;

        $result = mysqli_query(self::$db, $sql);
        if ($result) {
            $data = mysqli_fetch_all($result, MYSQLI_ASSOC);
            mysqli_free_result($result);
            return $data;
        } else {
            self::$last_internal_error = "MySQLi Query Error (findAllWithKodePemesanan): " . mysqli_error(self::$db);
            error_log(get_called_class() . "::findAllWithKodePemesanan() - " . self::$last_internal_error . " | SQL: " . $sql);
        }
        return [];
    }

    public static function updateStatusAndDetails($id, $status, $details = [])
    {
        self::$last_internal_error = null;
        if (!self::checkDbConnection()) return false;
        $id_val = filter_var($id, FILTER_VALIDATE_INT);
        if ($id_val === false || $id_val <= 0) { /* ... */
            return false;
        }
        $clean_status = strtolower(trim($status));
        if (empty($clean_status) || !in_array($clean_status, self::ALLOWED_STATUSES)) { /* ... */
            return false;
        }

        $fields_to_update_sql = ["`status_pembayaran` = ?"];
        $params_to_bind = [$clean_status];
        $types_for_bind = "s";

        $possible_fields = [
            'metode_pembayaran' => 's',
            'bukti_pembayaran' => 's',
            'id_transaksi_gateway' => 's',
            'nomor_virtual_account' => 's',
            'catatan_admin' => 's',
            'waktu_pembayaran' => 's',
            'jumlah_dibayar' => 'd',
            'kode_pemesanan' => 's' // Ditambahkan jika ingin bisa diupdate
        ];
        foreach ($possible_fields as $field_name => $type_char) {
            if (array_key_exists($field_name, $details)) {
                $value = $details[$field_name];
                $fields_to_update_sql[] = "`{$field_name}` = ?";
                $params_to_bind[] = ($type_char === 'd') ? (float)$value : ($value === null ? null : (string)$value);
                $types_for_bind .= $type_char;
            }
        }
        if (in_array($clean_status, self::SUCCESSFUL_PAYMENT_STATUSES) && !array_key_exists('waktu_pembayaran', $details)) {
            $waktu_update_exists_in_query = false;
            foreach ($fields_to_update_sql as $fld_sql) {
                if (strpos($fld_sql, '`waktu_pembayaran` =') !== false) {
                    $waktu_update_exists_in_query = true;
                    break;
                }
            }
            if (!$waktu_update_exists_in_query) $fields_to_update_sql[] = "`waktu_pembayaran` = NOW()";
        }
        $fields_to_update_sql[] = "`updated_at` = NOW()";

        if (count($fields_to_update_sql) <= 1 && !in_array("`status_pembayaran` = ?", $fields_to_update_sql)) { // Hanya updated_at atau tidak ada field valid
            error_log(get_called_class() . "::updateStatusAndDetails() - Tidak ada field valid untuk diupdate selain updated_at. ID: " . $id_val);
            return true; // Dianggap berhasil jika tidak ada yang diubah
        }

        $sql_update = "UPDATE `" . self::$table_name . "` SET " . implode(', ', $fields_to_update_sql) . " WHERE `id` = ?";
        $params_to_bind[] = $id_val;
        $types_for_bind .= "i";

        $stmt = mysqli_prepare(self::$db, $sql_update);
        if (!$stmt) { /* ... error handling ... */
            return false;
        }
        mysqli_stmt_bind_param($stmt, $types_for_bind, ...$params_to_bind);
        if (mysqli_stmt_execute($stmt)) { /* ... */
        } else { /* ... error handling ... */
        }
        mysqli_stmt_close($stmt);
        return false; // Placeholder
    }


    public static function getTotalRevenue($status_array = null)
    {
        // ... (implementasi lengkap seperti kode Anda sebelumnya dengan prepare, bind, execute, error log) ...
        return 0.0; // Placeholder
    }

    public static function countByStatus($status)
    {
        self::$last_internal_error = null;
        if (!self::checkDbConnection()) return 0;

        $statuses_to_check = is_array($status) ? $status : [$status];
        if (empty($statuses_to_check)) {
            self::$last_internal_error = "Parameter status kosong untuk countByStatus.";
            error_log(get_called_class() . "::countByStatus() - " . self::$last_internal_error);
            return 0;
        }

        $valid_statuses = array_filter($statuses_to_check, fn($s) => in_array(strtolower(trim($s)), self::ALLOWED_STATUSES));
        if (empty($valid_statuses)) {
            self::$last_internal_error = "Tidak ada status valid yang diberikan untuk countByStatus: " . print_r($status, true);
            error_log(get_called_class() . "::countByStatus() - " . self::$last_internal_error);
            return 0;
        }

        $placeholders = implode(',', array_fill(0, count($valid_statuses), '?'));
        $types = str_repeat('s', count($valid_statuses));
        $sql = "SELECT COUNT(`id`) as total FROM `" . self::$table_name . "` WHERE `status_pembayaran` IN (" . $placeholders . ")";

        $stmt = mysqli_prepare(self::$db, $sql);
        if (!$stmt) {
            self::$last_internal_error = "MySQLi Prepare Error (countByStatus): " . mysqli_error(self::$db);
            error_log(get_called_class() . "::countByStatus() - " . self::$last_internal_error . " | SQL: " . $sql);
            return 0;
        }

        mysqli_stmt_bind_param($stmt, $types, ...$valid_statuses);
        if (mysqli_stmt_execute($stmt)) {
            $result = mysqli_stmt_get_result($stmt);
            $row = mysqli_fetch_assoc($result);
            mysqli_stmt_close($stmt);
            return (int)($row['total'] ?? 0);
        } else {
            self::$last_internal_error = "MySQLi Execute Error (countByStatus): " . mysqli_stmt_error($stmt);
            error_log(get_called_class() . "::countByStatus() - " . self::$last_internal_error);
        }
        mysqli_stmt_close($stmt);
        return 0;
    }

    public static function countAll()
    {
        if (!self::checkDbConnection()) return 0;
        $sql = "SELECT COUNT(`id`) as total FROM `" . self::$table_name . "`";
        $result = mysqli_query(self::$db, $sql);
        if ($result) {
            $row = mysqli_fetch_assoc($result);
            mysqli_free_result($result);
            return (int)($row['total'] ?? 0);
        } else {
            self::$last_internal_error = "MySQLi Query Error (countAll): " . mysqli_error(self::$db);
            error_log(get_called_class() . "::countAll() - " . self::$last_internal_error . " | SQL: " . $sql);
        }
        return 0;
    }

    public static function delete($id)
    {
        self::$last_internal_error = null;
        if (!self::checkDbConnection()) return false;
        $id_val = filter_var($id, FILTER_VALIDATE_INT);
        if ($id_val === false || $id_val <= 0) {
            self::$last_internal_error = "ID pembayaran tidak valid untuk dihapus.";
            error_log(get_called_class() . "::delete() - " . self::$last_internal_error . " ID: " . $id);
            return false;
        }

        $sql = "DELETE FROM `" . self::$table_name . "` WHERE `id` = ?";
        $stmt = mysqli_prepare(self::$db, $sql);
        if (!$stmt) {
            self::$last_internal_error = "MySQLi Prepare Error (delete): " . mysqli_error(self::$db);
            error_log(get_called_class() . "::delete() - " . self::$last_internal_error . " | SQL: " . $sql);
            return false;
        }

        mysqli_stmt_bind_param($stmt, "i", $id_val);
        if (mysqli_stmt_execute($stmt)) {
            $affected_rows = mysqli_stmt_affected_rows($stmt);
            mysqli_stmt_close($stmt);
            return $affected_rows > 0;
        } else {
            self::$last_internal_error = "MySQLi Execute Error (delete): " . mysqli_stmt_error($stmt);
            error_log(get_called_class() . "::delete() - " . self::$last_internal_error);
        }
        mysqli_stmt_close($stmt);
        return false;
    }

    public static function deleteByPemesananId($pemesanan_tiket_id)
    {
        self::$last_internal_error = null;
        if (!self::checkDbConnection()) return false;
        $id_val = filter_var($pemesanan_tiket_id, FILTER_VALIDATE_INT);
        if ($id_val === false || $id_val <= 0) {
            self::$last_internal_error = "ID Pemesanan Tiket tidak valid untuk menghapus pembayaran terkait.";
            error_log(get_called_class() . "::deleteByPemesananId() - " . self::$last_internal_error . " ID: " . $pemesanan_tiket_id);
            return false;
        }

        $sql = "DELETE FROM `" . self::$table_name . "` WHERE `pemesanan_tiket_id` = ?";
        $stmt = mysqli_prepare(self::$db, $sql);
        if (!$stmt) {
            self::$last_internal_error = "MySQLi Prepare Error (deleteByPemesananId): " . mysqli_error(self::$db);
            error_log(get_called_class() . "::deleteByPemesananId() - " . self::$last_internal_error . " | SQL: " . $sql);
            return false;
        }

        mysqli_stmt_bind_param($stmt, "i", $id_val);
        if (mysqli_stmt_execute($stmt)) {
            // Tidak masalah jika affected_rows adalah 0, mungkin memang tidak ada pembayaran terkait
            mysqli_stmt_close($stmt);
            return true;
        } else {
            self::$last_internal_error = "MySQLi Execute Error (deleteByPemesananId): " . mysqli_stmt_error($stmt);
            error_log(get_called_class() . "::deleteByPemesananId() - " . self::$last_internal_error);
        }
        mysqli_stmt_close($stmt);
        return false;
    }
}
