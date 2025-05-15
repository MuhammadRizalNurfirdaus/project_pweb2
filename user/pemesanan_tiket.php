<?php
// File: C:\xampp\htdocs\Cilengkrang-Web-Wisata\user\pemesanan_tiket.php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once __DIR__ . '/../config/config.php';

if (!class_exists('PemesananTiketController')) {
    error_log("FATAL pemesanan_tiket.php: PemesananTiketController tidak ditemukan.");
    exit("Kesalahan sistem kritis. Komponen pemesanan tidak tersedia.");
}

if (!class_exists('JenisTiket')) error_log("PERINGATAN pemesanan_tiket.php: Model JenisTiket tidak ditemukan.");
if (!class_exists('SewaAlat')) error_log("PERINGATAN pemesanan_tiket.php: Model SewaAlat tidak ditemukan.");

require_login();

$tanggal_kunjungan_input = date('Y-m-d', strtotime('+1 day'));
$catatan_umum_input = '';
$items_tiket_input = [];
$items_sewa_input = [];

if (!is_post() && isset($_SESSION['form_data_pemesanan'])) {
    $tanggal_kunjungan_input = $_SESSION['form_data_pemesanan']['tanggal_kunjungan'] ?? $tanggal_kunjungan_input;
    $catatan_umum_input = $_SESSION['form_data_pemesanan']['catatan_umum_pemesanan'] ?? $catatan_umum_input;
    $items_tiket_input = $_SESSION['form_data_pemesanan']['items_tiket'] ?? [];
    $items_sewa_input = $_SESSION['form_data_pemesanan']['items_sewa'] ?? [];
    unset($_SESSION['form_data_pemesanan']);
}

if (is_post()) {
    $tanggal_kunjungan_input_post = input('tanggal_kunjungan');
    $catatan_umum_input_post = input('catatan_umum_pemesanan');

    $posted_items_tiket = $_POST['items_tiket'] ?? [];
    $items_tiket_to_process = [];
    foreach ($posted_items_tiket as $item_t) {
        if (!empty($item_t['jenis_tiket_id']) && isset($item_t['jumlah']) && (int)$item_t['jumlah'] > 0) {
            $items_tiket_to_process[] = [
                'jenis_tiket_id' => (int)$item_t['jenis_tiket_id'],
                'jumlah' => (int)$item_t['jumlah']
            ];
        }
    }

    $posted_items_sewa = $_POST['items_sewa'] ?? [];
    $items_sewa_to_process = [];
    foreach ($posted_items_sewa as $item_s) {
        if (!empty($item_s['sewa_alat_id']) && isset($item_s['jumlah_sewa']) && (int)$item_s['jumlah_sewa'] > 0 && !empty($item_s['tanggal_mulai_sewa']) && !empty($item_s['tanggal_akhir_sewa_rencana'])) {
            $items_sewa_to_process[] = [
                'sewa_alat_id' => (int)$item_s['sewa_alat_id'],
                'jumlah' => (int)$item_s['jumlah_sewa'],
                'tanggal_mulai_sewa' => $item_s['tanggal_mulai_sewa'],
                'tanggal_akhir_sewa_rencana' => $item_s['tanggal_akhir_sewa_rencana'],
                'catatan_item_sewa' => $item_s['catatan_item_sewa'] ?? null
            ];
        }
    }

    $_SESSION['form_data_pemesanan'] = [
        'tanggal_kunjungan' => $tanggal_kunjungan_input_post,
        'catatan_umum_pemesanan' => $catatan_umum_input_post,
        'items_tiket' => $items_tiket_to_process,
        'items_sewa' => $items_sewa_to_process
    ];

    if (empty($items_tiket_to_process)) {
        set_flash_message('danger', 'Anda harus memilih minimal satu jenis tiket dan jumlahnya.');
        redirect('user/pemesanan_tiket.php');
        exit;
    }

    $data_pemesan_info = [
        'user_id' => get_current_user_id(),
        'tanggal_kunjungan' => $tanggal_kunjungan_input_post,
        'catatan_umum_pemesanan' => $catatan_umum_input_post,
        'metode_pembayaran_pilihan' => 'Belum Dipilih'
    ];

    $kode_pemesanan_hasil = PemesananTiketController::prosesPemesananLengkap(
        $data_pemesan_info,
        $items_tiket_to_process,
        $items_sewa_to_process
    );

    if ($kode_pemesanan_hasil) {
        unset($_SESSION['form_data_pemesanan']);
        if (!isset($_SESSION['flash_message'])) {
            set_flash_message('success', 'Pemesanan Anda dengan kode ' . e($kode_pemesanan_hasil) . ' berhasil dibuat. Silakan lanjutkan ke pembayaran.');
        }
        redirect('user/detail_pemesanan.php?kode=' . urlencode($kode_pemesanan_hasil));
        exit;
    } else {
        if (!isset($_SESSION['flash_message'])) {
            set_flash_message('danger', 'Gagal memproses pemesanan. Periksa kembali input Anda atau kuota/stok mungkin tidak mencukupi.');
        }
        redirect('user/pemesanan_tiket.php');
        exit;
    }
}

