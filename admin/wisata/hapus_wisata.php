<?php
include "../../config/Koneksi.php";

if (isset($_GET['id'])) {
    $id = intval($_GET['id']); // Sanitize input

    // First, get the filename to delete the file from the server
    $stmt_select = mysqli_prepare($conn, "SELECT gambar FROM wisata WHERE id = ?");
    if ($stmt_select) {
        mysqli_stmt_bind_param($stmt_select, "i", $id);
        mysqli_stmt_execute($stmt_select);
        $result_select = mysqli_stmt_get_result($stmt_select);
        if ($row = mysqli_fetch_assoc($result_select)) {
            $filename_to_delete = $row['gambar'];
            if (!empty($filename_to_delete)) {
                $filepath = "../../public/img/" . $filename_to_delete; // Path to the image
                if (file_exists($filepath)) {
                    unlink($filepath); // Delete the file
                }
            }
        }
        mysqli_stmt_close($stmt_select);
    } else {
        // Handle error in preparing select statement if necessary
        error_log("Failed to prepare select statement for wisata deletion: " . mysqli_error($conn));
    }


    // Then, delete the record from the database
    $stmt_delete = mysqli_prepare($conn, "DELETE FROM wisata WHERE id = ?");
    if ($stmt_delete) {
        mysqli_stmt_bind_param($stmt_delete, "i", $id);
        mysqli_stmt_execute($stmt_delete);
        mysqli_stmt_close($stmt_delete);
    } else {
        // Handle error in preparing delete statement if necessary
        error_log("Failed to prepare delete statement for wisata: " . mysqli_error($conn));
    }
}

header("Location: kelola_wisata.php");
exit;
