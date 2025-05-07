<?php
include "../../config/Koneksi.php";

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    mysqli_query($conn, "DELETE FROM booking WHERE id = $id");
}

header("Location: kelola_booking.php");
exit;