$jenis_tiket_list = [];
$alat_sewa_list = [];
try {
    if (class_exists('JenisTiket')) {
        $jenis_tiket_list = JenisTiket::getAll();
    }
    if (class_exists('SewaAlat')) {
        $alat_sewa_list = SewaAlat::getAll();
    }
} catch (Exception $e) {
    error_log("Error saat mengambil data list tiket/alat di pemesanan_tiket.php: " . $e->getMessage());
    set_flash_message('danger', 'Tidak dapat memuat daftar pilihan tiket/alat saat ini. Coba lagi nanti.');
}

$page_title = "Form Pemesanan Tiket & Sewa Alat";
// Pastikan VIEWS_PATH sudah didefinisikan di config.php
$header_path = (defined('VIEWS_PATH') ? VIEWS_PATH : __DIR__ . '/../template') . '/header_user.php';
if (file_exists($header_path)) {
    include_once $header_path;
} else {
    error_log("FATAL pemesanan_tiket.php: File header_user.php tidak ditemukan di " . $header_path);
    exit("Kesalahan tampilan: Komponen header tidak ditemukan.");
}
?>

<div class="main-page-content">
    <div class="container section-padding">
        <div class="row justify-content-center">
            <div class="col-lg-10 col-xl-9">
                <div class="card shadow-lg">
                    <div class="card-header bg-success text-white">
                        <h2 class="mb-0 text-center section-title" style="color:white; margin-bottom:0; margin-top:0;">Form Pemesanan Wisata</h2>
                    </div>
                    <div class="card-body p-4 p-md-5">
                        <?= display_flash_message(); ?>
                        <p class="text-muted mb-4">Pemesanan atas nama: <strong><?= e(get_current_user_name()) ?></strong> (ID: <?= e(get_current_user_id()) ?>).</p>

                        <form action="<?= e(USER_URL . 'pemesanan_tiket.php') ?>" method="POST" class="needs-validation" novalidate>

                            <h4 class="mb-3 mt-4 pt-2 border-top">Informasi Kunjungan</h4>
                            <div class="row g-3 mb-4">
                                <div class="col-md-12">
                                    <label for="tanggal_kunjungan" class="form-label fs-5">Tanggal Kunjungan</label>
                                    <input type="date" class="form-control form-control-lg" id="tanggal_kunjungan" name="tanggal_kunjungan"
                                        value="<?= e($tanggal_kunjungan_input) ?>"
                                        min="<?= date('Y-m-d') ?>" required>
                                    <div class="invalid-feedback">Silakan pilih tanggal kunjungan yang valid.</div>
                                </div>
                            </div>

                            <h4 class="mb-3 mt-4 pt-3 border-top">Pilihan Tiket</h4>
                            <div id="items-tiket-container">
                                <?php
                                $tiket_item_count = max(1, count($items_tiket_input));
                                for ($i = 0; $i < $tiket_item_count; $i++):
                                    $current_item_tiket = $items_tiket_input[$i] ?? ['jenis_tiket_id' => '', 'jumlah' => 1];
                                ?>
                                    <div class="row g-3 align-items-start mb-3 item-tiket">
                                        <div class="col-md-6">
                                            <label for="jenis_tiket_id_<?= $i ?>" class="form-label">Jenis Tiket</label>
                                            <select class="form-select" name="items_tiket[<?= $i ?>][jenis_tiket_id]" id="jenis_tiket_id_<?= $i ?>" required>
                                                <option value="" disabled <?= empty($current_item_tiket['jenis_tiket_id']) ? 'selected' : '' ?>>-- Pilih Jenis Tiket --</option>
                                                <?php if (!empty($jenis_tiket_list)): ?>
                                                    <?php foreach ($jenis_tiket_list as $jt) : ?>
                                                        <?php if ($jt['aktif']): ?>
                                                            <option value="<?= e($jt['id']) ?>" data-harga="<?= e($jt['harga']) ?>" <?= ((string)$current_item_tiket['jenis_tiket_id'] === (string)$jt['id']) ? 'selected' : '' ?>>
                                                                <?= e($jt['nama_layanan_display']) ?> (<?= e($jt['tipe_hari']) ?>) - Rp <?= e(number_format($jt['harga'], 0, ',', '.')) ?>
                                                            </option>
                                                        <?php endif; ?>
                                                    <?php endforeach; ?>
                                                <?php endif; ?>
                                            </select>
                                            <div class="invalid-feedback">Jenis tiket wajib dipilih.</div>
                                        </div>
                                        <div class="col-md-4">
                                            <label for="jumlah_tiket_<?= $i ?>" class="form-label">Jumlah</label>
                                            <input type="number" class="form-control" name="items_tiket[<?= $i ?>][jumlah]" id="jumlah_tiket_<?= $i ?>" min="1" value="<?= e($current_item_tiket['jumlah'] ?: 1) ?>" required>
                                            <div class="invalid-feedback" id="invalid-feedback-jumlah_tiket_<?= $i ?>">Jumlah minimal 1.</div>
                                            <div class="form-text mt-1 feedback-ajax" id="feedback-kuota-jumlah_tiket_<?= $i ?>"></div>
                                        </div>
                                        <div class="col-md-2 align-self-end">
                                            <?php if ($i > 0): ?>
                                                <button type="button" class="btn btn-danger btn-sm w-100 remove-item-tiket mt-1">Hapus</button>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endfor; ?>
                            </div>
                            <button type="button" id="add-item-tiket" class="btn btn-outline-success btn-sm mb-4"><i class="fas fa-plus"></i> Tambah Jenis Tiket Lain</button>

                            <hr class="my-4">

                            <h4 class="mb-3 mt-2">Sewa Alat (Opsional)</h4>
                            <div id="items-sewa-container">
                                <?php
                                $sewa_item_count = count($items_sewa_input);
                                for ($j = 0; $j < $sewa_item_count; $j++):
                                    $current_item_sewa = $items_sewa_input[$j] ?? ['sewa_alat_id' => '', 'jumlah' => 1, 'tanggal_mulai_sewa' => '', 'tanggal_akhir_sewa_rencana' => '', 'catatan_item_sewa' => ''];
                                ?>
                                    <div class="card mb-3 item-sewa p-3">
                                        <div class="row g-3 align-items-start">
                                            <div class="col-12 text-end">
                                                <button type="button" class="btn-close remove-item-sewa" aria-label="Close"></button>
                                            </div>
                                            <div class="col-md-6">
                                                <label for="sewa_alat_id_<?= $j ?>" class="form-label">Alat Sewa</label>
                                                <select class="form-select" name="items_sewa[<?= $j ?>][sewa_alat_id]" id="sewa_alat_id_<?= $j ?>">
                                                    <option value="" disabled <?= empty($current_item_sewa['sewa_alat_id']) ? 'selected' : '' ?>>-- Pilih Alat --</option>
                                                    <?php if (!empty($alat_sewa_list)): ?>
                                                        <?php foreach ($alat_sewa_list as $as) : ?>
                                                            <option value="<?= e($as['id']) ?>" <?= ((string)$current_item_sewa['sewa_alat_id'] === (string)$as['id']) ? 'selected' : '' ?>>
                                                                <?= e($as['nama_item']) ?> (Stok: <?= e($as['stok_tersedia']) ?>) - Rp <?= e(number_format($as['harga_sewa'], 0, ',', '.')) ?> /<?= e($as['durasi_harga_sewa']) ?> <?= e($as['satuan_durasi_harga']) ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    <?php endif; ?>
                                                </select>
                                            </div>
                                            <div class="col-md-3">
                                                <label for="jumlah_sewa_<?= $j ?>" class="form-label">Jumlah</label>
                                                <input type="number" class="form-control" name="items_sewa[<?= $j ?>][jumlah_sewa]" id="jumlah_sewa_<?= $j ?>" min="1" value="<?= e($current_item_sewa['jumlah'] ?: 1) ?>">
                                                <div class="form-text mt-1 feedback-ajax" id="feedback-stok-jumlah_sewa_<?= $j ?>"></div>
                                            </div>
                                            <div class="col-md-6 mt-2">
                                                <label for="tanggal_mulai_sewa_<?= $j ?>" class="form-label">Tgl & Jam Mulai Sewa</label>
                                                <input type="datetime-local" class="form-control" name="items_sewa[<?= $j ?>][tanggal_mulai_sewa]" id="tanggal_mulai_sewa_<?= $j ?>" value="<?= e($current_item_sewa['tanggal_mulai_sewa']) ?>">
                                            </div>
                                            <div class="col-md-6 mt-2">
                                                <label for="tanggal_akhir_sewa_rencana_<?= $j ?>" class="form-label">Tgl & Jam Akhir Sewa</label>
                                                <input type="datetime-local" class="form-control" name="items_sewa[<?= $j ?>][tanggal_akhir_sewa_rencana]" id="tanggal_akhir_sewa_rencana_<?= $j ?>" value="<?= e($current_item_sewa['tanggal_akhir_sewa_rencana']) ?>">
                                            </div>
                                            <div class="col-12 mt-2">
                                                <label for="catatan_item_sewa_<?= $j ?>" class="form-label">Catatan Alat</label>
                                                <textarea class="form-control" name="items_sewa[<?= $j ?>][catatan_item_sewa]" id="catatan_item_sewa_<?= $j ?>" rows="2"><?= e($current_item_sewa['catatan_item_sewa']) ?></textarea>
                                            </div>
                                        </div>
                                    </div>
                                <?php endfor; ?>
                            </div>
                            <button type="button" id="add-item-sewa" class="btn btn-outline-primary btn-sm mb-4"><i class="fas fa-plus"></i> Tambah Alat Sewa</button>

                            <hr class="my-4">

                            <div class="mb-4">
                                <label for="catatan_umum_pemesanan" class="form-label fs-5">Catatan Umum Pemesanan <span class="text-muted small">(Opsional)</span></label>
                                <textarea class="form-control" id="catatan_umum_pemesanan" name="catatan_umum_pemesanan" rows="3" placeholder="Catatan umum untuk seluruh pemesanan ini..."><?= e($catatan_umum_input) ?></textarea>
                            </div>

                            <div class="d-grid mt-5">
                                <button type="submit" class="btn btn-success btn-lg py-3">
                                    <i class="fas fa-paper-plane me-2"></i>Kirim Pemesanan
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Pastikan VIEWS_PATH sudah didefinisikan di config.php
// DAN pastikan file footer_user.php benar-benar ada di folder template
$footer_path = (defined('VIEWS_PATH') ? VIEWS_PATH : __DIR__ . '/../template') . '/footer.php';
if (file_exists($footer_path)) {
    include_once $footer_path;
} else {
    // Fallback ke footer.php jika footer_user.php tidak ada
    $fallback_footer_path = (defined('VIEWS_PATH') ? VIEWS_PATH : __DIR__ . '/../template') . '/footer.php';
    if (file_exists($fallback_footer_path)) {
        error_log("PERINGATAN pemesanan_tiket.php: File footer_user.php tidak ditemukan, menggunakan footer.php sebagai fallback.");
        include_once $fallback_footer_path;
    } else {
        error_log("FATAL pemesanan_tiket.php: File footer_user.php dan footer.php tidak ditemukan di " . $footer_path . " atau " . $fallback_footer_path);
        // echo "<p style='text-align:center;color:red;'>Error: Komponen footer tidak ditemukan.</p>";
    }
}
?>

