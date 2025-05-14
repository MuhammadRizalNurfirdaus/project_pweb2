<?php
// File: C:\xampp\htdocs\Cilengkrang-Web-Wisata\models\PemesananTiket.php

class PemesananTiket
{
    private static $table_name = "pemesanan_tiket";
    private static $db;

    public const ALLOWED_STATUSES = [
        'pending',
        'waiting_payment',
        'paid',
        'confirmed',
        'completed',
        'cancelled',
        'expired',
        'refunded'
    ];
    // Tambahkan ini jika belum ada, untuk sinkronisasi dengan PembayaranController
    public const SUCCESSFUL_PAYMENT_STATUSES = ['paid', 'success', 'confirmed'];


    public static function setDbConnection(mysqli $connection)
    {
        self::$db = $connection;
    }

    private static function checkDbConnection()
    {
        if (!self::$db || (self::$db instanceof mysqli && self::$db->connect_error)) {
            error_log(get_called_class() . " - Koneksi database tidak tersedia atau gagal: " .
                (self::$db instanceof mysqli ? self::$db->connect_error : (self::$db === null ? 'Koneksi belum diset.' : 'Koneksi bukan objek mysqli.')));
            return false;
        }
        return true;
    }

    public static function getLastError()
    {
        if (self::$db instanceof mysqli && !empty(self::$db->error)) {
            return self::$db->error;
        } elseif (!self::$db || !(self::$db instanceof mysqli)) {
            return 'Koneksi database belum diinisialisasi atau tidak valid untuk kelas ' . get_called_class() . '.';
        }
        return 'Tidak ada error database spesifik yang dilaporkan dari model ' . get_called_class() . '.';
    }

