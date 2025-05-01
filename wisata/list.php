<?php
require_once '../config/Koneksi.php';
$conn = Koneksi::getInstance();
?>
<?php $title = 'Daftar Wisata';
include '../template/header.php'; ?>
<h2>Daftar Wisata</h2>
<?php
$stmt = $conn->prepare("SELECT * FROM wisata");
$stmt->execute();
$res = $stmt->get_result();
if ($res->num_rows > 0) {
    echo "<table><tr><th>Nama</th><th>Harga</th><th>Aksi</th></tr>";
    while ($row = $res->fetch_assoc()) {
        echo "<tr><td>" . $row['nama'] . "</td><td>" . $row['harga'] . "</td><td><a href='detail.php?id=" . $row['id'] . "'>Lihat Detail</a></td></tr>";
    }
    echo "</table>";
} else {
    echo "<p>Tidak ada wisata yang tersedia.</p>";
}
?>
<?php include '../template/footer.php'; ?>