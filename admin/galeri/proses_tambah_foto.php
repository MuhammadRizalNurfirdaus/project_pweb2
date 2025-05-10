<?php
include "../../config/Koneksi.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $keterangan = isset($_POST['keterangan']) ? trim($_POST['keterangan']) : '';
    $nama_file_final = null; // To store the final filename

    // File upload handling
    if (isset($_FILES['nama_file']) && $_FILES['nama_file']['error'] == UPLOAD_ERR_OK) {
        $file_tmp_name = $_FILES['nama_file']['tmp_name'];
        $file_original_name = $_FILES['nama_file']['name'];
        $file_size = $_FILES['nama_file']['size'];
        $file_ext = strtolower(pathinfo($file_original_name, PATHINFO_EXTENSION));

        $allowed_extensions = array("jpg", "jpeg", "png", "gif");

        if (in_array($file_ext, $allowed_extensions)) {
            if ($file_size < 5000000) { // 5MB limit
                // Create a unique filename
                $nama_file_final = uniqid('galeri_', true) . '.' . $file_ext;
                $upload_path = "../../public/img/" . $nama_file_final; // Make sure this directory exists and is writable

                if (!is_dir("../../public/img/")) {
                    mkdir("../../public/img/", 0777, true);
                }

                if (move_uploaded_file($file_tmp_name, $upload_path)) {
                    // File uploaded successfully
                } else {
                    echo "<script>alert('Gagal memindahkan file yang diunggah.'); window.history.back();</script>";
                    exit;
                }
            } else {
                echo "<script>alert('Ukuran file terlalu besar. Maksimal 5MB.'); window.history.back();</script>";
                exit;
            }
        } else {
            echo "<script>alert('Ekstensi file tidak diizinkan. Hanya JPG, JPEG, PNG, GIF.'); window.history.back();</script>";
            exit;
        }
    } else {
        echo "<script>alert('Pilih file gambar untuk diunggah.'); window.history.back();</script>";
        exit;
    }

    if (empty($keterangan) || empty($nama_file_final)) {
        echo "<script>alert('Keterangan dan file gambar wajib diisi.'); window.history.back();</script>";
        exit;
    }

    // Insert into database
    // DESCRIBE galeri: id, nama_file, keterangan. created_at is not specified.
    $stmt = mysqli_prepare($conn, "INSERT INTO galeri (nama_file, keterangan) VALUES (?, ?)");
    mysqli_stmt_bind_param($stmt, "ss", $nama_file_final, $keterangan);

    if (mysqli_stmt_execute($stmt)) {
        mysqli_stmt_close($stmt);
        header("Location: kelola_galeri.php?success=1");
        exit;
    } else {
        // If insert fails, try to delete the uploaded file to avoid orphans
        if (file_exists($upload_path)) {
            unlink($upload_path);
        }
        echo "<script>alert('Gagal menyimpan data ke database: " . htmlspecialchars(mysqli_stmt_error($stmt)) . "'); window.history.back();</script>";
    }
} else {
    header("Location: tambah_foto.php");
    exit;
}