<script>
    // --- Kode JavaScript dari respons sebelumnya ---
    // (Salin dan tempel kode JavaScript yang sudah stabil di sini)
    document.addEventListener('DOMContentLoaded', function() {
        const tiketContainer = document.getElementById('items-tiket-container');
        const addTiketButton = document.getElementById('add-item-tiket');
        let tiketIndex = <?= $tiket_item_count ?>;

        const sewaContainer = document.getElementById('items-sewa-container');
        const addSewaButton = document.getElementById('add-item-sewa');
        let sewaIndex = <?= $sewa_item_count ?>;

        const tanggalKunjunganInput = document.getElementById('tanggal_kunjungan');
        const formPemesanan = document.querySelector('form.needs-validation');

        const ajaxHandlerUrl = "<?= defined('BASE_URL') ? rtrim(BASE_URL, '/') . '/public/stok_form_handler.php' : '../public/stok_form_handler.php' ?>";

        function createFeedbackElement(siblingElement, idSuffix) {
            const feedbackElementId = `feedback-ajax-${idSuffix}`;
            let feedbackElement = document.getElementById(feedbackElementId);
            if (!feedbackElement) {
                feedbackElement = document.createElement('div');
                feedbackElement.id = feedbackElementId;
                feedbackElement.classList.add('form-text', 'mt-1', 'feedback-ajax');
                if (siblingElement.nextElementSibling && siblingElement.nextElementSibling.classList.contains('invalid-feedback')) {
                    siblingElement.nextElementSibling.after(feedbackElement);
                } else {
                    siblingElement.after(feedbackElement);
                }
            }
            return feedbackElement;
        }


        async function cekKuotaTiket(jenisTiketSelect, jumlahInput, tanggalKunjungan) {
            const jenisTiketId = jenisTiketSelect.value;
            const jumlahDiminta = parseInt(jumlahInput.value);
            const feedbackElement = jumlahInput.closest('.item-tiket').querySelector('.feedback-ajax');


            if (!feedbackElement) {
                console.error('Feedback element tiket tidak ditemukan untuk:', jumlahInput.id);
                return;
            }
            feedbackElement.textContent = '';
            feedbackElement.className = 'form-text mt-1 feedback-ajax';
            jumlahInput.setCustomValidity('');

            if (!jenisTiketId || !tanggalKunjungan || isNaN(jumlahDiminta) || jumlahDiminta <= 0) return;

            try {
                const formData = new FormData();
                formData.append('action', 'cek_kuota_tiket');
                formData.append('jenis_tiket_id', jenisTiketId);
                formData.append('tanggal_kunjungan', tanggalKunjungan);
                formData.append('jumlah_diminta', jumlahDiminta);

                feedbackElement.textContent = 'Mengecek kuota...';
                feedbackElement.classList.add('text-muted');

                const response = await fetch(ajaxHandlerUrl, {
                    method: 'POST',
                    body: formData
                });
                if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
                const data = await response.json();

                feedbackElement.classList.remove('text-muted', 'text-success', 'text-danger');
                if (data.success) {
                    feedbackElement.textContent = `Kuota tersedia (sisa: ${data.available}).`;
                    feedbackElement.classList.add('text-success');
                } else {
                    feedbackElement.textContent = data.message || 'Kuota tidak mencukupi.';
                    feedbackElement.classList.add('text-danger');
                    jumlahInput.setCustomValidity(data.message || 'Kuota tidak mencukupi.');
                }
            } catch (error) {
                console.error('Error cek kuota tiket:', error);
                feedbackElement.classList.remove('text-muted', 'text-success');
                feedbackElement.textContent = 'Gagal mengecek kuota. Coba lagi.';
                feedbackElement.classList.add('text-danger');
            }
        }

        async function cekStokAlat(alatSewaSelect, jumlahInput) {
            const sewaAlatId = alatSewaSelect.value;
            const jumlahDiminta = parseInt(jumlahInput.value);
            const feedbackElement = jumlahInput.closest('.item-sewa').querySelector('.feedback-ajax');

            if (!feedbackElement) {
                console.error('Feedback element sewa tidak ditemukan untuk:', jumlahInput.id);
                return;
            }
            feedbackElement.textContent = '';
            feedbackElement.className = 'form-text mt-1 feedback-ajax';
            jumlahInput.setCustomValidity('');

            if (!sewaAlatId || isNaN(jumlahDiminta) || jumlahDiminta <= 0) return;

            try {
                const formData = new FormData();
                formData.append('action', 'cek_stok_alat');
                formData.append('sewa_alat_id', sewaAlatId);
                formData.append('jumlah_diminta', jumlahDiminta);

                feedbackElement.textContent = 'Mengecek stok...';
                feedbackElement.classList.add('text-muted');

                const response = await fetch(ajaxHandlerUrl, {
                    method: 'POST',
                    body: formData
                });
                if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
                const data = await response.json();

                feedbackElement.classList.remove('text-muted', 'text-success', 'text-danger');
                if (data.success) {
                    feedbackElement.textContent = `Stok tersedia (sisa: ${data.available}).`;
                    feedbackElement.classList.add('text-success');
                } else {
                    feedbackElement.textContent = data.message || 'Stok tidak mencukupi.';
                    feedbackElement.classList.add('text-danger');
                    jumlahInput.setCustomValidity(data.message || 'Stok tidak mencukupi.');
                }
            } catch (error) {
                console.error('Error cek stok alat:', error);
                feedbackElement.classList.remove('text-muted', 'text-success');
                feedbackElement.textContent = 'Gagal mengecek stok. Coba lagi.';
                feedbackElement.classList.add('text-danger');
            }
        }

        function addEventListenersToTiketItem(itemTiketDiv) {
            const jenisTiketSelect = itemTiketDiv.querySelector('select[name^="items_tiket["]');
            const jumlahInput = itemTiketDiv.querySelector('input[name^="items_tiket["][name$="][jumlah]"]');
            if (jenisTiketSelect && jumlahInput) {
                createFeedbackElement(jumlahInput, `kuota-${jumlahInput.id}`);

                jenisTiketSelect.addEventListener('change', function() {
                    cekKuotaTiket(this, jumlahInput, tanggalKunjunganInput.value);
                });
                jumlahInput.addEventListener('input', function() {
                    cekKuotaTiket(jenisTiketSelect, this, tanggalKunjunganInput.value);
                });
                if (jenisTiketSelect.value && jumlahInput.value > 0 && tanggalKunjunganInput.value) {
                    cekKuotaTiket(jenisTiketSelect, jumlahInput, tanggalKunjunganInput.value);
                }
            }
        }

        function addEventListenersToSewaItem(itemSewaDiv) {
            const alatSewaSelect = itemSewaDiv.querySelector('select[name^="items_sewa["]');
            const jumlahInput = itemSewaDiv.querySelector('input[name^="items_sewa["][name$="][jumlah_sewa]"]');
            if (alatSewaSelect && jumlahInput) {
                createFeedbackElement(jumlahInput, `stok-${jumlahInput.id}`);

                alatSewaSelect.addEventListener('change', function() {
                    cekStokAlat(this, jumlahInput);
                });
                jumlahInput.addEventListener('input', function() {
                    cekStokAlat(alatSewaSelect, this);
                });
                if (alatSewaSelect.value && jumlahInput.value > 0) {
                    cekStokAlat(alatSewaSelect, jumlahInput);
                }
            }
        }

        if (addTiketButton && tiketContainer) {
            addTiketButton.addEventListener('click', function() {
                const newHtml = `
                <div class="row g-3 align-items-start mb-3 item-tiket">
                    <div class="col-md-6">
                        <label for="jenis_tiket_id_${tiketIndex}" class="form-label">Jenis Tiket</label>
                        <select class="form-select" name="items_tiket[${tiketIndex}][jenis_tiket_id]" id="jenis_tiket_id_${tiketIndex}" required>
                            <option value="" disabled selected>-- Pilih Jenis Tiket --</option>
                            <?php if (!empty($jenis_tiket_list)) {
                                foreach ($jenis_tiket_list as $jt) : ?>
                                <?php if ($jt['aktif']): ?>
                                <option value="<?= e($jt['id']) ?>" data-harga="<?= e($jt['harga']) ?>">
                                    <?= e($jt['nama_layanan_display']) ?> (<?= e($jt['tipe_hari']) ?>) - Rp <?= e(number_format($jt['harga'], 0, ',', '.')) ?>
                                </option>
                                <?php endif; ?>
                            <?php endforeach;
                            } ?>
                        </select>
                        <div class="invalid-feedback">Jenis tiket wajib dipilih.</div>
                    </div>
                    <div class="col-md-4">
                        <label for="jumlah_tiket_${tiketIndex}" class="form-label">Jumlah</label>
                        <input type="number" class="form-control" name="items_tiket[${tiketIndex}][jumlah]" id="jumlah_tiket_${tiketIndex}" min="1" value="1" required>
                        <div class="invalid-feedback" id="invalid-feedback-jumlah_tiket_${tiketIndex}">Jumlah minimal 1.</div>
                        <div class="form-text mt-1 feedback-ajax" id="feedback-kuota-jumlah_tiket_${tiketIndex}"></div>
                    </div>
                    <div class="col-md-2 align-self-end">
                        <button type="button" class="btn btn-danger btn-sm w-100 remove-item-tiket mt-1">Hapus</button>
                    </div>
                </div>`;
                tiketContainer.insertAdjacentHTML('beforeend', newHtml);
                const newItemDiv = tiketContainer.lastElementChild;
                if (newItemDiv) addEventListenersToTiketItem(newItemDiv);
                tiketIndex++;
            });
            tiketContainer.addEventListener('click', function(e) {
                if (e.target && e.target.classList.contains('remove-item-tiket')) {
                    e.target.closest('.item-tiket').remove();
                }
            });
        }

        if (addSewaButton && sewaContainer) {
            addSewaButton.addEventListener('click', function() {
                const tanggalKunjunganValue = tanggalKunjunganInput.value;
                let baseDate;
                if (tanggalKunjunganValue) {
                    baseDate = new Date(tanggalKunjunganValue + 'T00:00:00Z');
                    if (isNaN(baseDate.getTime())) baseDate = new Date();
                } else {
                    baseDate = new Date();
                }
                const formatDateTimeLocal = (dateObj, hours, minutes) => {
                    const tempDate = new Date(dateObj);
                    tempDate.setHours(hours, minutes, 0, 0);
                    const year = tempDate.getFullYear();
                    const month = (tempDate.getMonth() + 1).toString().padStart(2, '0');
                    const day = tempDate.getDate().toString().padStart(2, '0');
                    const hour = tempDate.getHours().toString().padStart(2, '0');
                    const minute = tempDate.getMinutes().toString().padStart(2, '0');
                    return `${year}-${month}-${day}T${hour}:${minute}`;
                };

                let defaultMulaiSewa = formatDateTimeLocal(new Date(baseDate), 14, 0);
                const defaultAkhirDate = new Date(baseDate);
                defaultAkhirDate.setDate(baseDate.getDate() + 1);
                let defaultAkhirSewa = formatDateTimeLocal(defaultAkhirDate, 12, 0);

                const newHtml = `
                <div class="card mb-3 item-sewa p-3">
                    <div class="row g-3 align-items-start">
                        <div class="col-12 text-end">
                             <button type="button" class="btn-close remove-item-sewa" aria-label="Close"></button>
                        </div>
                        <div class="col-md-6">
                            <label for="sewa_alat_id_${sewaIndex}" class="form-label">Alat Sewa</label>
                            <select class="form-select" name="items_sewa[${sewaIndex}][sewa_alat_id]" id="sewa_alat_id_${sewaIndex}">
                                <option value="" disabled selected>-- Pilih Alat --</option>
                                <?php if (!empty($alat_sewa_list)) {
                                    foreach ($alat_sewa_list as $as) : ?>
                                    <option value="<?= e($as['id']) ?>">
                                        <?= e($as['nama_item']) ?> (Stok: <?= e($as['stok_tersedia']) ?>) - Rp <?= e(number_format($as['harga_sewa'], 0, ',', '.')) ?> /<?= e($as['durasi_harga_sewa']) ?> <?= e($as['satuan_durasi_harga']) ?>
                                    </option>
                                <?php endforeach;
                                } ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="jumlah_sewa_${sewaIndex}" class="form-label">Jumlah</label>
                            <input type="number" class="form-control" name="items_sewa[${sewaIndex}][jumlah_sewa]" id="jumlah_sewa_${sewaIndex}" min="1" value="1">
                             <div class="form-text mt-1 feedback-ajax" id="feedback-stok-jumlah_sewa_${sewaIndex}"></div>
                        </div>
                        <div class="col-md-6 mt-2">
                            <label for="tanggal_mulai_sewa_${sewaIndex}" class="form-label">Tgl & Jam Mulai Sewa</label>
                            <input type="datetime-local" class="form-control" name="items_sewa[${sewaIndex}][tanggal_mulai_sewa]" id="tanggal_mulai_sewa_${sewaIndex}" value="${defaultMulaiSewa}">
                        </div>
                        <div class="col-md-6 mt-2">
                            <label for="tanggal_akhir_sewa_rencana_${sewaIndex}" class="form-label">Tgl & Jam Akhir Sewa</label>
                            <input type="datetime-local" class="form-control" name="items_sewa[${sewaIndex}][tanggal_akhir_sewa_rencana]" id="tanggal_akhir_sewa_rencana_${sewaIndex}" value="${defaultAkhirSewa}">
                        </div>
                        <div class="col-12 mt-2">
                            <label for="catatan_item_sewa_${sewaIndex}" class="form-label">Catatan Alat</label>
                            <textarea class="form-control" name="items_sewa[${sewaIndex}][catatan_item_sewa]" id="catatan_item_sewa_${sewaIndex}" rows="2"></textarea>
                        </div>
                    </div>
                </div>`;
                sewaContainer.insertAdjacentHTML('beforeend', newHtml);
                const newItemDiv = sewaContainer.lastElementChild;
                if (newItemDiv) addEventListenersToSewaItem(newItemDiv);
                sewaIndex++;
            });
            sewaContainer.addEventListener('click', function(e) {
                if (e.target && e.target.classList.contains('remove-item-sewa')) {
                    e.target.closest('.item-sewa').remove();
                }
            });
        }

        if (tanggalKunjunganInput) {
            tanggalKunjunganInput.addEventListener('change', function() {
                document.querySelectorAll('.item-tiket').forEach(itemTiketDiv => {
                    const jenisTiketSelect = itemTiketDiv.querySelector('select[name^="items_tiket["]');
                    const jumlahInput = itemTiketDiv.querySelector('input[name^="items_tiket["][name$="][jumlah]"]');
                    if (jenisTiketSelect && jenisTiketSelect.value && jumlahInput && jumlahInput.value > 0) {
                        cekKuotaTiket(jenisTiketSelect, jumlahInput, this.value);
                    }
                });
            });
        }

        document.querySelectorAll('.item-tiket').forEach(addEventListenersToTiketItem);
        document.querySelectorAll('.item-sewa').forEach(addEventListenersToSewaItem);

        (function() {
            'use strict'
            var forms = document.querySelectorAll('.needs-validation')
            Array.prototype.slice.call(forms)
                .forEach(function(form) {
                    form.addEventListener('submit', function(event) {
                        let formIsValid = true;

                        const existingManualAlert = form.querySelector('.alert.alert-danger.manual-alert-validation');
                        if (existingManualAlert) existingManualAlert.remove();

                        document.querySelectorAll('.item-tiket input[name$="[jumlah]"], .item-sewa input[name$="[jumlah_sewa]"]').forEach(input => {
                            if (input.validationMessage !== '') {
                                formIsValid = false;
                            }
                        });

                        if (!form.checkValidity()) {
                            formIsValid = false;
                        }

                        if (!formIsValid) {
                            event.preventDefault();
                            event.stopPropagation();
                            let generalErrorDiv = document.createElement('div');
                            generalErrorDiv.className = 'alert alert-danger manual-alert-validation mt-3';
                            generalErrorDiv.setAttribute('role', 'alert');
                            generalErrorDiv.textContent = 'Periksa kembali form Anda. Ada input yang tidak valid atau kuota/stok tidak mencukupi.';

                            const submitButton = form.querySelector('button[type="submit"]');
                            if (submitButton && submitButton.parentNode) {
                                submitButton.parentNode.before(generalErrorDiv);
                            } else {
                                form.prepend(generalErrorDiv);
                            }
                        }
                        form.classList.add('was-validated');
                    }, false)
                })
        })()
    });
</script>