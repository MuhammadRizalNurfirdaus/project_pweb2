<?php
// File: C:\xampp\htdocs\Cilengkrang-Web-Wisata\models\PemesananSewaAlat.php

/**
 * Class PemesananSewaAlat
 * Mengelola operasi database untuk tabel pemesanan_sewa_alat.
 * Menggunakan pendekatan statis dan koneksi global $conn.
 */
class PemesananSewaAlat
{
    private static $table_name = "pemesanan_sewa_alat";

    /**
     * Membuat record pemesanan sewa alat baru.
     * @param array $data Data pemesanan.
     * Kunci yang diharapkan: 'pemesanan_tiket_id', 'sewa_alat_id', 'jumlah',
     * 'harga_satuan_saat_pesan', 'durasi_satuan_saat_pesan', 'satuan_durasi_saat_pesan',
     * 'tanggal_mulai_sewa', 'tanggal_akhir_sewa_rencana'.
     * @return int|false ID pemesanan baru atau false jika gagal.
     */
    public static function create($data)
    {
        global $conn;
        if (!$conn) {
            error_log("PemesananSewaAlat::create() - Koneksi database gagal.");
            return false;
        }

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

        if ($pemesanan_tiket_id <= 0 || $sewa_alat_id <= 0 || $jumlah <= 0 || $harga_satuan < 0 || $durasi_satuan <= 0 || empty($satuan_durasi) || empty($tgl_mulai) || empty($tgl_akhir)) {
            error_log("PemesananSewaAlat::create() - Error: Data input dasar tidak valid (ID tiket, ID alat, jumlah, harga, durasi, satuan, atau tanggal).");
            return false;
        }
        $allowed_satuan = ['Jam', 'Hari', 'Peminjaman'];
        if (!in_array($satuan_durasi, $allowed_satuan)) {
            error_log("PemesananSewaAlat::create() - Satuan durasi tidak valid: " . e($satuan_durasi));
            return false;
        }
        $allowed_status = ['Dipesan', 'Diambil', 'Dikembalikan', 'Hilang', 'Rusak', 'Dibatalkan'];
        if (!in_array($status_item_sewa, $allowed_status)) {
            error_log("PemesananSewaAlat::create() - Status item sewa tidak valid: " . e($status_item_sewa));
            return false;
        }

        $total_harga_item = $jumlah * $harga_satuan;
        if (($satuan_durasi === 'Hari' || $satuan_durasi === 'Jam') && $durasi_satuan > 0 && !empty($tgl_mulai) && !empty($tgl_akhir)) {
            try {
                $dtMulai = new DateTime($tgl_mulai);
                $dtAkhir = new DateTime($tgl_akhir);
                if ($dtMulai >= $dtAkhir) {
                    error_log("PemesananSewaAlat::create() - Tanggal mulai sewa harus sebelum tanggal akhir sewa.");
                } else {
                    $interval = $dtMulai->diff($dtAkhir);
                    $faktor_pengali_durasi = 1;
                    if ($satuan_durasi == 'Hari') {
                        $total_hari = $interval->days;
                        if ($interval->h > 0 || $interval->i > 0 || $interval->s > 0) $total_hari++;
                        $faktor_pengali_durasi = ceil($total_hari / max(1, $durasi_satuan));
                    } elseif ($satuan_durasi == 'Jam') {
                        $total_jam = ($interval->days * 24) + $interval->h;
                        if ($interval->i > 0 || $interval->s > 0) $total_jam++;
                        $faktor_pengali_durasi = ceil($total_jam / max(1, $durasi_satuan));
                    }
                    $total_harga_item = $jumlah * $harga_satuan * $faktor_pengali_durasi;
                }
            } catch (Exception $e) {
                error_log("PemesananSewaAlat::create() - Error kalkulasi durasi/harga: " . $e->getMessage());
            }
        }

        // Tabel pemesanan_sewa_alat TIDAK ada user_id, jadi dihilangkan dari INSERT
        $sql = "INSERT INTO " . self::$table_name . "
                    (pemesanan_tiket_id, sewa_alat_id, jumlah, harga_satuan_saat_pesan,
                     durasi_satuan_saat_pesan, satuan_durasi_saat_pesan, tanggal_mulai_sewa,
                     tanggal_akhir_sewa_rencana, total_harga_item, status_item_sewa,
                     catatan_item_sewa, denda, created_at, updated_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())"; // 12 placeholders

        $stmt = mysqli_prepare($conn, $sql);
        if (!$stmt) {
            error_log("PemesananSewaAlat::create() - MySQLi Prepare Error: " . mysqli_error($conn) . " | SQL: " . $sql);
            return false;
        }
        // Tipe data: i, i, i, d, i, s, s, s, d, s, s, d
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
            $new_id = mysqli_insert_id($conn);
            mysqli_stmt_close($stmt);
            require_once __DIR__ . '/SewaAlat.php';
            if (class_exists('SewaAlat') && method_exists('SewaAlat', 'updateStok')) {
                if (!SewaAlat::updateStok($sewa_alat_id, -$jumlah)) {
                    error_log("PemesananSewaAlat::create() - Warning: Gagal update stok untuk alat ID {$sewa_alat_id}.");
                }
            }
            return $new_id;
        } else {
            error_log("PemesananSewaAlat::create() - MySQLi Execute Error: " . mysqli_stmt_error($stmt) . " | SQL: " . $sql);
            mysqli_stmt_close($stmt);
            return false;
        }
    }

    public static function getAll()
    {
        global $conn;
        if (!$conn || $conn->connect_error) {
            error_log("PemesananSewaAlat::getAll() - Koneksi database gagal: " . ($conn ? $conn->connect_error : 'Koneksi tidak diset'));
            return [];
        }
        $sql = "SELECT 
                    psa.id, psa.pemesanan_tiket_id, psa.sewa_alat_id, psa.jumlah, 
                    psa.harga_satuan_saat_pesan, psa.durasi_satuan_saat_pesan, psa.satuan_durasi_saat_pesan, 
                    psa.tanggal_mulai_sewa, psa.tanggal_akhir_sewa_rencana, psa.total_harga_item, 
                    psa.status_item_sewa, psa.catatan_item_sewa, psa.denda, 
                    psa.created_at, psa.updated_at,
                    sa.nama_item AS nama_alat,
                    pt.kode_pemesanan AS kode_pemesanan_tiket, 
                    COALESCE(u.nama, pt.nama_pemesan_tamu) AS nama_pemesan,
                    pt.user_id AS id_user_pemesan_tiket 
                FROM " . self::$table_name . " psa
                INNER JOIN sewa_alat sa ON psa.sewa_alat_id = sa.id
                INNER JOIN pemesanan_tiket pt ON psa.pemesanan_tiket_id = pt.id
                LEFT JOIN users u ON pt.user_id = u.id 
                ORDER BY psa.created_at DESC, psa.id DESC";
        $result = mysqli_query($conn, $sql);
        if ($result === false) {
            error_log("PemesananSewaAlat::getAll() - MySQLi Query Error: " . mysqli_error($conn) . " | SQL: " . $sql);
            return [];
        }
        $data = mysqli_fetch_all($result, MYSQLI_ASSOC);
        mysqli_free_result($result);
        return $data;
    }

    public static function getById($id)
    {
        global $conn;
        if (!$conn) {
            error_log("PemesananSewaAlat::getById() - Koneksi DB gagal.");
            return null;
        }
        $id_val = filter_var($id, FILTER_VALIDATE_INT);
        if ($id_val === false || $id_val <= 0) {
            error_log("PemesananSewaAlat::getById() - ID tidak valid: " . e($id));
            return null;
        }
        $sql = "SELECT psa.*, 
                       sa.nama_item AS nama_alat, sa.harga_sewa AS harga_sewa_terkini_alat, sa.satuan_durasi_harga AS satuan_durasi_alat,
                       pt.kode_pemesanan AS kode_pemesanan_tiket,
                       COALESCE(u.nama, pt.nama_pemesan_tamu) AS nama_pemesan,
                       COALESCE(u.email, pt.email_pemesan_tamu) AS email_pemesan,
                       COALESCE(u.no_hp, pt.nohp_pemesan_tamu) AS nohp_pemesan, 
                       pt.user_id AS id_user_pemesan_tiket
                FROM " . self::$table_name . " psa
                JOIN sewa_alat sa ON psa.sewa_alat_id = sa.id
                JOIN pemesanan_tiket pt ON psa.pemesanan_tiket_id = pt.id
                LEFT JOIN users u ON pt.user_id = u.id
                WHERE psa.id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        if (!$stmt) {
            error_log("PemesananSewaAlat::getById() - Prepare Error: " . mysqli_error($conn));
            return null;
        }
        mysqli_stmt_bind_param($stmt, "i", $id_val);
        if (mysqli_stmt_execute($stmt)) {
            $result = mysqli_stmt_get_result($stmt);
            $pesanan = mysqli_fetch_assoc($result);
            mysqli_stmt_close($stmt);
            return $pesanan ?: null;
        }
        error_log("PemesananSewaAlat::getById() - Execute Error: " . mysqli_stmt_error($stmt));
        mysqli_stmt_close($stmt);
        return null;
    }

    public static function getByPemesananTiketId($pemesanan_tiket_id)
    {
        global $conn;
        if (!$conn) {
            error_log("PemesananSewaAlat::getByPemesananTiketId() - Koneksi DB gagal.");
            return [];
        }
        $id_val = filter_var($pemesanan_tiket_id, FILTER_VALIDATE_INT);
        if ($id_val === false || $id_val <= 0) {
            error_log("PemesananSewaAlat::getByPemesananTiketId() - ID tidak valid: " . e($pemesanan_tiket_id));
            return [];
        }
        $sql = "SELECT psa.*, sa.nama_item AS nama_alat FROM " . self::$table_name . " psa JOIN sewa_alat sa ON psa.sewa_alat_id = sa.id WHERE psa.pemesanan_tiket_id = ? ORDER BY psa.id ASC";
        $stmt = mysqli_prepare($conn, $sql);
        if (!$stmt) {
            error_log("PemesananSewaAlat::getByPemesananTiketId() - Prepare Error: " . mysqli_error($conn));
            return [];
        }
        mysqli_stmt_bind_param($stmt, "i", $id_val);
        if (mysqli_stmt_execute($stmt)) {
            $result = mysqli_stmt_get_result($stmt);
            $data = mysqli_fetch_all($result, MYSQLI_ASSOC);
            mysqli_stmt_close($stmt);
            return $data;
        }
        error_log("PemesananSewaAlat::getByPemesananTiketId() - Execute Error: " . mysqli_stmt_error($stmt));
        mysqli_stmt_close($stmt);
        return [];
    }

    public static function update($data)
    {
        global $conn;
        if (!$conn || !isset($data['id'])) {
            error_log("PemesananSewaAlat::update() - Koneksi/ID tidak ada.");
            return false;
        }
        $id = (int)$data['id'];
        if ($id <= 0) {
            error_log("PemesananSewaAlat::update() - ID tidak valid.");
            return false;
        }
        $currentData = self::getById($id);
        if (!$currentData) {
            error_log("PemesananSewaAlat::update() - Data ID {$id} tidak ditemukan.");
            return false;
        }
        $catatan = $data['catatan_item_sewa'] ?? $currentData['catatan_item_sewa'];
        $denda = isset($data['denda']) ? (float)$data['denda'] : (float)$currentData['denda'];
        $sql = "UPDATE " . self::$table_name . " SET catatan_item_sewa = ?, denda = ?, updated_at = NOW() WHERE id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        if (!$stmt) {
            error_log("PemesananSewaAlat::update() - Prepare Error: " . mysqli_error($conn));
            return false;
        }
        mysqli_stmt_bind_param($stmt, "sdi", $catatan, $denda, $id);
        if (mysqli_stmt_execute($stmt)) {
            mysqli_stmt_close($stmt);
            return true;
        }
        error_log("PemesananSewaAlat::update() - Execute Error: " . mysqli_stmt_error($stmt));
        mysqli_stmt_close($stmt);
        return false;
    }

    public static function updateStatus($id, $newStatus)
    {
        global $conn;
        if (!$conn) {
            return false;
        }
        $id_val = filter_var($id, FILTER_VALIDATE_INT);
        if ($id_val === false || $id_val <= 0) {
            return false;
        }
        $allowed_status = ['Dipesan', 'Diambil', 'Dikembalikan', 'Hilang', 'Rusak', 'Dibatalkan'];
        if (!in_array($newStatus, $allowed_status)) {
            return false;
        }
        $pemesananInfo = self::getById($id_val);
        $oldStatus = $pemesananInfo['status_item_sewa'] ?? null;
        $sql = "UPDATE " . self::$table_name . " SET status_item_sewa = ?, updated_at = NOW() WHERE id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        if (!$stmt) {
            return false;
        }
        mysqli_stmt_bind_param($stmt, "si", $newStatus, $id_val);
        if (mysqli_stmt_execute($stmt)) {
            mysqli_stmt_close($stmt);
            if ($pemesananInfo && $oldStatus !== $newStatus) {
                require_once __DIR__ . '/SewaAlat.php';
                $jumlah_alat = (int)$pemesananInfo['jumlah'];
                $sewa_alat_id = (int)$pemesananInfo['sewa_alat_id'];
                $stokChange = 0;
                if (in_array($oldStatus, ['Diambil', 'Hilang', 'Rusak']) && $newStatus === 'Dikembalikan') {
                    $stokChange = $jumlah_alat;
                } elseif ((in_array($oldStatus, ['Dipesan', 'Dikembalikan'])) && (in_array($newStatus, ['Diambil', 'Hilang', 'Rusak']))) {
                    $stokChange = -$jumlah_alat;
                } elseif (in_array($oldStatus, ['Diambil', 'Hilang', 'Rusak']) && in_array($newStatus, ['Dipesan', 'Dibatalkan'])) {
                    $stokChange = $jumlah_alat;
                }
                if ($stokChange !== 0 && class_exists('SewaAlat') && method_exists('SewaAlat', 'updateStok')) {
                    if (!SewaAlat::updateStok($sewa_alat_id, $stokChange)) {
                        error_log("Warning: Gagal update stok alat ID {$sewa_alat_id}.");
                    }
                }
            }
            return true;
        }
        mysqli_stmt_close($stmt);
        return false;
    }

    public static function delete($id)
    {
        global $conn;
        if (!$conn) {
            return false;
        }
        $id_val = filter_var($id, FILTER_VALIDATE_INT);
        if ($id_val === false || $id_val <= 0) {
            return false;
        }
        $pemesananInfo = self::getById($id_val);
        $sql = "DELETE FROM " . self::$table_name . " WHERE id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        if (!$stmt) {
            return false;
        }
        mysqli_stmt_bind_param($stmt, "i", $id_val);
        if (mysqli_stmt_execute($stmt)) {
            $affected_rows = mysqli_stmt_affected_rows($stmt);
            mysqli_stmt_close($stmt);
            if ($affected_rows > 0 && $pemesananInfo && in_array($pemesananInfo['status_item_sewa'], ['Dipesan', 'Diambil'])) {
                require_once __DIR__ . '/SewaAlat.php';
                if (class_exists('SewaAlat') && method_exists('SewaAlat', 'updateStok')) {
                    SewaAlat::updateStok((int)$pemesananInfo['sewa_alat_id'], (int)$pemesananInfo['jumlah']);
                }
            }
            return $affected_rows > 0;
        }
        mysqli_stmt_close($stmt);
        return false;
    }

    public static function countByStatus($status_item_sewa)
    {
        global $conn;
        if (!$conn || empty(trim($status_item_sewa))) {
            return 0;
        }
        $sql = "SELECT COUNT(id) as total FROM " . self::$table_name . " WHERE status_item_sewa = ?";
        $stmt = mysqli_prepare($conn, $sql);
        if (!$stmt) {
            return 0;
        }
        mysqli_stmt_bind_param($stmt, "s", $status_item_sewa);
        if (mysqli_stmt_execute($stmt)) {
            $result = mysqli_stmt_get_result($stmt);
            $row = mysqli_fetch_assoc($result);
            mysqli_stmt_close($stmt);
            return (int)($row['total'] ?? 0);
        }
        mysqli_stmt_close($stmt);
        return 0;
    }

    public static function countAll()
    {
        global $conn;
        if (!$conn) {
            return 0;
        }
        $sql = "SELECT COUNT(id) as total FROM " . self::$table_name;
        $result = mysqli_query($conn, $sql);
        if ($result) {
            $row = mysqli_fetch_assoc($result);
            mysqli_free_result($result);
            return (int)($row['total'] ?? 0);
        }
        return 0;
    }
}
