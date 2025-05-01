<?php
session_start();
if ($_SESSION['role'] != 'admin') header('Location: ../auth/login.php');
require_once '../config/Koneksi.php';
$conn = Koneksi::getInstance();

// Tangani form submit
if (isset($_POST['simpan'])) {
    $wisata_id = $_POST['wisata_id'];
    $nama_file  = $_POST['nama_file'];
    $stmt = $conn->prepare("INSERT INTO galeri (wisata_id, nama_file) VALUES (?, ?)");
    $stmt->bind_param("is", $wisata_id, $nama_file);
    if ($stmt->execute()) {
        header('Location: kelola_galeri.php');
        exit;
    } else {
        echo "Gagal tambah foto.";
    }
}

// Ambil daftar wisata untuk dropdown
$wisata = $conn->query("SELECT id,nama FROM wisata");
?>
<?php $title = 'Tambah Foto Galeri';
include '../template/header.php'; ?>
<h2>Tambah Foto Galeri</h2>
<form action="" method="POST">
    <label>Wisata:</label>
    <select name="wisata_id" required>
        <option value="">--Pilih--</option>
        <?php while ($w = $wisata->fetch_assoc()): ?>
            <option value="<?php echo $w['id']; ?>"><?php echo $w['nama']; ?></option>
        <?php endwhile; ?>
    </select><br>
    <label>Nama File (path/gambar.jpg):</label>
    <input type="text" name="nama_file" required><br><br>
    <button type="submit" name="simpan">Simpan</button>
</form>
<?php include '../template/footer.php'; ?>