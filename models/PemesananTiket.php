<?php
// File: C:\xampp\htdocs\Cilengkrang-Web-Wisata\models\PemesananTiket.php

class PemesananTiket
{
    private static $table_name = "pemesanan_tiket";
    private static $db;
    private static $last_error = null;

    public const ALLOWED_STATUSES = [
        'pending',
        'waiting_payment',
        'paid',
        'confirmed',
        'completed',
        'cancelled',
        'expired',
        'refunded',
        'awaiting_confirmation' // Tambahkan jika ini status valid Anda
    ];
    public const SUCCESSFUL_PAYMENT_STATUSES = ['paid', 'success', 'confirmed'];


    public static function setDbConnection(mysqli $connection)
    {
        self::$db = $connection;
    }

    private static function checkDbConnection(): bool
    {
        self::$last_error = null;
        if (!self::$db || (self::$db instanceof mysqli && self::$db->connect_error)) {
            $error_msg = get_called_class() . " - Koneksi database tidak tersedia atau gagal: " .
                (self::$db instanceof mysqli ? self::$db->connect_error : (self::$db === null ? 'Koneksi DB (self::$db) belum diset.' : 'Koneksi DB bukan objek mysqli.'));
            self::$last_error = $error_msg;
            error_log($error_msg);
            return false;
        }
        return true;
    }

    public static function getLastError(): ?string
    {
        if (self::$last_error) {
            $temp_error = self::$last_error;
            // self::$last_error = null; // Uncomment jika ingin error di-reset setelah diambil
            return $temp_error;
        }
        if (self::$db instanceof mysqli && !empty(self::$db->error)) {
            return "MySQLi Error: " . self::$db->error;
        } elseif (!self::$db || !(self::$db instanceof mysqli)) {
            return 'Koneksi database belum diinisialisasi atau tidak valid untuk kelas ' . get_called_class() . '.';
        }
        return null;
    }

