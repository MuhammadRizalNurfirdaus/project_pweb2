<?php
include "../../config/Koneksi.php";

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);

    // First, get the filename to delete the file from server
    $stmt_select = mysqli_prepare($conn, "SELECT nama_file FROM galeri WHERE id = ?");
    mysqli_stmt_bind_param($stmt_select, "i", $id);
    mysqli_stmt_execute($stmt_select);
    $result_select = mysqli_stmt_get_result($stmt_select);
    if ($row = mysqli_fetch_assoc($result_select)) {
        $filename_to_delete = $row['nama_file'];
        $filepath = "../../public/img/" . $filename_to_delete; // Adjust path if different
        if (file_exists($filepath) && !empty($filename_to_delete)) {
            unlink($filepath);
        }
    }
    mysqli_stmt_close($stmt_select);

    // Then, delete the record from database
    $stmt_delete = mysqli_prepare($conn, "DELETE FROM galeri WHERE id = ?");
    mysqli_stmt_bind_param($stmt_delete, "i", $id);
    mysqli_stmt_execute($stmt_delete);
    mysqli_stmt_close($stmt_delete);
}

header("Location: kelola_galeri.php");
exit;
