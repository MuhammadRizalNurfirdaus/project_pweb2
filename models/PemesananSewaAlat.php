<?php
// File: C:\xampp\htdocs\Cilengkrang-Web-Wisata\models\PemesananSewaAlat.php

/**
 * Class PemesananSewaAlat
 * Mengelola operasi database untuk tabel pemesanan_sewa_alat.
 */
class PemesananSewaAlat
{
    private static $table_name = "pemesanan_sewa_alat"; // Nama tabel detail item sewa
    private static $db; // Properti untuk menyimpan koneksi database

    // Daftar status item sewa yang diizinkan
    private const ALLOWED_ITEM_STATUSES = ['Dipesan', 'Diambil', 'Dikembalikan', 'Hilang', 'Rusak', 'Dibatalkan'];
    // Daftar satuan durasi yang diizinkan
    private const ALLOWED_DURATION_UNITS = ['Jam', 'Hari', 'Peminjaman'];

    /**
     * Mengatur koneksi database untuk digunakan oleh kelas ini.
     * Metode ini HARUS dipanggil sekali (misalnya dari config.php) sebelum metode lain digunakan.
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
                (self::$db instanceof mysqli ? self::$db->connect_error : (self::$db === null ? 'Koneksi DB (self::$db) belum diset.' : 'Koneksi DB bukan objek mysqli.')));
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
     * Menghitung subtotal untuk satu item sewa alat berdasarkan durasi.
     * @param int $jumlah Jumlah item.
     * @param float $harga_satuan Harga sewa per unit durasi.
     * @param int $durasi_satuan_alat Durasi standar untuk harga satuan (misal, harga untuk 1 hari, 2 jam).
     * @param string $satuan_durasi_alat Satuan dari durasi_satuan_alat ('Jam', 'Hari', 'Peminjaman').
     * @param string $tgl_mulai_sewa Tanggal dan waktu mulai sewa (Y-m-d H:i:s).
     * @param string $tgl_akhir_sewa Tanggal dan waktu akhir sewa (Y-m-d H:i:s).
     * @return float Subtotal harga item sewa.
     */
    public static function calculateSubtotalItem($jumlah, $harga_satuan, $durasi_satuan_alat, $satuan_durasi_alat, $tgl_mulai_sewa, $tgl_akhir_sewa)
    {
        if ($jumlah <= 0 || $harga_satuan < 0) return 0.0;

        $subtotal = $jumlah * $harga_satuan; // Default untuk satuan 'Peminjaman' atau jika tanggal tidak valid

        if (($satuan_durasi_alat === 'Hari' || $satuan_durasi_alat === 'Jam') && $durasi_satuan_alat > 0) {
            try {
                $dtMulai = new DateTime($tgl_mulai_sewa);
                $dtAkhir = new DateTime($tgl_akhir_sewa);

                if ($dtMulai < $dtAkhir) {
                    $interval = $dtMulai->diff($dtAkhir);
                    $faktor_pengali_durasi = 1;

                    if ($satuan_durasi_alat == 'Hari') {
                        $total_hari_aktual = $interval->days;
                        if ($interval->h > 0 || $interval->i > 0 || $interval->s > 0) $total_hari_aktual++;
                        if ($total_hari_aktual == 0 && ($interval->h > 0 || $interval->i > 0 || $interval->s > 0)) $total_hari_aktual = 1;
                        $faktor_pengali_durasi = ceil($total_hari_aktual / max(1, $durasi_satuan_alat));
                    } elseif ($satuan_durasi_alat == 'Jam') {
                        $total_jam_aktual = ($interval->days * 24) + $interval->h;
                        if ($interval->i > 0 || $interval->s > 0) $total_jam_aktual++;
                        if ($total_jam_aktual == 0 && ($interval->i > 0 || $interval->s > 0)) $total_jam_aktual = 1;
                        $faktor_pengali_durasi = ceil($total_jam_aktual / max(1, $durasi_satuan_alat));
                    }
                    $subtotal = $jumlah * $harga_satuan * max(1, $faktor_pengali_durasi);
                }
            } catch (Exception $e) {
                error_log(get_called_class() . "::calculateSubtotalItem() - Exception: " . $e->getMessage());
            }
        }
        return (float)$subtotal;
    }