    public static function create($data)
    {
        if (!self::checkDbConnection()) {
            error_log(get_called_class() . "::create() - Gagal: Koneksi DB tidak valid di awal.");
            return false;
        }

        // Log koneksi aktif sebelum operasi penting
        if (self::$db instanceof mysqli && !self::$db->connect_error) {
            $dbNameQueryTest = null;
            $active_db_name_test = 'TIDAK DAPAT DIAMBIL (query gagal)';
            try {
                $dbNameQueryTest = self::$db->query("SELECT DATABASE()");
                if ($dbNameQueryTest) {
                    $dbNameRowTest = $dbNameQueryTest->fetch_row();
                    $active_db_name_test = $dbNameRowTest[0] ?? 'NULL DARI FETCH';
                    $dbNameQueryTest->close();
                }
            } catch (Exception $e) {
                error_log(get_called_class() . "::create() - Exception saat get DATABASE(): " . $e->getMessage());
            }
            error_log(get_called_class() . "::create() - Menggunakan koneksi ke DB: " . $active_db_name_test . " | Host: " . (self::$db->host_info ?? 'N/A'));
        } else {
            error_log(get_called_class() . "::create() - self::\$db BUKAN koneksi mysqli yang valid atau ada connect_error sebelum prepare.");
            return false; // Tambahkan return false jika koneksi ternyata bermasalah di sini
        }

        // Ambil dan proses data dari array $data
        $user_id = isset($data['user_id']) && !empty($data['user_id']) ? (int)$data['user_id'] : null;
        $nama_pemesan_tamu = ($user_id === null && isset($data['nama_pemesan_tamu'])) ? trim($data['nama_pemesan_tamu']) : null;
        $email_pemesan_tamu = ($user_id === null && isset($data['email_pemesan_tamu'])) ? trim($data['email_pemesan_tamu']) : null;
        $nohp_pemesan_tamu = ($user_id === null && isset($data['nohp_pemesan_tamu'])) ? trim($data['nohp_pemesan_tamu']) : null;

        $kode_pemesanan_to_bind = trim($data['kode_pemesanan'] ?? ('PT' . date('Ymd') . strtoupper(bin2hex(random_bytes(4)))));
        $tanggal_kunjungan_to_bind = trim($data['tanggal_kunjungan'] ?? '');
        $total_harga_akhir_to_bind = isset($data['total_harga_akhir']) ? (float)$data['total_harga_akhir'] : 0.0;
        $status_pemesanan_to_bind = strtolower(trim($data['status'] ?? 'pending'));
        $catatan_umum_pemesanan_to_bind = isset($data['catatan_umum_pemesanan']) ? trim($data['catatan_umum_pemesanan']) : null;

        // Validasi Input Penting
        if (empty($kode_pemesanan_to_bind)) {
            error_log(get_called_class() . "::create() - Validasi Gagal: Kode pemesanan kosong setelah diproses.");
            return false;
        }
        if (
            empty($tanggal_kunjungan_to_bind) ||
            !($dtKunjungan = DateTime::createFromFormat('Y-m-d', $tanggal_kunjungan_to_bind)) ||
            $dtKunjungan->format('Y-m-d') !== $tanggal_kunjungan_to_bind
        ) {
            error_log(get_called_class() . "::create() - Validasi Gagal: Tanggal kunjungan tidak valid: '" . $tanggal_kunjungan_to_bind . "'");
            return false;
        }
        if ($user_id === null) {
            if (empty($nama_pemesan_tamu)) {
                error_log(get_called_class() . "::create() - Validasi Gagal: Nama tamu kosong.");
                return false;
            }
            if (empty($email_pemesan_tamu) || !filter_var($email_pemesan_tamu, FILTER_VALIDATE_EMAIL)) {
                error_log(get_called_class() . "::create() - Validasi Gagal: Email tamu tidak valid: '" . $email_pemesan_tamu . "'");
                return false;
            }
            if (empty($nohp_pemesan_tamu)) {
                error_log(get_called_class() . "::create() - Validasi Gagal: No HP tamu kosong.");
                return false;
            }
        }
        if (!in_array($status_pemesanan_to_bind, self::ALLOWED_STATUSES)) {
            error_log(get_called_class() . "::create() - Validasi Gagal: Status pemesanan tidak valid: '" . $status_pemesanan_to_bind . "'");
            return false;
        }
        // --- AKHIR VALIDASI INPUT PENTING ---

        $sql = "INSERT INTO `" . self::$table_name . "` " .
            " (`user_id`, `nama_pemesan_tamu`, `email_pemesan_tamu`, `nohp_pemesan_tamu`, `kode_pemesanan`, `tanggal_kunjungan`, `total_harga_akhir`, `status`, `catatan_umum_pemesanan`, `created_at`, `updated_at`) 
           VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";

        error_log("PemesananTiket::create() - SQL String yang akan di-prepare: " . $sql); // <--- PASTIKAN INI ADA

        $stmt = mysqli_prepare(self::$db, $sql);
        if (!$stmt) {
            // INI PENTING JIKA PREPARE GAGAL
            error_log(get_called_class() . "::create() - MySQLi Prepare Error: " . mysqli_error(self::$db) . " | Attempted SQL: " . $sql);
            return false;
        }
        error_log(get_called_class() . "::create() - MySQLi Prepare SUKSES.");

        mysqli_stmt_bind_param(
            $stmt,
            "isssssdss",
            $user_id, // Pastikan variabel ini sudah didefinisikan dengan benar di atas
            $nama_pemesan_tamu, // dan seterusnya untuk semua variabel bind
            $email_pemesan_tamu,
            $nohp_pemesan_tamu,
            $kode_pemesanan_to_bind,
            $tanggal_kunjungan_to_bind,
            $total_harga_akhir_to_bind,
            $status_pemesanan_to_bind,
            $catatan_umum_pemesanan_to_bind
        );
        error_log(get_called_class() . "::create() - MySQLi Bind Param SUKSES.");


        if (mysqli_stmt_execute($stmt)) {
            $new_id = mysqli_insert_id(self::$db);
            mysqli_stmt_close($stmt);
            error_log(get_called_class() . "::create() - MySQLi Execute SUKSES. ID baru: " . $new_id);
            return $new_id;
        } else {
            // INI PENTING JIKA EXECUTE GAGAL
            error_log(get_called_class() . "::create() - MySQLi Execute Error: " . mysqli_stmt_error($stmt) . " | Query yang disiapkan (struktur): " . $sql);
            mysqli_stmt_close($stmt);
            return false;
        }
    }

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

