<?php
// File: C:\xampp\htdocs\Cilengkrang-Web-Wisata\controllers\UserController.php

// Pastikan Model User sudah dimuat, idealnya oleh config.php atau autoloader
if (!class_exists('User')) {
    if (defined('MODELS_PATH') && file_exists(MODELS_PATH . '/User.php')) {
        require_once MODELS_PATH . '/User.php';
    }
    if (!class_exists('User')) {
        $error_msg_user_ctrl = "KRITIS UserController: Model User tidak dapat dimuat.";
        error_log($error_msg_user_ctrl);
        if (function_exists('set_flash_message')) set_flash_message('danger', 'Kesalahan sistem: Komponen pengguna tidak tersedia (MdlU_NF_Ctrl).');
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Kesalahan sistem (MdlU_NF_Ctrl).']);
        } else {
            // Jika config.php dimuat, BASE_URL seharusnya ada
            if (function_exists('redirect') && defined('BASE_URL')) redirect(BASE_URL);
            else exit("Kesalahan sistem: Komponen pengguna tidak tersedia (MdlU_NF_Ctrl).");
        }
        exit;
    }
}


class UserController
{
    /**
     * Memeriksa apakah model-model yang diperlukan dan metode spesifik ada.
     * @param array $models_with_methods Array asosiatif ['NamaModel' => ['metode1', 'metode2'], ...].
     * @throws RuntimeException Jika salah satu model atau metode tidak tersedia.
     */
    private static function checkRequiredModelsAndMethods(array $models_with_methods)
    {
        foreach ($models_with_methods as $model_name => $methods) {
            if (!class_exists($model_name)) {
                $error_msg = get_called_class() . " Fatal Error: Model {$model_name} tidak ditemukan.";
                error_log($error_msg);
                throw new RuntimeException($error_msg);
            }
            if (is_array($methods)) {
                foreach ($methods as $method_name) {
                    if (!method_exists($model_name, $method_name)) {
                        $error_msg = get_called_class() . " Fatal Error: Metode {$model_name}::{$method_name} tidak ditemukan.";
                        error_log($error_msg);
                        throw new RuntimeException($error_msg);
                    }
                }
            }
        }
    }