    public static function create(array $data)
    {
        if (!self::checkDbConnection()) {
            return false;
        }

        $user_id = isset($data['user_id']) && !empty($data['user_id']) ? (int)$data['user_id'] : null;
        $nama_pemesan_tamu = ($user_id === null && isset($data['nama_pemesan_tamu'])) ? trim($data['nama_pemesan_tamu']) : null;
        $email_pemesan_tamu = ($user_id === null && isset($data['email_pemesan_tamu'])) ? trim($data['email_pemesan_tamu']) : null;
        $nohp_pemesan_tamu = ($user_id === null && isset($data['nohp_pemesan_tamu'])) ? trim($data['nohp_pemesan_tamu']) : null;

        $kode_pemesanan_to_bind = trim($data['kode_pemesanan'] ?? '');
        if (empty($kode_pemesanan_to_bind)) {
            try {
                $kode_pemesanan_to_bind = 'PT-' . date('Ymd') . strtoupper(bin2hex(random_bytes(4)));
            } catch (Exception $e) {
                $kode_pemesanan_to_bind = 'PT-' . date('YmdHis') . mt_rand(1000, 9999);
                error_log(get_called_class() . "::create() - Peringatan: random_bytes gagal, menggunakan fallback kode pemesanan. Error: " . $e->getMessage());
            }
        }

        $tanggal_kunjungan_to_bind = trim($data['tanggal_kunjungan'] ?? '');
        $total_harga_akhir_to_bind = isset($data['total_harga_akhir']) ? (float)$data['total_harga_akhir'] : 0.0;
        $status_pemesanan_to_bind = strtolower(trim($data['status'] ?? 'pending'));
        $catatan_umum_pemesanan_to_bind = isset($data['catatan_umum_pemesanan']) ? trim($data['catatan_umum_pemesanan']) : null;
        // Kolom baru untuk soft delete, defaultnya akan 0 (atau false) dari DB
        // $dihapus_oleh_user = 0; 

        if (empty($kode_pemesanan_to_bind)) {
            self::$last_error = "Kode pemesanan kosong.";
            return false;
        }
        if (empty($tanggal_kunjungan_to_bind) || !($dtKunjungan = DateTime::createFromFormat('Y-m-d', $tanggal_kunjungan_to_bind)) || $dtKunjungan->format('Y-m-d') !== $tanggal_kunjungan_to_bind) {
            self::$last_error = "Tanggal kunjungan tidak valid.";
            return false;
        }
        if ($user_id === null) {
            if (empty($nama_pemesan_tamu)) {
                self::$last_error = "Nama tamu kosong.";
                return false;
            }
            if (empty($email_pemesan_tamu) || !filter_var($email_pemesan_tamu, FILTER_VALIDATE_EMAIL)) {
                self::$last_error = "Email tamu tidak valid.";
                return false;
            }
            if (empty($nohp_pemesan_tamu)) {
                self::$last_error = "No HP tamu kosong.";
                return false;
            }
        }
        if (!in_array($status_pemesanan_to_bind, self::ALLOWED_STATUSES)) {
            self::$last_error = "Status pemesanan tidak valid: '{$status_pemesanan_to_bind}'";
            return false;
        }

        // Pastikan kolom dihapus_oleh_user ada di tabel jika Anda ingin menyertakannya di INSERT secara eksplisit.
        // Jika kolomnya sudah ada dengan default 0, Anda tidak perlu menyertakannya di sini.
        $sql = "INSERT INTO `" . self::$table_name . "` " .
            " (`user_id`, `nama_pemesan_tamu`, `email_pemesan_tamu`, `nohp_pemesan_tamu`, `kode_pemesanan`, `tanggal_kunjungan`, `total_harga_akhir`, `status`, `catatan_umum_pemesanan`, `created_at`, `updated_at`) 
           VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";
        // Jika ingin menyertakan `dihapus_oleh_user` secara eksplisit:
        // " (`user_id`, ..., `catatan_umum_pemesanan`, `dihapus_oleh_user`, `created_at`, `updated_at`) VALUES (?, ..., ?, 0, NOW(), NOW())"
        // dan sesuaikan `mysqli_stmt_bind_param`

        $stmt = mysqli_prepare(self::$db, $sql);
        if (!$stmt) {
            self::$last_error = "MySQLi Prepare Error: " . mysqli_error(self::$db);
            error_log(get_called_class() . "::create() - " . self::$last_error . " | SQL: " . $sql);
            return false;
        }

        mysqli_stmt_bind_param(
            $stmt,
            "isssssdss", // Jika tambah dihapus_oleh_user, tipe "i" di akhir sebelum timestamp
            $user_id,
            $nama_pemesan_tamu,
            $email_pemesan_tamu,
            $nohp_pemesan_tamu,
            $kode_pemesanan_to_bind,
            $tanggal_kunjungan_to_bind,
            $total_harga_akhir_to_bind,
            $status_pemesanan_to_bind,
            $catatan_umum_pemesanan_to_bind
            // $dihapus_oleh_user // Jika disertakan
        );

        if (mysqli_stmt_execute($stmt)) {
            $new_id = mysqli_insert_id(self::$db);
            mysqli_stmt_close($stmt);
            return $new_id;
        } else {
            self::$last_error = "MySQLi Execute Error: " . mysqli_stmt_error($stmt);
            error_log(get_called_class() . "::create() - " . self::$last_error . " | Untuk kode " . $kode_pemesanan_to_bind);
            mysqli_stmt_close($stmt);
            return false;
        }
    }

    public static function findById(int $id): ?array
    {
        if (!self::checkDbConnection()) return null;
        if ($id <= 0) {
            self::$last_error = "ID tidak valid: " . $id;
            error_log(get_called_class() . "::findById() - " . self::$last_error);
            return null;
        }

        $sql = "SELECT pt.*, u.nama_lengkap AS user_nama_lengkap, u.email AS user_email, u.no_hp as user_no_hp 
                FROM `" . self::$table_name . "` pt
                LEFT JOIN `users` u ON pt.user_id = u.id
                WHERE pt.id = ?";
        $stmt = mysqli_prepare(self::$db, $sql);
        if (!$stmt) {
            self::$last_error = "MySQLi Prepare Error: " . mysqli_error(self::$db);
            error_log(get_called_class() . "::findById() - " . self::$last_error);
            return null;
        }
        mysqli_stmt_bind_param($stmt, "i", $id);
        if (mysqli_stmt_execute($stmt)) {
            $result = mysqli_stmt_get_result($stmt);
            $pemesanan = mysqli_fetch_assoc($result);
            mysqli_stmt_close($stmt);
            return $pemesanan ?: null;
        } else {
            self::$last_error = "MySQLi Execute Error: " . mysqli_stmt_error($stmt);
            error_log(get_called_class() . "::findById() - " . self::$last_error);
            mysqli_stmt_close($stmt);
        }
        return null;
    }

