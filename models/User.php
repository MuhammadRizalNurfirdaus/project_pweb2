<?php
// File: C:\xampp\htdocs\Cilengkrang-Web-Wisata\models\User.php

class User
{
    private static $table_name = "users";
    private static $db;

    private const ALLOWED_ROLES = ['user', 'admin', 'editor'];
    private const ALLOWED_ACCOUNT_STATUSES = ['aktif', 'non-aktif', 'diblokir'];

    public static function setDbConnection(mysqli $connection)
    {
        self::$db = $connection;
    }

    private static function checkDbConnection()
    {
        if (!self::$db || (self::$db instanceof mysqli && self::$db->connect_error)) {
            error_log(get_called_class() . " - Koneksi database tidak tersedia atau gagal: " .
                (self::$db instanceof mysqli ? self::$db->connect_error : (self::$db === null ? 'Koneksi belum diset.' : 'Koneksi bukan objek mysqli.')));
            return false;
        }
        return true;
    }

    public static function getLastError()
    {
        if (self::$db instanceof mysqli && !empty(self::$db->error)) {
            return self::$db->error;
        } elseif (!self::$db || !(self::$db instanceof mysqli)) {
            return 'Koneksi database belum diinisialisasi atau tidak valid untuk kelas ' . get_called_class() . '.';
        }
        return 'Tidak ada error database spesifik yang dilaporkan dari model ' . get_called_class() . '.';
    }

    public static function register($data)
    {
        if (!self::checkDbConnection()) return false;

        // Menggunakan 'nama' sesuai struktur tabel Anda
        $nama_input = trim($data['nama'] ?? ''); // PERBAIKAN: dari 'nama_lengkap' atau 'nama' ke 'nama'
        $email_input = trim($data['email'] ?? '');
        $password_input = $data['password'] ?? '';

        if (empty($nama_input) || empty($email_input) || empty($password_input)) {
            return 'missing_fields';
        }
        if (!filter_var($email_input, FILTER_VALIDATE_EMAIL)) {
            return 'email_invalid';
        }
        if (strlen($password_input) < 6) {
            return 'password_short';
        }

        $sql_check = "SELECT id FROM " . self::$table_name . " WHERE email = ? LIMIT 1";
        $stmt_check = mysqli_prepare(self::$db, $sql_check);
        if (!$stmt_check) {
            error_log(get_called_class() . "::register() Prepare Check Error: " . mysqli_error(self::$db));
            return false;
        }
        mysqli_stmt_bind_param($stmt_check, "s", $email_input);
        mysqli_stmt_execute($stmt_check);
        $result_check = mysqli_stmt_get_result($stmt_check);
        if (mysqli_fetch_assoc($result_check)) {
            mysqli_stmt_close($stmt_check);
            return 'email_exists';
        }
        mysqli_stmt_close($stmt_check);

        $hashed_password = password_hash($password_input, PASSWORD_DEFAULT);
        if ($hashed_password === false) {
            error_log(get_called_class() . "::register() Hashing Error");
            return false;
        }

        $nama = $nama_input;
        $email_clean = $email_input;
        $no_hp = isset($data['no_hp']) && !empty($data['no_hp']) ? trim($data['no_hp']) : null;
        $alamat = isset($data['alamat']) && !empty($data['alamat']) ? trim($data['alamat']) : null;
        $role_input = strtolower(trim($data['role'] ?? 'user'));
        $role = in_array($role_input, self::ALLOWED_ROLES) ? $role_input : 'user';
        // Asumsi tabel users BELUM punya status_akun, jadi dikomentari
        // $status_akun_input = strtolower(trim($data['status_akun'] ?? 'aktif'));
        // $status_akun = in_array($status_akun_input, self::ALLOWED_ACCOUNT_STATUSES) ? $status_akun_input : 'aktif';

        // PERBAIKAN: kolom `nama` bukan `nama_lengkap`. Hapus `status_akun` jika tidak ada di DB.
        $sql_insert = "INSERT INTO " . self::$table_name .
            " (nama, email, password, no_hp, alamat, role, created_at, updated_at) 
                      VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())";
        $stmt_insert = mysqli_prepare(self::$db, $sql_insert);

