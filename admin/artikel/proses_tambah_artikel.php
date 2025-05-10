<?php
include_once '../../config/Koneksi.php';
// Assuming you might use an Artikel model in the future, but for now, direct DB interaction.
// include_once '../../models/Artikel.php'; 

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $judul = isset($_POST['judul']) ? trim($_POST['judul']) : '';
    $isi = isset($_POST['isi']) ? trim($_POST['isi']) : '';
    $gambar = null;
    $uploadOk = 1;
    $target_file = "";

    // Handle file upload for 'gambar'
    if (isset($_FILES['gambar']) && $_FILES['gambar']['error'] == UPLOAD_ERR_OK && !empty($_FILES['gambar']['name'])) {
        $target_dir = "../../public/uploads/artikel/"; // Create this directory if it doesn't exist
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        $imageFileType = strtolower(pathinfo($_FILES["gambar"]["name"], PATHINFO_EXTENSION));
        // Generate a unique name for the file to prevent overwriting
        $gambar = uniqid('artikel_') . '.' . $imageFileType;
        $target_file = $target_dir . $gambar;

        // Check if image file is a actual image or fake image
        $check = getimagesize($_FILES["gambar"]["tmp_name"]);
        if ($check === false) {
            echo "File bukan gambar.";
            $uploadOk = 0;
        }

        // Check file size (e.g., 5MB limit)
        if ($_FILES["gambar"]["size"] > 5000000) {
            echo "Maaf, ukuran file terlalu besar.";
            $uploadOk = 0;
        }

        // Allow certain file formats
        if ($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg" && $imageFileType != "gif") {
            echo "Maaf, hanya file JPG, JPEG, PNG & GIF yang diperbolehkan.";
            $uploadOk = 0;
        }

        if ($uploadOk == 0) {
            echo "Maaf, file Anda tidak terunggah.";
        } else {
            if (!move_uploaded_file($_FILES["gambar"]["tmp_name"], $target_file)) {
                echo "Maaf, terjadi error saat mengunggah file Anda.";
                $uploadOk = 0;
                $gambar = null;
            }
        }
    } else if (isset($_FILES['gambar']) && $_FILES['gambar']['error'] != UPLOAD_ERR_NO_FILE && $_FILES['gambar']['error'] != UPLOAD_ERR_OK) {
        echo "Error unggah file: " . $_FILES['gambar']['error'];
        $uploadOk = 0;
    }


    if (empty($judul) || empty($isi)) {
        echo "Judul dan isi artikel wajib diisi.";
    } elseif ($uploadOk) {

        $sql = "INSERT INTO articles (judul, isi, gambar) VALUES (?, ?, ?)";
        $stmt = mysqli_prepare($conn, $sql);

        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "sss", $judul, $isi, $gambar);
            if (mysqli_stmt_execute($stmt)) {
                mysqli_stmt_close($stmt);
                header("Location: kelola_artikel.php?success=1");
                exit();
            } else {
                echo "Gagal menambahkan artikel: " . mysqli_stmt_error($stmt);
            }
        } else {
            echo "Gagal mempersiapkan statement: " . mysqli_error($conn);
        }
    }
} else {
    header("Location: tambah_artikel.php");
    exit();
}
