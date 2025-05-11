<?php
require_once __DIR__ . '/../config/config.php';

class BookingController
{
    /**
     * Buat booking baru
     * @param array $data [nama_wisata, user_id (opsional), tanggal_kunjungan, jumlah_orang, status (opsional)]
     *                   OR [nama, email, no_hp, jumlah_tiket, tanggal_kunjungan, catatan] (sesuaikan dgn skema yg dipakai)
     * @return bool True jika berhasil, false jika gagal
     */
    public static function create($data)
    {
        global $conn;

        // DESCRIBE bookings: id, user_id, nama_wisata, tanggal_kunjungan, jumlah_orang, status
        // Your form from `proses_booking.php` had: nama, email, no_hp, jumlah_tiket, tanggal_kunjungan, catatan for a table `booking` (singular)
        // LET'S ASSUME we are using the 'bookings' (plural) table structure from DESCRIBE.
        // If you have a separate `booking` (singular) table with different fields, this needs adjustment.

        $user_id = isset($data['user_id']) ? (int)$data['user_id'] : null;
        $nama_wisata = $data['nama_wisata'];
        $tanggal_kunjungan = $data['tanggal_kunjungan']; // Format YYYY-MM-DD
        $jumlah_orang = (int)$data['jumlah_orang'];
        $status = isset($data['status']) ? $data['status'] : 'pending'; // Default status

        $sql = "INSERT INTO bookings (user_id, nama_wisata, tanggal_kunjungan, jumlah_orang, status) 
                  VALUES (?, ?, ?, ?, ?)";
        $stmt = mysqli_prepare($conn, $sql);

        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "issis", $user_id, $nama_wisata, $tanggal_kunjungan, $jumlah_orang, $status);
            if (mysqli_stmt_execute($stmt)) {
                mysqli_stmt_close($stmt);
                return true;
            }
            mysqli_stmt_close($stmt);
        }
        return false;
    }

    /**
     * Ambil semua data booking
     * @return mysqli_result|false
     */
    public static function getAll()
    {
        global $conn;
        // Assuming 'bookings' table and 'tanggal_kunjungan' or 'created_at' for ordering
        $result = mysqli_query($conn, "SELECT b.*, u.nama as user_nama, u.email as user_email 
                                       FROM bookings b
                                       LEFT JOIN users u ON b.user_id = u.id
                                       ORDER BY b.tanggal_kunjungan DESC");
        return $result;
    }

    /**
     * Hapus data booking
     * @param int $id
     * @return bool
     */
    public static function delete($id)
    {
        global $conn;
        $sql = "DELETE FROM bookings WHERE id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "i", $id);
            if (mysqli_stmt_execute($stmt)) {
                mysqli_stmt_close($stmt);
                return true;
            }
            mysqli_stmt_close($stmt);
        }
        return false;
    }

    /**
     * Update status booking
     * @param int $id
     * @param string $status
     * @return bool
     */
    public static function updateStatus($id, $status)
    {
        global $conn;
        $sql = "UPDATE bookings SET status = ? WHERE id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "si", $status, $id);
            if (mysqli_stmt_execute($stmt)) {
                mysqli_stmt_close($stmt);
                return true;
            }
            mysqli_stmt_close($stmt);
        }
        return false;
    }
}
