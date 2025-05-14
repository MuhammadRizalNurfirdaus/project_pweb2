<?php
require_once __DIR__ . '/../config/config.php';
// Controller utama yang akan menangani proses gabungan
require_once __DIR__ . '/../controllers/PemesananTiketController.php';
// Model yang dibutuhkan untuk mengisi dropdown di form
require_once __DIR__ . '/../models/JenisTiket.php';
require_once __DIR__ . '/../models/SewaAlat.php';
require_once __DIR__ . '/../models/Wisata.php'; // Jika ingin ada filter berdasarkan wisata

require_login();

// --- Data Awal untuk Form ---
$tanggal_kunjungan_input = date('Y-m-d', strtotime('+1 day'));
$catatan_umum_input = '';

// Untuk menyimpan item tiket yang dipilih pengguna (jika ada error dan form di-reload)
$items_tiket_input = isset($_SESSION['form_data_pemesanan']['items_tiket']) ? $_SESSION['form_data_pemesanan']['items_tiket'] : [];
// Untuk menyimpan item sewa yang dipilih pengguna
$items_sewa_input = isset($_SESSION['form_data_pemesanan']['items_sewa']) ? $_SESSION['form_data_pemesanan']['items_sewa'] : [];

if (is_post()) {
    // Ambil data pemesan dasar
    $tanggal_kunjungan_input = input('tanggal_kunjungan');
    $catatan_umum_input = input('catatan_umum_pemesanan');

    // Ambil item tiket dari form (ini akan berupa array)
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

    // Ambil item sewa dari form (juga array)
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
        'tanggal_kunjungan' => $tanggal_kunjungan_input,
        'catatan_umum_pemesanan' => $catatan_umum_input,
        'items_tiket' => $items_tiket_to_process,
        'items_sewa' => $items_sewa_to_process
    ];

    if (empty($items_tiket_to_process)) {
        set_flash_message('danger', 'Anda harus memilih minimal satu jenis tiket dan jumlahnya.');
        redirect('user/pemesanan_tiket.php');
        exit;
    } else {
        $data_pemesan_info = [
            'user_id' => get_current_user_id(),
            'tanggal_kunjungan' => $tanggal_kunjungan_input,
            'catatan_umum_pemesanan' => $catatan_umum_input,
            'metode_pembayaran_pilihan' => 'Belum Dipilih'
        ];

        $kode_pemesanan_hasil = PemesananTiketController::prosesPemesananLengkap(
            $data_pemesan_info,
            $items_tiket_to_process,
            $items_sewa_to_process
        );

        if ($kode_pemesanan_hasil) {
            unset($_SESSION['form_data_pemesanan']);

            // Pesan flash disarankan diatur oleh Controller, tapi jika tidak, ini fallbacknya
            if (!isset($_SESSION['flash_message'])) {
                set_flash_message('success', 'Pemesanan Anda dengan kode ' . e($kode_pemesanan_hasil) . ' berhasil dibuat. Silakan lanjutkan ke pembayaran.');
            }

            // ---- PERUBAHAN UTAMA DI SINI ----
            redirect('user/detail_pemesanan.php?kode=' . $kode_pemesanan_hasil);
            // Pengguna diarahkan ke detail pemesanan dengan membawa kode pemesanan
            exit;
        } else {
            if (!isset($_SESSION['flash_message'])) {
                set_flash_message('danger', 'Gagal memproses pemesanan. Silakan coba lagi.');
            }
            redirect('user/pemesanan_tiket.php');
            exit;
        }
    }
} else {
    if (isset($_SESSION['form_data_pemesanan'])) {
        $tanggal_kunjungan_input = $_SESSION['form_data_pemesanan']['tanggal_kunjungan'] ?? $tanggal_kunjungan_input;
        $catatan_umum_input = $_SESSION['form_data_pemesanan']['catatan_umum_pemesanan'] ?? $catatan_umum_input;
        $items_tiket_input = $_SESSION['form_data_pemesanan']['items_tiket'] ?? [];
        $items_sewa_input = $_SESSION['form_data_pemesanan']['items_sewa'] ?? [];
        unset($_SESSION['form_data_pemesanan']);
    }
}

$jenis_tiket_list = JenisTiket::getAll();
$alat_sewa_list = SewaAlat::getAll();

