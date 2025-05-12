<?php
// File: C:\xampp\htdocs\Cilengkrang-Web-Wisata\controllers\ArtikelController.php

// TIDAK PERLU: require_once __DIR__ . '/../config/config.php';
// config.php seharusnya sudah di-include oleh file yang memanggil metode controller ini (misal: kelola_artikel.php)

// Jika controller ini perlu berinteraksi dengan model lain (misal FeedbackModel),
// maka require_once model tersebut di sini.
// Untuk saat ini, query ke tabel feedback dilakukan langsung.

class ArtikelController
{
    /**
     * Tambah artikel baru.
     * @param string $judul Judul artikel.
     * @param string $isi Isi artikel.
     * @param string|null $gambar Nama file gambar (opsional).
     * @return int|false ID artikel baru jika berhasil, false jika gagal.
     */
    public static function create($judul, $isi, $gambar = null)
    {
        global $conn;
        if (!$conn) {
            error_log("ArtikelController::create() - Koneksi database tidak tersedia.");
            return false;
        }

        // Validasi input
        $judul_bersih = htmlspecialchars(strip_tags(trim($judul)));
        // Isi bisa mengandung HTML, jadi tidak di strip_tags, tapi pastikan validasi/sanitasi di frontend/sebelum simpan
        $isi_artikel = trim($isi);
        $gambar_bersih = (!empty($gambar) && is_string($gambar)) ? htmlspecialchars(strip_tags($gambar)) : null;

        if (empty($judul_bersih)) {
            set_flash_message('danger', 'Judul artikel tidak boleh kosong.');
            return false;
        }
        if (empty($isi_artikel)) {
            set_flash_message('danger', 'Isi artikel tidak boleh kosong.');
            return false;
        }

        $sql = "INSERT INTO articles (judul, isi, gambar, created_at) VALUES (?, ?, ?, NOW())";
        $stmt = mysqli_prepare($conn, $sql);

        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "sss", $judul_bersih, $isi_artikel, $gambar_bersih);
            if (mysqli_stmt_execute($stmt)) {
                $new_id = mysqli_insert_id($conn);
                mysqli_stmt_close($stmt);
                return $new_id;
            } else {
                error_log("ArtikelController::create execute failed: " . mysqli_stmt_error($stmt) . " | SQL: " . $sql);
                mysqli_stmt_close($stmt);
                set_flash_message('danger', 'Gagal menyimpan artikel ke database.');
                return false;
            }
        } else {
            error_log("ArtikelController::create prepare failed: " . mysqli_error($conn) . " | SQL: " . $sql);
            set_flash_message('danger', 'Terjadi kesalahan saat persiapan database untuk artikel.');
            return false;
        }
    }

    /**
     * Ambil semua artikel.
     * @return array Array data artikel atau array kosong jika gagal/tidak ada.
     */
    public static function getAll()
    {
        global $conn;
        if (!$conn) {
            error_log("ArtikelController::getAll() - Koneksi database tidak tersedia.");
            return [];
        }
        $result = mysqli_query($conn, "SELECT id, judul, isi, gambar, created_at FROM articles ORDER BY created_at DESC");
        if ($result) {
            $data = mysqli_fetch_all($result, MYSQLI_ASSOC);
            mysqli_free_result($result);
            return $data;
        }
        error_log("ArtikelController::getAll query failed: " . mysqli_error($conn));
        return []; // Kembalikan array kosong jika gagal
    }

    /**
     * Ambil satu artikel berdasarkan ID.
     * @param int $id ID Artikel.
     * @return array|null Data artikel atau null jika tidak ditemukan/error.
     */
    public static function getById($id)
    {
        global $conn;
        if (!$conn) {
            error_log("ArtikelController::getById() - Koneksi database tidak tersedia.");
            return null;
        }
        $id_val = filter_var($id, FILTER_VALIDATE_INT);
        if ($id_val === false || $id_val <= 0) {
            error_log("ArtikelController::getById() - ID artikel tidak valid: " . $id);
            return null;
        }

        $sql = "SELECT id, judul, isi, gambar, created_at FROM articles WHERE id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "i", $id_val);
            if (mysqli_stmt_execute($stmt)) {
                $result_get = mysqli_stmt_get_result($stmt);
                $artikel = mysqli_fetch_assoc($result_get);
                mysqli_free_result($result_get); // Bebaskan hasil
                mysqli_stmt_close($stmt);
                return $artikel ?: null;
            } else {
                error_log("ArtikelController::getById execute failed for ID $id_val: " . mysqli_stmt_error($stmt));
                mysqli_stmt_close($stmt);
                return null;
            }
        }
        error_log("ArtikelController::getById prepare failed for ID $id_val: " . mysqli_error($conn));
        return null;
    }

    /**
     * Update artikel.
     * @param int $id ID Artikel.
     * @param string $judul Judul baru.
     * @param string $isi Isi baru.
     * @param string|null $gambar_new_name Nama file gambar baru (null jika tidak diubah, "REMOVE_IMAGE" jika ingin dihapus).
     * @param string|null $gambar_old_name Nama file gambar lama (untuk dihapus dari server jika diganti/dihapus).
     * @return bool True jika berhasil, false jika gagal.
     */
    public static function update($id, $judul, $isi, $gambar_new_name = null, $gambar_old_name = null)
    {
        global $conn;
        if (!$conn) {
            error_log("ArtikelController::update() - Koneksi database tidak tersedia.");
            set_flash_message('danger', 'Koneksi database gagal.');
            return false;
        }

        $id_val = filter_var($id, FILTER_VALIDATE_INT);
        if ($id_val === false || $id_val <= 0) {
            set_flash_message('danger', 'ID Artikel tidak valid untuk update.');
            return false;
        }

        // Validasi input
        $judul_bersih = htmlspecialchars(strip_tags(trim($judul)));
        $isi_artikel = trim($isi); // Isi bisa HTML

        if (empty($judul_bersih)) {
            set_flash_message('danger', 'Judul artikel tidak boleh kosong saat update.');
            return false;
        }
        if (empty($isi_artikel)) {
            set_flash_message('danger', 'Isi artikel tidak boleh kosong saat update.');
            return false;
        }


        // Logika untuk menentukan query berdasarkan ada/tidaknya gambar baru
        $params = [$judul_bersih, $isi_artikel];
        $types = "ss";

        if ($gambar_new_name === "REMOVE_IMAGE") {
            $sql_set_gambar = "gambar = NULL";
        } elseif ($gambar_new_name && is_string($gambar_new_name)) { // Gambar baru diupload
            $sql_set_gambar = "gambar = ?";
            $params[] = htmlspecialchars(strip_tags($gambar_new_name));
            $types .= "s";
        } else { // Tidak ada perubahan gambar
            $sql_set_gambar = null;
        }

        $sql = "UPDATE articles SET judul = ?, isi = ?";
        if ($sql_set_gambar) {
            $sql .= ", " . $sql_set_gambar;
        }
        $sql .= " WHERE id = ?";
        $params[] = $id_val;
        $types .= "i";


        $stmt = mysqli_prepare($conn, $sql);
        if (!$stmt) {
            error_log("ArtikelController::update prepare failed for ID $id_val: " . mysqli_error($conn) . " | SQL: " . $sql);
            set_flash_message('danger', 'Terjadi kesalahan saat persiapan update database.');
            return false;
        }

        mysqli_stmt_bind_param($stmt, $types, ...$params);

        if (mysqli_stmt_execute($stmt)) {
            $affected_rows = mysqli_stmt_affected_rows($stmt);
            mysqli_stmt_close($stmt);

            // Logika penghapusan file lama setelah update DB berhasil
            // Pastikan UPLOADS_ARTIKEL_PATH sudah didefinisikan di config.php
            if (defined('UPLOADS_ARTIKEL_PATH')) {
                if (($gambar_new_name === "REMOVE_IMAGE" || ($gambar_new_name && $gambar_new_name !== $gambar_old_name)) && !empty($gambar_old_name)) {
                    $old_file_path = UPLOADS_ARTIKEL_PATH . DIRECTORY_SEPARATOR . basename($gambar_old_name); // Keamanan path
                    if (file_exists($old_file_path) && is_file($old_file_path)) {
                        if (!@unlink($old_file_path)) {
                            error_log("ArtikelController::update Warning: Gagal menghapus file gambar lama: " . $old_file_path);
                            // Jangan set flash error di sini karena update DB sudah berhasil
                        }
                    }
                }
            } else {
                error_log("ArtikelController::update Warning: Konstanta UPLOADS_ARTIKEL_PATH tidak terdefinisi.");
            }

            // Mengembalikan true bahkan jika tidak ada baris yang terpengaruh (misal data sama)
            // karena query eksekusinya berhasil. Atau bisa `return $affected_rows > 0;`
            return true;
        } else {
            error_log("ArtikelController::update execute failed for ID $id_val: " . mysqli_stmt_error($stmt));
            mysqli_stmt_close($stmt);
            set_flash_message('danger', 'Gagal mengupdate artikel di database.');
            return false;
        }
    }

    /**
     * Hapus artikel berdasarkan ID.
     * Ini juga akan menghapus semua feedback yang terkait dengan artikel tersebut.
     * @param int $id ID Artikel.
     * @return bool True jika berhasil, false jika gagal.
     */
    public static function delete($id)
    {
        global $conn;
        if (!$conn) {
            error_log("ArtikelController::delete() - Koneksi database tidak tersedia.");
            set_flash_message('danger', 'Koneksi database gagal.');
            return false;
        }

        $id_val = filter_var($id, FILTER_VALIDATE_INT);
        if ($id_val === false || $id_val <= 0) {
            error_log("ArtikelController::delete Error: ID artikel tidak valid ($id).");
            set_flash_message('danger', 'ID Artikel tidak valid untuk dihapus.');
            return false;
        }

        // Ambil nama file gambar artikel untuk dihapus nanti
        $artikel = self::getById($id_val); // Gunakan getById yang sudah ada
        $gambar_to_delete_on_server = null;
        if ($artikel && !empty($artikel['gambar'])) {
            $gambar_to_delete_on_server = $artikel['gambar'];
        }

        // LANGKAH 1: Hapus semua feedback yang terkait dengan artikel_id ini
        $sql_delete_feedback = "DELETE FROM feedback WHERE artikel_id = ?";
        $stmt_feedback = mysqli_prepare($conn, $sql_delete_feedback);

        if (!$stmt_feedback) {
            error_log("ArtikelController::delete prepare failed (feedback) for artikel ID $id_val: " . mysqli_error($conn));
            set_flash_message('danger', 'Gagal persiapan penghapusan feedback terkait.');
            return false;
        }

        mysqli_stmt_bind_param($stmt_feedback, "i", $id_val);
        // Eksekusi penghapusan feedback, tidak perlu cek affected_rows di sini, lanjutkan meski tidak ada feedback
        if (!mysqli_stmt_execute($stmt_feedback)) {
            error_log("ArtikelController::delete execute failed (feedback) for artikel ID $id_val: " . mysqli_stmt_error($stmt_feedback));
            // Lanjutkan untuk menghapus artikel utama, feedback mungkin tidak ada
        }
        mysqli_stmt_close($stmt_feedback);


        // LANGKAH 2: Hapus artikel itu sendiri
        $sql_delete_article = "DELETE FROM articles WHERE id = ?";
        $stmt_article = mysqli_prepare($conn, $sql_delete_article);

        if (!$stmt_article) {
            error_log("ArtikelController::delete prepare failed (article) for ID $id_val: " . mysqli_error($conn));
            set_flash_message('danger', 'Gagal persiapan penghapusan artikel.');
            return false;
        }

        mysqli_stmt_bind_param($stmt_article, "i", $id_val);
        if (mysqli_stmt_execute($stmt_article)) {
            $affected_rows = mysqli_stmt_affected_rows($stmt_article);
            mysqli_stmt_close($stmt_article);

            if ($affected_rows > 0) {
                // LANGKAH 3: Hapus file gambar artikel jika ada dan record DB berhasil dihapus
                if ($gambar_to_delete_on_server && defined('UPLOADS_ARTIKEL_PATH')) {
                    $file_path = UPLOADS_ARTIKEL_PATH . DIRECTORY_SEPARATOR . basename($gambar_to_delete_on_server);
                    if (file_exists($file_path) && is_file($file_path)) {
                        if (!@unlink($file_path)) {
                            error_log("ArtikelController::delete Warning: Gagal menghapus file gambar artikel " . $file_path);
                            // Jangan set flash error di sini, fokus pada keberhasilan hapus DB
                        }
                    }
                } elseif ($gambar_to_delete_on_server && !defined('UPLOADS_ARTIKEL_PATH')) {
                    error_log("ArtikelController::delete Warning: Konstanta UPLOADS_ARTIKEL_PATH tidak terdefinisi, gambar tidak dihapus.");
                }
                return true; // Artikel berhasil dihapus
            } else {
                error_log("ArtikelController::delete Warning: Tidak ada artikel yang terhapus untuk ID $id_val (mungkin sudah dihapus).");
                // Jika tidak ada baris yang terpengaruh, bisa jadi ID tidak ada.
                // Pertimbangkan apakah ini error atau bukan. Untuk sekarang, return false.
                set_flash_message('warning', 'Artikel tidak ditemukan atau sudah dihapus sebelumnya.');
                return false;
            }
        } else {
            error_log("ArtikelController::delete execute failed (article) for ID $id_val: " . mysqli_stmt_error($stmt_article));
            mysqli_stmt_close($stmt_article);
            set_flash_message('danger', 'Gagal menghapus artikel dari database.');
            return false;
        }
    }

    /**
     * Mengambil sejumlah artikel terbaru.
     * @param int $limit Jumlah artikel yang ingin diambil.
     * @return array Array berisi data artikel terbaru, atau array kosong jika gagal/tidak ada.
     */
    public static function getLatest($limit = 3)
    {
        global $conn;
        if (!$conn) {
            error_log("ArtikelController::getLatest() - Koneksi database tidak tersedia.");
            return [];
        }

        $limit_val = filter_var($limit, FILTER_VALIDATE_INT);
        if ($limit_val === false || $limit_val <= 0) {
            $limit_val = 3; // Default limit
        }

        $sql = "SELECT id, judul, isi, gambar, created_at 
                  FROM articles 
                  ORDER BY created_at DESC
                  LIMIT ?";
        $stmt = mysqli_prepare($conn, $sql);

        if (!$stmt) {
            error_log("ArtikelController::getLatest() Prepare Error: " . mysqli_error($conn));
            return [];
        }

        mysqli_stmt_bind_param($stmt, "i", $limit_val);

        if (mysqli_stmt_execute($stmt)) {
            $result = mysqli_stmt_get_result($stmt);
            $articles = mysqli_fetch_all($result, MYSQLI_ASSOC);
            mysqli_free_result($result);
            mysqli_stmt_close($stmt);
            return $articles;
        } else {
            error_log("ArtikelController::getLatest() Execute Error: " . mysqli_stmt_error($stmt));
            mysqli_stmt_close($stmt);
            return [];
        }
    }

    /**
     * Menghitung semua artikel.
     * @return int Jumlah artikel atau 0 jika error.
     */
    public static function countAll()
    {
        global $conn;
        if (!$conn) {
            error_log("ArtikelController::countAll() - Koneksi DB gagal.");
            return 0;
        }
        $sql = "SELECT COUNT(id) as total FROM articles";
        $result = mysqli_query($conn, $sql);
        if ($result) {
            $row = mysqli_fetch_assoc($result);
            mysqli_free_result($result);
            return (int)($row['total'] ?? 0);
        } else {
            error_log("ArtikelController::countAll() - MySQLi Query Error: " . mysqli_error($conn));
            return 0;
        }
    }
}
