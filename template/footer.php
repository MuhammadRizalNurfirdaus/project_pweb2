<?php
// File: C:\xampp\htdocs\Cilengkrang-Web-Wisata\template\footer.php
?>
</div> <!-- Menutup .main-page-content dari header.php -->

<footer class="footer mt-auto py-5">
    <div class="container">
        <div class="row">
            <!-- ... (konten footer Anda yang sudah ada) ... -->
        </div>
        <hr class="my-4" style="border-color: rgba(255,255,255,0.2);">
        <p class="mb-0 small text-center text-white-50">© <?= date('Y') ?> Lembah Cilengkrang Wisata. Hak Cipta Dilindungi.</p>
    </div>
</footer>

<button class="back-to-top-btn" title="Kembali ke atas">
    <i class="fas fa-arrow-up"></i>
</button>

<!-- Tombol Pengalih Tema Mode Gelap/Terang -->
<button id="theme-toggle-btn" class="theme-toggle-btn" title="Ganti Tema">
    <i class="fas fa-moon"></i> <!-- Ikon awal (bulan untuk mode terang) -->
</button>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-C6RzsynM9kWDrMNeT87bh95OGNyZPhcTNXj1NW7RuBCsyN/o0jlpcV8Qyq46cDfL" crossorigin="anonymous"></script>
<script src="<?= $base_url ?>public/js/script.js"></script>
<?= isset($additional_js) ? $additional_js : '' ?>
</body>

</html>