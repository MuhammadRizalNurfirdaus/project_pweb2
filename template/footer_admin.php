<?php
// File: C:\xampp\htdocs\Cilengkrang-Web-Wisata\template\footer_admin.php
// Diasumsikan file ini diinclude dari file-file di dalam folder admin/,
// seperti admin/dashboard.php yang sudah meng-include config.php sehingga $base_url tersedia.
?>
</div> <!-- Menutup .container-fluid dari main-admin-content (di header_admin.php) -->
</main> <!-- Menutup .main-admin-content (di header_admin.php) -->
</div> <!-- Menutup .admin-wrapper (di header_admin.php) -->

<footer class="footer mt-auto py-3 bg-light border-top text-center shadow-sm"
    style="background-color: var(--admin-card-header-bg, #f8f9fa) !important; 
                   border-top-color: var(--admin-border-color, #dee2e6) !important;
                   transition: background-color 0.3s ease, border-top-color 0.3s ease;">
    <div class="container">
        <span class="text-muted small" style="color: var(--admin-text-muted, #6c757d) !important; transition: color 0.3s ease;">
            © <?= date('Y') ?> Cilengkrang Admin Panel. Dibuat dengan <i class="fas fa-heart text-danger"></i> untuk pariwisata.
        </span>
    </div>
</footer>

<!-- Tombol Pengalih Tema untuk Admin Panel -->
<button id="theme-toggle-btn" class="theme-toggle-btn" title="Ganti Tema" style="z-index: 1051; left: 25px; bottom: 25px;">
    <i class="fas fa-moon"></i> <!-- Ikon awal, akan diubah oleh JS -->
</button>

<!-- Tombol Back to Top akan dibuat oleh JavaScript jika script.js dimuat -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-C6RzsynM9kWDrMNeT87bh95OGNyZPhcTNXj1NW7RuBCsyN/o0jlpcV8Qyq46cDfL" crossorigin="anonymous"></script>

<?php // Memuat script.js yang berisi logika tombol tema dan fungsi lainnya 
?>
<?php if (isset($base_url)): ?>
    <script src="<?= $base_url ?>public/js/script.js"></script>
<?php else: ?>
    <?php // Fallback path jika $base_url tidak terdefinisi 
    // Path ini dari template/footer_admin.php ke public/js/script.js adalah '../public/js/script.js'
    ?>
    <script src="../public/js/script.js"></script>
    <?php error_log("PERINGATAN: \$base_url tidak terdefinisi di template/footer_admin.php, menggunakan path relatif untuk script.js"); ?>
<?php endif; ?>

<?php
// Jika Anda memiliki file JavaScript khusus untuk panel admin:
// Anda bisa membuat file public/js/admin_script.js
// if (isset($base_url)):
//    echo '<script src="' . $base_url . 'public/js/admin_script.js"></script>';
// else:
//    echo '<script src="../public/js/admin_script.js"></script>';
// endif;
?>

<?php
// Untuk memuat script JS tambahan per halaman admin jika diperlukan
// Contoh: di admin/dashboard.php Anda bisa set $additional_js_admin = 'dashboard_charts.js';
if (isset($additional_js_admin) && !empty($additional_js_admin)) {
    if (is_array($additional_js_admin)) {
        foreach ($additional_js_admin as $js_file) {
            echo '<script src="' . (isset($base_url) ? $base_url . 'public/js/' : '../public/js/') . e(ltrim($js_file, '/')) . '"></script>';
        }
    } elseif (is_string($additional_js_admin)) {
        echo '<script src="' . (isset($base_url) ? $base_url . 'public/js/' : '../public/js/') . e(ltrim($additional_js_admin, '/')) . '"></script>';
    }
}
?>
</body>

</html>