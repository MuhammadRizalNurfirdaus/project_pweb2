<?php
// auth/register.php

// Mulai session (tidak wajib jika register tidak memakai session)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Muat konfigurasi & koneksi
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/Koneksi.php';

// Jika sudah login, redirect saja
if (isset($_SESSION['role'])) {
    header('Location: ' . BASE_URL . 'index.php');
    exit;
}

$error = '';
$success = '';

// Proses form register
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Ambil data
    $nama     = trim($_POST['nama_lengkap'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    // Validasi sederhana
    if (empty($nama) || empty($email) || empty($password)) {
        $error = 'Semua field wajib diisi.';
    } else {
        // Hash password
        $hash = password_hash($password, PASSWORD_DEFAULT);

        // Koneksi DB
        $conn = Koneksi::getInstance();

        // Cek email sudah terdaftar?
        $stmt = $conn->prepare("SELECT id FROM user WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $error = 'Email sudah terdaftar.';
        } else {
            // Insert user baru
            $stmt = $conn->prepare("INSERT INTO user (nama_lengkap, email, password) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $nama, $email, $hash);
            if ($stmt->execute()) {
                $success = 'Registrasi berhasil! <a href="' . BASE_URL . 'auth/login.php">Login di sini</a>.';
            } else {
                $error = 'Gagal registrasi, silakan coba lagi.';
            }
        }
    }
}
?>
<?php
// Judul halaman
$title = 'Register - Cilengkrang';
include __DIR__ . '/../template/header.php';
?>

<div class="auth-form">
    <h2>Registrasi Akun</h2>
    <?php if ($error): ?>
        <p class="error"><?php echo htmlspecialchars($error); ?></p>
    <?php elseif ($success): ?>
        <p class="success"><?php echo $success; ?></p>
    <?php endif; ?>

    <?php if (!$success): // Tampilkan form kalau belum sukses 
    ?>
        <form action="" method="POST">
            <label for="nama_lengkap">Nama Lengkap:</label>
            <input type="text" name="nama_lengkap" id="nama_lengkap" required value="<?php echo htmlspecialchars($nama ?? ''); ?>">

            <label for="email">Email:</label>
            <input type="email" name="email" id="email" required value="<?php echo htmlspecialchars($email ?? ''); ?>">

            <label for="password">Password:</label>
            <input type="password" name="password" id="password" required>

            <button type="submit">Daftar</button>
        </form>
        <p>Sudah punya akun? <a href="<?php echo BASE_URL; ?>auth/login.php">Login di sini</a>.</p>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../template/footer.php'; ?>