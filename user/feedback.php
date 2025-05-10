<?php
// File: C:\xampp\htdocs\Cilengkrang-Web-Wisata\user\feedback.php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../models/Feedback.php'; // Atau FeedbackController

require_login(); // User harus login untuk memberi feedback

// Variabel untuk pre-fill
$komentar_input = '';
$rating_input = 0; // Default rating
// Nama dan email akan diambil dari user yang login

if (is_post()) {
    $komentar_input = input('komentar');
    $rating_input = (int)input('rating');
    // $artikel_id_input = input('artikel_id'); // Jika feedback terkait artikel tertentu

    if (empty($komentar_input) || $rating_input < 1 || $rating_input > 5) {
        set_flash_message('danger', 'Rating (1-5) dan komentar wajib diisi.');
    } else {
        $data_feedback = [
            'user_id' => get_current_user_id(),
            // 'artikel_id' => $artikel_id_input, // Jika ada
            'komentar' => $komentar_input,
            'rating' => $rating_input
        ];

        if (Feedback::create($data_feedback)) { // Memanggil method static dari model Feedback
            set_flash_message('success', 'Terima kasih atas feedback Anda!');
            // Reset form fields
            $komentar_input = '';
            $rating_input = 0;
            // Tidak redirect agar pesan sukses terlihat
        } else {
            set_flash_message('danger', 'Maaf, gagal mengirim feedback. Silakan coba lagi.');
        }
    }
}

include_once __DIR__ . '/../template/header_user.php';
?>

<div class="main-page-content">
    <div class="container section-padding">
        <div class="row justify-content-center">
            <div class="col-lg-7">
                <div class="text-center mb-5">
                    <h1 class="section-title" style="margin-top:0;">Beri Kami Feedback</h1>
                    <p class="section-subtitle">Kami sangat menghargai masukan Anda untuk meningkatkan kualitas layanan dan destinasi wisata Cilengkrang.</p>
                </div>

                <?= display_flash_message(); ?>

                <div class="card shadow-lg">
                    <div class="card-body p-4 p-md-5">
                        <form action="<?= $base_url ?>user/feedback.php" method="POST" class="needs-validation" novalidate>
                            <p class="mb-3">Feedback dari: <strong><?= e(get_current_user_name()) ?></strong> (<?= e($_SESSION['user_email']) ?>)</p>

                            <div class="mb-4">
                                <label class="form-label fs-5">Rating Anda (1 Bintang Terendah, 5 Bintang Tertinggi):</label>
                                <div class="rating-stars text-center">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <input type="radio" id="rating-<?= $i ?>" name="rating" value="<?= $i ?>" <?= ($rating_input == $i) ? 'checked' : '' ?> required class="d-none">
                                        <label for="rating-<?= $i ?>" title="<?= $i ?> bintang"><i class="fas fa-star"></i></label>
                                    <?php endfor; ?>
                                </div>
                                <div class="invalid-feedback d-block text-center">Silakan pilih rating.</div> <!-- Selalu tampilkan jika validasi sisi klien diperlukan -->
                            </div>

                            <!-- Jika feedback terkait artikel tertentu (opsional) -->
                            <!-- <div class="mb-3">
                                <label for="artikel_id" class="form-label">Artikel Terkait (Opsional)</label>
                                <select class="form-select" id="artikel_id" name="artikel_id">
                                    <option value="">-- Tidak terkait artikel tertentu --</option>
                                    <?php
                                    // require_once __DIR__ . '/../models/Artikel.php';
                                    // $artikel_model_temp = new Artikel($conn);
                                    // $all_articles = $artikel_model_temp->getAll();
                                    // if ($all_articles) {
                                    //     foreach ($all_articles as $art) {
                                    //         echo "<option value=\"".e($art['id'])."\">".e($art['judul'])."</option>";
                                    //     }
                                    // }
                                    ?>
                                </select>
                            </div> -->

                            <div class="mb-4">
                                <label for="komentar" class="form-label fs-5">Komentar atau Masukan Anda:</label>
                                <textarea class="form-control form-control-lg" id="komentar" name="komentar" rows="6" placeholder="Tuliskan pengalaman atau saran Anda di sini..." required><?= e($komentar_input) ?></textarea>
                                <div class="invalid-feedback">Komentar wajib diisi.</div>
                            </div>

                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary btn-lg py-3">
                                    <i class="fas fa-paper-plane me-2"></i>Kirim Feedback
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<style>
    /* Style untuk Rating Bintang */
    .rating-stars label {
        font-size: 2.5rem;
        /* Ukuran bintang */
        color: #ddd;
        /* Warna bintang default (abu-abu) */
        cursor: pointer;
        transition: color 0.2s ease-in-out;
        padding: 0 0.2em;
    }

    .rating-stars input:checked~label,
    /* Bintang yang dipilih dan setelahnya */
    .rating-stars label:hover,
    /* Bintang saat di-hover */
    .rating-stars label:hover~label {
        /* Bintang setelah yang di-hover */
        color: var(--secondary-color, #FFB300);
        /* Warna bintang saat dipilih/hover */
    }

    /* Balik urutan bintang agar efek hover dari kanan ke kiri */
    .rating-stars {
        direction: rtl;
        display: inline-block;
        /* Agar bisa di text-center */
    }

    .rating-stars label:hover~label {
        color: #ddd;
        /* Kembalikan warna bintang setelah yang di-hover ke default */
    }

    .rating-stars input:checked+label~label {
        /* Bintang setelah yang dipilih tetap default */
        color: #ddd;
    }
</style>

<?php
include_once __DIR__ . '/../template/footer.php';
?>
<script>
    // Script validasi Bootstrap dasar
    (function() {
        'use strict'
        var forms = document.querySelectorAll('.needs-validation')
        Array.prototype.slice.call(forms)
            .forEach(function(form) {
                form.addEventListener('submit', function(event) {
                    // Cek rating bintang secara manual karena radio button required tidak selalu intuitif
                    const ratingChecked = form.querySelector('input[name="rating"]:checked');
                    if (!ratingChecked) {
                        // Bisa tambahkan pesan error spesifik untuk rating di sini jika mau
                        // alert('Silakan pilih rating bintang.');
                    }
                    if (!form.checkValidity() || !ratingChecked) {
                        event.preventDefault()
                        event.stopPropagation()
                    }
                    form.classList.add('was-validated')
                }, false)
            })
    })()
</script>