    public static function getAll()
    {
        if (!self::checkDbConnection()) return [];
        // Pastikan kolom 'kode_pemesanan' ada dan benar ejaannya
        $sql = "SELECT pt.id, pt.user_id, pt.nama_pemesan_tamu, pt.email_pemesan_tamu, pt.nohp_pemesan_tamu, pt.kode_pemesanan, pt.tanggal_kunjungan, pt.total_harga_akhir, pt.status, pt.catatan_umum_pemesanan, pt.created_at, pt.updated_at, u.nama_lengkap AS user_nama_lengkap, u.email AS user_email 
                FROM " . self::$table_name . " pt 
                LEFT JOIN users u ON pt.user_id = u.id 
                ORDER BY pt.created_at DESC, pt.id DESC";
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
        // Pastikan kolom 'kode_pemesanan' ada dan benar ejaannya
        $sql = "SELECT pt.*, u.nama_lengkap AS user_nama_lengkap, u.email AS user_email, u.no_hp as user_no_hp 
                FROM " . self::$table_name . " pt 
                LEFT JOIN users u ON pt.user_id = u.id 
                WHERE pt.kode_pemesanan = ?";
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
        $sql = "SELECT pt.*, u.nama_lengkap AS user_nama_lengkap, u.email AS user_email 
                FROM " . self::$table_name . " pt 
                LEFT JOIN users u ON pt.user_id = u.id 
                WHERE pt.user_id = ? 
                ORDER BY pt.created_at DESC, pt.id DESC";
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
            return $affected_rows >= 0; // Berhasil jika 0 baris terpengaruh (data sama) atau lebih
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
        if ($id_val === false || $id_val <= 0 || $harga_val === false) { // harga 0.00 valid
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
            // Hapus dari tabel anak terlebih dahulu
            if (class_exists('DetailPemesananTiket') && method_exists('DetailPemesananTiket', 'deleteByPemesananTiketId')) {
                if (!DetailPemesananTiket::deleteByPemesananTiketId($id_val)) {
                    // Tidak melempar exception jika tidak ada detail, tapi log jika ada error DB dari model tsb
                    $dptError = DetailPemesananTiket::getLastError();
                    if ($dptError && strpos(strtolower($dptError), 'tidak ada error') === false) { // Cek jika ada error DB riil
                        throw new Exception("Gagal menghapus detail pemesanan tiket terkait: " . $dptError);
                    }
                }
            }
            if (class_exists('PemesananSewaAlat') && method_exists('PemesananSewaAlat', 'deleteByPemesananTiketId')) {
                if (!PemesananSewaAlat::delete($id_val)) { // Asumsi metode ini ada
                    $psaError = PemesananSewaAlat::getLastError();
                    if ($psaError && strpos(strtolower($psaError), 'tidak ada error') === false) {
                        throw new Exception("Gagal menghapus detail pemesanan sewa terkait: " . $psaError);
                    }
                }
            }
            if (class_exists('Pembayaran') && method_exists('Pembayaran', 'deleteByPemesananId')) {
                if (!Pembayaran::delete($id_val)) { // Asumsi metode ini ada
                    $pembayaranError = Pembayaran::getLastError();
                    if ($pembayaranError && strpos(strtolower($pembayaranError), 'tidak ada error') === false) {
                        throw new Exception("Gagal menghapus pembayaran terkait: " . $pembayaranError);
                    }
                }
            }

            // Hapus header pemesanan
            $sql_delete_header = "DELETE FROM " . self::$table_name . " WHERE id = ?";
            $stmt_header = mysqli_prepare(self::$db, $sql_delete_header);
            if (!$stmt_header) {
                throw new Exception("Gagal prepare statement hapus header pemesanan: " . mysqli_error(self::$db));
            }
            mysqli_stmt_bind_param($stmt_header, "i", $id_val);
            if (!mysqli_stmt_execute($stmt_header)) {
                throw new Exception("Gagal execute statement hapus header pemesanan: " . mysqli_stmt_error($stmt_header));
            }
            $affected_rows = mysqli_stmt_affected_rows($stmt_header);
            mysqli_stmt_close($stmt_header);

            mysqli_commit(self::$db);
            return $affected_rows > 0;
        } catch (Exception $e) {
            if (self::$db->thread_id) { // Cek apakah koneksi masih ada
                mysqli_rollback(self::$db);
            }
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
}
