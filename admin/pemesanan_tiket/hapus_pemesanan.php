<?php
include "../../config/Koneksi.php";

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);

    // Use prepared statement to prevent SQL injection
    $stmt = mysqli_prepare($conn, "DELETE FROM booking WHERE id = ?"); // Assuming table name is 'booking'
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
}

header("Location: kelola_booking.php");
exit;
