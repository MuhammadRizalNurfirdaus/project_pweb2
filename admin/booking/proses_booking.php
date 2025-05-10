<?php
include "../../config/Koneksi.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize inputs
    $nama = isset($_POST['nama']) ? trim($_POST['nama']) : '';
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $no_hp = isset($_POST['no_hp']) ? trim($_POST['no_hp']) : '';
    $jumlah_tiket = isset($_POST['jumlah_tiket']) ? intval($_POST['jumlah_tiket']) : 0;
    $tanggal_kunjungan = isset($_POST['tanggal_kunjungan']) ? $_POST['tanggal_kunjungan'] : '';
    $catatan = isset($_POST['catatan']) ? trim($_POST['catatan']) : '';

    // Basic validation
    if (empty($nama) || empty($email) || empty($no_hp) || $jumlah_tiket <= 0 || empty($tanggal_kunjungan)) {
        // Consider redirecting back with an error message instead of just alert
        echo "<script>alert('Harap lengkapi semua data yang wajib diisi (Nama, Email, No HP, Jumlah Tiket, Tanggal Kunjungan).'); window.history.back();</script>";
        exit;
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo "<script>alert('Format email tidak valid.'); window.history.back();</script>";
        exit;
    }

    // Using 'booking' as table name, consistent with kelola_booking.php and hapus_booking.php
    // The columns here are: nama, email, no_hp, jumlah_tiket, tanggal_kunjungan, catatan
    // The 'DESCRIBE bookings' output was for a different structure (user_id, nama_wisata, etc.)
    $query = "INSERT INTO booking (nama, email, no_hp, jumlah_tiket, tanggal_kunjungan, catatan)
              VALUES (?, ?, ?, ?, ?, ?)";

    $stmt = mysqli_prepare($conn, $query);
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "sssiis", $nama, $email, $no_hp, $jumlah_tiket, $tanggal_kunjungan, $catatan);

        if (mysqli_stmt_execute($stmt)) {
            mysqli_stmt_close($stmt);
            // Redirect to a success page or back to the booking form page, not necessarily '../../booking.php' if that's the public form
            echo "<script>alert('Booking berhasil!'); window.location.href='../../booking.php';</script>"; // Assuming booking.php is the public form
            exit;
        } else {
            echo "<script>alert('Gagal melakukan booking: " . htmlspecialchars(mysqli_stmt_error($stmt)) . "'); window.history.back();</script>";
        }
    } else {
        echo "<script>alert('Gagal mempersiapkan statement: " . htmlspecialchars(mysqli_error($conn)) . "'); window.history.back();</script>";
    }
} else {
    // If not POST, redirect to the booking form page
    header("Location: ../../booking.php"); // Assuming booking.php is the public form
    exit;
}
