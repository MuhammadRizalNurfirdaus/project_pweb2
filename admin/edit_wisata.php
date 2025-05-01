<?php
session_start();
if ($_SESSION['role'] != 'admin') header('Location: ../auth/login.php');

require_once '../config/Koneksi.php';
$conn = Koneksi::getInstance();

if (isset($_GET['id'])) {
    $wisata_id = $_GET['id'];
    $stmt = $conn->prepare("SELECT * FROM wisata WHERE id=?");
    $stmt->bind_param("i", $wisata_id);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res->num_rows > 0) {
        $wisata = $res->fetch_assoc();
    } else {
        echo "Wisata tidak ditemukan!";
        exit;
    }
} else {
    echo "ID Wisata tidak valid!";
    exit;
}
?>
<?php $title = 'Edit Wisata';
include '../template/header.php'; ?>
<h2>Edit Wisata</h2>
<form action="proses_edit_wisata.php" method="POST">
    <input type="hidden" name="id" value="<?php echo $wisata['id']; ?>">
    <label for="nama">Nama Wisata:</label>
    <input type="text" name="nama" value="<?php echo $wisata['nama']; ?>" required><br>
    <label for="harga">Harga:</label>
    <input type="number" name="harga" value="<?php echo $wisata['harga']; ?>" required><br>
    <label for="lokasi">Lokasi:</label>
    <input type="text" name="lokasi" value="<?php echo $wisata['lokasi']; ?>" required><br>
    <label for="deskripsi">Deskripsi:</label>
    <textarea name="deskripsi" required><?php echo $wisata['deskripsi']; ?></textarea><br>
    <label for="gambar">Gambar (URL atau path):</label>
    <input type="text" name="gambar" value="<?php echo $wisata['gambar']; ?>" required><br>
    <button type="submit">Simpan Perubahan</button>
</form>
<?php include '../template/footer.php'; ?>