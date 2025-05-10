<?php
// File: C:\xampp\htdocs\Cilengkrang-Web-Wisata\models\Artikel.php
class Artikel
{
    private $conn;
    private $table_name = "articles"; // Pastikan nama tabel ini benar

    public $id;
    public $judul;
    public $isi;
    public $gambar;
    public $created_at;

    public function __construct($db_connection)
    {
        $this->conn = $db_connection;
    }

    // ... (method create, getAll, getById, update, delete seperti di respons sebelumnya) ...
    // Saya akan menyertakan kembali method-method tersebut untuk kelengkapan file ini

    public function create()
    {
        $query = "INSERT INTO " . $this->table_name . " (judul, isi, gambar) VALUES (?, ?, ?)";
        $stmt = mysqli_prepare($this->conn, $query);
        if (!$stmt) {
            error_log("MySQLi Prepare Error (Artikel Create): " . mysqli_error($this->conn));
            return false;
        }
        $this->judul = htmlspecialchars(strip_tags($this->judul ?? ''));
        $this->isi = $this->isi ?? '';
        $gambar_to_insert = !empty($this->gambar) ? htmlspecialchars(strip_tags($this->gambar)) : null;
        mysqli_stmt_bind_param($stmt, "sss", $this->judul, $this->isi, $gambar_to_insert);
        if (mysqli_stmt_execute($stmt)) {
            $this->id = mysqli_insert_id($this->conn);
            mysqli_stmt_close($stmt);
            return true;
        } else {
            error_log("MySQLi Execute Error (Artikel Create): " . mysqli_stmt_error($stmt));
            mysqli_stmt_close($stmt);
            return false;
        }
    }

    public function getAll()
    {
        $query = "SELECT id, judul, isi, gambar, created_at FROM " . $this->table_name . " ORDER BY created_at DESC";
        $result = mysqli_query($this->conn, $query);
        if ($result) {
            return mysqli_fetch_all($result, MYSQLI_ASSOC);
        } else {
            error_log("MySQLi Error (Artikel getAll): " . mysqli_error($this->conn));
            return [];
        }
    }

    public function getById($id)
    {
        $id_to_get = intval($id);
        if ($id_to_get <= 0) return false;
        $query = "SELECT id, judul, isi, gambar, created_at FROM " . $this->table_name . " WHERE id = ? LIMIT 1";
        $stmt = mysqli_prepare($this->conn, $query);
        if (!$stmt) {
            error_log("MySQLi Prepare Error (Artikel getById): " . mysqli_error($this->conn));
            return false;
        }
        mysqli_stmt_bind_param($stmt, "i", $id_to_get);
        if (mysqli_stmt_execute($stmt)) {
            $result = mysqli_stmt_get_result($stmt);
            $row = mysqli_fetch_assoc($result);
            mysqli_stmt_close($stmt);
            if ($row) {
                $this->id = $row['id'];
                $this->judul = $row['judul'];
                $this->isi = $row['isi'];
                $this->gambar = $row['gambar'];
                $this->created_at = $row['created_at'];
                return true;
            }
            return false;
        } else {
            error_log("MySQLi Execute Error (Artikel getById): " . mysqli_stmt_error($stmt));
            mysqli_stmt_close($stmt);
            return false;
        }
    }

    public function update()
    {
        if (!$this->id || intval($this->id) <= 0) {
            error_log("Artikel Update Error: ID not set or invalid.");
            return false;
        }
        $query = "UPDATE " . $this->table_name . " SET judul = ?, isi = ?";
        $params = [];
        $params[] = htmlspecialchars(strip_tags($this->judul ?? ''));
        $params[] = $this->isi ?? '';
        $types = "ss";
        if (property_exists($this, 'gambar') && isset($this->gambar)) { // Cek jika properti gambar di-set untuk update
            $query .= ", gambar = ?";
            $params[] = !empty($this->gambar) ? htmlspecialchars(strip_tags($this->gambar)) : null;
            $types .= "s";
        }
        $query .= " WHERE id = ?";
        $params[] = intval($this->id);
        $types .= "i";
        $stmt = mysqli_prepare($this->conn, $query);
        if (!$stmt) {
            error_log("MySQLi Prepare Error (Artikel Update): " . mysqli_error($this->conn));
            return false;
        }
        mysqli_stmt_bind_param($stmt, $types, ...$params);
        if (mysqli_stmt_execute($stmt)) {
            mysqli_stmt_close($stmt);
            return true;
        } else {
            error_log("MySQLi Execute Error (Artikel Update): " . mysqli_stmt_error($stmt));
            mysqli_stmt_close($stmt);
            return false;
        }
    }

    public function delete()
    {
        if (!$this->id || intval($this->id) <= 0) {
            error_log("Artikel Delete Error: ID not set or invalid.");
            return false;
        }
        $query = "DELETE FROM " . $this->table_name . " WHERE id = ?";
        $stmt = mysqli_prepare($this->conn, $query);
        if (!$stmt) {
            error_log("MySQLi Prepare Error (Artikel Delete): " . mysqli_error($this->conn));
            return false;
        }
        $id_to_delete = intval($this->id);
        mysqli_stmt_bind_param($stmt, "i", $id_to_delete);
        if (mysqli_stmt_execute($stmt)) {
            $affected_rows = mysqli_stmt_affected_rows($stmt);
            mysqli_stmt_close($stmt);
            return $affected_rows > 0;
        } else {
            error_log("MySQLi Execute Error (Artikel Delete): " . mysqli_stmt_error($stmt));
            mysqli_stmt_close($stmt);
            return false;
        }
    }

    /**
     * Mengambil sejumlah artikel terbaru.
     * @param int $limit Jumlah artikel yang ingin diambil.
     * @return array Array berisi data artikel terbaru, atau array kosong jika gagal/tidak ada.
     */
    public function getLatest($limit = 3)
    {
        $limit = intval($limit);
        if ($limit <= 0) {
            $limit = 3; // Default limit
        }

        $query = "SELECT id, judul, isi, gambar, created_at 
                  FROM " . $this->table_name . " 
                  ORDER BY created_at DESC
                  LIMIT ?";
        $stmt = mysqli_prepare($this->conn, $query);

        if (!$stmt) {
            error_log("MySQLi Prepare Error (Artikel getLatest): " . mysqli_error($this->conn));
            return [];
        }

        mysqli_stmt_bind_param($stmt, "i", $limit);

        if (mysqli_stmt_execute($stmt)) {
            $result = mysqli_stmt_get_result($stmt);
            $articles = mysqli_fetch_all($result, MYSQLI_ASSOC);
            mysqli_stmt_close($stmt);
            return $articles;
        } else {
            error_log("MySQLi Execute Error (Artikel getLatest): " . mysqli_stmt_error($stmt));
            mysqli_stmt_close($stmt);
            return [];
        }
    }
}
