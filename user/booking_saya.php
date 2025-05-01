<?php session_start();
if ($_SESSION['role'] != 'user') header('Location: ../auth/login.php');
?>
<?php $title = 'Booking Saya';
include '../template/header.php'; ?>
<h2>Booking Saya</h2>
<?php
require_once '../config/Koneksi.php';
$conn = Koneksi::getInstance();
$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT * FROM booking INNER JOIN wisata ON booking.wisata_id=wisata.id WHERE booking.user_id=?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$res = $stmt->get_result();
if ($res->num_rows > 0) {
    echo "<table><tr><th>Wisata</th><th>Tanggal</th><th>Status</th></tr>";
    while ($row = $res->fetch_assoc()) {
        echo "<tr><td>" . $row['nama'] . "</td><td>" . $row['tanggal_booking'] . "</td><td>" . $row['status'] . "</td></tr>";
    }
    echo "</table>";
} else {
    echo "<p>Anda belum melakukan booking.</p>";
}
?>
<?php include '../template/footer.php'; ?>