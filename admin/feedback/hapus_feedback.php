<?php
include "../../config/Koneksi.php";

if (isset($_GET['id'])) {
    $id = intval($_GET['id']); // Sanitize input

    // Use prepared statement to prevent SQL injection
    $stmt = mysqli_prepare($conn, "DELETE FROM feedback WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
}

header("Location: kelola_feedback.php");
exit;
