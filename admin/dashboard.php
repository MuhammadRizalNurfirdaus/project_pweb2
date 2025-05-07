<?php
session_start();
include "../config/Koneksi.php";
include "../template/header.php";

if (!isset($_SESSION['admin'])) {
    header("Location: ../auth/login.php");
    exit();
}
?>

<div class="container mt-5">
    <h2>Dashboard Admin</h2>
    <ul>
        <li><a href="booking/kelola_booking.php">Kelola Booking</a></li>
        <li><a href="galeri/kelola_galeri.php">Kelola Galeri</a></li>
        <li><a href="wisata/kelola_wisata.php">Kelola Wisata</a></li>
        <li><a href="artikel/kelola_artikel.php">Kelola Artikel</a></li>
        <li><a href="feedback/kelola_feedback.php">Kelola Feedback</a></li>
    </ul>
</div>

<?php include "../template/footer.php"; ?>