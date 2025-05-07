<?php
include_once '../config/Koneksi.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nama = $_POST['nama'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $no_hp = $_POST['no_hp'];
    $alamat = $_POST['alamat'];

    $query = "INSERT INTO users (nama, email, password, no_hp, alamat, role) 
              VALUES ('$nama', '$email', '$password', '$no_hp', '$alamat', 'user')";

    if (mysqli_query($conn, $query)) {
        echo "<div class='alert alert-success'>Registrasi berhasil. Silakan login.</div>";
    } else {
        echo "<div class='alert alert-danger'>Registrasi gagal.</div>";
    }
}
?>

<?php include_once '../template/header.php'; ?>

<div class="container mt-5">
    <h2>Registrasi</h2>
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
            <label>Password</label>
            <input type="password" name="password" class="form-control" required>
        </div>
        <div class="mb-3">
            <label>No HP</label>
            <input type="text" name="no_hp" class="form-control" required>
        </div>
        <div class="mb-3">
            <label>Alamat</label>
            <input type="text" name="alamat" class="form-control" required>
        </div>
        <button class="btn btn-success">Daftar</button>
    </form>
</div>

<?php include_once '../template/footer.php'; ?>