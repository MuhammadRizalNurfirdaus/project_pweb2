<?php
include "../../config/Koneksi.php";
include '../template/header_admin.php';

// Assuming 'booking' table and it has a 'tanggal_kunjungan' or a 'created_at' for ordering
// The DESCRIBE output for 'bookings' (plural) is very different from fields used here.
// This code assumes a 'booking' (singular) table with columns:
// id, nama, email, no_hp, jumlah_tiket, tanggal_kunjungan, catatan
// And possibly a 'created_at' or similar for ordering. Let's use tanggal_kunjungan for ordering.
?>
<div class="container mt-5">
    <h2>Kelola Data Booking</h2>
    <table class="table table-bordered mt-3">
        <thead class="table-dark">
            <tr>
                <th>ID</th>
                <th>Nama</th>
                <th>Email</th>
                <th>No HP</th>
                <th>Jumlah Tiket</th>
                <th>Tanggal Kunjungan</th>
                <th>Catatan</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            <?php
            // Corrected table name to 'booking' as used in hapus_booking.php and proses_booking.php
            $result = mysqli_query($conn, "SELECT id, nama, email, no_hp, jumlah_tiket, tanggal_kunjungan, catatan FROM booking ORDER BY tanggal_kunjungan DESC");
            if ($result && mysqli_num_rows($result) > 0) {
                while ($row = mysqli_fetch_assoc($result)) {
                    echo "<tr>
                        <td>{$row['id']}</td>
                        <td>" . htmlspecialchars($row['nama']) . "</td>
                        <td>" . htmlspecialchars($row['email']) . "</td>
                        <td>" . htmlspecialchars($row['no_hp']) . "</td>
                        <td>" . htmlspecialchars($row['jumlah_tiket']) . "</td>
                        <td>" . htmlspecialchars(date('d M Y', strtotime($row['tanggal_kunjungan']))) . "</td>
                        <td>" . nl2br(htmlspecialchars($row['catatan'])) . "</td>
                        <td>
                            <a href='hapus_booking.php?id={$row['id']}' class='btn btn-danger btn-sm' onclick='return confirm(\"Yakin hapus data booking ini?\")'>Hapus</a>
                        </td>
                    </tr>";
                }
            } else {
                echo "<tr><td colspan='8' class='text-center'>Belum ada data booking.</td></tr>";
            }
            ?>
        </tbody>
    </table>
</div>
<?php include "../../template/footer.php"; ?>