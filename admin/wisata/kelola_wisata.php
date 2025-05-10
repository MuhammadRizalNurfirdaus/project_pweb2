<?php
include "../../config/Koneksi.php";
include '../template/header_admin.php';


$query = mysqli_query($conn, "SELECT id, nama, deskripsi, gambar, lokasi, harga, created_at FROM wisata ORDER BY created_at DESC");

echo "<div class='container mt-5'>";
echo "<h2>Kelola Data Wisata</h2>";
echo "<a href='tambah_wisata.php' class='btn btn-success mb-3'>Tambah Wisata</a>";
echo "<table class='table table-bordered'>";
echo "<thead class='table-dark'><tr><th>ID</th><th>Nama</th><th>Deskripsi</th><th>Lokasi</th><th>Harga</th><th>Gambar</th><th>Tanggal Dibuat</th><th>Aksi</th></tr></thead>";
echo "<tbody>";

if ($query && mysqli_num_rows($query) > 0) {
  while ($data = mysqli_fetch_assoc($query)) { // Changed to mysqli_fetch_assoc for clarity
    echo "<tr>
                <td>{$data['id']}</td>
                <td>" . htmlspecialchars($data['nama']) . "</td>
                <td>" . nl2br(htmlspecialchars(substr($data['deskripsi'], 0, 150))) . (strlen($data['deskripsi']) > 150 ? "..." : "") . "</td>
                <td>" . htmlspecialchars($data['lokasi']) . "</td>
                <td>Rp " . htmlspecialchars(number_format($data['harga'], 0, ',', '.')) . "</td>
                <td>";
    if (!empty($data['gambar'])) {
      echo "<img src='../../public/img/" . htmlspecialchars($data['gambar']) . "' width='100' alt='" . htmlspecialchars($data['nama']) . "'>";
    } else {
      echo "Tidak ada gambar";
    }
    echo "</td>
                <td>" . htmlspecialchars(date('d M Y', strtotime($data['created_at']))) . "</td>
                <td>
                    <a href='edit_wisata.php?id={$data['id']}' class='btn btn-warning btn-sm'>Edit</a>
                    <a href='hapus_wisata.php?id={$data['id']}' class='btn btn-danger btn-sm' onclick='return confirm(\"Yakin ingin menghapus data wisata ini?\")'>Hapus</a>
                </td>
              </tr>";
  }
} else {
  echo "<tr><td colspan='8' class='text-center'>Belum ada data wisata.</td></tr>";
}
echo "</tbody></table></div>";

include "../../template/footer.php";
