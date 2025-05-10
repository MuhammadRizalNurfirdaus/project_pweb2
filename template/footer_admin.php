<?php
// File: C:\xampp\htdocs\Cilengkrang-Web-Wisata\admin\template\footer_admin.php
?>
</div> <!-- Menutup .container-fluid dari header_admin.php -->
</main> <!-- Menutup .main-admin-content dari header_admin.php -->
</div> <!-- Menutup .admin-wrapper dari header_admin.php -->

<footer class="footer mt-auto py-3 bg-white border-top text-center shadow-sm">
    <div class="container">
        <span class="text-muted small">© <?= date('Y') ?> Cilengkrang Admin Panel. Hak Cipta Dilindungi.</span>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-C6RzsynM9kWDrMNeT87bh95OGNyZPhcTNXj1NW7RuBCsyN/o0jlpcV8Qyq46cDfL" crossorigin="anonymous"></script>
<script src="<?= $base_url ?>public/js/script.js"></script> <!-- General JS, mungkin berisi preloader atau back-to-top jika relevan -->
<!-- <script src="<?= $base_url ?>public/js/admin_script.js"></script> <!-- JS Khusus Admin -->
</body>

</html>