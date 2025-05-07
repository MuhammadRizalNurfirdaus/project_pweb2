<?php
include "../../config/Koneksi.php";
include "../../template/header.php";
?>

<div class="container mt-5">
    <h2>Kelola Data Booking</h2>
    <table class="table table-bordered mt-3">
        <thead class="table-dark">
            <tr>
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
            $result = mysqli_query($conn, "SELECT * FROM booking ORDER BY tanggal_kunjungan DESC");
            while ($row = mysqli_fetch_assoc($result)) {
                echo "<tr>
                    <td>{$row['nama']}</td>
                    <td>{$row['email']}</td>
                    <td>{$row['no_hp']}</td>
                    <td>{$row['jumlah_tiket']}</td>
                    <td>{$row['tanggal_kunjungan']}</td>
                    <td>{$row['catatan']}</td>
                    <td>
                        <a href='hapus_booking.php?id={$row['id']}' class='btn btn-danger btn-sm' onclick='return confirm(\"Yakin hapus data ini?\")'>Hapus</a>
                    </td>
                </tr>";
            }
            ?>
        </tbody>
    </table>
</div>

<?php include "../../template/footer.php"; ?>