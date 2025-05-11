<?php
// File: C:\xampp\htdocs\Cilengkrang-Web-Wisata\models\PemesananSewaAlat.php

class PemesananSewaAlat
{
    private static $table_name = "pemesanan_sewa_alat";

    public static function countByStatus($status)
    {
        global $conn;
        if (!$conn) {
            error_log("PemesananSewaAlat::countByStatus() - Koneksi database gagal.");
            return 0;
        }

        $status_val = trim($status);
        if (empty($status_val)) {
            error_log("PemesananSewaAlat::countByStatus() - Error: Status tidak boleh kosong.");
            return 0;
        }
        // Pastikan nama kolom status_item_sewa sudah benar
        $sql = "SELECT COUNT(id) as total FROM " . self::$table_name . " WHERE status_item_sewa = ?";
        $stmt = mysqli_prepare($conn, $sql);

        if (!$stmt) {
            error_log("PemesananSewaAlat::countByStatus() - MySQLi Prepare Error: " . mysqli_error($conn) . " | SQL: " . $sql);
            return 0;
        }

        mysqli_stmt_bind_param($stmt, "s", $status_val);

        if (mysqli_stmt_execute($stmt)) {
            $result = mysqli_stmt_get_result($stmt);
            $row = mysqli_fetch_assoc($result);
            mysqli_stmt_close($stmt);
            return $row['total'] ?? 0;
        } else {
            error_log("PemesananSewaAlat::countByStatus() - MySQLi Execute Error: " . mysqli_stmt_error($stmt) . " | SQL: " . $sql);
            mysqli_stmt_close($stmt);
            return 0;
        }
    }

    public static function create($data)
    {
        global $conn;
        if (!$conn) {
            error_log("PemesananSewaAlat::create() - Koneksi database gagal.");
            return false;
        }

        if (
            empty($data['pemesanan_tiket_id']) || empty($data['sewa_alat_id']) || !isset($data['jumlah']) || (int)$data['jumlah'] <= 0 ||
            !isset($data['harga_satuan_saat_pesan']) || !isset($data['total_harga_item']) ||
            empty($data['tanggal_mulai_sewa']) || empty($data['tanggal_akhir_sewa_rencana'])
        ) {
            error_log("PemesananSewaAlat::create() - Data input tidak lengkap atau tidak valid.");
            return false;
        }

        $pemesanan_tiket_id = (int)$data['pemesanan_tiket_id'];
        $sewa_alat_id = (int)$data['sewa_alat_id'];
        $jumlah = (int)$data['jumlah'];
        $harga_satuan_saat_pesan = (int)$data['harga_satuan_saat_pesan'];
        $durasi_satuan_saat_pesan = (int)($data['durasi_satuan_saat_pesan'] ?? 1);
        $satuan_durasi_saat_pesan = trim($data['satuan_durasi_saat_pesan'] ?? 'Hari');
        $tanggal_mulai_sewa = $data['tanggal_mulai_sewa'];
        $tanggal_akhir_sewa_rencana = $data['tanggal_akhir_sewa_rencana'];
        $total_harga_item = (int)$data['total_harga_item'];
        $status_item_sewa = trim($data['status_item_sewa'] ?? 'Dipesan');
        $catatan_item_sewa = isset($data['catatan_item_sewa']) ? trim($data['catatan_item_sewa']) : null;

        $sql = "INSERT INTO " . self::$table_name . " 
                (pemesanan_tiket_id, sewa_alat_id, jumlah, harga_satuan_saat_pesan, 
                 durasi_satuan_saat_pesan, satuan_durasi_saat_pesan, 
                 tanggal_mulai_sewa, tanggal_akhir_sewa_rencana, total_harga_item, 
                 status_item_sewa, catatan_item_sewa) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = mysqli_prepare($conn, $sql);
        if (!$stmt) {
            error_log("PemesananSewaAlat::create() - MySQLi Prepare Error: " . mysqli_error($conn) . " | SQL: " . $sql);
            return false;
        }

        mysqli_stmt_bind_param(
            $stmt,
            "iiiisssssss",
            $pemesanan_tiket_id,
            $sewa_alat_id,
            $jumlah,
            $harga_satuan_saat_pesan,
            $durasi_satuan_saat_pesan,
            $satuan_durasi_saat_pesan,
            $tanggal_mulai_sewa,
            $tanggal_akhir_sewa_rencana,
            $total_harga_item,
            $status_item_sewa,
            $catatan_item_sewa
        );

        if (mysqli_stmt_execute($stmt)) {
            $new_id = mysqli_insert_id($conn);
            mysqli_stmt_close($stmt);

            // Kurangi stok alat setelah berhasil menyimpan pemesanan item sewa
            if (class_exists('SewaAlat') && method_exists('SewaAlat', 'updateStok')) {
                if (!SewaAlat::updateStok($sewa_alat_id, -$jumlah)) { // Mengurangi stok
                    error_log("PemesananSewaAlat::create() - Warning: Gagal mengurangi stok untuk alat ID: " . $sewa_alat_id);
                    // Pemesanan item sewa tetap dibuat, tapi ada masalah stok. Perlu penanganan/notifikasi.
                }
            }
            return $new_id;
        } else {
            error_log("PemesananSewaAlat::create() - MySQLi Execute Error: " . mysqli_stmt_error($stmt) . " | SQL: " . $sql);
            mysqli_stmt_close($stmt);
            return false;
        }
    }

