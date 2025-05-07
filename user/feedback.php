<?php
include_once '../config/database.php';
include '../template/header_user.php';


if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nama = $_POST['nama'];
    $email = $_POST['email'];
    $pesan = $_POST['pesan'];

    $query = "INSERT INTO feedback (nama, email, pesan) VALUES ('$nama', '$email', '$pesan')";
    mysqli_query($conn, $query);
    echo "<div class='alert alert-success'>Feedback berhasil dikirim!</div>";
}
?>

<div class="container mt-5">
    <h2>Beri Feedback</h2>
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
            <label>Pesan</label>
            <textarea name="pesan" class="form-control" required></textarea>
        </div>
        <button type="submit" class="btn btn-primary">Kirim</button>
    </form>
</div>

<?php include_once '../template/footer.php'; ?>