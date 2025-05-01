<?php
$title = 'Home';
require_once __DIR__ . '/template/header.php';
?>
<h2>Selamat datang di Cilengkrang Wisata</h2>
<p>Eksplorasi destinasi & booking online.</p>
<ul>
    <li><a href="<?php echo BASE_URL; ?>wisata/list.php">Daftar Wisata</a></li>
    <li><a href="<?php echo BASE_URL; ?>galeri.php">Galeri Foto</a></li>
</ul>
<?php require_once __DIR__ . '/template/footer.php'; ?>