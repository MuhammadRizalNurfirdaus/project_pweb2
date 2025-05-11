<?php
// File: C:\xampp\htdocs\Cilengkrang-Web-Wisata\models\User.php
// Versi AMAN dengan password_hash dan password_verify

class User
{
    private static $table_name = "users"; // Pastikan nama tabel users Anda benar

    /**
     * Registrasi pengguna baru dengan password yang di-hash.
     * @param array $data Data pengguna [nama, email, password, no_hp (ops), alamat (ops), role (ops)]
     * @return int|string|false ID pengguna baru jika sukses, string error ('email_exists', 'password_short', 'email_invalid'), atau false jika gagal.
     */
    public static function register($data)
    {
        global $conn;

        // Validasi dasar input
        if (empty($data['nama']) || empty($data['email']) || empty($data['password'])) {
            error_log("Registrasi Gagal: Field wajib (nama, email, password) tidak diisi.");
            return false;
        }
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            error_log("Registrasi Gagal: Format email tidak valid untuk - " . $data['email']);
            return 'email_invalid';
        }
        if (strlen($data['password']) < 6) { // Aturan panjang password minimal
            error_log("Registrasi Gagal: Password terlalu pendek untuk email " . $data['email']);
            return 'password_short';
        }

        // 1. Cek apakah email sudah ada untuk mencegah duplikasi
        $email_to_check = $data['email'];
        $sql_check = "SELECT id FROM " . self::$table_name . " WHERE email = ? LIMIT 1";
        $stmt_check = mysqli_prepare($conn, $sql_check);
        if (!$stmt_check) {
            error_log("MySQLi Prepare Error (User Register Check Email): " . mysqli_error($conn));
            return false;
        }
        mysqli_stmt_bind_param($stmt_check, "s", $email_to_check);
        if (!mysqli_stmt_execute($stmt_check)) {
            error_log("MySQLi Execute Error (User Register Check Email): " . mysqli_stmt_error($stmt_check));
            mysqli_stmt_close($stmt_check);
            return false;
        }
        $result_check = mysqli_stmt_get_result($stmt_check);
        if (mysqli_fetch_assoc($result_check)) {
            mysqli_stmt_close($stmt_check);
            error_log("Registrasi Gagal: Email sudah terdaftar - " . $email_to_check);
            return 'email_exists';
        }
        mysqli_stmt_close($stmt_check);

        // 2. Hash password sebelum disimpan ke database
        $hashed_password = password_hash($data['password'], PASSWORD_DEFAULT); // PASSWORD_DEFAULT adalah yang terbaik
        if ($hashed_password === false) {
            error_log("Registrasi Gagal: Gagal melakukan hashing password untuk email " . $data['email']);
            return false;
        }

        // Sanitasi input lainnya
        $nama = htmlspecialchars(strip_tags($data['nama']));
        $email_clean = htmlspecialchars(strip_tags($data['email']));
        $no_hp = isset($data['no_hp']) && !empty($data['no_hp']) ? htmlspecialchars(strip_tags($data['no_hp'])) : null;
        $alamat = isset($data['alamat']) && !empty($data['alamat']) ? htmlspecialchars(strip_tags($data['alamat'])) : null;
        $role = $data['role'] ?? 'user'; // Default role adalah 'user' jika tidak dispesifikkan

        // 3. Insert pengguna baru ke database
        $sql_insert = "INSERT INTO " . self::$table_name . " (nama, email, password, no_hp, alamat, role) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt_insert = mysqli_prepare($conn, $sql_insert);

        if (!$stmt_insert) {
            error_log("MySQLi Prepare Error (User Register Insert): " . mysqli_error($conn));
            return false;
        }

        mysqli_stmt_bind_param($stmt_insert, "ssssss", $nama, $email_clean, $hashed_password, $no_hp, $alamat, $role);

        if (mysqli_stmt_execute($stmt_insert)) {
            $new_user_id = mysqli_insert_id($conn);
            mysqli_stmt_close($stmt_insert);
            return $new_user_id; // Mengembalikan ID user baru jika registrasi berhasil
        } else {
            error_log("MySQLi Execute Error (User Register Insert): " . mysqli_stmt_error($stmt_insert) . " untuk email " . $email_clean);
            mysqli_stmt_close($stmt_insert);
            return false;
        }
    }

    /**
     * Proses login pengguna dengan memverifikasi password yang di-hash.
     * @param string $email Email pengguna
     * @param string $password_input Password mentah yang dimasukkan pengguna
     * @return array|false Data pengguna (tanpa hash password) jika login sukses, false jika gagal.
     */
    public static function login($email, $password_input)
    {
        global $conn;

        $sql = "SELECT id, nama, email, password, role FROM " . self::$table_name . " WHERE email = ? LIMIT 1";
        $stmt = mysqli_prepare($conn, $sql);

        if (!$stmt) {
            error_log("MySQLi Prepare Error (User Login): " . mysqli_error($conn) . " untuk email: " . $email);
            return false;
        }

        mysqli_stmt_bind_param($stmt, "s", $email);
        if (!mysqli_stmt_execute($stmt)) {
            error_log("MySQLi Execute Error (User Login): " . mysqli_stmt_error($stmt) . " untuk email: " . $email);
            mysqli_stmt_close($stmt);
            return false;
        }

        $result = mysqli_stmt_get_result($stmt);
        $user = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);

        if ($user) {
            // Verifikasi password yang diinput dengan hash di database menggunakan password_verify()
            if (password_verify($password_input, $user['password'])) {
                unset($user['password']); // Sangat penting: Jangan kembalikan hash password ke session atau controller!
                return $user; // Login berhasil, kembalikan data user (tanpa hash password)
            } else {
                // Password tidak cocok
                error_log("Login Gagal: Password salah untuk email - " . $email);
                return false;
            }
        } else {
            // User (email) tidak ditemukan
            error_log("Login Gagal: Email tidak ditemukan - " . $email);
            return false;
        }
    }
}
