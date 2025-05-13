<?php
// File: C:\xampp\htdocs\Cilengkrang-Web-Wisata\models\User.php

class User
{
    private static $table_name = "users";
    private static $db;

    // Daftar role yang diizinkan
    public const ALLOWED_ROLES = ['user', 'admin']; // HANYA user dan admin
    // Daftar status akun yang diizinkan (sesuai dengan COMMENT di database Anda)
    public const ALLOWED_ACCOUNT_STATUSES = ['aktif', 'non-aktif', 'diblokir']; // Sesuaikan jika perlu

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

        $nama_input = trim($data['nama'] ?? '');
        $nama_lengkap_input = isset($data['nama_lengkap']) && !empty(trim($data['nama_lengkap'])) ? trim($data['nama_lengkap']) : null;
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

        // PERBAIKAN: Hapus created_at dan updated_at dari VALUES jika dihandle DB
        // created_at memiliki DEFAULT current_timestamp(), updated_at TIDAK ADA di tabel Anda
        $sql_insert = "INSERT INTO " . self::$table_name .
            " (nama, nama_lengkap, email, password, no_hp, alamat, role, status_akun) 
                      VALUES (?, ?, ?, ?, ?, ?, ?, ?)"; // 8 placeholders
        $stmt_insert = mysqli_prepare(self::$db, $sql_insert);

        if (!$stmt_insert) {
            error_log(get_called_class() . "::register() Prepare Insert Error: " . mysqli_error(self::$db));
            return false;
        }

        // PERBAIKAN: Sesuaikan tipe bind_param menjadi 8 's'
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

        // PERBAIKAN: Hapus updated_at dari SELECT jika tidak ada di tabel
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

        // PERBAIKAN: Hapus updated_at dari SELECT jika tidak ada di tabel
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

        // PERBAIKAN: Hapus updated_at dari SET jika tidak ada di tabel
        // Jika Anda menambahkan kolom updated_at dengan ON UPDATE CURRENT_TIMESTAMP, ini tidak perlu.
        // Jika tidak, Anda harus menambahkan kolomnya atau menghapus baris ini.
        // $fields_to_update[] = "updated_at = NOW()"; 

        $sql = "UPDATE " . self::$table_name . " SET " . implode(', ', $fields_to_update);
        // Tambahkan updated_at = NOW() di sini jika kolomnya ada dan tidak otomatis update
        if (in_array('updated_at = NOW()', $fields_to_update) == false && self::columnExists('updated_at')) {
            $sql .= (count($fields_to_update) > 0 ? ", " : "") . "updated_at = NOW()";
        }
        $sql .= " WHERE id = ?";

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
    { /* ... sama seperti sebelumnya ... */
    }
    public static function updateStatusAkun($user_id, $new_status_akun)
    { /* ... sama seperti sebelumnya ... */
    }
    public static function delete($id)
    { /* ... sama seperti sebelumnya ... */
    }
    public static function countAll()
    { /* ... sama seperti sebelumnya ... */
    }

    /**
     * Helper untuk memeriksa apakah kolom ada di tabel.
     * Ini bisa berguna jika struktur tabel bisa bervariasi atau untuk kode yang lebih dinamis.
     * Namun, untuk kasus ini, lebih baik pastikan query SQL sesuai dengan skema tabel yang pasti.
     */
    private static function columnExists($columnName)
    {
        if (!self::checkDbConnection()) return false;
        $result = self::$db->query("SHOW COLUMNS FROM `" . self::$table_name . "` LIKE '" . $columnName . "'");
        return $result && $result->num_rows > 0;
    }
} // End of class User