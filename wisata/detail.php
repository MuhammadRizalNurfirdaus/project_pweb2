<?php
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
<?php $title = 'Detail Wisata';
include '../template/header.php'; ?>
<h2>Detail Wisata</h2>
<h3><?php echo $wisata['nama']; ?></h3>
<p><strong>Harga:</strong> <?php echo $wisata['harga']; ?></p>
<p><strong>Deskripsi:</strong><br> <?php echo nl2br($wisata['deskripsi']); ?></p>
<p><strong>Lokasi:</strong> <?php echo $wisata['lokasi']; ?></p>
<form action="proses_booking.php" method="POST">
    <input type="hidden" name="wisata_id" value="<?php echo $wisata['id']; ?>">
    <label for="jumlah">Jumlah Orang:</label>
    <input type="number" name="jumlah" id="jumlah" required><br>
    <label for="tanggal">Tanggal Booking:</label>
    <input type="date" name="tanggal" id="tanggal" required><br>
    <button type="submit">Booking</button>
</form>
<?php include '../template/footer.php'; ?>