    public static function getByPemesananTiketId($pemesanan_tiket_id)
    {
        global $conn;
        if (!$conn) {
            error_log("PemesananSewaAlat::getByPemesananTiketId() - Koneksi database gagal.");
            return [];
        }
        $id_val = filter_var($pemesanan_tiket_id, FILTER_VALIDATE_INT);
        if ($id_val === false || $id_val <= 0) {
            error_log("PemesananSewaAlat::getByPemesananTiketId() - Error: ID Pemesanan Tiket tidak valid (" . e($pemesanan_tiket_id) . ").");
            return [];
        }

        $sql = "SELECT psa.*, sa.nama_item, sa.gambar_alat, sa.harga_sewa AS harga_satuan_master, sa.durasi_harga_sewa AS durasi_satuan_master, sa.satuan_durasi_harga AS satuan_durasi_master
                FROM " . self::$table_name . " psa
                JOIN sewa_alat sa ON psa.sewa_alat_id = sa.id
                WHERE psa.pemesanan_tiket_id = ? 
                ORDER BY psa.id ASC";
        $stmt = mysqli_prepare($conn, $sql);
        if (!$stmt) {
            error_log("PemesananSewaAlat::getByPemesananTiketId() - MySQLi Prepare Error: " . mysqli_error($conn) . " | SQL: " . $sql);
            return [];
        }
        mysqli_stmt_bind_param($stmt, "i", $id_val);
        if (mysqli_stmt_execute($stmt)) {
            $result = mysqli_stmt_get_result($stmt);
            $items = mysqli_fetch_all($result, MYSQLI_ASSOC);
            mysqli_stmt_close($stmt);
            return $items;
        } else {
            error_log("PemesananSewaAlat::getByPemesananTiketId() - MySQLi Execute Error: " . mysqli_stmt_error($stmt) . " | SQL: " . $sql);
            mysqli_stmt_close($stmt);
            return [];
        }
    }

    // Method untuk menghapus semua item sewa berdasarkan pemesanan_tiket_id
    // Berguna jika pemesanan tiket dibatalkan/dihapus
    public static function deleteByPemesananTiketId($pemesanan_tiket_id)
    {
        global $conn;
        if (!$conn) return false;
        $id_val = (int)$pemesanan_tiket_id;
        if ($id_val <= 0) return false;

        // Sebelum menghapus, ambil dulu item yang akan dihapus untuk mengembalikan stok
        $items_to_delete = self::getByPemesananTiketId($id_val);

        $sql = "DELETE FROM " . self::$table_name . " WHERE pemesanan_tiket_id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        if (!$stmt) { /* error log */
            return false;
        }
        mysqli_stmt_bind_param($stmt, "i", $id_val);
        if (mysqli_stmt_execute($stmt)) {
            mysqli_stmt_close($stmt);
            // Kembalikan stok untuk setiap item yang dihapus dari pemesanan
            if (!empty($items_to_delete) && class_exists('SewaAlat') && method_exists('SewaAlat', 'updateStok')) {
                foreach ($items_to_delete as $item) {
                    SewaAlat::updateStok($item['sewa_alat_id'], $item['jumlah']); // Menambah stok kembali
                }
            }
            return true;
        }
        mysqli_stmt_close($stmt);
        return false;
    }
}