    public static function getAll(string $orderBy = 'pt.created_at DESC'): array
    {
        if (!self::checkDbConnection()) return [];

        $allowed_columns = ['pt.id', 'pt.kode_pemesanan', 'pt.tanggal_kunjungan', 'pt.total_harga_akhir', 'pt.status', 'pt.created_at', 'u.nama_lengkap', 'u.email', 'pt.dihapus_oleh_user'];
        $order_parts = explode(' ', trim($orderBy), 2);
        $column = $order_parts[0];
        $direction = (isset($order_parts[1]) && strtoupper($order_parts[1]) === 'ASC') ? 'ASC' : 'DESC';

        if (!in_array($column, $allowed_columns)) {
            $column = 'pt.created_at';
            $direction = 'DESC';
        }
        $orderBySafe = $column . " " . $direction;

        // Admin melihat semua, termasuk yang di-soft-delete oleh user
        $sql = "SELECT pt.*, u.nama_lengkap AS user_nama_lengkap, u.email AS user_email 
                FROM `" . self::$table_name . "` pt 
                LEFT JOIN `users` u ON pt.user_id = u.id 
                ORDER BY " . $orderBySafe;
        $result = mysqli_query(self::$db, $sql);
        if ($result) {
            $data = mysqli_fetch_all($result, MYSQLI_ASSOC);
            mysqli_free_result($result);
            return $data;
        } else {
            self::$last_error = "MySQLi Query Error: " . mysqli_error(self::$db);
            error_log(get_called_class() . "::getAll() - " . self::$last_error . " | SQL: " . $sql);
            return [];
        }
    }

    public static function getByKodePemesanan(string $kode_pemesanan): ?array
    {
        if (!self::checkDbConnection()) return null;
        $kode = trim($kode_pemesanan);
        if (empty($kode)) {
            self::$last_error = "Kode pemesanan kosong.";
            error_log(get_called_class() . "::getByKodePemesanan() - " . self::$last_error);
            return null;
        }
        // Admin atau sistem mungkin perlu melihat ini meskipun di-soft-delete user, jadi tidak ada filter dihapus_oleh_user
        $sql = "SELECT pt.*, u.nama_lengkap AS user_nama_lengkap, u.email AS user_email, u.no_hp as user_no_hp 
                FROM `" . self::$table_name . "` pt 
                LEFT JOIN `users` u ON pt.user_id = u.id 
                WHERE pt.kode_pemesanan = ?";
        $stmt = mysqli_prepare(self::$db, $sql);
        if (!$stmt) {
            self::$last_error = "MySQLi Prepare Error: " . mysqli_error(self::$db);
            error_log(get_called_class() . "::getByKodePemesanan() - " . self::$last_error);
            return null;
        }
        mysqli_stmt_bind_param($stmt, "s", $kode);
        if (mysqli_stmt_execute($stmt)) {
            $result = mysqli_stmt_get_result($stmt);
            $data = mysqli_fetch_assoc($result);
            mysqli_stmt_close($stmt);
            return $data ?: null;
        } else {
            self::$last_error = "MySQLi Execute Error: " . mysqli_stmt_error($stmt);
            error_log(get_called_class() . "::getByKodePemesanan() - " . self::$last_error);
            mysqli_stmt_close($stmt);
        }
        return null;
    }

    /**
     * Mengambil riwayat pemesanan untuk user, tidak termasuk yang sudah di-"soft delete" oleh user.
     */
    public static function getByUserId(int $user_id, ?int $limit = null): array
    {
        if (!self::checkDbConnection()) return [];
        if ($user_id <= 0) {
            self::$last_error = "User ID tidak valid: " . $user_id;
            error_log(get_called_class() . "::getByUserId() - " . self::$last_error);
            return [];
        }
        // MODIFIKASI: Tambahkan kondisi WHERE dihapus_oleh_user = 0
        $sql = "SELECT pt.*, u.nama_lengkap AS user_nama_lengkap, u.email AS user_email 
                FROM `" . self::$table_name . "` pt 
                LEFT JOIN `users` u ON pt.user_id = u.id 
                WHERE pt.user_id = ? AND pt.dihapus_oleh_user = 0 
                ORDER BY pt.created_at DESC, pt.id DESC";
        $params = [$user_id];
        $types = "i";

        if ($limit !== null && $limit > 0) {
            $sql .= " LIMIT ?";
            $params[] = $limit;
            $types .= "i";
        }
        $stmt = mysqli_prepare(self::$db, $sql);
        if (!$stmt) {
            self::$last_error = "MySQLi Prepare Error: " . mysqli_error(self::$db);
            error_log(get_called_class() . "::getByUserId() - " . self::$last_error);
            return [];
        }
        mysqli_stmt_bind_param($stmt, $types, ...$params);
        if (mysqli_stmt_execute($stmt)) {
            $result = mysqli_stmt_get_result($stmt);
            $data = mysqli_fetch_all($result, MYSQLI_ASSOC);
            mysqli_stmt_close($stmt);
            return $data;
        } else {
            self::$last_error = "MySQLi Execute Error: " . mysqli_stmt_error($stmt);
            error_log(get_called_class() . "::getByUserId() - " . self::$last_error);
            mysqli_stmt_close($stmt);
        }
        return [];
    }

