<?php
// File: C:\xampp\htdocs\Cilengkrang-Web-Wisata\models\PengaturanSitus.php

class PengaturanSitus
{
    private static $table_name = "pengaturan_situs";
    private static $db;
    private static $last_error = null;
    // Asumsi path upload untuk logo dan favicon akan sama dengan path upload umum atau profil
    // Jika berbeda, Anda perlu menambahkannya ke init() dan checkDependencies()
    private static $upload_dir_umum;

    public static function init(mysqli $connection, $general_upload_path) // Path untuk logo/favicon
    {
        self::$db = $connection;
        self::$upload_dir_umum = rtrim($general_upload_path, '/\\') . DIRECTORY_SEPARATOR;
    }

    private static function checkDbConnection($require_upload_dir = false)
    {
        self::$last_error = null;
        if (!self::$db || (self::$db instanceof mysqli && self::$db->connect_error)) {
            self::$last_error = "Koneksi DB Model PengaturanSitus gagal.";
            error_log(get_called_class() . " - " . self::$last_error);
            return false;
        }
        if ($require_upload_dir && (empty(self::$upload_dir_umum) || !is_dir(self::$upload_dir_umum))) {
            self::$last_error = "Path upload umum untuk PengaturanSitus belum di-init atau tidak valid.";
            error_log(get_called_class() . " - " . self::$last_error);
            return false;
        }
        return true;
    }

    public static function getLastError(): ?string
    {
        return self::$last_error ?: (self::$db instanceof mysqli ? self::$db->error : null);
    }

    /**
     * Mengambil semua pengaturan situs (dari baris pertama).
     * @return array|null Array asosiatif pengaturan atau null jika tidak ditemukan/error.
     */
    public static function getPengaturan(): ?array
    {
        if (!self::checkDbConnection()) return null;

        // Selalu ambil baris dengan ID 1 (atau primary key lain jika Anda set berbeda)
        $sql = "SELECT * FROM " . self::$table_name . " WHERE id = 1 LIMIT 1";
        $result = mysqli_query(self::$db, $sql);

        if ($result) {
            $settings = mysqli_fetch_assoc($result);
            mysqli_free_result($result);
            return $settings ?: null; // Kembalikan null jika tidak ada baris (seharusnya tidak terjadi jika sudah di-seed)
        } else {
            self::$last_error = "Gagal mengambil pengaturan: " . mysqli_error(self::$db);
            error_log(get_called_class() . "::getPengaturan() - " . self::$last_error);
            return null;
        }
    }

    /**
     * Mengupdate pengaturan situs (pada baris dengan ID 1).
     * @param array $data Array asosiatif data pengaturan yang akan diupdate.
     * @return bool True jika berhasil, false jika gagal.
     */
    public static function updatePengaturan(array $data): bool
    {
        if (!self::checkDbConnection()) return false;
        self::$last_error = null;

        if (empty($data)) {
            self::$last_error = "Tidak ada data untuk diupdate.";
            return true; // Dianggap sukses jika tidak ada yang diubah
        }

        $fields_to_set_sql = [];
        $params_for_bind = [];
        $types_for_bind = "";

        // Daftar field yang diizinkan untuk diupdate dari tabel pengaturan_situs
        // Sesuaikan dengan kolom di tabel Anda
        $allowed_fields = [
            'nama_situs' => 's',
            'tagline_situs' => 's',
            'deskripsi_situs' => 's',
            'email_kontak_situs' => 's',
            'telepon_kontak_situs' => 's',
            'alamat_situs' => 's',
            'logo_situs' => 's',
            'favicon_situs' => 's', // Nama file
            'link_facebook' => 's',
            'link_instagram' => 's',
            'link_twitter' => 's',
            'link_youtube' => 's',
            'google_analytics_id' => 's',
            'items_per_page' => 'i',
            'mode_pemeliharaan' => 'i'
        ];

        foreach ($allowed_fields as $field_key => $type_char) {
            if (array_key_exists($field_key, $data)) {
                $fields_to_set_sql[] = "`" . $field_key . "` = ?";
                $value_to_bind = $data[$field_key];

                // Khusus untuk mode_pemeliharaan, pastikan 0 atau 1
                if ($field_key === 'mode_pemeliharaan') {
                    $value_to_bind = in_array((int)$value_to_bind, [0, 1]) ? (int)$value_to_bind : 0;
                }
                // Untuk items_per_page, pastikan integer positif
                if ($field_key === 'items_per_page') {
                    $value_to_bind = max(1, (int)$value_to_bind); // Minimal 1
                }
                // Untuk field string yang bisa null, jika dikirim string kosong, set jadi null
                if (in_array($field_key, ['tagline_situs', 'email_kontak_situs', 'telepon_kontak_situs', 'alamat_situs', 'logo_situs', 'favicon_situs', 'link_facebook', 'link_instagram', 'link_twitter', 'link_youtube', 'google_analytics_id']) && $value_to_bind === '') {
                    $value_to_bind = null;
                }

                $params_for_bind[] = $value_to_bind;
                $types_for_bind .= $type_char;
            }
        }

        if (empty($fields_to_set_sql)) {
            self::$last_error = "Tidak ada field valid yang dikirim untuk diupdate.";
            error_log(get_called_class() . "::updatePengaturan() - " . self::$last_error . " Data: " . print_r($data, true));
            return true; // Dianggap sukses jika tidak ada yang diubah
        }

        // `updated_at` akan diupdate otomatis oleh DB

        $sql_update = "UPDATE `" . self::$table_name . "` SET " . implode(', ', $fields_to_set_sql) . " WHERE `id` = 1"; // Selalu update ID 1

        $stmt = mysqli_prepare(self::$db, $sql_update);
        if (!$stmt) {
            self::$last_error = "MySQLi Prepare Error (updatePengaturan): " . mysqli_error(self::$db);
            error_log(get_called_class() . "::updatePengaturan() - " . self::$last_error . " | SQL: " . $sql_update);
            return false;
        }

        mysqli_stmt_bind_param($stmt, $types_for_bind, ...$params_for_bind);

        if (mysqli_stmt_execute($stmt)) {
            mysqli_stmt_close($stmt);
            return true;
        } else {
            self::$last_error = "MySQLi Execute Error (updatePengaturan): " . mysqli_stmt_error($stmt);
            error_log(get_called_class() . "::updatePengaturan() - " . self::$last_error);
            mysqli_stmt_close($stmt);
            return false;
        }
    }

    /**
     * Mendapatkan path direktori upload umum (untuk logo/favicon).
     */
    public static function getUploadDir(): ?string
    {
        return self::$upload_dir_umum;
    }
}