$page_title = "Form Pemesanan Tiket & Sewa Alat";
include_once __DIR__ . '/../template/header_user.php';
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
                                        min="<?= date('Y-m-d') // Bisa juga '+0 day' jika ingin hari ini juga bisa 
                                                ?>" required>
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
                                                <?php foreach ($jenis_tiket_list as $jt) : ?>
                                                    <option value="<?= e($jt['id']) ?>" data-harga="<?= e($jt['harga']) ?>" <?= ((string)$current_item_tiket['jenis_tiket_id'] === (string)$jt['id']) ? 'selected' : '' ?>>
                                                        <?= e($jt['nama_layanan_display']) ?> (<?= e($jt['tipe_hari']) ?>) - Rp <?= e(number_format($jt['harga'], 0, ',', '.')) ?>
                                                    </option>
                                                <?php endforeach; ?>
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
                                                    <?php foreach ($alat_sewa_list as $as) : ?>
                                                        <option value="<?= e($as['id']) ?>" <?= ((string)$current_item_sewa['sewa_alat_id'] === (string)$as['id']) ? 'selected' : '' ?>>
                                                            <?= e($as['nama_item']) ?> (Stok: <?= e($as['stok_tersedia']) ?>) - Rp <?= e(number_format($as['harga_sewa'], 0, ',', '.')) ?> /<?= e($as['durasi_harga_sewa']) ?> <?= e($as['satuan_durasi_harga']) ?>
                                                        </option>
                                                    <?php endforeach; ?>
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
include_once __DIR__ . '/../template/footer.php';
?>