    public static function updateStatusPemesanan(int $id, string $new_status): bool
    {
        if (!self::checkDbConnection()) return false;
        $status_val = strtolower(trim($new_status));
        if ($id <= 0 || empty($status_val) || !in_array($status_val, self::ALLOWED_STATUSES)) {
            self::$last_error = "Input tidak valid untuk update status. ID: " . $id . ", Status: '" . $status_val . "'";
            error_log(get_called_class() . "::updateStatusPemesanan() - " . self::$last_error);
            return false;
        }
        $sql = "UPDATE `" . self::$table_name . "` SET status = ?, updated_at = NOW() WHERE id = ?";
        $stmt = mysqli_prepare(self::$db, $sql);
        if (!$stmt) {
            self::$last_error = "MySQLi Prepare Error: " . mysqli_error(self::$db);
            error_log(get_called_class() . "::updateStatusPemesanan() - " . self::$last_error);
            return false;
        }
        mysqli_stmt_bind_param($stmt, "si", $status_val, $id);
        if (mysqli_stmt_execute($stmt)) {
            $affected_rows = mysqli_stmt_affected_rows($stmt);
            mysqli_stmt_close($stmt);
            return $affected_rows >= 0;
        } else {
            self::$last_error = "MySQLi Execute Error: " . mysqli_stmt_error($stmt);
            error_log(get_called_class() . "::updateStatusPemesanan() - " . self::$last_error);
            mysqli_stmt_close($stmt);
        }
        return false;
    }

    public static function updateTotalHargaAkhir(int $pemesanan_tiket_id, float $total_harga_baru): bool
    {
        if (!self::checkDbConnection()) return false;
        if ($pemesanan_tiket_id <= 0 || $total_harga_baru < 0) {
            self::$last_error = "Input tidak valid untuk update total harga. ID: " . $pemesanan_tiket_id . ", Harga: " . $total_harga_baru;
            error_log(get_called_class() . "::updateTotalHargaAkhir() - " . self::$last_error);
            return false;
        }
        $sql = "UPDATE `" . self::$table_name . "` SET total_harga_akhir = ?, updated_at = NOW() WHERE id = ?";
        $stmt = mysqli_prepare(self::$db, $sql);
        if (!$stmt) {
            self::$last_error = "MySQLi Prepare Error: " . mysqli_error(self::$db);
            error_log(get_called_class() . "::updateTotalHargaAkhir() - " . self::$last_error);
            return false;
        }
        mysqli_stmt_bind_param($stmt, "di", $total_harga_baru, $pemesanan_tiket_id);
        if (mysqli_stmt_execute($stmt)) {
            $affected_rows = mysqli_stmt_affected_rows($stmt);
            mysqli_stmt_close($stmt);
            return $affected_rows >= 0;
        } else {
            self::$last_error = "MySQLi Execute Error: " . mysqli_stmt_error($stmt);
            error_log(get_called_class() . "::updateTotalHargaAkhir() - " . self::$last_error);
            mysqli_stmt_close($stmt);
        }
        return false;
    }

