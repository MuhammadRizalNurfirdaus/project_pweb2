<?php
include "../../config/Koneksi.php";
include "../../template/header.php";

$query = mysqli_query($conn, "SELECT * FROM feedback");

echo "<div class='container mt-5'>";
echo "<h2>Kelola Feedback</h2>";
echo "<table class='table table-bordered'>";
echo "<tr><th>ID</th><th>Nama</th><th>Email</th><th>Pesan</th><th>Rating</th><th>Aksi</th></tr>";

while ($data = mysqli_fetch_array($query)) {
    echo "<tr>
            <td>{$data['id']}</td>
            <td>{$data['nama']}</td>
            <td>{$data['email']}</td>
            <td>{$data['pesan']}</td>
            <td>{$data['rating']}</td>
            <td>
                <a href='hapus_feedback.php?id={$data['id']}' class='btn btn-danger btn-sm'>Hapus</a>
            </td>
          </tr>";
}
echo "</table></div>";

include "../../template/footer.php";
