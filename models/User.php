<?php
// File: C:\xampp\htdocs\Cilengkrang-Web-Wisata\models\User.php

class User
{
    private static $table_name = "users";
    private static $db;

    // Daftar role yang diizinkan
    public const ALLOWED_ROLES = ['user', 'admin']; // Disesuaikan: HANYA user dan admin
    // Daftar status akun yang diizinkan (sesuai dengan COMMENT di database Anda)
    public const ALLOWED_ACCOUNT_STATUSES = ['aktif', 'non-aktif', 'diblokir'];

    /**
     * Mengatur koneksi database untuk digunakan oleh kelas ini.
     * @param mysqli $connection Instance koneksi mysqli.
     */
    public static function setDbConnection(mysqli $connection)
    {
        self::$db = $connection;
    }

    /**
     * Memeriksa apakah koneksi database tersedia.
     * @return bool True jika koneksi valid, false jika tidak.
     */
    private static function checkDbConnection()
    {
        if (!self::$db || (self::$db instanceof mysqli && self::$db->connect_error)) {
            error_log(get_called_class() . " - Koneksi database tidak tersedia atau gagal: " .
                (self::$db instanceof mysqli ? self::$db->connect_error : (self::$db === null ? 'Koneksi belum diset.' : 'Koneksi bukan objek mysqli.')));
            return false;
        }
        return true;
    }

    /**
     * Mengambil pesan error terakhir dari koneksi database model ini.
     * @return string Pesan error.
     */
    public static function getLastError()
    {
        if (self::$db instanceof mysqli && !empty(self::$db->error)) {
            return self::$db->error;
        } elseif (!self::$db || !(self::$db instanceof mysqli)) {
            return 'Koneksi database belum diinisialisasi atau tidak valid untuk kelas ' . get_called_class() . '.';
        }
        return 'Tidak ada error database spesifik yang dilaporkan dari model ' . get_called_class() . '.';
    }