    /**
     * Melakukan hard delete pemesanan dan semua data terkaitnya (detail tiket, sewa, pembayaran).
     * Digunakan oleh Admin.
     */
    public static function delete(int $id): bool
    {
        if (!self::checkDbConnection()) return false;
        if ($id <= 0) {
            self::$last_error = "ID tidak valid untuk dihapus: " . $id;
            error_log(get_called_class() . "::delete() - " . self::$last_error);
            return false;
        }

        mysqli_begin_transaction(self::$db);
        try {
            $child_models_delete_methods = [
                'DetailPemesananTiket' => 'deleteByPemesananTiketId',
                'PemesananSewaAlat' => 'deleteByPemesananTiketId', // Pastikan ada metode 'deleteByPemesananTiketId' di model PemesananSewaAlat
                'Pembayaran' => 'deleteByPemesananId' // atau 'deleteByPemesananTiketId' jika itu nama metodenya
            ];

            foreach ($child_models_delete_methods as $model_name => $method_name) {
                if (class_exists($model_name) && method_exists($model_name, $method_name)) {
                    if (!call_user_func([$model_name, $method_name], $id)) {
                        $child_error = call_user_func([$model_name, 'getLastError']);
                        if ($child_error && strpos(strtolower($child_error), 'tidak ada error') === false && strpos(strtolower($child_error), 'belum diinisialisasi') === false) {
                            throw new Exception("Gagal menghapus data terkait dari {$model_name} untuk Pemesanan ID {$id}: " . $child_error);
                        }
                    }
                } else {
                    error_log(get_called_class() . "::delete() - Peringatan: Model {$model_name} atau metode {$method_name} tidak ditemukan. Data terkait mungkin tidak terhapus.");
                }
            }

            $sql_delete_header = "DELETE FROM `" . self::$table_name . "` WHERE id = ?";
            $stmt_header = mysqli_prepare(self::$db, $sql_delete_header);
            if (!$stmt_header) {
                throw new Exception("Gagal prepare statement hapus header pemesanan: " . mysqli_error(self::$db));
            }
            mysqli_stmt_bind_param($stmt_header, "i", $id);
            if (!mysqli_stmt_execute($stmt_header)) {
                throw new Exception("Gagal execute statement hapus header pemesanan: " . mysqli_stmt_error($stmt_header));
            }
            $affected_rows = mysqli_stmt_affected_rows($stmt_header);
            mysqli_stmt_close($stmt_header);

            // if ($affected_rows <= 0) { // Bisa jadi tidak ada header-nya jika ID salah, atau sudah dihapus
            //     throw new Exception("Tidak ada baris pemesanan yang terhapus untuk ID {$id}. Mungkin ID tidak ditemukan.");
            // }

            mysqli_commit(self::$db);
            return true; // Bahkan jika affected_rows = 0 (misal ID tidak ada), transaksi berhasil
        } catch (Exception $e) {
            if (isset(self::$db) && self::$db->thread_id && mysqli_get_connection_stats(self::$db)['connect_success']) { // Cek koneksi masih ada
                mysqli_rollback(self::$db);
            }
            self::$last_error = "Exception saat menghapus pemesanan: " . $e->getMessage();
            error_log(get_called_class() . "::delete() - " . self::$last_error);
            return false;
        }
    }

