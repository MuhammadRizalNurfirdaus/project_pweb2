<?php
// File: C:\xampp\htdocs\Cilengkrang-Web-Wisata\controllers\PengaturanController.php

// config.php seharusnya sudah memuat Model PengaturanSitus.php
// dan memanggil PengaturanSitus::init($conn, UPLOADS_PATH) atau path yang sesuai

class PengaturanController
{
    private static function checkModelIntegrity(): bool
    {
        if (
            !class_exists('PengaturanSitus') ||
            !method_exists('PengaturanSitus', 'getPengaturan') ||
            !method_exists('PengaturanSitus', 'updatePengaturan') ||
            !method_exists('PengaturanSitus', 'getUploadDir') ||
            !method_exists('PengaturanSitus', 'getLastError')
        ) {
            error_log(get_called_class() . " Fatal Error: Model PengaturanSitus atau metode penting tidak ditemukan.");
            return false;
        }
        return true;
    }

    /**
     * Mengambil semua data pengaturan situs.
     * @return array|null Pengaturan situs atau null jika error.
     */
    public static function getPengaturanSitus(): ?array
    {
        if (!self::checkModelIntegrity()) {
            return null;
        }
        return PengaturanSitus::getPengaturan();
    }

    /**
     * Memproses update pengaturan situs.
     * @param array $data_form Data dari form POST.
     * @param array|null $file_logo_data Data dari $_FILES untuk logo.
     * @param array|null $file_favicon_data Data dari $_FILES untuk favicon.
     * @return bool|string True jika sukses, string kode error jika gagal.
     */
    public static function updatePengaturanSitus(array $data_form, ?array $file_logo_data = null, ?array $file_favicon_data = null): bool|string
    {
        if (!self::checkModelIntegrity()) {
            return 'system_error_model_unavailable';
        }

        $current_settings = PengaturanSitus::getPengaturan();
        $data_to_update = [];

        // Ambil nilai dari form
        $data_to_update['nama_situs'] = trim($data_form['nama_situs'] ?? ($current_settings['nama_situs'] ?? ''));
        $data_to_update['tagline_situs'] = trim($data_form['tagline_situs'] ?? ($current_settings['tagline_situs'] ?? null));
        $data_to_update['deskripsi_situs'] = trim($data_form['deskripsi_situs'] ?? ($current_settings['deskripsi_situs'] ?? null));
        $data_to_update['email_kontak_situs'] = trim($data_form['email_kontak_situs'] ?? ($current_settings['email_kontak_situs'] ?? null));
        $data_to_update['telepon_kontak_situs'] = trim($data_form['telepon_kontak_situs'] ?? ($current_settings['telepon_kontak_situs'] ?? null));
        $data_to_update['alamat_situs'] = trim($data_form['alamat_situs'] ?? ($current_settings['alamat_situs'] ?? null));
        $data_to_update['link_facebook'] = trim($data_form['link_facebook'] ?? ($current_settings['link_facebook'] ?? null));
        $data_to_update['link_instagram'] = trim($data_form['link_instagram'] ?? ($current_settings['link_instagram'] ?? null));
        $data_to_update['link_twitter'] = trim($data_form['link_twitter'] ?? ($current_settings['link_twitter'] ?? null));
        $data_to_update['link_youtube'] = trim($data_from_form['link_youtube'] ?? ($current_settings['link_youtube'] ?? null));
        $data_to_update['google_analytics_id'] = trim($data_form['google_analytics_id'] ?? ($current_settings['google_analytics_id'] ?? null));
        $data_to_update['items_per_page'] = isset($data_form['items_per_page']) ? max(1, (int)$data_form['items_per_page']) : ($current_settings['items_per_page'] ?? 10);
        $data_to_update['mode_pemeliharaan'] = isset($data_form['mode_pemeliharaan']) ? 1 : 0; // Jika checkbox

        // Validasi dasar
        if (empty($data_to_update['nama_situs'])) {
            return 'missing_nama_situs';
        }
        if (!empty($data_to_update['email_kontak_situs']) && !filter_var($data_to_update['email_kontak_situs'], FILTER_VALIDATE_EMAIL)) {
            return 'invalid_email_kontak';
        }

        // Handle Upload Logo
        $upload_dir = PengaturanSitus::getUploadDir(); // Upload dir dari Model
        if (!$upload_dir || !is_writable($upload_dir)) {
            error_log("PengaturanController: Direktori upload umum tidak valid atau tidak dapat ditulis: " . ($upload_dir ?? 'Tidak diset'));
            // Lanjutkan tanpa upload jika direktori bermasalah, tapi catat
        }

        $old_logo_filename = $current_settings['logo_situs'] ?? null;
        $new_logo_filename_to_save = $old_logo_filename; // Defaultnya pertahankan yang lama

        if (isset($data_form['hapus_logo_situs']) && $data_form['hapus_logo_situs'] == '1' && $upload_dir && !empty($old_logo_filename)) {
            if (file_exists($upload_dir . $old_logo_filename)) {
                @unlink($upload_dir . $old_logo_filename);
            }
            $new_logo_filename_to_save = null;
        } elseif ($file_logo_data && $file_logo_data['error'] == UPLOAD_ERR_OK && !empty($file_logo_data['name']) && $upload_dir && is_writable($upload_dir)) {
            // Proses upload (mirip dengan controller lain)
            $file_ext = strtolower(pathinfo(basename($file_logo_data['name']), PATHINFO_EXTENSION));
            $allowed_img_ext = ['jpg', 'jpeg', 'png', 'gif', 'svg', 'webp'];
            if (in_array($file_ext, $allowed_img_ext) && $file_logo_data['size'] <= 2 * 1024 * 1024) { // Maks 2MB
                $new_logo_filename = "logo_situs_" . time() . "." . $file_ext;
                if (move_uploaded_file($file_logo_data['tmp_name'], $upload_dir . $new_logo_filename)) {
                    // Hapus logo lama jika ada dan berbeda
                    if (!empty($old_logo_filename) && $old_logo_filename !== $new_logo_filename && file_exists($upload_dir . $old_logo_filename)) {
                        @unlink($upload_dir . $old_logo_filename);
                    }
                    $new_logo_filename_to_save = $new_logo_filename;
                } else {
                    return 'upload_logo_failed';
                }
            } else {
                return 'invalid_logo_file';
            }
        }
        $data_to_update['logo_situs'] = $new_logo_filename_to_save;

        // Handle Upload Favicon (logika serupa dengan logo)
        $old_favicon_filename = $current_settings['favicon_situs'] ?? null;
        $new_favicon_filename_to_save = $old_favicon_filename;

        if (isset($data_form['hapus_favicon_situs']) && $data_form['hapus_favicon_situs'] == '1' && $upload_dir && !empty($old_favicon_filename)) {
            if (file_exists($upload_dir . $old_favicon_filename)) {
                @unlink($upload_dir . $old_favicon_filename);
            }
            $new_favicon_filename_to_save = null;
        } elseif ($file_favicon_data && $file_favicon_data['error'] == UPLOAD_ERR_OK && !empty($file_favicon_data['name']) && $upload_dir && is_writable($upload_dir)) {
            $file_ext_fav = strtolower(pathinfo(basename($file_favicon_data['name']), PATHINFO_EXTENSION));
            $allowed_fav_ext = ['ico', 'png', 'svg']; // Favicon bisa .ico
            if (in_array($file_ext_fav, $allowed_fav_ext) && $file_favicon_data['size'] <= 512 * 1024) { // Maks 512KB
                $new_favicon_filename = "favicon_situs_" . time() . "." . $file_ext_fav;
                if (move_uploaded_file($file_favicon_data['tmp_name'], $upload_dir . $new_favicon_filename)) {
                    if (!empty($old_favicon_filename) && $old_favicon_filename !== $new_favicon_filename && file_exists($upload_dir . $old_favicon_filename)) {
                        @unlink($upload_dir . $old_favicon_filename);
                    }
                    $new_favicon_filename_to_save = $new_favicon_filename;
                } else {
                    return 'upload_favicon_failed';
                }
            } else {
                return 'invalid_favicon_file';
            }
        }
        $data_to_update['favicon_situs'] = $new_favicon_filename_to_save;

        // Panggil Model untuk update
        if (PengaturanSitus::updatePengaturan($data_to_update)) {
            return true;
        } else {
            $model_error = PengaturanSitus::getLastError();
            error_log("PengaturanController::updatePengaturanSitus() - Gagal update. Model Error: " . $model_error . " | Data: " . print_r($data_to_update, true));
            return $model_error ?: 'db_update_failed';
        }
    }
}
