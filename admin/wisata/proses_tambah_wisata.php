<?php
include "../../config/Koneksi.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = isset($_POST['nama']) ? trim($_POST['nama']) : '';
    $deskripsi = isset($_POST['deskripsi']) ? trim($_POST['deskripsi']) : '';
    $lokasi = isset($_POST['lokasi']) ? trim($_POST['lokasi']) : null; // Nullable
    $harga = isset($_POST['harga']) && is_numeric($_POST['harga']) ? (int)$_POST['harga'] : null; // Nullable, ensure numeric

    $gambar_final_name = null; // Initialize
    $uploadOk = 1;

    // File upload logic
    if (isset($_FILES['gambar']) && $_FILES['gambar']['error'] == UPLOAD_ERR_OK && !empty($_FILES['gambar']['name'])) {
        $target_dir = "../../public/img/"; // Make sure this directory exists and is writable
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0777, true);
        }

        $imageFileType = strtolower(pathinfo($_FILES["gambar"]["name"], PATHINFO_EXTENSION));
        $gambar_final_name = uniqid('wisata_', true) . '.' . $imageFileType; // Unique name
        $target_file = $target_dir . $gambar_final_name;

        // Check if image file is a actual image or fake image
        $check = getimagesize($_FILES["gambar"]["tmp_name"]);
        if ($check === false) {
            echo "<script>alert('File yang diupload bukan gambar.'); window.history.back();</script>";
            $uploadOk = 0;
        }

        // Check file size (e.g., 5MB)
        if ($_FILES["gambar"]["size"] > 5000000) {
            echo "<script>alert('Maaf, ukuran file terlalu besar (maks 5MB).'); window.history.back();</script>";
            $uploadOk = 0;
        }

        // Allow certain file formats
        $allowed_formats = ["jpg", "png", "jpeg", "gif"];
        if (!in_array($imageFileType, $allowed_formats)) {
            echo "<script>alert('Maaf, hanya file JPG, JPEG, PNG & GIF yang diperbolehkan.'); window.history.back();</script>";
            $uploadOk = 0;
        }

        if ($uploadOk == 1) {
            if (!move_uploaded_file($_FILES["gambar"]["tmp_name"], $target_file)) {
                echo "<script>alert('Maaf, terjadi error saat mengunggah file Anda.'); window.history.back();</script>";
                $uploadOk = 0; // Mark as failed
                $gambar_final_name = null; // Reset if upload failed
            }
        }
    } elseif (isset($_FILES['gambar']) && $_FILES['gambar']['error'] != UPLOAD_ERR_NO_FILE && $_FILES['gambar']['error'] != UPLOAD_ERR_OK) {
        // Handle other upload errors specifically
        echo "<script>alert('Error unggah file: Kode " . $_FILES['gambar']['error'] . "'); window.history.back();</script>";
        $uploadOk = 0;
    }


    // Basic validation for required fields
    if (empty($nama) || empty($deskripsi)) {
        echo "<script>alert('Nama dan Deskripsi wisata wajib diisi.'); window.history.back();</script>";
        exit;
    }

    if ($uploadOk) { // Proceed only if upload was okay or no file was intended/uploaded
        // 'created_at' will be handled by database default (CURRENT_TIMESTAMP)
        // 'id' is auto_increment

        // SQL Injection prevention using prepared statements
        // Using all columns from DESCRIBE wisata: id, nama, deskripsi, gambar, lokasi, harga, created_at
        $sql = "INSERT INTO wisata (nama, deskripsi, gambar, lokasi, harga) VALUES (?, ?, ?, ?, ?)";
        $stmt = mysqli_prepare($conn, $sql);

        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "ssssi", $nama, $deskripsi, $gambar_final_name, $lokasi, $harga);

            if (mysqli_stmt_execute($stmt)) {
                mysqli_stmt_close($stmt);
                header("Location: kelola_wisata.php?success=1");
                exit();
            } else {
                // If insert fails and a file was uploaded, try to delete it
                if ($gambar_final_name && file_exists($target_file)) {
                    unlink($target_file);
                }
                echo "<script>alert('Gagal menambahkan data wisata: " . htmlspecialchars(mysqli_stmt_error($stmt)) . "'); window.history.back();</script>";
            }
        } else {
            // If prepare fails and a file was uploaded, try to delete it
            if ($gambar_final_name && file_exists($target_file)) {
                unlink($target_file);
            }
            echo "<script>alert('Gagal mempersiapkan statement: " . htmlspecialchars(mysqli_error($conn)) . "'); window.history.back();</script>";
        }
    }
} else {
    // Not a POST request
    header("Location: tambah_wisata.php");
    exit();
}
