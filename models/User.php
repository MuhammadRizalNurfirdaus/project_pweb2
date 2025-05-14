<?php
// File: C:\xampp\htdocs\Cilengkrang-Web-Wisata\models\User.php

class User
{
    private static $table_name = "users";
    public static $db; // Akan di-set oleh config.php
    private static $last_internal_error = null;
    private static $upload_dir_profil = null; // Path absolut ke direktori upload foto profil

    public const ALLOWED_ROLES = ['user', 'admin'];
    public const ALLOWED_ACCOUNT_STATUSES = ['aktif', 'non-aktif', 'diblokir'];

    public static function init(mysqli $connection, $upload_path = null)
    {
        self::$db = $connection;
        if ($upload_path) {
            self::$upload_dir_profil = rtrim($upload_path, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        }
        // error_log(get_called_class() . "::init() dipanggil. Upload dir: " . (self::$upload_dir_profil ?: 'Tidak diset'));
    }

    public static function setDbConnection(mysqli $connection)
    {
        self::$db = $connection;
        // error_log(get_called_class() . "::setDbConnection dipanggil.");
    }

    private static function checkDbConnection($require_upload_dir = false)
    {
        self::$last_internal_error = null; // Reset di awal
        if (!self::$db || !(self::$db instanceof mysqli) || (self::$db instanceof mysqli && self::$db->connect_error)) {
            $error_msg_detail = (self::$db && self::$db instanceof mysqli ? self::$db->connect_error : 'Koneksi belum diinisialisasi atau bukan objek mysqli.');
            error_log(get_called_class() . " - Koneksi DB gagal: " . $error_msg_detail);
            self::$last_internal_error = "Masalah koneksi database internal.";
            return false;
        }
        if ($require_upload_dir && (empty(self::$upload_dir_profil) || !is_dir(self::$upload_dir_profil) || !is_writable(self::$upload_dir_profil))) {
            $error_msg_detail = (self::$upload_dir_profil ?: 'Belum diset via init()');
            error_log(get_called_class() . " - Direktori upload profil tidak valid/writable: " . $error_msg_detail . ". Path dicek: " . realpath(self::$upload_dir_profil));
            self::$last_internal_error = "Masalah konfigurasi direktori upload.";
            return false;
        }
        return true;
    }

    public static function getLastError()
    {
        if (self::$last_internal_error) {
            $temp_error = self::$last_internal_error;
            // self::$last_internal_error = null; // Opsional: reset setelah diambil
            return $temp_error;
        }
        if (self::$db instanceof mysqli && !empty(self::$db->error)) {
            return "MySQL Error: " . self::$db->error . " (Code: " . self::$db->errno . ")";
        }
        if (!self::$db || !(self::$db instanceof mysqli)) {
            return 'Koneksi DB tidak valid untuk model ' . get_called_class() . '.';
        }
        // Jika tidak ada error spesifik yang di-set, dan tidak ada mysqli error, ini adalah fallbacknya.
        return 'Tidak ada error spesifik yang dilaporkan dari model ' . get_called_class() . '.';
    }

    public static function register($data)
    {
        // error_log(get_called_class() . "::register() data: " . print_r($data, true));
        self::$last_internal_error = null; // Reset untuk operasi ini
        if (!self::checkDbConnection()) {
            // self::$last_internal_error sudah di-set oleh checkDbConnection()
            return false;
        }

        $nama_pengguna_input = trim($data['nama'] ?? '');          // Ini adalah 'nama_pengguna' dari form
        $nama_lengkap_input = trim($data['nama_lengkap'] ?? '');
        $email_input = trim($data['email'] ?? '');
        $password_input = $data['password'] ?? ''; // Jangan trim password
        $foto_profil_input = $data['foto_profil'] ?? null;      // Ini adalah NAMA FILE foto, bukan path absolut
        $no_hp_input = isset($data['no_hp']) && !empty(trim($data['no_hp'])) ? trim($data['no_hp']) : null;
        $alamat_input = isset($data['alamat']) && !empty(trim($data['alamat'])) ? trim($data['alamat']) : null;
        $role_input = in_array(strtolower(trim($data['role'] ?? 'user')), self::ALLOWED_ROLES) ? strtolower(trim($data['role'] ?? 'user')) : 'user';
        $status_akun_input = in_array(strtolower(trim($data['status_akun'] ?? 'aktif')), self::ALLOWED_ACCOUNT_STATUSES) ? strtolower(trim($data['status_akun'] ?? 'aktif')) : 'aktif';


        // --- Validasi Input Server-Side ---
        if (empty($nama_pengguna_input)) {
            self::$last_internal_error = "Nama Pengguna wajib diisi.";
            return 'missing_nama_pengguna'; // Kembalikan kode error spesifik
        }
        if (empty($nama_lengkap_input)) {
            self::$last_internal_error = "Nama Lengkap wajib diisi.";
            return 'missing_nama_lengkap';
        }
        if (empty($email_input)) {
            self::$last_internal_error = "Alamat Email wajib diisi.";
            return 'missing_email';
        }
        if (!filter_var($email_input, FILTER_VALIDATE_EMAIL)) {
            self::$last_internal_error = "Format Alamat Email tidak valid.";
            return 'email_invalid';
        }
        if (empty($password_input)) {
            self::$last_internal_error = "Password wajib diisi.";
            return 'missing_password';
        }
        if (strlen($password_input) < 6) {
            self::$last_internal_error = "Password minimal 6 karakter.";
            return 'password_short';
        }

        // --- Cek Duplikasi Email ---
        $sql_check_email = "SELECT `id` FROM `" . self::$table_name . "` WHERE `email` = ? LIMIT 1";
        $stmt_check_email = mysqli_prepare(self::$db, $sql_check_email);
        if (!$stmt_check_email) {
            self::$last_internal_error = "Gagal mempersiapkan pengecekan email: " . mysqli_error(self::$db);
            error_log(get_called_class() . "::register() - " . self::$last_internal_error);
            return false;
        }
        mysqli_stmt_bind_param($stmt_check_email, "s", $email_input);
        if (!mysqli_stmt_execute($stmt_check_email)) {
            self::$last_internal_error = "Gagal menjalankan pengecekan email: " . mysqli_stmt_error($stmt_check_email);
            error_log(get_called_class() . "::register() - " . self::$last_internal_error);
            mysqli_stmt_close($stmt_check_email);
            return false;
        }
        mysqli_stmt_store_result($stmt_check_email);
        if (mysqli_stmt_num_rows($stmt_check_email) > 0) {
            mysqli_stmt_close($stmt_check_email);
            // self::$last_internal_error = "Email sudah terdaftar."; // Tidak perlu, register.php akan handle 'email_exists'
            return 'email_exists';
        }
        mysqli_stmt_close($stmt_check_email);

        // --- Cek Duplikasi Nama Pengguna (jika kolom 'nama' memiliki UNIQUE constraint) ---
        // Jika 'nama' (Nama Pengguna) harus unik, tambahkan pengecekan serupa di sini.
        // $sql_check_username = "SELECT `id` FROM `" . self::$table_name . "` WHERE `nama` = ? LIMIT 1";
        // // ... (prepare, bind, execute, store_result, check num_rows) ...
        // if (mysqli_stmt_num_rows($stmt_check_username) > 0) {
        //     mysqli_stmt_close($stmt_check_username);
        //     return 'username_exists';
        // }
        // mysqli_stmt_close($stmt_check_username);


        // --- Hash Password ---
        $hashed_password = password_hash($password_input, PASSWORD_DEFAULT);
        if ($hashed_password === false) {
            self::$last_internal_error = "Gagal melakukan hashing password.";
            error_log(get_called_class() . "::register() - " . self::$last_internal_error);
            return false;
        }

        // --- Query INSERT ---
        $sql_insert = "INSERT INTO `" . self::$table_name . "` 
            (`nama`, `nama_lengkap`, `email`, `password`, `no_hp`, `alamat`, `foto_profil`, `role`, `status_akun`) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt_insert = mysqli_prepare(self::$db, $sql_insert);
        if (!$stmt_insert) {
            self::$last_internal_error = "Gagal mempersiapkan statement insert pengguna: " . mysqli_error(self::$db);
            error_log(get_called_class() . "::register() - " . self::$last_internal_error);
            return false;
        }

        mysqli_stmt_bind_param(
            $stmt_insert,
            "sssssssss", // 9 tipe 's'
            $nama_pengguna_input,
            $nama_lengkap_input,
            $email_input,
            $hashed_password,
            $no_hp_input,
            $alamat_input,
            $foto_profil_input, // nama file atau null
            $role_input,
            $status_akun_input
        );

        if (mysqli_stmt_execute($stmt_insert)) {
            $new_user_id = mysqli_insert_id(self::$db);
            mysqli_stmt_close($stmt_insert);
            if ($new_user_id > 0) {
                return $new_user_id; // Sukses, kembalikan ID pengguna baru
            } else {
                // Ini jarang terjadi jika execute sukses, tapi untuk jaga-jaga
                self::$last_internal_error = "Pengguna berhasil ditambahkan, namun gagal mendapatkan ID pengguna baru.";
                error_log(get_called_class() . "::register() - " . self::$last_internal_error . " (MySQL Insert ID Error: " . mysqli_error(self::$db) . ")");
                return false;
            }
        } else {
            self::$last_internal_error = "Gagal menambahkan pengguna baru ke database: " . mysqli_stmt_error($stmt_insert) . " (Code: " . mysqli_stmt_errno($stmt_insert) . ")";
            // Cek error spesifik seperti duplikasi (meskipun idealnya sudah dicek sebelumnya)
            $mysql_errno = mysqli_stmt_errno($stmt_insert);
            if ($mysql_errno == 1062) { // Error code for Duplicate entry
                if (strpos(strtolower(mysqli_stmt_error($stmt_insert)), 'email') !== false) {
                    mysqli_stmt_close($stmt_insert);
                    return 'email_exists'; // Sebagai fallback jika pengecekan awal terlewat
                }
                // Tambahkan pengecekan untuk 'nama' jika ada UNIQUE constraint
                // if (strpos(strtolower(mysqli_stmt_error($stmt_insert)), 'nama') !== false) {
                //    mysqli_stmt_close($stmt_insert);
                //    return 'username_exists';
                // }
            }
            error_log(get_called_class() . "::register() - " . self::$last_internal_error);
            mysqli_stmt_close($stmt_insert);
            return false;
        }
    }

    public static function login($email, $password_input)
    {
        self::$last_internal_error = null;
        if (!self::checkDbConnection()) return false; // Bisa juga return kode error spesifik

        $email_input = trim($email);
        if (empty($email_input) || !filter_var($email_input, FILTER_VALIDATE_EMAIL)) {
            self::$last_internal_error = "Format email tidak valid untuk login.";
            return 'login_invalid_email';
        }
        if (empty($password_input)) {
            self::$last_internal_error = "Password tidak boleh kosong untuk login.";
            return 'login_empty_password';
        }

        $sql = "SELECT `id`, `nama`, `nama_lengkap`, `email`, `password`, `role`, `status_akun`, `foto_profil` 
                FROM `" . self::$table_name . "` WHERE `email` = ? LIMIT 1";
        $stmt = mysqli_prepare(self::$db, $sql);
        if (!$stmt) {
            self::$last_internal_error = "Gagal mempersiapkan statement login: " . mysqli_error(self::$db);
            error_log(get_called_class() . "::login() - " . self::$last_internal_error);
            return false;
        }

        mysqli_stmt_bind_param($stmt, "s", $email_input);
        if (!mysqli_stmt_execute($stmt)) {
            self::$last_internal_error = "Gagal menjalankan statement login: " . mysqli_stmt_error($stmt);
            error_log(get_called_class() . "::login() - " . self::$last_internal_error);
            mysqli_stmt_close($stmt);
            return false;
        }

        $result = mysqli_stmt_get_result($stmt);
        $user = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);

        if ($user) {
            if (password_verify($password_input, $user['password'])) {
                if (strtolower($user['status_akun']) === 'aktif') {
                    unset($user['password']);
                    return $user;
                } elseif (strtolower($user['status_akun']) === 'non-aktif') {
                    return 'account_not_active';
                } elseif (strtolower($user['status_akun']) === 'diblokir') {
                    return 'account_blocked';
                } else {
                    return 'account_status_unknown';
                }
            } else {
                return 'login_failed_credentials'; // Password salah
            }
        } else {
            return 'login_failed_credentials'; // User tidak ditemukan
        }
    }