<script>
    // --- KODE JAVASCRIPT SAMA PERSIS DENGAN YANG SEBELUMNYA ---
    // Tidak ada perubahan pada JavaScript karena logika frontend tetap sama.
    // Perubahan hanya pada redirect di sisi PHP.
    document.addEventListener('DOMContentLoaded', function() {
        const tiketContainer = document.getElementById('items-tiket-container');
        const addTiketButton = document.getElementById('add-item-tiket');
        let tiketIndex = <?= $tiket_item_count ?>;

        const sewaContainer = document.getElementById('items-sewa-container');
        const addSewaButton = document.getElementById('add-item-sewa');
        let sewaIndex = <?= $sewa_item_count ?>;

        const tanggalKunjunganInput = document.getElementById('tanggal_kunjungan');
        const formPemesanan = document.querySelector('form.needs-validation');

        const ajaxHandlerUrl = "<?= defined('BASE_URL') ? BASE_URL . 'public/stok_form_handler.php' : '../public/stok_form_handler.php' ?>";

        function createFeedbackElement(siblingElement, idSuffix) {
            const feedbackElementId = `feedback-ajax-${idSuffix}`;
            let feedbackElement = document.getElementById(feedbackElementId);
            if (!feedbackElement) {
                feedbackElement = document.createElement('div');
                feedbackElement.id = feedbackElementId;
                feedbackElement.classList.add('form-text', 'mt-1', 'feedback-ajax');
                siblingElement.parentNode.appendChild(feedbackElement);
            }
            return feedbackElement;
        }

        async function cekKuotaTiket(jenisTiketSelect, jumlahInput, tanggalKunjungan) {
            const jenisTiketId = jenisTiketSelect.value;
            const jumlahDiminta = parseInt(jumlahInput.value);
            const feedbackElement = jumlahInput.parentNode.querySelector('.feedback-ajax');

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
                feedbackElement.classList.remove('text-muted');
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
                feedbackElement.textContent = 'Gagal mengecek kuota. Coba lagi.';
                feedbackElement.classList.remove('text-muted');
                feedbackElement.classList.add('text-danger');
            }
        }

        async function cekStokAlat(alatSewaSelect, jumlahInput) {
            const sewaAlatId = alatSewaSelect.value;
            const jumlahDiminta = parseInt(jumlahInput.value);
            const feedbackElement = jumlahInput.parentNode.querySelector('.feedback-ajax');
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
                feedbackElement.classList.remove('text-muted');
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
                feedbackElement.textContent = 'Gagal mengecek stok. Coba lagi.';
                feedbackElement.classList.remove('text-muted');
                feedbackElement.classList.add('text-danger');
            }
        }

        function addEventListenersToTiketItem(itemTiketDiv) {
            const jenisTiketSelect = itemTiketDiv.querySelector('select[name^="items_tiket["]');
            const jumlahInput = itemTiketDiv.querySelector('input[name^="items_tiket["][name$="][jumlah]"]');
            if (jenisTiketSelect && jumlahInput) {
                jenisTiketSelect.addEventListener('change', function() {
                    cekKuotaTiket(this, jumlahInput, tanggalKunjunganInput.value);
                });
                jumlahInput.addEventListener('input', function() {
                    cekKuotaTiket(jenisTiketSelect, this, tanggalKunjunganInput.value);
                });
            }
        }

        function addEventListenersToSewaItem(itemSewaDiv) {
            const alatSewaSelect = itemSewaDiv.querySelector('select[name^="items_sewa["]');
            const jumlahInput = itemSewaDiv.querySelector('input[name^="items_sewa["][name$="][jumlah_sewa]"]');
            if (alatSewaSelect && jumlahInput) {
                alatSewaSelect.addEventListener('change', function() {
                    cekStokAlat(this, jumlahInput);
                });
                jumlahInput.addEventListener('input', function() {
                    cekStokAlat(alatSewaSelect, this);
                });
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
                            <?php foreach ($jenis_tiket_list as $jt) : ?>
                                <option value="<?= e($jt['id']) ?>" data-harga="<?= e($jt['harga']) ?>">
                                    <?= e($jt['nama_layanan_display']) ?> (<?= e($jt['tipe_hari']) ?>) - Rp <?= e(number_format($jt['harga'], 0, ',', '.')) ?>
                                </option>
                            <?php endforeach; ?>
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
                    baseDate = new Date(tanggalKunjunganValue + 'T00:00:00');
                    if (isNaN(baseDate.getTime())) baseDate = new Date();
                } else {
                    baseDate = new Date();
                }
                const formatDateTimeLocal = (dateObj, hours, minutes) => {
                    dateObj.setHours(hours, minutes, 0, 0);
                    const offset = dateObj.getTimezoneOffset() * 60000;
                    return (new Date(dateObj.getTime() - offset)).toISOString().slice(0, 16);
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
                                <?php foreach ($alat_sewa_list as $as) : ?>
                                    <option value="<?= e($as['id']) ?>">
                                        <?= e($as['nama_item']) ?> (Stok: <?= e($as['stok_tersedia']) ?>) - Rp <?= e(number_format($as['harga_sewa'], 0, ',', '.')) ?> /<?= e($as['durasi_harga_sewa']) ?> <?= e($as['satuan_durasi_harga']) ?>
                                    </option>
                                <?php endforeach; ?>
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
                    if (jenisTiketSelect && jenisTiketSelect.value && jumlahInput) {
                        cekKuotaTiket(jenisTiketSelect, jumlahInput, this.value);
                    }
                });
            });
        }

        document.querySelectorAll('.item-tiket').forEach(addEventListenersToTiketItem);
        document.querySelectorAll('.item-sewa').forEach(addEventListenersToSewaItem);

        if (tanggalKunjunganInput && tanggalKunjunganInput.value) {
            document.querySelectorAll('.item-tiket').forEach(itemTiketDiv => {
                const jenisTiketSelect = itemTiketDiv.querySelector('select[name^="items_tiket["]');
                const jumlahInput = itemTiketDiv.querySelector('input[name^="items_tiket["][name$="][jumlah]"]');
                if (jenisTiketSelect && jenisTiketSelect.value && jumlahInput && jumlahInput.value > 0) {
                    cekKuotaTiket(jenisTiketSelect, jumlahInput, tanggalKunjunganInput.value);
                }
            });
            document.querySelectorAll('.item-sewa').forEach(itemSewaDiv => {
                const alatSewaSelect = itemSewaDiv.querySelector('select[name^="items_sewa["]');
                const jumlahInput = itemSewaDiv.querySelector('input[name^="items_sewa["][name$="][jumlah_sewa]"]');
                if (alatSewaSelect && alatSewaSelect.value && jumlahInput && jumlahInput.value > 0) {
                    cekStokAlat(alatSewaSelect, jumlahInput);
                }
            });
        }

        (function() {
            'use strict'
            var forms = document.querySelectorAll('.needs-validation')
            Array.prototype.slice.call(forms)
                .forEach(function(form) {
                    form.addEventListener('submit', function(event) {
                        let formIsValid = true;
                        document.querySelectorAll('.item-tiket input[name$="[jumlah]"], .item-sewa input[name$="[jumlah_sewa]"]').forEach(input => {
                            if (input.validationMessage !== '') {
                                formIsValid = false;
                            }
                        });
                        if (!form.checkValidity()) formIsValid = false;
                        if (!formIsValid) {
                            event.preventDefault();
                            event.stopPropagation();
                            let generalErrorDiv = form.querySelector('.alert.alert-danger.manual-alert-validation');
                            if (!generalErrorDiv) {
                                generalErrorDiv = document.createElement('div');
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
                        } else {
                            const manualAlert = form.querySelector('.alert.alert-danger.manual-alert-validation');
                            if (manualAlert) manualAlert.remove();
                        }
                        form.classList.add('was-validated');
                    }, false)
                })
        })()
    });
</script>