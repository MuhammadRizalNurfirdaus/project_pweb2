<?php
include "../../config/Koneksi.php";
include '../template/header_admin.php';

// DESCRIBE feedback has: id, user_id, artikel_id, komentar, rating
// It does NOT have nama, email, pesan directly. 'pesan' likely maps to 'komentar'.
// To display user's name/email, you'd need to JOIN with a users table using user_id.
// For now, displaying available columns.
// Assuming 'created_at' column exists for ordering, or use 'id'.
$query_sql = "SELECT id, user_id, artikel_id, komentar, rating, created_at FROM feedback ORDER BY created_at DESC";
$query_result = mysqli_query($conn, $query_sql);

echo "<div class='container mt-5'>";
echo "<h2>Kelola Feedback</h2>";
echo "<table class='table table-bordered'>";
echo "<thead><tr><th>ID</th><th>User ID</th><th>Artikel ID</th><th>Komentar</th><th>Rating</th><th>Tanggal</th><th>Aksi</th></tr></thead>";
echo "<tbody>";

if ($query_result && mysqli_num_rows($query_result) > 0) {
    while ($data = mysqli_fetch_assoc($query_result)) {
        echo "<tr>
                <td>{$data['id']}</td>
                <td>" . htmlspecialchars($data['user_id']) . "</td>
                <td>" . htmlspecialchars($data['artikel_id']) . "</td>
                <td>" . nl2br(htmlspecialchars($data['komentar'])) . "</td>
                <td>" . htmlspecialchars($data['rating']) . "</td>
                <td>" . htmlspecialchars(date('d M Y H:i', strtotime($data['created_at']))) . "</td>
                <td>
                    <a href='hapus_feedback.php?id={$data['id']}' class='btn btn-danger btn-sm' onclick='return confirm(\"Yakin ingin menghapus feedback ini?\")'>Hapus</a>
                </td>
              </tr>";
    }
} else {
    echo "<tr><td colspan='7' class='text-center'>Belum ada feedback.</td></tr>";
}
echo "</tbody></table></div>";

include "../../template/footer.php";