    /**
     * Registrasi pengguna baru.
     * @param array $data Kunci yang diharapkan: 'nama' (wajib), 'email' (wajib), 'password' (wajib).
     *                    Opsional: 'nama_lengkap', 'no_hp', 'alamat', 'role', 'status_akun'.
     * @return int|string|false ID pengguna baru, string error, atau false.
     */
    public static function register($data)
    {
        if (!self::checkDbConnection()) return false;

        $nama_input = trim($data['nama'] ?? '');
        $nama_lengkap_input = isset($data['nama_lengkap']) && !empty(trim($data['nama_lengkap'])) ? trim($data['nama_lengkap']) : null;
        $email_input = trim($data['email'] ?? '');
        $password_input = $data['password'] ?? '';

        if (empty($nama_input) || empty($email_input) || empty($password_input)) {
            error_log(get_called_class() . "::register() - Gagal: Field wajib (nama, email, password) tidak diisi.");
            return 'missing_fields';
        }
        if (!filter_var($email_input, FILTER_VALIDATE_EMAIL)) {
            error_log(get_called_class() . "::register() - Gagal: Format email tidak valid: " . $email_input);
            return 'email_invalid';
        }
        if (strlen($password_input) < 6) {
            error_log(get_called_class() . "::register() - Gagal: Password pendek: " . $email_input);
            return 'password_short';
        }

        $sql_check = "SELECT id FROM " . self::$table_name . " WHERE email = ? LIMIT 1";
        $stmt_check = mysqli_prepare(self::$db, $sql_check);
        if (!$stmt_check) {
            error_log(get_called_class() . "::register() Prepare Check Email Error: " . mysqli_error(self::$db));
            return false;
        }
        mysqli_stmt_bind_param($stmt_check, "s", $email_input);
        if (!mysqli_stmt_execute($stmt_check)) {
            error_log(get_called_class() . "::register() Execute Check Email Error: " . mysqli_stmt_error($stmt_check));
            mysqli_stmt_close($stmt_check);
            return false;
        }
        $result_check = mysqli_stmt_get_result($stmt_check);
        if (mysqli_fetch_assoc($result_check)) {
            mysqli_stmt_close($stmt_check);
            error_log(get_called_class() . "::register() - Gagal: Email sudah terdaftar: " . $email_input);
            return 'email_exists';
        }
        mysqli_stmt_close($stmt_check);

        $hashed_password = password_hash($password_input, PASSWORD_DEFAULT);
        if ($hashed_password === false) {
            error_log(get_called_class() . "::register() Hashing Error");
            return false;
        }

        $no_hp = isset($data['no_hp']) && !empty(trim($data['no_hp'])) ? trim($data['no_hp']) : null;
        $alamat = isset($data['alamat']) && !empty(trim($data['alamat'])) ? trim($data['alamat']) : null;
        $role_input = strtolower(trim($data['role'] ?? 'user'));
        $role = in_array($role_input, self::ALLOWED_ROLES) ? $role_input : 'user';
        $status_akun_input = strtolower(trim($data['status_akun'] ?? 'aktif'));
        $status_akun = in_array($status_akun_input, self::ALLOWED_ACCOUNT_STATUSES) ? $status_akun_input : 'aktif';

        // Kolom created_at akan diisi DEFAULT oleh DB. Kolom updated_at tidak ada.
        $sql_insert = "INSERT INTO " . self::$table_name .
            " (nama, nama_lengkap, email, password, no_hp, alamat, role, status_akun) 
                      VALUES (?, ?, ?, ?, ?, ?, ?, ?)"; // 8 placeholders
        $stmt_insert = mysqli_prepare(self::$db, $sql_insert);

        if (!$stmt_insert) {
            error_log(get_called_class() . "::register() Prepare Insert Error: " . mysqli_error(self::$db));
            return false;
        }

        mysqli_stmt_bind_param(
            $stmt_insert,
            "ssssssss",
            $nama_input,
            $nama_lengkap_input,
            $email_input,
            $hashed_password,
            $no_hp,
            $alamat,
            $role,
            $status_akun
        );

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

        $sql = "SELECT id, nama, nama_lengkap, email, password, role, no_hp, alamat, status_akun 
                FROM " . self::$table_name . " WHERE email = ? LIMIT 1";
        $stmt = mysqli_prepare(self::$db, $sql);
        if (!$stmt) {
            error_log(get_called_class() . "::login() Prepare Error: " . mysqli_error(self::$db));
            return false;
        }

        mysqli_stmt_bind_param($stmt, "s", $email_clean);
        if (!mysqli_stmt_execute($stmt)) {
            error_log(get_called_class() . "::login() Execute Error: " . mysqli_stmt_error($stmt));
            mysqli_stmt_close($stmt);
            return false;
        }

        $result = mysqli_stmt_get_result($stmt);
        $user = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);

        if ($user) {
            if (isset($user['status_akun']) && strtolower($user['status_akun']) !== 'aktif') {
                return 'inactive_account';
            }
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
            error_log(get_called_class() . "::findById() ID tidak valid: " . $id);
            return null;
        }

        // Hanya ambil kolom yang ada di tabel
        $sql = "SELECT id, nama, nama_lengkap, email, no_hp, alamat, role, status_akun, created_at 
                FROM " . self::$table_name . " WHERE id = ? LIMIT 1";
        $stmt = mysqli_prepare(self::$db, $sql);
        if (!$stmt) {
            error_log(get_called_class() . "::findById() Prepare Error: " . mysqli_error(self::$db));
            return null;
        }

