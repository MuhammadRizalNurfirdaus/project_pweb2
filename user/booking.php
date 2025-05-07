<?php
include_once '../config/database.php';
include '../template/header_user.php';


if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nama = $_POST['nama'];
    $email = $_POST['email'];
    $tanggal = $_POST['tanggal'];
    $jumlah = $_POST['jumlah'];

    $query = "INSERT INTO booking (nama, email, tanggal, jumlah) VALUES ('$nama', '$email', '$tanggal', '$jumlah')";
    mysqli_query($conn, $query);
    echo "<div class='alert alert-success'>Pemesanan berhasil dilakukan!</div>";
}
?>

<div class="container mt-5">
    <h2>Form Pemesanan Tiket</h2>
    <form method="POST">
        <div class="mb-3">
            <label>Nama</label>
            <input type="text" name="nama" class="form-control" required>
        </div>
        <div class="mb-3">
            <label>Email</label>
            <input type="email" name="email" class="form-control" required>
        </div>
        <div class="mb-3">
            <label>Tanggal Kunjungan</label>
            <input type="date" name="tanggal" class="form-control" required>
        </div>
        <div class="mb-3">
            <label>Jumlah Tiket</label>
            <input type="number" name="jumlah" class="form-control" required>
        </div>
        <button type="submit" class="btn btn-success">Pesan Tiket</button>
    </form>
</div>

<?php include_once '../template/footer.php'; ?>