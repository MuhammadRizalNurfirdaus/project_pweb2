<?php
require_once 'config/Koneksi.php';
$conn = Koneksi::getInstance();
?>
<?php $title = 'Galeri Foto';
include 'template/header.php'; ?>
<h2>Galeri Foto Wisata</h2>
<div class="gallery">
    <?php
    $res = $conn->query(
        "SELECT g.nama_file, w.nama 
     FROM galeri g 
     JOIN wisata w ON g.wisata_id=w.id"
    );
    while ($row = $res->fetch_assoc()) {
        echo "<div class='item'>
              <img src='public/img/{$row['nama_file']}' alt='Foto {$row['nama']}'>
              <p>{$row['nama']}</p>
            </div>";
    }
    ?>
</div>
<?php include 'template/footer.php'; ?>