        mysqli_stmt_bind_param($stmt, "i", $id_val);
        if (mysqli_stmt_execute($stmt)) {
            $result = mysqli_stmt_get_result($stmt);
            $user = mysqli_fetch_assoc($result);
            mysqli_stmt_close($stmt);
            return $user ?: null;
        }
        error_log(get_called_class() . "::findById() Execute Error: " . mysqli_stmt_error($stmt));
        mysqli_stmt_close($stmt);
        return null;
    }

    public static function findByEmail($email)
    {
        if (!self::checkDbConnection()) return null;
        $email_clean = trim($email);
        if (!filter_var($email_clean, FILTER_VALIDATE_EMAIL)) {
            error_log(get_called_class() . "::findByEmail() Email tidak valid: " . $email);
            return null;
        }

        // Hanya ambil kolom yang ada di tabel
        $sql = "SELECT id, nama, nama_lengkap, email, no_hp, alamat, role, status_akun, created_at 
                FROM " . self::$table_name . " WHERE email = ? LIMIT 1";
        $stmt = mysqli_prepare(self::$db, $sql);
        if (!$stmt) {
            error_log(get_called_class() . "::findByEmail() Prepare Error: " . mysqli_error(self::$db));
            return null;
        }

        mysqli_stmt_bind_param($stmt, "s", $email_clean);
        if (mysqli_stmt_execute($stmt)) {
            $result = mysqli_stmt_get_result($stmt);
            $user = mysqli_fetch_assoc($result);
            mysqli_stmt_close($stmt);
            return $user ?: null;
        }
        error_log(get_called_class() . "::findByEmail() Execute Error: " . mysqli_stmt_error($stmt));
        mysqli_stmt_close($stmt);
        return null;
    }

    public static function getAll()
    {
        if (!self::checkDbConnection()) return [];

        // Hanya ambil kolom yang ada di tabel
        $sql = "SELECT id, nama, nama_lengkap, email, no_hp, alamat, role, status_akun, created_at 
                FROM " . self::$table_name . " ORDER BY nama ASC";
        $result = mysqli_query(self::$db, $sql);
        if ($result) {
            $data = mysqli_fetch_all($result, MYSQLI_ASSOC);
            mysqli_free_result($result);
            return $data;
        }
        error_log(get_called_class() . "::getAll() Query Error: " . mysqli_error(self::$db));
        return [];
    }

    public static function update($data)
    {
        if (!self::checkDbConnection() || !isset($data['id'])) {
            error_log(get_called_class() . "::update() Koneksi/ID Error");
            return false;
        }
        $id = (int)$data['id'];
        if ($id <= 0) {
            error_log(get_called_class() . "::update() ID tidak valid: " . $data['id']);
            return false;
        }

        $fields_to_update = [];
        $params = [];
        $types = "";

        if (isset($data['nama'])) {
            $fields_to_update[] = "nama = ?";
            $params[] = trim($data['nama']);
            $types .= "s";
        }
        if (array_key_exists('nama_lengkap', $data)) {
            $fields_to_update[] = "nama_lengkap = ?";
            $params[] = !empty(trim($data['nama_lengkap'])) ? trim($data['nama_lengkap']) : null;
            $types .= "s";
        }

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
                    if (function_exists('set_flash_message')) set_flash_message('danger', 'Email sudah terdaftar untuk pengguna lain.');
                    return 'email_exists';
                }
                mysqli_stmt_close($stmt_check);
                $fields_to_update[] = "email = ?";
                $params[] = $email_clean;
                $types .= "s";
            } else {
                error_log(get_called_class() . "::update() Format email baru tidak valid: " . $data['email']);
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
                } else {
                    error_log(get_called_class() . "::update() Percobaan ubah role admin utama ID 1.");
                }
            }
        }
        if (isset($data['status_akun'])) {
            $status_akun_input = strtolower(trim($data['status_akun']));
            if (in_array($status_akun_input, self::ALLOWED_ACCOUNT_STATUSES)) {
                if (!($id == 1 && $status_akun_input !== 'aktif')) {
                    $fields_to_update[] = "status_akun = ?";
                    $params[] = $status_akun_input;
                    $types .= "s";
                } else {
                    error_log(get_called_class() . "::update() Percobaan nonaktifkan admin utama ID 1.");
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
                } else {
                    error_log(get_called_class() . "::update() Gagal hashing password baru ID: {$id}.");
                }
            } else {
                error_log(get_called_class() . "::update() Password baru pendek ID: {$id}.");
            }
        }

        if (empty($fields_to_update)) {
            error_log(get_called_class() . "::update() Tidak ada field valid diupdate ID: {$id}.");
            return true;
        }

        // updated_at tidak ada di tabel, jadi tidak di-set di query UPDATE
        $sql = "UPDATE " . self::$table_name . " SET " . implode(', ', $fields_to_update) . " WHERE id = ?";

        $params[] = $id;
        $types .= "i";

        $stmt = mysqli_prepare(self::$db, $sql);
        if (!$stmt) {
            error_log(get_called_class() . "::update() Prepare Error: " . mysqli_error(self::$db) . " SQL: " . $sql);
            return false;
        }

        mysqli_stmt_bind_param($stmt, $types, ...$params);
        if (mysqli_stmt_execute($stmt)) {
            $affected_rows = mysqli_stmt_affected_rows($stmt);
            mysqli_stmt_close($stmt);
            return $affected_rows >= 0;
        }
        error_log(get_called_class() . "::update() Execute Error: " . mysqli_stmt_error($stmt));
        mysqli_stmt_close($stmt);
        return false;
    }

    public static function updatePassword($user_id, $new_password)
    {
        if (!self::checkDbConnection()) return false;
        $id_val = (int)$user_id;
        if ($id_val <= 0 || empty($new_password) || strlen($new_password) < 6) {
            error_log(get_called_class() . "::updatePassword() Input tidak valid.");
            return false;
        }
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        if (!$hashed_password) {
            error_log(get_called_class() . "::updatePassword() Gagal hashing.");
            return false;
        }

        // updated_at tidak ada di tabel
        $sql = "UPDATE " . self::$table_name . " SET password = ? WHERE id = ?";
        $stmt = mysqli_prepare(self::$db, $sql);
        if (!$stmt) {
            error_log(get_called_class() . "::updatePassword() Prepare Error: " . mysqli_error(self::$db));
            return false;
        }
        mysqli_stmt_bind_param($stmt, "si", $hashed_password, $id_val);
        if (mysqli_stmt_execute($stmt)) {
            mysqli_stmt_close($stmt);
            return true;
        }
        error_log(get_called_class() . "::updatePassword() Execute Error: " . mysqli_stmt_error($stmt));
        mysqli_stmt_close($stmt);
        return false;
    }

    public static function updateStatusAkun($user_id, $new_status_akun)
    {
        if (!self::checkDbConnection()) return false;
        $id_val = (int)$user_id;
        $status_val = strtolower(trim($new_status_akun));

        if ($id_val <= 0) {
            error_log(get_called_class() . "::updateStatusAkun() User ID tidak valid.");
            return false;
        }
        if (!in_array($status_val, self::ALLOWED_ACCOUNT_STATUSES)) {
            error_log(get_called_class() . "::updateStatusAkun() Status akun tidak valid: " . $new_status_akun);
            return false;
        }
        if ($id_val == 1 && $status_val !== 'aktif') {
            error_log(get_called_class() . "::updateStatusAkun() Percobaan nonaktifkan admin utama (ID 1).");
            return false;
        }

        // updated_at tidak ada di tabel
        $sql = "UPDATE " . self::$table_name . " SET status_akun = ? WHERE id = ?";
        $stmt = mysqli_prepare(self::$db, $sql);
        if (!$stmt) {
            error_log(get_called_class() . "::updateStatusAkun() Prepare Error: " . mysqli_error(self::$db));
            return false;
        }
        mysqli_stmt_bind_param($stmt, "si", $status_val, $id_val);
        if (mysqli_stmt_execute($stmt)) {
            mysqli_stmt_close($stmt);
            return true;
        }
        error_log(get_called_class() . "::updateStatusAkun() Execute Error: " . mysqli_stmt_error($stmt));
        mysqli_stmt_close($stmt);
        return false;
    }

    public static function delete($id)
    {
        if (!self::checkDbConnection()) return false;
        $id_val = filter_var($id, FILTER_VALIDATE_INT);
        if ($id_val === false || $id_val <= 0) {
            error_log(get_called_class() . "::delete() ID tidak valid: " . $id);
            return false;
        }
        if ($id_val == 1) {
            if (function_exists('set_flash_message')) set_flash_message('danger', 'Admin utama (ID 1) tidak dapat dihapus.');
            error_log(get_called_class() . "::delete() Percobaan hapus admin utama ID 1.");
            return false;
        }
        $sql = "DELETE FROM " . self::$table_name . " WHERE id = ?";
        $stmt = mysqli_prepare(self::$db, $sql);
        if (!$stmt) {
            error_log(get_called_class() . "::delete() Prepare Error: " . mysqli_error(self::$db));
            return false;
        }
        mysqli_stmt_bind_param($stmt, "i", $id_val);
        if (mysqli_stmt_execute($stmt)) {
            $affected_rows = mysqli_stmt_affected_rows($stmt);
            mysqli_stmt_close($stmt);
            return $affected_rows > 0;
        }
        error_log(get_called_class() . "::delete() Execute Error: " . mysqli_stmt_error($stmt));
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
        error_log(get_called_class() . "::countAll() Query Error: " . mysqli_error(self::$db));
        return 0;
    }
} // End of class User