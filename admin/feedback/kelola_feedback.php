<?php
// File: C:\xampp\htdocs\Cilengkrang-Web-Wisata\admin\feedback\kelola_feedback.php

if (!require_once __DIR__ . '/../../config/config.php') { /* ... error handling ... */
    exit("...");
}
try {
    require_admin();
} catch (Exception $e) { /* ... error handling ... */
    redirect(AUTH_URL . '/login.php');
    exit;
}
$pageTitle = "Kelola Feedback";
if (!include_once VIEWS_PATH . '/header_admin.php') { /* ... error handling ... */
    exit("...");
}

$feedbacks = [];
// TAMBAHKAN LOG DI SINI
error_log("kelola_feedback.php - SEBELUM pengecekan class_exists('FeedbackController').");

if (class_exists('FeedbackController') && method_exists('FeedbackController', 'getAllFeedbacksForAdmin')) {
    error_log("kelola_feedback.php - FeedbackController dan method getAllFeedbacksForAdmin DITEMUKAN. Memanggil method...");
    $feedbacks = FeedbackController::getAllFeedbacksForAdmin();
} else {
    set_flash_message('danger', 'Kesalahan sistem: Komponen Feedback tidak dapat dimuat.');
    error_log("Error di kelola_feedback.php: FeedbackController class " . (class_exists('FeedbackController') ? "ADA" : "TIDAK ADA") . ", method getAllFeedbacksForAdmin " . (method_exists('FeedbackController', 'getAllFeedbacksForAdmin') ? "ADA" : "TIDAK ADA"));
}
error_log("kelola_feedback.php - Data feedbacks yang diterima dari Controller (jumlah: " . count($feedbacks) . "): " . substr(print_r($feedbacks, true), 0, 500));
?>

<!-- Breadcrumb -->
<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?= e(ADMIN_URL) ?>/dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
        <li class="breadcrumb-item active" aria-current="page"><i class="fas fa-comments"></i> Kelola Feedback</li>
    </ol>
</nav>
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Kelola Feedback Pengguna</h1>
</div>
<?php display_flash_message(); ?>
<div class="card shadow-sm">
    <div class="card-header bg-light">
        <h5 class="mb-0"><i class="fas fa-list me-2"></i>Daftar Feedback</h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered table-hover table-striped align-middle" id="dataTableFeedback">
                <thead class="table-dark">
                    <tr>
                        <th scope="col" style="width: 5%;">ID</th>
                        <th scope="col" style="width: 15%;">Pengguna (ID)</th>
                        <th scope="col" style="width: 20%;">Artikel (ID)</th>
                        <th scope="col">Komentar</th>
                        <th scope="col" style="width: 5%;" class="text-center">Rating</th>
                        <th scope="col" style="width: 15%;">Tanggal</th>
                        <th scope="col" style="width: 10%;" class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($feedbacks)): ?>
                        <?php foreach ($feedbacks as $feedback): ?>
                            <tr>
                                <td><?= e($feedback['id']) ?></td>
                                <td>
                                    <?= e($feedback['user_nama']) // Dari Model 
                                    ?>
                                    <?php if (!empty($feedback['user_id'])): ?>
                                        <small class="text-muted">(ID: <?= e($feedback['user_id']) ?>)</small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?= e($feedback['artikel_judul'] ?? 'N/A') // Dari Model 
                                    ?>
                                    <?php if (!empty($feedback['artikel_id'])): ?>
                                        <small class="text-muted">(ID: <?= e($feedback['artikel_id']) ?>)</small>
                                    <?php endif; ?>
                                </td>
                                <td><?= nl2br(e($feedback['komentar'])) ?></td>
                                <td class="text-center"><?= e($feedback['rating']) ?> <i class="fas fa-star text-warning"></i></td>
                                <td><?= e(formatTanggalIndonesia($feedback['created_at'], true)) ?></td>
                                <td class="text-center">
                                    <form action="<?= e(ADMIN_URL) ?>/feedback/hapus_feedback.php" method="POST" class="d-inline">
                                        <?= generate_csrf_token_input() ?>
                                        <input type="hidden" name="feedback_id" value="<?= e($feedback['id']) ?>">
                                        <button type="submit" name="hapus_feedback_submit" class="btn btn-danger btn-sm" title="Hapus Feedback"
                                            onclick="return confirm('PERHATIAN: Anda yakin ingin menghapus feedback ini (ID: <?= e($feedback['id']) ?>)? Aksi ini tidak dapat diurungkan.');">
                                            <i class="fas fa-trash-alt"></i> Hapus
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="text-center py-4">
                                <p class="mb-0">Belum ada data feedback.</p>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php
if (!include_once VIEWS_PATH . '/footer_admin.php') { /* ... error handling ... */
}
?>