<?php
// File: C:\xampp\htdocs\Cilengkrang-Web-Wisata\models\Artikel.php

class Artikel
{
    private static $table_name = "articles"; // Sesuai screenshot Anda
    private static $db;
    private static $upload_dir_artikel; // Path absolut ke direktori upload artikel
    private static $last_error = null; // Untuk menyimpan pesan error internal model

    /**
     * Mengatur koneksi database dan path upload untuk digunakan oleh kelas ini.
     * Dipanggil sekali dari config.php atau file bootstrap.
     * @param mysqli $connection Instance koneksi mysqli.
     * @param string $upload_path Path absolut ke direktori upload artikel.
     */
    public static function init(mysqli $connection, string $upload_path)
    {
        self::$db = $connection;
        self::$upload_dir_artikel = rtrim($upload_path, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        // error_log(get_called_class() . "::init() dipanggil. DB " . (self::$db && !self::$db->connect_error ? "OK" : "FAIL") . ". Upload dir: " . self::$upload_dir_artikel);
    }

    /**
     * Memeriksa apakah koneksi database dan path upload (jika diperlukan) sudah diinisialisasi.
     * @param bool $require_upload_dir Apakah path upload direktori wajib ada untuk operasi ini.
     * @return bool True jika dependensi valid, false jika tidak. Error di-set di self::$last_error.
     */
    private static function checkDependencies(bool $require_upload_dir = false): bool
    {
        self::$last_error = null; // Reset error
        if (!self::$db || (self::$db instanceof mysqli && self::$db->connect_error)) {
            self::$last_error = (self::$db instanceof mysqli ? self::$db->connect_error : (self::$db === null ? 'Koneksi DB (self::$db) belum diset via init().' : 'Koneksi DB bukan objek mysqli.'));
            error_log(get_called_class() . " - Ketergantungan Error: " . self::$last_error);
            return false;
        }
        if ($require_upload_dir && (empty(self::$upload_dir_artikel) || !is_dir(self::$upload_dir_artikel) || !is_writable(self::$upload_dir_artikel))) {
            self::$last_error = 'Path upload direktori artikel (self::$upload_dir_artikel) belum diinisialisasi dengan benar, tidak ada, atau tidak dapat ditulis: ' . (self::$upload_dir_artikel ?: 'Kosong');
            error_log(get_called_class() . " - Ketergantungan Error: " . self::$last_error);
            return false;
        }
        return true;
    }

    /**
     * Mengambil pesan error terakhir yang terjadi di model ini atau dari mysqli.
     * @return string|null Pesan error atau null jika tidak ada.
     */
    public static function getLastError(): ?string
    {
        if (self::$last_error) {
            return self::$last_error;
        }
        if (self::$db instanceof mysqli && !empty(self::$db->error)) {
            return self::$db->error;
        } elseif (!self::$db || !(self::$db instanceof mysqli)) {
            return 'Koneksi database belum diinisialisasi atau tidak valid untuk kelas ' . get_called_class() . '.';
        }
        return null;
    }

    /**
     * Membuat artikel baru.
     * @param array $data Harus berisi 'judul', 'isi'. Opsional: 'gambar' (nama file).
     * @return int|false ID artikel baru jika berhasil, false jika gagal.
     */
    public static function create(array $data)
    {
        // Untuk create, upload_dir tidak dicek wajib di sini, karena penanganan file
        // biasanya terjadi di controller sebelum memanggil model. Model hanya menyimpan nama file.
        if (!self::checkDependencies(false)) return false;

        $judul = trim($data['judul'] ?? '');
        $isi = trim($data['isi'] ?? ''); // Bisa mengandung HTML dari editor
        $gambar = isset($data['gambar']) && !empty($data['gambar']) ? trim($data['gambar']) : null;

        if (empty($judul)) {
            self::$last_error = "Judul artikel tidak boleh kosong.";
            error_log(get_called_class() . "::create() - " . self::$last_error);
            return false;
        }
        if (empty($isi)) {
            self::$last_error = "Isi artikel tidak boleh kosong.";
            error_log(get_called_class() . "::create() - " . self::$last_error);
            return false;
        }

        $sql = "INSERT INTO " . self::$table_name . " (judul, isi, gambar, created_at) 
                VALUES (?, ?, ?, NOW())";
        $stmt = mysqli_prepare(self::$db, $sql);
        if (!$stmt) {
            self::$last_error = "MySQLi Prepare Error: " . mysqli_error(self::$db);
            error_log(get_called_class() . "::create() - " . self::$last_error . " | SQL: " . $sql);
            return false;
        }

        mysqli_stmt_bind_param($stmt, "sss", $judul, $isi, $gambar);
        if (mysqli_stmt_execute($stmt)) {
            $new_id = mysqli_insert_id(self::$db);
            mysqli_stmt_close($stmt);
            return $new_id;
        } else {
            self::$last_error = "MySQLi Execute Error: " . mysqli_stmt_error($stmt);
            error_log(get_called_class() . "::create() - " . self::$last_error);
            mysqli_stmt_close($stmt);
            return false;
        }
    }

    /**
     * Mengambil semua artikel.
     * @param string $orderBy Pengurutan hasil, contoh: 'created_at DESC'.
     * @return array Array data artikel atau array kosong jika gagal/tidak ada data.
     */
    public static function getAll(string $orderBy = 'created_at DESC'): array
    {
        if (!self::checkDependencies(false)) return [];

        $allowed_order_columns = ['id', 'judul', 'created_at'];
        $order_parts = explode(' ', trim($orderBy), 2); // Batasi hingga 2 bagian
        $column = $order_parts[0] ?? 'created_at';
        $direction = (isset($order_parts[1]) && strtoupper($order_parts[1]) === 'ASC') ? 'ASC' : 'DESC';

        if (!in_array(strtolower($column), $allowed_order_columns, true)) {
            $column = 'created_at'; // Default jika kolom tidak diizinkan
            $direction = 'DESC';
        }
        $orderBySafe = "`" . mysqli_real_escape_string(self::$db, $column) . "` " . $direction;

        $sql = "SELECT id, judul, isi, gambar, created_at FROM " . self::$table_name . " ORDER BY " . $orderBySafe;
        $result = mysqli_query(self::$db, $sql);
        if ($result) {
            $data = mysqli_fetch_all($result, MYSQLI_ASSOC);
            mysqli_free_result($result);
            return $data;
        } else {
            self::$last_error = "MySQLi Query Error: " . mysqli_error(self::$db);
            error_log(get_called_class() . "::getAll() - " . self::$last_error . " | SQL: " . $sql);
            return [];
        }
    }

    /**
     * Mengambil satu artikel berdasarkan ID.
     * @param int $id ID artikel.
     * @return array|null Data artikel jika ditemukan, atau null jika tidak/error.
     */
    public static function findById(int $id): ?array
    {
        if (!self::checkDependencies(false)) return null;
        if ($id <= 0) {
            self::$last_error = "ID artikel tidak valid: " . $id;
            error_log(get_called_class() . "::findById() - " . self::$last_error);
            return null;
        }

        $sql = "SELECT id, judul, isi, gambar, created_at FROM " . self::$table_name . " WHERE id = ? LIMIT 1";
        $stmt = mysqli_prepare(self::$db, $sql);
        if (!$stmt) {
            self::$last_error = "MySQLi Prepare Error: " . mysqli_error(self::$db);
            error_log(get_called_class() . "::findById() - " . self::$last_error . " | SQL: " . $sql);
            return null;
        }
        mysqli_stmt_bind_param($stmt, "i", $id);
        if (mysqli_stmt_execute($stmt)) {
            $result = mysqli_stmt_get_result($stmt);
            $artikel = mysqli_fetch_assoc($result);
            mysqli_stmt_close($stmt);
            return $artikel ?: null;
        } else {
            self::$last_error = "MySQLi Execute Error: " . mysqli_stmt_error($stmt);
            error_log(get_called_class() . "::findById() - " . self::$last_error);
            mysqli_stmt_close($stmt);
            return null;
        }
    }

    /**
     * Mengupdate artikel.
     * @param array $data Harus berisi 'id'. Opsional: 'judul', 'isi', 'gambar' (nama file baru),
     *                    'hapus_gambar' (boolean/string '1' untuk menghapus gambar).
     * @return bool True jika berhasil, false jika gagal.
     */
    public static function update(array $data): bool
    {
        // $require_upload_dir true jika ada potensi operasi file (update/hapus gambar)
        $potential_file_op = isset($data['gambar']) || (isset($data['hapus_gambar']) && $data['hapus_gambar']);
        if (!self::checkDependencies($potential_file_op)) return false;

        if (!isset($data['id'])) {
            self::$last_error = "ID artikel harus disertakan untuk update.";
            error_log(get_called_class() . "::update() - " . self::$last_error);
            return false;
        }
        $id = filter_var($data['id'], FILTER_VALIDATE_INT);
        if ($id === false || $id <= 0) {
            self::$last_error = "ID artikel tidak valid untuk update: " . print_r($data['id'], true);
            error_log(get_called_class() . "::update() - " . self::$last_error);
            return false;
        }

        $current_artikel = self::findById($id);
        if (!$current_artikel) {
            self::$last_error = "Artikel dengan ID {$id} tidak ditemukan untuk diupdate.";
            // error_log sudah dilakukan oleh findById() jika gagal
            return false;
        }

        $update_fields = [];
        $params = [];
        $types = "";

        // Update judul jika ada dan berbeda
        if (isset($data['judul']) && trim($data['judul']) !== $current_artikel['judul']) {
            $judul = trim($data['judul']);
            if (empty($judul)) {
                self::$last_error = "Judul tidak boleh kosong saat update.";
                error_log(get_called_class() . "::update() - " . self::$last_error);
                return false;
            }
            $update_fields[] = "judul = ?";
            $params[] = $judul;
            $types .= "s";
        }

        // Update isi jika ada dan berbeda
        if (isset($data['isi']) && trim($data['isi']) !== $current_artikel['isi']) {
            $isi = trim($data['isi']);
            if (empty($isi)) {
                self::$last_error = "Isi tidak boleh kosong saat update.";
                error_log(get_called_class() . "::update() - " . self::$last_error);
                return false;
            }
            $update_fields[] = "isi = ?";
            $params[] = $isi;
            $types .= "s";
        }

        $old_gambar_filename = $current_artikel['gambar'];
        $gambar_to_set_in_db = $old_gambar_filename; // Defaultnya gambar tidak berubah
        $gambar_lama_untuk_dihapus_fisik = null;

        if (isset($data['hapus_gambar']) && ($data['hapus_gambar'] == '1' || $data['hapus_gambar'] === true)) {
            $gambar_to_set_in_db = null; // Hapus nama file gambar dari DB
            if (!empty($old_gambar_filename)) {
                $gambar_lama_untuk_dihapus_fisik = $old_gambar_filename;
            }
        } elseif (isset($data['gambar'])) { // Ada field 'gambar' di data (bisa nama file baru atau string kosong jika tidak ada upload)
            $new_gambar_filename = trim($data['gambar']);
            if (!empty($new_gambar_filename) && $new_gambar_filename !== $old_gambar_filename) {
                $gambar_to_set_in_db = $new_gambar_filename;
                if (!empty($old_gambar_filename)) {
                    $gambar_lama_untuk_dihapus_fisik = $old_gambar_filename;
                }
            } elseif (empty($new_gambar_filename) && $old_gambar_filename !== null) {
                // Jika $data['gambar'] adalah string kosong, dan sebelumnya ada gambar, berarti gambar tidak diubah (kecuali hapus_gambar diset)
                // Tidak melakukan apa-apa, $gambar_to_set_in_db tetap $old_gambar_filename
            }
        }

        // Hanya tambahkan 'gambar' ke query jika nilainya berubah dari yang ada di DB
        if ($gambar_to_set_in_db !== $old_gambar_filename) {
            $update_fields[] = "gambar = ?";
            $params[] = $gambar_to_set_in_db; // Bisa null
            $types .= "s";
        }

        // Jika tidak ada field yang diupdate
        if (empty($update_fields)) {
            // Jika ada operasi penghapusan file fisik gambar lama (misal, hapus_gambar true tapi gambar memang sudah null di DB)
            if ($gambar_lama_untuk_dihapus_fisik && self::$upload_dir_artikel) {
                $file_path_lama = self::$upload_dir_artikel . basename($gambar_lama_untuk_dihapus_fisik);
                if (file_exists($file_path_lama) && is_file($file_path_lama)) {
                    if (!@unlink($file_path_lama)) {
                        error_log(get_called_class() . "::update() Peringatan: Gagal hapus file gambar lama (tanpa update DB) " . $file_path_lama);
                    }
                }
            }
            return true; // Dianggap berhasil karena tidak ada perubahan data atau hanya operasi file
        }

        // Tambahkan 'updated_at' jika ada di tabel (misal, 'updated_at = NOW()')
        // $update_fields[] = "updated_at = NOW()";

        $sql = "UPDATE " . self::$table_name . " SET " . implode(', ', $update_fields) . " WHERE id = ?";
        $params[] = $id;
        $types .= "i";

        $stmt = mysqli_prepare(self::$db, $sql);
        if (!$stmt) {
            self::$last_error = "MySQLi Prepare Error: " . mysqli_error(self::$db);
            error_log(get_called_class() . "::update() - " . self::$last_error . " | SQL: " . $sql);
            return false;
        }
        mysqli_stmt_bind_param($stmt, $types, ...$params);
        if (mysqli_stmt_execute($stmt)) {
            // $affected_rows = mysqli_stmt_affected_rows($stmt); // Bisa 0 jika data sama
            mysqli_stmt_close($stmt);

            // Hapus file gambar lama dari server jika operasi DB berhasil
            if ($gambar_lama_untuk_dihapus_fisik && self::$upload_dir_artikel) {
                $file_path_lama = self::$upload_dir_artikel . basename($gambar_lama_untuk_dihapus_fisik);
                if (file_exists($file_path_lama) && is_file($file_path_lama)) {
                    if (!@unlink($file_path_lama)) {
                        // Ini adalah peringatan, bukan kegagalan utama update
                        error_log(get_called_class() . "::update() Peringatan: Gagal hapus file gambar lama setelah update DB: " . $file_path_lama);
                        // Mungkin set flash message 'warning' di Controller
                    }
                }
            }
            return true;
        } else {
            self::$last_error = "MySQLi Execute Error: " . mysqli_stmt_error($stmt);
            error_log(get_called_class() . "::update() - " . self::$last_error);
            mysqli_stmt_close($stmt);
            return false;
        }
    }

    /**
     * Menghapus artikel berdasarkan ID.
     * Ini juga akan menghapus file gambar terkait dan semua feedback terkait.
     * Menggunakan transaksi database.
     * @param int $id ID artikel.
     * @return bool True jika berhasil, false jika gagal.
     */
    public static function delete(int $id): bool
    {
        // upload_dir dibutuhkan untuk hapus file gambar
        if (!self::checkDependencies(true)) return false;
        if ($id <= 0) {
            self::$last_error = "ID artikel tidak valid untuk dihapus: " . $id;
            error_log(get_called_class() . "::delete() - " . self::$last_error);
            return false;
        }

        $artikel = self::findById($id); // Ambil info untuk hapus gambar nanti
        if (!$artikel) {
            self::$last_error = "Artikel dengan ID {$id} tidak ditemukan untuk dihapus.";
            // error_log sudah di findById
            return false; // Artikel tidak ada, dianggap gagal menghapus
        }

        mysqli_begin_transaction(self::$db);
        try {
            // 1. Hapus feedback terkait
            if (class_exists('Feedback') && method_exists('Feedback', 'deleteByArtikelId')) {
                if (!Feedback::deleteByArtikelId($id)) {
                    // Gagal hapus feedback bisa jadi karena tidak ada feedback, atau error DB.
                    // Jika Feedback::deleteByArtikelId mengembalikan false pada error DB, maka throw.
                    $feedback_error = Feedback::getLastError();
                    if ($feedback_error) { // Jika ada error spesifik dari model Feedback
                        throw new Exception("Gagal menghapus feedback terkait artikel ID {$id}: " . $feedback_error);
                    }
                    // Jika tidak ada error, mungkin memang tidak ada feedback, lanjutkan.
                    error_log(get_called_class() . "::delete() - Info: Tidak ada feedback yang dihapus atau operasi Feedback::deleteByArtikelId mengindikasikan sukses tanpa error untuk artikel ID {$id}.");
                }
            } elseif (class_exists('Feedback')) {
                error_log(get_called_class() . "::delete() - Peringatan: Metode Feedback::deleteByArtikelId() tidak ditemukan, feedback mungkin tidak terhapus.");
            }

            // 2. Hapus artikel utama
            $sql_delete_article = "DELETE FROM " . self::$table_name . " WHERE id = ?";
            $stmt_article = mysqli_prepare(self::$db, $sql_delete_article);
            if (!$stmt_article) {
                throw new Exception("Gagal prepare statement hapus artikel: " . mysqli_error(self::$db));
            }
            mysqli_stmt_bind_param($stmt_article, "i", $id);
            if (!mysqli_stmt_execute($stmt_article)) {
                throw new Exception("Gagal eksekusi statement hapus artikel: " . mysqli_stmt_error($stmt_article));
            }
            $affected_rows = mysqli_stmt_affected_rows($stmt_article);
            mysqli_stmt_close($stmt_article);

            if ($affected_rows <= 0) {
                // Ini aneh jika $artikel ditemukan sebelumnya, tapi bisa jadi race condition.
                throw new Exception("Tidak ada baris artikel yang terhapus untuk ID {$id}, padahal artikel ditemukan sebelumnya.");
            }

            // 3. Hapus file gambar fisik (setelah DB commit berhasil)
            $gambar_dihapus_fisik = false;
            if (!empty($artikel['gambar'])) {
                $file_path = self::$upload_dir_artikel . basename($artikel['gambar']);
                if (file_exists($file_path) && is_file($file_path)) {
                    if (!@unlink($file_path)) {
                        // Jangan throw exception di sini karena DB sudah ter-commit.
                        // Cukup log sebagai peringatan. Controller bisa memberi pesan tambahan.
                        self::$last_error = "Peringatan: Artikel dan feedback dihapus, tetapi gagal menghapus file gambar fisik: " . $file_path;
                        error_log(get_called_class() . "::delete() - " . self::$last_error);
                    } else {
                        $gambar_dihapus_fisik = true;
                    }
                } else {
                    error_log(get_called_class() . "::delete() Info: File gambar tidak ditemukan di server untuk dihapus: " . $file_path);
                }
            }

            mysqli_commit(self::$db);
            return true;
        } catch (Exception $e) {
            if (isset(self::$db) && self::$db->thread_id) { // Cek jika koneksi masih ada
                mysqli_rollback(self::$db);
            }
            self::$last_error = "Exception saat menghapus artikel: " . $e->getMessage();
            error_log(get_called_class() . "::delete() - " . self::$last_error);
            return false;
        }
    }

    /**
     * Mengambil sejumlah artikel terbaru.
     * @param int $limit Jumlah artikel yang diinginkan.
     * @return array Daftar artikel atau array kosong.
     */
    public static function getLatest(int $limit = 3): array
    {
        if (!self::checkDependencies(false)) return [];
        if ($limit <= 0) $limit = 3; // Pastikan limit valid

        $sql = "SELECT id, judul, isi, gambar, created_at FROM " . self::$table_name . " ORDER BY created_at DESC LIMIT ?";
        $stmt = mysqli_prepare(self::$db, $sql);
        if (!$stmt) {
            self::$last_error = "MySQLi Prepare Error: " . mysqli_error(self::$db);
            error_log(get_called_class() . "::getLatest() - " . self::$last_error . " | SQL: " . $sql);
            return [];
        }
        mysqli_stmt_bind_param($stmt, "i", $limit);
        if (mysqli_stmt_execute($stmt)) {
            $result = mysqli_stmt_get_result($stmt);
            $articles = mysqli_fetch_all($result, MYSQLI_ASSOC);
            mysqli_stmt_close($stmt);
            return $articles;
        } else {
            self::$last_error = "MySQLi Execute Error: " . mysqli_stmt_error($stmt);
            error_log(get_called_class() . "::getLatest() - " . self::$last_error);
            mysqli_stmt_close($stmt);
            return [];
        }
    }

    /**
     * Menghitung jumlah total artikel.
     * @return int Jumlah artikel, atau 0 jika error.
     */
    public static function countAll(): int
    {
        if (!self::checkDependencies(false)) return 0;
        $sql = "SELECT COUNT(id) as total FROM " . self::$table_name;
        $result = mysqli_query(self::$db, $sql);
        if ($result) {
            $row = mysqli_fetch_assoc($result);
            mysqli_free_result($result);
            return (int)($row['total'] ?? 0);
        } else {
            self::$last_error = "MySQLi Query Error: " . mysqli_error(self::$db);
            error_log(get_called_class() . "::countAll() - " . self::$last_error . " | SQL: " . $sql);
            return 0;
        }
    }

    /**
     * Mengambil artikel lain, mengecualikan ID tertentu.
     * @param int $limit Jumlah artikel yang diinginkan.
     * @param array $exclude_ids_arr Array ID artikel yang akan dikecualikan.
     * @return array Daftar artikel atau array kosong.
     */
    public static function getOtherArticles(int $limit, array $exclude_ids_arr = []): array
    {
        if (!self::checkDependencies(false)) return [];
        if ($limit <= 0) $limit = 3;

        $params = [];
        $types = "";

        $sql_where_not_in = "";
        if (!empty($exclude_ids_arr)) {
            // Pastikan semua ID adalah integer
            $valid_exclude_ids = array_filter(array_map('intval', $exclude_ids_arr), function ($id) {
                return $id > 0;
            });
            if (!empty($valid_exclude_ids)) {
                $placeholders = implode(',', array_fill(0, count($valid_exclude_ids), '?'));
                $sql_where_not_in = " WHERE id NOT IN (" . $placeholders . ")";
                foreach ($valid_exclude_ids as $ex_id) {
                    $params[] = $ex_id;
                    $types .= "i";
                }
            }
        }

        $sql = "SELECT id, judul, gambar, created_at FROM " . self::$table_name . $sql_where_not_in . " ORDER BY created_at DESC LIMIT ?";
        $params[] = $limit;
        $types .= "i";

        $stmt = mysqli_prepare(self::$db, $sql);
        if (!$stmt) {
            self::$last_error = "MySQLi Prepare Error: " . mysqli_error(self::$db);
            error_log(get_called_class() . "::getOtherArticles() - " . self::$last_error . " | SQL: " . $sql);
            return [];
        }

        if (!empty($params)) { // Hanya bind jika ada parameter
            mysqli_stmt_bind_param($stmt, $types, ...$params);
        }

        if (mysqli_stmt_execute($stmt)) {
            $result = mysqli_stmt_get_result($stmt);
            $articles = mysqli_fetch_all($result, MYSQLI_ASSOC);
            mysqli_stmt_close($stmt);
            return $articles;
        } else {
            self::$last_error = "MySQLi Execute Error: " . mysqli_stmt_error($stmt);
            error_log(get_called_class() . "::getOtherArticles() - " . self::$last_error);
            mysqli_stmt_close($stmt);
            return [];
        }
    }

    // Metode findByIdWithAuthor() yang sebelumnya ada:
    // Jika tabel articles Anda TIDAK memiliki kolom user_id, metode ini mungkin tidak begitu berguna
    // atau nama penulis harus diambil dari sumber lain atau di-hardcode.
    // Jika ADA user_id di tabel articles dan Anda ingin JOIN dengan tabel users:

    public static function findByIdWithAuthor(int $id): ?array
    {
        if (!self::checkDependencies(false)) return null;
        if ($id <= 0) {
            self::$last_error = "ID artikel tidak valid: " . $id;
            error_log(get_called_class() . "::findByIdWithAuthor() - " . self::$last_error);
            return null;
        }

        // PASTIKAN 'articles' punya kolom user_id dan 'users' punya nama_lengkap
        $sql = "SELECT a.id, a.judul, a.isi, a.gambar, a.created_at, u.nama_lengkap AS nama_penulis
                FROM " . self::$table_name . " a
                LEFT JOIN users u ON a.user_id = u.id
                WHERE a.id = ? LIMIT 1";

        $stmt = mysqli_prepare(self::$db, $sql);
        if (!$stmt) {
            self::$last_error = "MySQLi Prepare Error: " . mysqli_error(self::$db);
            error_log(get_called_class() . "::findByIdWithAuthor() - " . self::$last_error . " | SQL: " . $sql);
            return null;
        }
        mysqli_stmt_bind_param($stmt, "i", $id);
        if (mysqli_stmt_execute($stmt)) {
            $result = mysqli_stmt_get_result($stmt);
            $artikel = mysqli_fetch_assoc($result);
            mysqli_stmt_close($stmt);
            return $artikel ?: null;
        } else {
            self::$last_error = "MySQLi Execute Error: " . mysqli_stmt_error($stmt);
            error_log(get_called_class() . "::findByIdWithAuthor() - " . self::$last_error);
            mysqli_stmt_close($stmt);
            return null;
        }
    }
} // End of class Artikel