    /**
     * Membuat record detail pemesanan sewa alat baru.
     * @param array $data Data pemesanan.
     * @return int|false ID record baru atau false jika gagal.
     */
    public static function create($data)
    {
        if (!self::checkDbConnection()) return false;

        $pemesanan_tiket_id = isset($data['pemesanan_tiket_id']) && !empty($data['pemesanan_tiket_id']) ? (int)$data['pemesanan_tiket_id'] : 0;
        $sewa_alat_id = isset($data['sewa_alat_id']) ? (int)$data['sewa_alat_id'] : 0;
        $jumlah = isset($data['jumlah']) ? (int)$data['jumlah'] : 0;
        $harga_satuan = isset($data['harga_satuan_saat_pesan']) ? (float)$data['harga_satuan_saat_pesan'] : 0.0;
        $durasi_satuan = isset($data['durasi_satuan_saat_pesan']) ? (int)$data['durasi_satuan_saat_pesan'] : 1;
        $satuan_durasi = trim($data['satuan_durasi_saat_pesan'] ?? '');
        $tgl_mulai = trim($data['tanggal_mulai_sewa'] ?? '');
        $tgl_akhir = trim($data['tanggal_akhir_sewa_rencana'] ?? '');
        $status_item_sewa = $data['status_item_sewa'] ?? 'Dipesan';
        $catatan = $data['catatan_item_sewa'] ?? null;
        $denda = isset($data['denda']) ? (float)$data['denda'] : 0.0;

        if ($pemesanan_tiket_id <= 0) {
            error_log(get_called_class() . "::create() - pemesanan_tiket_id tidak valid.");
            return false;
        }
        if ($sewa_alat_id <= 0) {
            error_log(get_called_class() . "::create() - sewa_alat_id tidak valid.");
            return false;
        }
        if ($jumlah <= 0) {
            error_log(get_called_class() . "::create() - jumlah harus lebih dari 0.");
            return false;
        }
        if ($harga_satuan < 0) {
            error_log(get_called_class() . "::create() - harga_satuan tidak boleh negatif.");
            return false;
        }
        if ($durasi_satuan <= 0 && $satuan_durasi !== 'Peminjaman') {
            error_log(get_called_class() . "::create() - durasi_satuan harus > 0 kecuali 'Peminjaman'.");
            return false;
        }
        if (empty($satuan_durasi) || !in_array($satuan_durasi, self::ALLOWED_DURATION_UNITS)) {
            error_log(get_called_class() . "::create() - satuan_durasi tidak valid: " . $satuan_durasi);
            return false;
        }

        $dtMulaiValid = DateTime::createFromFormat('Y-m-d H:i:s', $tgl_mulai) ?: DateTime::createFromFormat('Y-m-d', $tgl_mulai);
        if (!$dtMulaiValid) {
            error_log(get_called_class() . "::create() - tanggal_mulai_sewa tidak valid: " . $tgl_mulai);
            return false;
        }

        $dtAkhirValid = DateTime::createFromFormat('Y-m-d H:i:s', $tgl_akhir) ?: DateTime::createFromFormat('Y-m-d', $tgl_akhir);
        if (!$dtAkhirValid) {
            error_log(get_called_class() . "::create() - tanggal_akhir_sewa_rencana tidak valid: " . $tgl_akhir);
            return false;
        }

        if ($dtMulaiValid >= $dtAkhirValid) {
            error_log(get_called_class() . "::create() - Tanggal mulai harus sebelum tanggal akhir.");
            return false;
        }
        if (!in_array($status_item_sewa, self::ALLOWED_ITEM_STATUSES)) {
            error_log(get_called_class() . "::create() - status_item_sewa tidak valid: " . $status_item_sewa);
            return false;
        }

        $total_harga_item = self::calculateSubtotalItem($jumlah, $harga_satuan, $durasi_satuan, $satuan_durasi, $dtMulaiValid->format('Y-m-d H:i:s'), $dtAkhirValid->format('Y-m-d H:i:s'));

        $sql = "INSERT INTO " . self::$table_name . "
                    (pemesanan_tiket_id, sewa_alat_id, jumlah, harga_satuan_saat_pesan,
                     durasi_satuan_saat_pesan, satuan_durasi_saat_pesan, tanggal_mulai_sewa,
                     tanggal_akhir_sewa_rencana, total_harga_item, status_item_sewa,
                     catatan_item_sewa, denda, created_at, updated_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";

        $stmt = mysqli_prepare(self::$db, $sql);
        if (!$stmt) {
            error_log(get_called_class() . "::create() - MySQLi Prepare Error: " . mysqli_error(self::$db));
            return false;
        }

        mysqli_stmt_bind_param(
            $stmt,
            "iiidissssdsd",
            $pemesanan_tiket_id,
            $sewa_alat_id,
            $jumlah,
            $harga_satuan,
            $durasi_satuan,
            $satuan_durasi,
            $tgl_mulai,
            $tgl_akhir,
            $total_harga_item,
            $status_item_sewa,
            $catatan,
            $denda
        );

        if (mysqli_stmt_execute($stmt)) {
            $new_id = mysqli_insert_id(self::$db);
            mysqli_stmt_close($stmt);

            if (class_exists('SewaAlat') && method_exists('SewaAlat', 'updateStok')) {
                // Pemanggilan SewaAlat::init() seharusnya sudah dilakukan di config.php
                if (!SewaAlat::updateStok($sewa_alat_id, -$jumlah)) {
                    error_log(get_called_class() . "::create() - Peringatan: Gagal update stok untuk alat ID {$sewa_alat_id}.");
                }
            } else {
                error_log(get_called_class() . "::create() - Peringatan: Model/Metode SewaAlat::updateStok tidak tersedia.");
            }
            return $new_id;
        } else {
            error_log(get_called_class() . "::create() - MySQLi Execute Error: " . mysqli_stmt_error($stmt));
            mysqli_stmt_close($stmt);
            return false;
        }
    }

    // ... (Metode getAll, getById, getByPemesananTiketId, update, updateStatus, delete, countByStatus, countAll) ...
    // SEMUA METODE LAIN DI SINI TETAP SAMA SEPERTI REVISI SEBELUMNYA,
    // PASTIKAN MEREKA MENGGUNAKAN self::$db dan self::checkDbConnection()
    // dan TIDAK ADA lagi pemanggilan `SewaAlat::setDbConnection(self::$db);`
    // Contoh untuk getAll:
    public static function getAll()
    {
        if (!self::checkDbConnection()) return [];
        $sql = "SELECT psa.*, sa.nama_item AS nama_alat, pt.kode_pemesanan AS kode_pemesanan_tiket, COALESCE(u.nama_lengkap, pt.nama_pemesan_tamu) AS nama_pemesan, pt.user_id AS id_user_pemesan_tiket FROM " . self::$table_name . " psa INNER JOIN sewa_alat sa ON psa.sewa_alat_id = sa.id INNER JOIN pemesanan_tiket pt ON psa.pemesanan_tiket_id = pt.id LEFT JOIN users u ON pt.user_id = u.id ORDER BY psa.created_at DESC, psa.id DESC";
        $result = mysqli_query(self::$db, $sql);
        if ($result === false) {
            error_log(get_called_class() . "::getAll() - MySQLi Query Error: " . mysqli_error(self::$db));
            return [];
        }
        $data = mysqli_fetch_all($result, MYSQLI_ASSOC);
        mysqli_free_result($result);
        return $data;
    }
    public static function getById($id)
    {
        if (!self::checkDbConnection()) return null;
        $id_val = filter_var($id, FILTER_VALIDATE_INT);
        if ($id_val === false || $id_val <= 0) {
            error_log(get_called_class() . "::getById() - ID tidak valid: " . $id);
            return null;
        }
        $sql = "SELECT psa.*, sa.nama_item AS nama_alat, sa.harga_sewa AS harga_sewa_terkini_alat, sa.satuan_durasi_harga AS satuan_durasi_alat, sa.stok_tersedia, pt.kode_pemesanan AS kode_pemesanan_tiket, COALESCE(u.nama_lengkap, pt.nama_pemesan_tamu) AS nama_pemesan, COALESCE(u.email, pt.email_pemesan_tamu) AS email_pemesan, COALESCE(u.no_hp, pt.nohp_pemesan_tamu) AS nohp_pemesan, pt.user_id AS id_user_pemesan_tiket FROM " . self::$table_name . " psa JOIN sewa_alat sa ON psa.sewa_alat_id = sa.id JOIN pemesanan_tiket pt ON psa.pemesanan_tiket_id = pt.id LEFT JOIN users u ON pt.user_id = u.id WHERE psa.id = ?";
        $stmt = mysqli_prepare(self::$db, $sql);
        if (!$stmt) {
            error_log(get_called_class() . "::getById() - Prepare Error: " . mysqli_error(self::$db));
            return null;
        }
        mysqli_stmt_bind_param($stmt, "i", $id_val);
        if (mysqli_stmt_execute($stmt)) {
            $result = mysqli_stmt_get_result($stmt);
            $pesanan = mysqli_fetch_assoc($result);
            mysqli_stmt_close($stmt);
            return $pesanan ?: null;
        } else {
            error_log(get_called_class() . "::getById() - Execute Error: " . mysqli_stmt_error($stmt));
        }
        mysqli_stmt_close($stmt);
        return null;
    }
    public static function getByPemesananTiketId($pemesanan_tiket_id)
    {
        if (!self::checkDbConnection()) return [];
        $id_val = filter_var($pemesanan_tiket_id, FILTER_VALIDATE_INT);
        if ($id_val === false || $id_val <= 0) {
            error_log(get_called_class() . "::getByPemesananTiketId() - ID tidak valid: " . $pemesanan_tiket_id);
            return [];
        }
        $sql = "SELECT psa.*, sa.nama_item AS nama_alat FROM " . self::$table_name . " psa JOIN sewa_alat sa ON psa.sewa_alat_id = sa.id WHERE psa.pemesanan_tiket_id = ? ORDER BY psa.id ASC";
        $stmt = mysqli_prepare(self::$db, $sql);
        if (!$stmt) {
            error_log(get_called_class() . "::getByPemesananTiketId() - Prepare Error: " . mysqli_error(self::$db));
            return [];
        }
        mysqli_stmt_bind_param($stmt, "i", $id_val);
        if (mysqli_stmt_execute($stmt)) {
            $result = mysqli_stmt_get_result($stmt);
            $data = mysqli_fetch_all($result, MYSQLI_ASSOC);
            mysqli_stmt_close($stmt);
            return $data;
        } else {
            error_log(get_called_class() . "::getByPemesananTiketId() - Execute Error: " . mysqli_stmt_error($stmt));
        }
        mysqli_stmt_close($stmt);
        return [];
    }
    public static function update($data)
    {
        if (!self::checkDbConnection() || !isset($data['id'])) {
            error_log(get_called_class() . "::update() - Koneksi DB gagal atau ID tidak disertakan.");
            return false;
        }
        $id = (int)$data['id'];
        if ($id <= 0) {
            error_log(get_called_class() . "::update() - ID tidak valid: " . $data['id']);
            return false;
        }
        $currentData = self::getById($id);
        if (!$currentData) {
            error_log(get_called_class() . "::update() - Data pemesanan sewa ID {$id} tidak ditemukan.");
            return false;
        }
        $catatan = $data['catatan_item_sewa'] ?? $currentData['catatan_item_sewa'];
        $denda = isset($data['denda']) ? (float)$data['denda'] : (float)$currentData['denda'];
        $sql = "UPDATE " . self::$table_name . " SET catatan_item_sewa = ?, denda = ?, updated_at = NOW() WHERE id = ?";
        $stmt = mysqli_prepare(self::$db, $sql);
        if (!$stmt) {
            error_log(get_called_class() . "::update() - Prepare Error: " . mysqli_error(self::$db));
            return false;
        }
        mysqli_stmt_bind_param($stmt, "sdi", $catatan, $denda, $id);
        if (mysqli_stmt_execute($stmt)) {
            $affected_rows = mysqli_stmt_affected_rows($stmt);
            mysqli_stmt_close($stmt);
            return $affected_rows >= 0;
        } else {
            error_log(get_called_class() . "::update() - Execute Error: " . mysqli_stmt_error($stmt));
        }
        mysqli_stmt_close($stmt);
        return false;
    }
    public static function updateStatus($id, $newStatus)
    {
        if (!self::checkDbConnection()) return false;
        $id_val = filter_var($id, FILTER_VALIDATE_INT);
        $cleanNewStatus = trim($newStatus);
        if ($id_val === false || $id_val <= 0 || !in_array($cleanNewStatus, self::ALLOWED_ITEM_STATUSES)) {
            error_log(get_called_class() . "::updateStatus() - Input tidak valid. ID: " . $id . ", Status: " . $newStatus);
            return false;
        }
        $pemesananInfo = self::getById($id_val);
        if (!$pemesananInfo) {
            error_log(get_called_class() . "::updateStatus() - Tidak dapat menemukan data pemesanan sewa ID: " . $id_val);
            return false;
        }
        $oldStatus = $pemesananInfo['status_item_sewa'] ?? null;
        $sql = "UPDATE " . self::$table_name . " SET status_item_sewa = ?, updated_at = NOW() WHERE id = ?";
        $stmt = mysqli_prepare(self::$db, $sql);
        if (!$stmt) {
            error_log(get_called_class() . "::updateStatus() - Prepare Error: " . mysqli_error(self::$db));
            return false;
        }
        mysqli_stmt_bind_param($stmt, "si", $cleanNewStatus, $id_val);
        if (mysqli_stmt_execute($stmt)) {
            $affected_rows_update = mysqli_stmt_affected_rows($stmt);
            mysqli_stmt_close($stmt);
            if ($oldStatus !== $cleanNewStatus) {
                if (class_exists('SewaAlat') && method_exists('SewaAlat', 'updateStok')) {
                    $jumlah_alat = (int)$pemesananInfo['jumlah'];
                    $sewa_alat_id = (int)$pemesananInfo['sewa_alat_id'];
                    $stokChange = 0;
                    $statusMengurangiStok = ['Diambil', 'Hilang', 'Rusak'];
                    $statusMenambahStokDariPenggunaan = ['Dikembalikan', 'Dibatalkan'];
                    $statusAwalPemesanan = ['Dipesan'];
                    if (in_array($oldStatus, $statusAwalPemesanan) && in_array($cleanNewStatus, $statusMengurangiStok)) {
                        $stokChange = -$jumlah_alat;
                    } elseif (in_array($oldStatus, $statusMengurangiStok) && in_array($cleanNewStatus, $statusMenambahStokDariPenggunaan)) {
                        $stokChange = $jumlah_alat;
                    } elseif (in_array($oldStatus, $statusAwalPemesanan) && $cleanNewStatus === 'Dibatalkan') {
                        $stokChange = $jumlah_alat;
                    }
                    if ($stokChange !== 0) {
                        if (!SewaAlat::updateStok($sewa_alat_id, $stokChange)) {
                            error_log(get_called_class() . "::updateStatus() - Peringatan: Gagal update stok alat ID {$sewa_alat_id} sejumlah {$stokChange}.");
                        } else {
                            error_log(get_called_class() . "::updateStatus() - Info: Stok alat ID {$sewa_alat_id} diubah {$stokChange}.");
                        }
                    }
                } else {
                    error_log(get_called_class() . "::updateStatus() - Peringatan: Model/Metode SewaAlat::updateStok tidak tersedia.");
                }
            }
            return $affected_rows_update >= 0;
        } else {
            error_log(get_called_class() . "::updateStatus() - Execute Error: " . mysqli_stmt_error($stmt));
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
        $pemesananInfo = self::getById($id_val);
        $sql = "DELETE FROM " . self::$table_name . " WHERE id = ?";
        $stmt = mysqli_prepare(self::$db, $sql);
        if (!$stmt) {
            error_log(get_called_class() . "::delete() - Prepare Error: " . mysqli_error(self::$db));
            return false;
        }
        mysqli_stmt_bind_param($stmt, "i", $id_val);
        if (mysqli_stmt_execute($stmt)) {
            $affected_rows = mysqli_stmt_affected_rows($stmt);
            mysqli_stmt_close($stmt);
            if ($affected_rows > 0 && $pemesananInfo) {
                $statusYangMengurangiStokSaatDibuat = ['Dipesan', 'Diambil', 'Hilang', 'Rusak'];
                if (in_array($pemesananInfo['status_item_sewa'], $statusYangMengurangiStokSaatDibuat)) {
                    if (class_exists('SewaAlat') && method_exists('SewaAlat', 'updateStok')) {
                        if (!SewaAlat::updateStok((int)$pemesananInfo['sewa_alat_id'], (int)$pemesananInfo['jumlah'])) {
                            error_log(get_called_class() . "::delete() - Peringatan: Gagal mengembalikan stok untuk alat ID " . $pemesananInfo['sewa_alat_id']);
                        }
                    } else {
                        error_log(get_called_class() . "::delete() - Peringatan: Model/Metode SewaAlat::updateStok tidak tersedia untuk mengembalikan stok.");
                    }
                }
            }
            return $affected_rows > 0;
        } else {
            error_log(get_called_class() . "::delete() - Execute Error: " . mysqli_stmt_error($stmt));
        }
        mysqli_stmt_close($stmt);
        return false;
    }
    public static function countByStatus($status_item_sewa)
    {
        if (!self::checkDbConnection()) return 0;
        $statuses_to_check = is_array($status_item_sewa) ? $status_item_sewa : [$status_item_sewa];
        if (empty($statuses_to_check)) return 0;
        $valid_statuses = array_filter($statuses_to_check, fn($s) => in_array(trim($s), self::ALLOWED_ITEM_STATUSES));
        if (empty($valid_statuses)) {
            error_log(get_called_class() . "::countByStatus() - Tidak ada status valid yang diberikan: " . print_r($status_item_sewa, true));
            return 0;
        }
        $placeholders = implode(',', array_fill(0, count($valid_statuses), '?'));
        $types = str_repeat('s', count($valid_statuses));
        $sql = "SELECT COUNT(id) as total FROM " . self::$table_name . " WHERE status_item_sewa IN (" . $placeholders . ")";
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
} // End of class PemesananSewaAlat