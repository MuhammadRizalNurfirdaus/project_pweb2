<?php
include_once '../config/database.php';
include '../template/header_user.php';


$query = "SELECT * FROM artikel ORDER BY created_at DESC";
$result = mysqli_query($conn, $query);
?>
<img src="public/img/air_panas_polos.jpg" class="img-fluid rounded mb-3" alt="Air Panas">

<div class="container mt-5">
    <h2>Artikel Wisata</h2>
    <?php while ($row = mysqli_fetch_assoc($result)) : ?>
        <div class="card mb-3">
            <div class="card-body">
                <h4><?= $row['judul'] ?></h4>
                <p><?= nl2br($row['isi']) ?></p>
                <small>Ditulis pada: <?= $row['created_at'] ?></small>
            </div>
        </div>
    <?php endwhile; ?>
</div>

<?php include_once '../template/footer.php'; ?>