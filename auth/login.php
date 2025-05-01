<?php
// login.php

session_start();

// Load konfigurasi dan koneksi
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/Koneksi.php';

// Jika sudah login, alihkan ke dashboard sesuai role
if (isset($_SESSION['role'])) {
    if ($_SESSION['role'] === 'admin') {
        header('Location: ' . BASE_URL . 'admin/dashboard.php');
    } else {
        header('Location: ' . BASE_URL . 'user/dashboard.php');
    }
    exit;
}

// Proses login saat form disubmit
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userInput = trim($_POST['user'] ?? '');
    $passInput = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? '';

    // Koneksi ke database
    $conn = Koneksi::getInstance();

    if ($role == 'admin') {
        // Cek admin
        $stmt = $conn->prepare("SELECT * FROM admin WHERE username = ?");
        $stmt->bind_param("s", $userInput);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($admin = $result->fetch_assoc()) {
            // DEBUG
            echo 'Input Password: ' . htmlspecialchars($passInput) . '<br>';
            echo 'Hash di DB: ' . htmlspecialchars($admin['password']) . '<br>';

            if (password_verify($passInput, $admin['password'])) {
                $_SESSION['role'] = 'admin';
                $_SESSION['nama'] = $admin['nama_lengkap'];
                $_SESSION['user_id'] = $admin['id'];
                header('Location: ' . BASE_URL . 'admin/dashboard.php');
                exit;
            } else {
                $error = 'Password salah untuk admin.';
            }
        } else {
            $error = 'Admin tidak ditemukan.';
        }
    } elseif ($role == 'user') {
        // Cek user
        $stmt = $conn->prepare("SELECT * FROM user WHERE email = ?");
        $stmt->bind_param("s", $userInput);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($user = $result->fetch_assoc()) {
            if (password_verify($passInput, $user['password'])) {
                $_SESSION['role'] = 'user';
                $_SESSION['nama'] = $user['nama_lengkap'];
                $_SESSION['user_id'] = $user['id'];
                header('Location: ' . BASE_URL . 'user/dashboard.php');
                exit;
            } else {
                $error = 'Password salah untuk user.';
            }
        } else {
            $error = 'User tidak ditemukan.';
        }
    } else {
        $error = 'Pilih role (admin atau user).';
    }
}
?>

<?php $title = 'Login - Cilengkrang Wisata';
include __DIR__ . '/../template/header.php'; ?>

<div class="auth-form">
    <h2>Login</h2>
    <?php if ($error): ?>
        <p class="error"><?php echo htmlspecialchars($error); ?></p>
    <?php endif; ?>

    <form action="" method="POST">
        <label for="user">Username / Email:</label>
        <input type="text" name="user" id="user" required>

        <label for="password">Password:</label>
        <input type="password" name="password" id="password" required>

        <label for="role">Login Sebagai:</label>
        <select name="role" id="role">
            <option value="admin">Admin</option>
            <option value="user">User</option>
        </select>

        <button type="submit">Login</button>
    </form>

    <p>Belum punya akun? <a href="<?php echo BASE_URL; ?>auth/register.php">Daftar di sini</a></p>
</div>

<?php include __DIR__ . '/../template/footer.php'; ?>