    /**
     * Melakukan soft delete pemesanan dari sisi pengguna.
     * Hanya mengubah flag `dihapus_oleh_user` menjadi 1.
     * @param int $pemesanan_id ID pemesanan tiket.
     * @param int $user_id ID pengguna yang melakukan aksi.
     * @return bool True jika berhasil, false jika gagal atau tidak berhak.
     */
    public static function softDeleteByUser(int $pemesanan_id, int $user_id): bool
    {
        if (!self::checkDbConnection()) return false;
        if ($pemesanan_id <= 0 || $user_id <= 0) {
            self::$last_error = "ID pemesanan atau User ID tidak valid untuk soft delete.";
            error_log(get_called_class() . "::softDeleteByUser() - " . self::$last_error . " (PemesananID: {$pemesanan_id}, UserID: {$user_id})");
            return false;
        }

        $sql = "UPDATE `" . self::$table_name . "` 
                SET `dihapus_oleh_user` = 1, `updated_at` = NOW() 
                WHERE `id` = ? AND `user_id` = ?";
        $stmt = mysqli_prepare(self::$db, $sql);
        if (!$stmt) {
            self::$last_error = "MySQLi Prepare Error (softDeleteByUser): " . mysqli_error(self::$db);
            error_log(get_called_class() . "::softDeleteByUser() - " . self::$last_error);
            return false;
        }
        mysqli_stmt_bind_param($stmt, "ii", $pemesanan_id, $user_id);
        if (mysqli_stmt_execute($stmt)) {
            $affected_rows = mysqli_stmt_affected_rows($stmt);
            mysqli_stmt_close($stmt);
            if ($affected_rows > 0) {
                error_log(get_called_class() . "::softDeleteByUser() - Berhasil soft delete untuk Pemesanan ID: {$pemesanan_id} oleh User ID: {$user_id}");
                return true;
            } else {
                // Bisa jadi karena ID tidak cocok dengan user_id, atau sudah di-soft-delete.
                // Untuk memastikan, kita bisa cek dulu apakah recordnya ada dan user_id cocok.
                $existing = self::findById($pemesanan_id);
                if (!$existing) {
                    self::$last_error = "Pemesanan ID {$pemesanan_id} tidak ditemukan.";
                } elseif ((int)$existing['user_id'] !== $user_id) {
                    self::$last_error = "User ID {$user_id} tidak berhak melakukan soft delete pada Pemesanan ID {$pemesanan_id}.";
                } elseif ((int)$existing['dihapus_oleh_user'] === 1) {
                    self::$last_error = "Pemesanan ID {$pemesanan_id} sudah di-soft-delete sebelumnya oleh user.";
                } else {
                    self::$last_error = "Tidak ada baris yang diupdate untuk soft delete Pemesanan ID {$pemesanan_id} oleh User ID {$user_id}. Kemungkinan kondisi WHERE tidak terpenuhi.";
                }
                error_log(get_called_class() . "::softDeleteByUser() - " . self::$last_error);
                return false;
            }
        } else {
            self::$last_error = "MySQLi Execute Error (softDeleteByUser): " . mysqli_stmt_error($stmt);
            error_log(get_called_class() . "::softDeleteByUser() - " . self::$last_error);
            mysqli_stmt_close($stmt);
            return false;
        }
    }


    public static function countByStatus($status_filter): int
    {
        if (!self::checkDbConnection()) return 0;
        $statuses_to_check = is_array($status_filter) ? $status_filter : [$status_filter];
        if (empty($statuses_to_check)) return 0;

        $valid_statuses = array_filter($statuses_to_check, fn($s) => in_array(strtolower(trim($s)), self::ALLOWED_STATUSES));
        if (empty($valid_statuses)) {
            self::$last_error = "Tidak ada status valid yang diberikan: " . print_r($status_filter, true);
            error_log(get_called_class() . "::countByStatus() - " . self::$last_error);
            return 0;
        }

        $placeholders = implode(',', array_fill(0, count($valid_statuses), '?'));
        $types = str_repeat('s', count($valid_statuses));
        // Admin menghitung semua, tidak peduli soft delete user
        $sql = "SELECT COUNT(id) as total FROM `" . self::$table_name . "` WHERE status IN (" . $placeholders . ")";

        $stmt = mysqli_prepare(self::$db, $sql);
        if (!$stmt) {
            self::$last_error = "MySQLi Prepare Error: " . mysqli_error(self::$db);
            error_log(get_called_class() . "::countByStatus() - " . self::$last_error);
            return 0;
        }
        mysqli_stmt_bind_param($stmt, $types, ...$valid_statuses);
        if (mysqli_stmt_execute($stmt)) {
            $result = mysqli_stmt_get_result($stmt);
            $row = mysqli_fetch_assoc($result);
            mysqli_stmt_close($stmt);
            return (int)($row['total'] ?? 0);
        } else {
            self::$last_error = "MySQLi Execute Error: " . mysqli_stmt_error($stmt);
            error_log(get_called_class() . "::countByStatus() - " . self::$last_error);
            mysqli_stmt_close($stmt);
        }
        return 0;
    }

    public static function countAll(): int
    {
        if (!self::checkDbConnection()) return 0;
        // Admin menghitung semua, tidak peduli soft delete user
        $sql = "SELECT COUNT(id) as total FROM `" . self::$table_name . "`";
        $result = mysqli_query(self::$db, $sql);
        if ($result) {
            $row = mysqli_fetch_assoc($result);
            mysqli_free_result($result);
            return (int)($row['total'] ?? 0);
        } else {
            self::$last_error = "MySQLi Query Error: " . mysqli_error(self::$db);
            error_log(get_called_class() . "::countAll() - " . self::$last_error . " | SQL: " . $sql);
        }
        return 0;
    }
}
// PemesananTiket.php