    /**
     * Mengambil data pengguna berdasarkan ID.
     * @param int $user_id
     * @return array|null Data pengguna atau null jika tidak ditemukan.
     */
    public static function getUserDataById($user_id)
    {
        $id_val = filter_var($user_id, FILTER_VALIDATE_INT);
        if ($id_val === false || $id_val <= 0) {
            error_log(get_called_class() . "::getUserDataById() - User ID tidak valid: " . htmlspecialchars((string)$user_id));
            return null;
        }
        try {
            self::checkRequiredModelsAndMethods(['User' => ['findById']]);
            return User::findById($id_val);
        } catch (RuntimeException $e) {
            error_log(get_called_class() . "::getUserDataById() - Exception: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Memproses permintaan update profil pengguna, termasuk foto profil.
     * @param int $user_id ID pengguna yang akan diupdate.
     * @param array $data Data baru dari form edit profil (dari $_POST).
     * @param array|null $file_input_data Data dari $_FILES['nama_input_file_foto'] jika ada.
     * @return bool|string True jika berhasil, string kode error (misal 'email_exists'), atau false jika gagal.
     */
    public static function processUpdateProfile($user_id, array $data, $file_input_data = null)
    {
        $id_val = filter_var($user_id, FILTER_VALIDATE_INT);
        if ($id_val === false || $id_val <= 0) {
            if (function_exists('set_flash_message')) set_flash_message('danger', 'ID Pengguna tidak valid untuk update.');
            return false;
        }

        // Ambil data dari form (gunakan fungsi input() jika ada untuk sanitasi dasar)
        $nama_lengkap = function_exists('input') ? input('nama_lengkap', $data) : trim($data['nama_lengkap'] ?? '');
        $nama_panggilan = function_exists('input') ? input('nama', $data) : trim($data['nama'] ?? '');
        $email = function_exists('input') ? input('email', $data) : trim($data['email'] ?? '');
        $no_hp = function_exists('input') ? input('no_hp', $data) : trim($data['no_hp'] ?? '');
        $alamat = function_exists('input') ? input('alamat', $data) : trim($data['alamat'] ?? '');
        $hapus_foto_profil_flag = isset($data['hapus_foto_profil']) && $data['hapus_foto_profil'] == '1';

        // Validasi input dasar
        $errors = [];
        if (empty($nama_lengkap)) {
            $errors[] = 'Nama Lengkap wajib diisi.';
        }
        if (empty($nama_panggilan)) {
            $errors[] = 'Nama Panggilan/Username wajib diisi.';
        }
        if (empty($email)) {
            $errors[] = 'Alamat Email wajib diisi.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Format alamat email tidak valid.';
        }
        if (!empty($no_hp) && !preg_match('/^[0-9]{10,15}$/', $no_hp)) {
            $errors[] = 'Format Nomor HP tidak valid (10-15 digit angka).';
        }

        if (!empty($errors)) {
            if (function_exists('set_flash_message')) set_flash_message('danger', implode('<br>', $errors));
            $_SESSION['edit_profil_form_data'] = $data; // Simpan data awal (sebelum trim) untuk repopulate
            return false;
        }

        $data_to_update_model = [
            'id' => $id_val,
            'nama_lengkap' => $nama_lengkap,
            'nama' => $nama_panggilan,
            'email' => $email,
            'no_hp' => !empty($no_hp) ? $no_hp : null,
            'alamat' => !empty($alamat) ? $alamat : null,
        ];

        // Penanganan Upload Foto Profil
        if ($hapus_foto_profil_flag) {
            $data_to_update_model['hapus_foto_profil'] = true;
            error_log("UserController::processUpdateProfile - User ID {$id_val} meminta hapus foto profil.");
        } elseif (isset($file_input_data) && $file_input_data['error'] === UPLOAD_ERR_OK) {
            if (!defined('UPLOADS_PROFIL_PATH') || !is_dir(UPLOADS_PROFIL_PATH) || !is_writable(UPLOADS_PROFIL_PATH)) {
                error_log("KRITIS UserController: UPLOADS_PROFIL_PATH ('" . (defined('UPLOADS_PROFIL_PATH') ? UPLOADS_PROFIL_PATH : 'TIDAK TERDEFINISI') . "') tidak terdefinisi / bukan direktori / tidak writable.");
                if (function_exists('set_flash_message')) set_flash_message('danger', 'Kesalahan konfigurasi server: Direktori upload profil tidak siap.');
                $_SESSION['edit_profil_form_data'] = $data;
                return false;
            }

            $allowed_mime_types = ['image/jpeg', 'image/png', 'image/gif'];
            $max_file_size = 2 * 1024 * 1024; // 2MB

            $file_info = finfo_open(FILEINFO_MIME_TYPE);
            $mime_type = finfo_file($file_info, $file_input_data['tmp_name']);
            finfo_close($file_info);

            if (!in_array($mime_type, $allowed_mime_types)) {
                if (function_exists('set_flash_message')) set_flash_message('danger', 'Format file foto profil tidak diizinkan (hanya JPG, PNG, GIF).');
                $_SESSION['edit_profil_form_data'] = $data;
                return false;
            }
            if ($file_input_data['size'] > $max_file_size) {
                if (function_exists('set_flash_message')) set_flash_message('danger', 'Ukuran file foto profil melebihi batas maksimal (2MB).');
                $_SESSION['edit_profil_form_data'] = $data;
                return false;
            }

            $file_extension = strtolower(pathinfo($file_input_data['name'], PATHINFO_EXTENSION));
            $new_foto_filename = "profil_" . $id_val . "_" . time() . "." . $file_extension;
            $upload_target_path = rtrim(UPLOADS_PROFIL_PATH, '/') . '/' . $new_foto_filename;

            if (move_uploaded_file($file_input_data['tmp_name'], $upload_target_path)) {
                $data_to_update_model['foto_profil'] = $new_foto_filename;
                error_log("UserController::processUpdateProfile - Foto profil baru '{$new_foto_filename}' diunggah untuk User ID {$id_val}.");
            } else {
                error_log("UserController::processUpdateProfile - Gagal memindahkan file foto profil yang diunggah untuk User ID {$id_val}. Target: {$upload_target_path}. Error PHP: " . ($file_input_data['error'] ?? 'Tidak diketahui'));
                if (function_exists('set_flash_message')) set_flash_message('danger', 'Gagal menyimpan foto profil yang diunggah. Kode error: ' . ($file_input_data['error'] ?? 'Tidak diketahui'));
                $_SESSION['edit_profil_form_data'] = $data;
                return false;
            }
        }

        try {
            self::checkRequiredModelsAndMethods(['User' => ['update', 'getLastError']]);
            $update_result = User::update($data_to_update_model); // Model akan menangani penghapusan file lama jika ada

            if ($update_result === true) {
                if (function_exists('set_flash_message')) set_flash_message('success', 'Profil berhasil diperbarui.');
                unset($_SESSION['edit_profil_form_data']);
                return true;
            } elseif ($update_result === 'email_exists') {
                if (function_exists('set_flash_message')) set_flash_message('danger', 'Gagal memperbarui profil: Alamat email tersebut sudah digunakan oleh pengguna lain.');
                $_SESSION['edit_profil_form_data'] = $data;
                return 'email_exists';
            } else {
                $model_error = User::getLastError();
                $error_message = 'Gagal memperbarui profil. ';
                if ($model_error && !preg_match('/^Tidak ada error database spesifik/', $model_error) && !preg_match('/Masalah koneksi database/i', $model_error)) {
                    $error_message .= 'Detail: ' . e($model_error);
                } else {
                    $error_message .= 'Terjadi kesalahan internal atau data tidak valid.';
                }
                if (function_exists('set_flash_message')) set_flash_message('danger', $error_message);
                error_log("Update Profil Gagal untuk User ID: {$id_val}. Model Error: " . $model_error . ". Data dikirim ke model: " . print_r($data_to_update_model, true));
                $_SESSION['edit_profil_form_data'] = $data;
                return false;
            }
        } catch (RuntimeException $e) {
            error_log(get_called_class() . "::processUpdateProfile() - Exception: " . $e->getMessage());
            if (function_exists('set_flash_message')) set_flash_message('danger', 'Kesalahan sistem saat update profil.');
            $_SESSION['edit_profil_form_data'] = $data;
            return false;
        }
    }

    public static function processChangePassword($user_id, $email_pengguna, $password_lama, $password_baru, $konfirmasi_password_baru)
    {
        $id_val = filter_var($user_id, FILTER_VALIDATE_INT);
        if ($id_val === false || $id_val <= 0) {
            if (function_exists('set_flash_message')) set_flash_message('danger', 'ID Pengguna tidak valid.');
            return false;
        }

        $errors = [];
        if (empty($password_lama)) {
            $errors[] = 'Password Lama wajib diisi.';
        }
        if (empty($password_baru)) {
            $errors[] = 'Password Baru wajib diisi.';
        } elseif (strlen($password_baru) < 6) {
            $errors[] = 'Password Baru minimal 6 karakter.';
        }
        if (empty($konfirmasi_password_baru)) {
            $errors[] = 'Konfirmasi Password Baru wajib diisi.';
        } elseif ($password_baru !== $konfirmasi_password_baru) {
            $errors[] = 'Password Baru dan Konfirmasi Password Baru tidak cocok.';
        }

        if (!empty($errors)) {
            if (function_exists('set_flash_message')) set_flash_message('danger', implode('<br>', $errors));
            return false;
        }

        try {
            self::checkRequiredModelsAndMethods(['User' => ['login', 'updatePassword', 'getLastError']]);

            $login_check_result = User::login($email_pengguna, $password_lama);

            if (is_array($login_check_result)) {
                if (User::updatePassword($id_val, $password_baru)) {
                    if (function_exists('set_flash_message')) set_flash_message('success', 'Password berhasil diperbarui.');
                    return true;
                } else {
                    $model_error = User::getLastError();
                    $error_message = 'Gagal memperbarui password. ';
                    if ($model_error && !preg_match('/^Tidak ada error database spesifik/', $model_error) && !preg_match('/Masalah koneksi database/i', $model_error)) {
                        $error_message .= 'Detail: ' . e($model_error);
                    } else {
                        $error_message .= 'Terjadi kesalahan internal.';
                    }
                    if (function_exists('set_flash_message')) set_flash_message('danger', $error_message);
                    error_log("Ganti Password Gagal (User::updatePassword false) untuk User ID: {$id_val}. Model Error: " . $model_error);
                    return false;
                }
            } else {
                $login_error_message = User::getLastError() ?: 'Password Lama yang Anda masukkan salah.';
                if ($login_check_result === 'not_found') {
                    $login_error_message = 'Autentikasi gagal (pengguna tidak ditemukan).';
                } elseif ($login_check_result === 'inactive_account') {
                    $login_error_message = 'Akun Anda saat ini tidak aktif.';
                }

                if (function_exists('set_flash_message')) set_flash_message('danger', $login_error_message);
                error_log("Verifikasi Password Lama Gagal untuk User ID: {$id_val}. Hasil User::login: " . print_r($login_check_result, true) . ". Pesan internal model: " . User::getLastError());
                return false;
            }
        } catch (RuntimeException $e) {
            error_log(get_called_class() . "::processChangePassword() - Exception: " . $e->getMessage());
            if (function_exists('set_flash_message')) set_flash_message('danger', 'Kesalahan sistem saat memproses perubahan password.');
            return false;
        }
    }
}
// End of UserController class