        if (!$stmt_insert) {
            error_log(get_called_class() . "::register() Prepare Insert Error: " . mysqli_error(self::$db));
            return false;
        }

        // PERBAIKAN: Sesuaikan tipe bind_param jika status_akun dihilangkan (6 's')
        mysqli_stmt_bind_param($stmt_insert, "ssssss", $nama, $email_clean, $hashed_password, $no_hp, $alamat, $role);

        if (mysqli_stmt_execute($stmt_insert)) {
            $new_user_id = mysqli_insert_id(self::$db);
            mysqli_stmt_close($stmt_insert);
            return $new_user_id;
        } else {
            error_log(get_called_class() . "::register() - MySQLi Execute Error (Insert User): " . mysqli_stmt_error($stmt_insert));
            mysqli_stmt_close($stmt_insert);
            return false;
        }
    }

    public static function login($email, $password_input)
    {
        if (!self::checkDbConnection()) return false;
        $email_clean = trim($email);

        // PERBAIKAN: `nama` bukan `nama_lengkap`. Hapus `status_akun` jika tidak ada.
        $sql = "SELECT id, nama, email, password, role, no_hp, alamat FROM " . self::$table_name . " WHERE email = ? LIMIT 1";
        $stmt = mysqli_prepare(self::$db, $sql);
        if (!$stmt) { /* ... error log ... */
            return false;
        }

        mysqli_stmt_bind_param($stmt, "s", $email_clean);
        if (!mysqli_stmt_execute($stmt)) { /* ... error log ... */
            mysqli_stmt_close($stmt);
            return false;
        }

        $result = mysqli_stmt_get_result($stmt);
        $user = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);

        if ($user) {
            // Tambahkan pengecekan status akun jika kolomnya ada
            // if (isset($user['status_akun']) && strtolower($user['status_akun']) !== 'aktif') {
            //     error_log(get_called_class() . "::login() - Login Gagal: Akun tidak aktif untuk email - " . $email_clean);
            //     return 'inactive_account';
            // }
            if (password_verify($password_input, $user['password'])) {
                unset($user['password']);
                return $user;
            } else {
                return 'wrong_password';
            }
        } else {
            return 'not_found';
        }
    }

    public static function findById($id)
    {
        if (!self::checkDbConnection()) return null;
        $id_val = filter_var($id, FILTER_VALIDATE_INT);
        if ($id_val === false || $id_val <= 0) {
            return null;
        }

        // PERBAIKAN: `nama` bukan `nama_lengkap`. Hapus `status_akun` jika tidak ada.
        $sql = "SELECT id, nama, email, no_hp, alamat, role, created_at, updated_at FROM " . self::$table_name . " WHERE id = ? LIMIT 1";
        $stmt = mysqli_prepare(self::$db, $sql);
        if (!$stmt) { /* ... error log ... */
            return null;
        }

        mysqli_stmt_bind_param($stmt, "i", $id_val);
        if (mysqli_stmt_execute($stmt)) {
            $result = mysqli_stmt_get_result($stmt);
            $user = mysqli_fetch_assoc($result);
            mysqli_stmt_close($stmt);
            return $user ?: null;
        }
        mysqli_stmt_close($stmt);
        return null;
    }

    public static function findByEmail($email)
    {
        if (!self::checkDbConnection()) return null;
        $email_clean = trim($email);
        if (!filter_var($email_clean, FILTER_VALIDATE_EMAIL)) {
            return null;
        }

        // PERBAIKAN: `nama` bukan `nama_lengkap`. Hapus `status_akun` jika tidak ada.
        $sql = "SELECT id, nama, email, no_hp, alamat, role, created_at, updated_at FROM " . self::$table_name . " WHERE email = ? LIMIT 1";
        $stmt = mysqli_prepare(self::$db, $sql);
        if (!$stmt) { /* ... error log ... */
            return null;
        }

        mysqli_stmt_bind_param($stmt, "s", $email_clean);
        if (mysqli_stmt_execute($stmt)) {
            $result = mysqli_stmt_get_result($stmt);
            $user = mysqli_fetch_assoc($result);
            mysqli_stmt_close($stmt);
            return $user ?: null;
        }
        mysqli_stmt_close($stmt);
        return null;
    }

    public static function getAll()
    {
        if (!self::checkDbConnection()) return [];

        // PERBAIKAN: `nama` bukan `nama_lengkap`. Hapus `status_akun` jika tidak ada.
        $sql = "SELECT id, nama, email, no_hp, alamat, role, created_at FROM " . self::$table_name . " ORDER BY nama ASC";
        $result = mysqli_query(self::$db, $sql);
        if ($result) {
            $data = mysqli_fetch_all($result, MYSQLI_ASSOC);
            mysqli_free_result($result);
            return $data;
        }
        return [];
    }

    public static function update($data)
    {
        if (!self::checkDbConnection() || !isset($data['id'])) {
            return false;
        }
        $id = (int)$data['id'];
        if ($id <= 0) {
            return false;
        }

        $fields_to_update = [];
        $params = [];
        $types = "";

        // PERBAIKAN: dari 'nama_lengkap' ke 'nama'
        if (isset($data['nama'])) {
            $fields_to_update[] = "nama = ?";
            $params[] = trim($data['nama']);
            $types .= "s";
        }
        // ... (sisa logika update, pastikan `status_akun` di-handle jika kolomnya ada atau dihapus jika tidak) ...
        // Contoh jika status_akun ada di $data dan di DB:
        // if (isset($data['status_akun'])) {
        //     $status_akun_input = strtolower(trim($data['status_akun']));
        //     if (in_array($status_akun_input, self::ALLOWED_ACCOUNT_STATUSES)) {
        //         if ($id == 1 && $status_akun_input !== 'aktif') { /* proteksi admin utama */ }
        //         else { $fields_to_update[] = "status_akun = ?"; $params[] = $status_akun_input; $types .= "s"; }
        //     }
        // }
        if (isset($data['email'])) {
            $email_clean = trim($data['email']);
            if (filter_var($email_clean, FILTER_VALIDATE_EMAIL)) {
                $sql_check = "SELECT id FROM " . self::$table_name . " WHERE email = ? AND id != ? LIMIT 1";
                $stmt_check = mysqli_prepare(self::$db, $sql_check);
                mysqli_stmt_bind_param($stmt_check, "si", $email_clean, $id);
                mysqli_stmt_execute($stmt_check);
                $result_check = mysqli_stmt_get_result($stmt_check);
                if (mysqli_fetch_assoc($result_check)) {
                    mysqli_stmt_close($stmt_check);
                    if (function_exists('set_flash_message')) set_flash_message('danger', 'Email sudah terdaftar.');
                    return 'email_exists';
                }
                mysqli_stmt_close($stmt_check);
                $fields_to_update[] = "email = ?";
                $params[] = $email_clean;
                $types .= "s";
            }
        }
        if (array_key_exists('no_hp', $data)) {
            $fields_to_update[] = "no_hp = ?";
            $params[] = !empty(trim($data['no_hp'])) ? trim($data['no_hp']) : null;
            $types .= "s";
        }
        if (array_key_exists('alamat', $data)) {
            $fields_to_update[] = "alamat = ?";
            $params[] = !empty(trim($data['alamat'])) ? trim($data['alamat']) : null;
            $types .= "s";
        }
        if (isset($data['role'])) {
            $role_input = strtolower(trim($data['role']));
            if (in_array($role_input, self::ALLOWED_ROLES)) {
                if (!($id == 1 && $role_input !== 'admin')) {
                    $fields_to_update[] = "role = ?";
                    $params[] = $role_input;
                    $types .= "s";
                }
            }
        }
        if (isset($data['password']) && !empty($data['password'])) {
            if (strlen($data['password']) >= 6) {
                $hashed_password = password_hash($data['password'], PASSWORD_DEFAULT);
                if ($hashed_password) {
                    $fields_to_update[] = "password = ?";
                    $params[] = $hashed_password;
                    $types .= "s";
                }
            }
        }


        if (empty($fields_to_update)) {
            return true;
        }

        $fields_to_update[] = "updated_at = NOW()";
        $sql = "UPDATE " . self::$table_name . " SET " . implode(', ', $fields_to_update) . " WHERE id = ?";
        $params[] = $id;
        $types .= "i";

        $stmt = mysqli_prepare(self::$db, $sql);
        if (!$stmt) {
            return false;
        }

        mysqli_stmt_bind_param($stmt, $types, ...$params);
        if (mysqli_stmt_execute($stmt)) {
            $affected_rows = mysqli_stmt_affected_rows($stmt);
            mysqli_stmt_close($stmt);
            return $affected_rows >= 0;
        }
        mysqli_stmt_close($stmt);
        return false;
    }

    public static function updatePassword($user_id, $new_password)
    {
        if (!self::checkDbConnection()) return false;
        $id_val = (int)$user_id;
        if ($id_val <= 0 || empty($new_password) || strlen($new_password) < 6) {
            return false;
        }
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        if (!$hashed_password) {
            return false;
        }
        $sql = "UPDATE " . self::$table_name . " SET password = ?, updated_at = NOW() WHERE id = ?";
        $stmt = mysqli_prepare(self::$db, $sql);
        if (!$stmt) {
            return false;
        }
        mysqli_stmt_bind_param($stmt, "si", $hashed_password, $id_val);
        if (mysqli_stmt_execute($stmt)) {
            mysqli_stmt_close($stmt);
            return true;
        }
        mysqli_stmt_close($stmt);
        return false;
    }
    // Jika Anda menambahkan kolom status_akun ke tabel:
    // public static function updateStatusAkun($user_id, $new_status_akun) {
    //     if (!self::checkDbConnection()) return false;
    //     $id_val = (int)$user_id;
    //     $status_val = strtolower(trim($new_status_akun));
    //     if ($id_val <= 0 || !in_array($status_val, self::ALLOWED_ACCOUNT_STATUSES)) return false;
    //     if ($id_val == 1 && $status_val !== 'aktif') return false; // Admin utama
    //     $sql = "UPDATE " . self::$table_name . " SET status_akun = ?, updated_at = NOW() WHERE id = ?";
    //     $stmt = mysqli_prepare(self::$db, $sql);
    //     if (!$stmt) return false;
    //     mysqli_stmt_bind_param($stmt, "si", $status_val, $id_val);
    //     if (mysqli_stmt_execute($stmt)) { mysqli_stmt_close($stmt); return true; }
    //     mysqli_stmt_close($stmt);
    //     return false;
    // }

    public static function delete($id)
    {
        if (!self::checkDbConnection()) return false;
        $id_val = filter_var($id, FILTER_VALIDATE_INT);
        if ($id_val === false || $id_val <= 0 || $id_val == 1) {
            if ($id_val == 1 && function_exists('set_flash_message')) set_flash_message('danger', 'Admin utama tidak dapat dihapus.');
            return false;
        }
        $sql = "DELETE FROM " . self::$table_name . " WHERE id = ?";
        $stmt = mysqli_prepare(self::$db, $sql);
        if (!$stmt) {
            return false;
        }
        mysqli_stmt_bind_param($stmt, "i", $id_val);
        if (mysqli_stmt_execute($stmt)) {
            $affected_rows = mysqli_stmt_affected_rows($stmt);
            mysqli_stmt_close($stmt);
            return $affected_rows > 0;
        }
        mysqli_stmt_close($stmt);
        return false;
    }
    public static function countAll()
    {
        if (!self::checkDbConnection()) return 0;
        $sql = "SELECT COUNT(id) as total FROM " . self::$table_name;
        $result = mysqli_query(self::$db, $sql);
        if ($result) {
            $row = mysqli_fetch_assoc($result);
            mysqli_free_result($result);
            return (int)($row['total'] ?? 0);
        }
        return 0;
    }
} // End of class User