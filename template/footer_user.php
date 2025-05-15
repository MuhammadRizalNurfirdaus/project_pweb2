<?php
// template/footer_user.php
?>
<footer class="footer mt-auto py-3 bg-light">
    <div class="container text-center">
        <span class="text-muted">© <?= date('Y') ?> <?= e(NAMA_SITUS ?? 'Nama Situs Anda') ?>. Semua Hak Dilindungi.</span>
    </div>
</footer>
</div> <!-- Penutup untuk wrapper konten utama jika ada di header_user.php -->

<!-- Bootstrap Bundle with Popper -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-kenU1KFdBIe4zVF0s0G1M5b4hcpxyD9F7jL+jjXkk+Q2h455rYXK/7HAuoJl+0I4" crossorigin="anonymous"></script>

<!-- Script kustom Anda (jika ada) -->
<?php if (defined('ASSETS_URL') && file_exists(PUBLIC_PATH . '/js/script.js')): ?>
    <script src="<?= e(ASSETS_URL) ?>js/script.js?v=<?= time() // Cache busting 
                                                    ?>"></script>
<?php endif; ?>

</body>

</html>