    public static function findById($id)
    {
        self::$last_internal_error = null;
        if (!self::checkDbConnection()) return null;
        $user_id = (int)$id;
        if ($user_id <= 0) {
            self::$last_internal_error = "ID pengguna tidak valid.";
            return null;
        }
        $sql = "SELECT `id`, `nama`, `nama_lengkap`, `email`, `no_hp`, `alamat`, `foto_profil`, `role`, `status_akun`, `created_at` 
                FROM `" . self::$table_name . "` WHERE `id` = ? LIMIT 1";
        $stmt = mysqli_prepare(self::$db, $sql);
        if (!$stmt) { /* ... error log ... */
            return null;
        }
        mysqli_stmt_bind_param($stmt, "i", $user_id);
        if (!mysqli_stmt_execute($stmt)) { /* ... error log ... */
            mysqli_stmt_close($stmt);
            return null;
        }
        $result = mysqli_stmt_get_result($stmt);
        $user = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);
        return $user ?: null;
    }

    public static function findByEmail($email)
    {
        self::$last_internal_error = null;
        if (!self::checkDbConnection()) return null;
        $email_input = trim($email);
        if (empty($email_input) || !filter_var($email_input, FILTER_VALIDATE_EMAIL)) { /* ... error ... */
            return null;
        }
        $sql = "SELECT `id`, `nama`, `nama_lengkap`, `email`, `no_hp`, `alamat`, `foto_profil`, `role`, `status_akun`, `created_at` 
                FROM `" . self::$table_name . "` WHERE `email` = ? LIMIT 1";
        $stmt = mysqli_prepare(self::$db, $sql);
        if (!$stmt) { /* ... error log ... */
            return null;
        }
        mysqli_stmt_bind_param($stmt, "s", $email_input);
        if (!mysqli_stmt_execute($stmt)) { /* ... error log ... */
            mysqli_stmt_close($stmt);
            return null;
        }
        $result = mysqli_stmt_get_result($stmt);
        $user = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);
        return $user ?: null;
    }

    public static function getAll($orderBy = 'nama_lengkap', $orderDir = 'ASC')
    {
        self::$last_internal_error = null;
        if (!self::checkDbConnection()) return [];

        // Sanitize orderBy and orderDir
        $allowedOrderBy = ['id', 'nama', 'nama_lengkap', 'email', 'role', 'status_akun', 'created_at'];
        $orderBy = in_array($orderBy, $allowedOrderBy) ? $orderBy : 'nama_lengkap';
        $orderDir = strtoupper($orderDir) === 'DESC' ? 'DESC' : 'ASC';

        $sql = "SELECT `id`, `nama`, `nama_lengkap`, `email`, `no_hp`, `alamat`, `foto_profil`, `role`, `status_akun`, `created_at` 
                FROM `" . self::$table_name . "` ORDER BY `" . $orderBy . "` " . $orderDir;
        $result = mysqli_query(self::$db, $sql);
        if (!$result) { /* ... error log ... */
            return [];
        }
        $users = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $users[] = $row;
        }
        mysqli_free_result($result);
        return $users;
    }

    public static function update($data)
    {
        self::$last_internal_error = null;
        $require_upload_for_foto = isset($data['foto_profil']) || (isset($data['hapus_foto_profil']) && $data['hapus_foto_profil']);
        if (!self::checkDbConnection($require_upload_for_foto)) return false;

        if (!isset($data['id'])) {
            self::$last_internal_error = "ID pengguna wajib ada untuk update.";
            return false;
        }
        $id = (int)$data['id'];
        if ($id <= 0) {
            self::$last_internal_error = "ID pengguna tidak valid untuk update.";
            return false;
        }

        $currentUserData = self::findById($id);
        if (!$currentUserData) {
            self::$last_internal_error = "Pengguna dengan ID {$id} tidak ditemukan untuk diupdate.";
            return false;
        }
        $old_foto_profil_filename = $currentUserData['foto_profil'] ?? null;

        $fields_to_update_sql = [];
        $params_to_bind = [];
        $types_for_bind = "";

        // Handle 'nama' (Nama Pengguna)
        if (isset($data['nama'])) {
            $nama_pengguna_input = trim($data['nama']);
            if (empty($nama_pengguna_input)) {
                return 'update_empty_nama_pengguna';
            } // Specific error code
            if ($nama_pengguna_input !== $currentUserData['nama']) {
                // Optional: Jika 'nama' harus unik, cek duplikasi di sini (kecuali untuk user saat ini)
                // ...
                $fields_to_update_sql[] = "`nama` = ?";
                $params_to_bind[] = $nama_pengguna_input;
                $types_for_bind .= "s";
            }
        }
        // Handle 'nama_lengkap'
        if (isset($data['nama_lengkap'])) {
            $nama_lengkap_input = trim($data['nama_lengkap']);
            if (empty($nama_lengkap_input)) {
                return 'update_empty_nama_lengkap';
            }
            if ($nama_lengkap_input !== $currentUserData['nama_lengkap']) {
                $fields_to_update_sql[] = "`nama_lengkap` = ?";
                $params_to_bind[] = $nama_lengkap_input;
                $types_for_bind .= "s";
            }
        }
        // Handle 'email'
        if (isset($data['email'])) {
            $email_input = trim($data['email']);
            if (empty($email_input) || !filter_var($email_input, FILTER_VALIDATE_EMAIL)) {
                return 'update_email_invalid';
            }
            if ($email_input !== $currentUserData['email']) {
                $sql_check_email = "SELECT `id` FROM `" . self::$table_name . "` WHERE `email` = ? AND `id` != ? LIMIT 1";
                $stmt_check_email = mysqli_prepare(self::$db, $sql_check_email);
                mysqli_stmt_bind_param($stmt_check_email, "si", $email_input, $id);
                mysqli_stmt_execute($stmt_check_email);
                mysqli_stmt_store_result($stmt_check_email);
                if (mysqli_stmt_num_rows($stmt_check_email) > 0) {
                    mysqli_stmt_close($stmt_check_email);
                    return 'email_exists'; // Email sudah dipakai user lain
                }
                mysqli_stmt_close($stmt_check_email);
                $fields_to_update_sql[] = "`email` = ?";
                $params_to_bind[] = $email_input;
                $types_for_bind .= "s";
            }
        }
        // Handle 'no_hp'
        if (array_key_exists('no_hp', $data)) { // array_key_exists untuk bisa set ke null
            $no_hp_input = !empty(trim($data['no_hp'])) ? trim($data['no_hp']) : null;
            if ($no_hp_input !== $currentUserData['no_hp']) {
                $fields_to_update_sql[] = "`no_hp` = ?";
                $params_to_bind[] = $no_hp_input;
                $types_for_bind .= "s";
            }
        }
        // Handle 'alamat'
        if (array_key_exists('alamat', $data)) {
            $alamat_input = !empty(trim($data['alamat'])) ? trim($data['alamat']) : null;
            if ($alamat_input !== $currentUserData['alamat']) {
                $fields_to_update_sql[] = "`alamat` = ?";
                $params_to_bind[] = $alamat_input;
                $types_for_bind .= "s";
            }
        }
        // Handle 'role'
        if (isset($data['role'])) {
            $role_input = strtolower(trim($data['role']));
            if (in_array($role_input, self::ALLOWED_ROLES) && $role_input !== $currentUserData['role']) {
                if ($id === 1 && $role_input !== 'admin') { /* cegah admin utama kehilangan role */
                    return false;
                }
                $fields_to_update_sql[] = "`role` = ?";
                $params_to_bind[] = $role_input;
                $types_for_bind .= "s";
            }
        }
        // Handle 'status_akun'
        if (isset($data['status_akun'])) {
            $status_input = strtolower(trim($data['status_akun']));
            if (in_array($status_input, self::ALLOWED_ACCOUNT_STATUSES) && $status_input !== $currentUserData['status_akun']) {
                if ($id === 1 && $status_input !== 'aktif') { /* cegah admin utama non-aktif */
                    return false;
                }
                $fields_to_update_sql[] = "`status_akun` = ?";
                $params_to_bind[] = $status_input;
                $types_for_bind .= "s";
            }
        }
        // Handle 'password'
        if (isset($data['password']) && !empty($data['password'])) {
            $password_input = $data['password'];
            if (strlen($password_input) < 6) {
                return 'update_password_short';
            }
            $hashed_password = password_hash($password_input, PASSWORD_DEFAULT);
            if ($hashed_password === false) { /* error hashing */
                return false;
            }
            $fields_to_update_sql[] = "`password` = ?";
            $params_to_bind[] = $hashed_password;
            $types_for_bind .= "s";
        }

        // Handle 'foto_profil' (nama file dari controller)
        $foto_profil_to_set_in_db = $old_foto_profil_filename; // Defaultnya tidak berubah
        $file_lama_untuk_dihapus_fisik = null;

        if (isset($data['hapus_foto_profil']) && $data['hapus_foto_profil'] == true) {
            if (!empty($old_foto_profil_filename)) {
                $file_lama_untuk_dihapus_fisik = $old_foto_profil_filename;
            }
            $foto_profil_to_set_in_db = null; // Akan set NULL di DB
            $fields_to_update_sql[] = "`foto_profil` = ?";
            $params_to_bind[] = $foto_profil_to_set_in_db;
            $types_for_bind .= "s";
        } elseif (isset($data['foto_profil'])) { // Ada nama file foto baru yang di-pass
            $new_foto_filename = trim($data['foto_profil']); // Ini adalah NAMA FILE BARU
            // Hanya update jika nama file baru ada dan beda dengan yang lama
            if (!empty($new_foto_filename) && $new_foto_filename !== $old_foto_profil_filename) {
                if (!empty($old_foto_profil_filename)) {
                    $file_lama_untuk_dihapus_fisik = $old_foto_profil_filename;
                }
                $foto_profil_to_set_in_db = $new_foto_filename;
                $fields_to_update_sql[] = "`foto_profil` = ?";
                $params_to_bind[] = $foto_profil_to_set_in_db;
                $types_for_bind .= "s";
            } elseif (empty($new_foto_filename) && $old_foto_profil_filename !== null && !isset($data['hapus_foto_profil'])) {
                // Jika nama file baru kosong, tapi tidak ada flag hapus_foto_profil,
                // dan ada foto lama, berarti foto tidak diubah.
                // Jika ingin menghapus foto dengan mengirim string kosong, harus ada 'hapus_foto_profil' => true
            }
        }


        if (empty($fields_to_update_sql)) {
            // Tidak ada field yang diubah di DB. Cek apakah hanya operasi hapus file fisik.
            if ($file_lama_untuk_dihapus_fisik && self::$upload_dir_profil && $foto_profil_to_set_in_db === null) {
                $file_path_lama_fisik = self::$upload_dir_profil . basename($file_lama_untuk_dihapus_fisik);
                if (file_exists($file_path_lama_fisik) && is_file($file_path_lama_fisik)) {
                    if (!@unlink($file_path_lama_fisik)) {
                        error_log(get_called_class() . "::update() Peringatan: Gagal hapus file foto lama (tanpa update DB): " . $file_path_lama_fisik);
                    }
                }
            }
            return true; // Tidak ada perubahan di DB
        }

        $sql_update = "UPDATE `" . self::$table_name . "` SET " . implode(', ', $fields_to_update_sql) . " WHERE `id` = ?";
        $params_to_bind[] = $id;
        $types_for_bind .= "i";

        $stmt = mysqli_prepare(self::$db, $sql_update);
        if (!$stmt) { /* ... error log ... */
            return false;
        }
        mysqli_stmt_bind_param($stmt, $types_for_bind, ...$params_to_bind);
        if (mysqli_stmt_execute($stmt)) {
            $affected_rows = mysqli_stmt_affected_rows($stmt);
            mysqli_stmt_close($stmt);
            // Hapus file fisik lama jika update DB sukses & ada file lama yg perlu dihapus
            if ($file_lama_untuk_dihapus_fisik && self::$upload_dir_profil) {
                $file_path_lama_fisik = self::$upload_dir_profil . basename($file_lama_untuk_dihapus_fisik);
                if (file_exists($file_path_lama_fisik) && is_file($file_path_lama_fisik)) {
                    if (!@unlink($file_path_lama_fisik)) {
                        error_log(get_called_class() . "::update() Peringatan: Gagal hapus file foto lama setelah update DB: " . $file_path_lama_fisik);
                    }
                }
            }
            return true; // atau $affected_rows > 0 jika ingin memastikan ada perubahan
        } else {
            self::$last_internal_error = "Gagal menjalankan statement update pengguna: " . mysqli_stmt_error($stmt);
            mysqli_stmt_close($stmt);
            return false;
        }
    }

    public static function updatePassword($user_id, $new_password)
    {
        self::$last_internal_error = null;
        if (!self::checkDbConnection()) return false;
        $id = (int)$user_id;
        if ($id <= 0 || empty($new_password) || strlen($new_password) < 6) { /* ... error ... */
            return false;
        }
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        if ($hashed_password === false) { /* ... error ... */
            return false;
        }
        $sql = "UPDATE `" . self::$table_name . "` SET `password` = ? WHERE `id` = ?";
        $stmt = mysqli_prepare(self::$db, $sql);
        if (!$stmt) { /* ... error log ... */
            return false;
        }
        mysqli_stmt_bind_param($stmt, "si", $hashed_password, $id);
        if (mysqli_stmt_execute($stmt)) {
            mysqli_stmt_close($stmt);
            return true;
        } else { /* ... error log ... */
            mysqli_stmt_close($stmt);
            return false;
        }
    }

    public static function updateStatusAkun($user_id, $new_status_akun)
    {
        self::$last_internal_error = null;
        if (!self::checkDbConnection()) return false;
        $id = (int)$user_id;
        $status_input = strtolower(trim($new_status_akun));
        if ($id <= 0 || !in_array($status_input, self::ALLOWED_ACCOUNT_STATUSES)) { /* ... error ... */
            return false;
        }
        if ($id === 1 && $status_input !== 'aktif') { /* cegah admin utama ... */
            return false;
        }
        $sql = "UPDATE `" . self::$table_name . "` SET `status_akun` = ? WHERE `id` = ?";
        $stmt = mysqli_prepare(self::$db, $sql);
        if (!$stmt) { /* ... error log ... */
            return false;
        }
        mysqli_stmt_bind_param($stmt, "si", $status_input, $id);
        if (mysqli_stmt_execute($stmt)) {
            mysqli_stmt_close($stmt);
            return true;
        } else { /* ... error log ... */
            mysqli_stmt_close($stmt);
            return false;
        }
    }

    public static function delete($id)
    {
        self::$last_internal_error = null;
        if (!self::checkDbConnection(true)) return false; // true karena akan hapus foto
        $user_id = (int)$id;
        if ($user_id <= 0) { /* error */
            return false;
        }
        if ($user_id === 1) { /* cegah hapus admin utama */
            return false;
        }
        $user_to_delete = self::findById($user_id); // Untuk dapat nama file foto
        $sql = "DELETE FROM `" . self::$table_name . "` WHERE `id` = ?";
        $stmt = mysqli_prepare(self::$db, $sql);
        if (!$stmt) { /* ... error log ... */
            return false;
        }
        mysqli_stmt_bind_param($stmt, "i", $user_id);
        if (mysqli_stmt_execute($stmt)) {
            $affected_rows = mysqli_stmt_affected_rows($stmt);
            mysqli_stmt_close($stmt);
            if ($affected_rows > 0) {
                if ($user_to_delete && !empty($user_to_delete['foto_profil']) && !empty(self::$upload_dir_profil)) {
                    $file_path_to_delete = self::$upload_dir_profil . basename($user_to_delete['foto_profil']);
                    if (file_exists($file_path_to_delete) && is_file($file_path_to_delete)) {
                        if (!@unlink($file_path_to_delete)) {
                            error_log(get_called_class() . "::delete() - Peringatan: Gagal hapus file foto profil: " . $file_path_to_delete);
                        }
                    }
                }
                return true;
            } else {
                self::$last_internal_error = "Pengguna dengan ID {$user_id} tidak ditemukan atau sudah dihapus.";
                return false; // Tidak ada baris yang terpengaruh
            }
        } else {
            self::$last_internal_error = "Gagal menjalankan statement delete pengguna: " . mysqli_stmt_error($stmt);
            mysqli_stmt_close($stmt);
            return false;
        }
    }

    public static function countAll()
    {
        self::$last_internal_error = null;
        if (!self::checkDbConnection()) return 0;
        $sql = "SELECT COUNT(`id`) as total FROM `" . self::$table_name . "`";
        $result = mysqli_query(self::$db, $sql);
        if ($result) {
            $row = mysqli_fetch_assoc($result);
            mysqli_free_result($result);
            return (int)($row['total'] ?? 0);
        } else { /* ... error log ... */
            return 0;
        }
    }
}
