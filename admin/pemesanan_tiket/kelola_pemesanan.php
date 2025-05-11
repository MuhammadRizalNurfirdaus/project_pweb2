<?php
// File: C:\xampp\htdocs\Cilengkrang-Web-Wisata\admin\pemesanan_tiket\kelola_pemesanan.php

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../controllers/PemesananTiketController.php';

$page_title = "Kelola Pemesanan Tiket";
include_once __DIR__ . '/../../template/header_admin.php';

$daftar_pemesanan = PemesananTiketController::getAll();

?>

<!-- Breadcrumb -->
<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?= $base_url ?>admin/dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
        <li class="breadcrumb-item active" aria-current="page"><i class="fas fa-calendar-check"></i> Kelola Pemesanan Tiket</li>
    </ol>
</nav>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Daftar Pemesanan Tiket</h1>
</div>

<?php
if (function_exists('display_flash_message')) {
    echo display_flash_message();
}
?>

<div class="card shadow-sm">
    <div class="card-header bg-light">
        <h5 class="mb-0"><i class="fas fa-list me-2"></i>Data Pemesanan Tiket Masuk</h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered table-hover table-striped">
                <thead class="table-dark">
                    <tr>
                        <th scope="col" style="width: 5%;">ID</th>
                        <th scope="col">Nama Pemesan</th>
                        <th scope="col">Destinasi</th>
                        <th scope="col">Item</th>
                        <th scope="col" class="text-center" style="width: 8%;">Jumlah</th>
                        <th scope="col" style="width: 12%;">Tgl. Kunjungan</th>
                        <th scope="col" class="text-end" style="width: 10%;">Total Harga</th>
                        <th scope="col" class="text-center" style="width: 10%;">Status</th>
                        <th scope="col" style="width: 12%;">Tgl. Pesan</th>
                        <th scope="col" style="width: 18%;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($daftar_pemesanan) && is_array($daftar_pemesanan)): ?>
                        <?php foreach ($daftar_pemesanan as $pemesanan): ?>
                            <tr>
                                <th scope="row"><?= e($pemesanan['id']) ?></th>
                                <td>
                                    <?php if (!empty($pemesanan['user_id']) && !empty($pemesanan['user_nama'])): ?>
                                        <i class="fas fa-user-check text-success me-1" title="Pengguna Terdaftar"></i>
                                        <?= e($pemesanan['user_nama']) ?>
                                        <br><small class="text-muted"><?= e($pemesanan['user_email']) ?></small>
                                    <?php else: ?>
                                        <i class="fas fa-user-alt-slash text-muted me-1" title="Tamu"></i>
                                        Tamu
                                        <?php
                                        if (preg_match('/\(Tamu: (.*?)\)/', $pemesanan['nama_item'], $matches)) {
                                            echo "<br><small class='text-info'><em>" . e($matches[1]) . "</em></small>";
                                        }
                                        ?>
                                    <?php endif; ?>
                                </td>
                                <td><?= e($pemesanan['nama_destinasi']) ?></td>
                                <td><?= e($pemesanan['nama_item']) ?></td>
                                <td class="text-center"><?= e($pemesanan['jumlah_item']) ?></td>
                                <td><?= e(date('d M Y', strtotime($pemesanan['tanggal_kunjungan']))) ?></td>
                                <td class="text-end">Rp <?= e(number_format($pemesanan['total_harga'], 0, ',', '.')) ?></td>
                                <td class="text-center">
                                    <?php
                                    $status_class = 'bg-secondary';
                                    if ($pemesanan['status'] == 'pending') $status_class = 'bg-warning text-dark';
                                    elseif ($pemesanan['status'] == 'confirmed') $status_class = 'bg-success';
                                    elseif ($pemesanan['status'] == 'completed') $status_class = 'bg-info text-dark';
                                    elseif ($pemesanan['status'] == 'cancelled') $status_class = 'bg-danger';
                                    ?>
                                    <span class="badge <?= $status_class ?>"><?= e(ucfirst($pemesanan['status'])) ?></span>
                                </td>
                                <td><?= e(date('d M Y, H:i', strtotime($pemesanan['created_at']))) ?></td>
                                <td>
                                    <button type="button" class="btn btn-primary btn-sm me-1 mb-1" data-bs-toggle="modal" data-bs-target="#detailPemesananModal"
                                        data-id="<?= e($pemesanan['id']) ?>"
                                        data-nama_pemesan="<?= e(!empty($pemesanan['user_nama']) ? $pemesanan['user_nama'] . ' (' . $pemesanan['user_email'] . ')' : 'Tamu' . (preg_match('/\(Tamu: (.*?)\)/', $pemesanan['nama_item'], $matches) ? ' - ' . $matches[1] : '')) ?>"
                                        data-destinasi="<?= e($pemesanan['nama_destinasi']) ?>"
                                        data-item="<?= e($pemesanan['nama_item']) ?>"
                                        data-jumlah="<?= e($pemesanan['jumlah_item']) ?>"
                                        data-tgl_kunjungan="<?= e(date('d F Y', strtotime($pemesanan['tanggal_kunjungan']))) ?>"
                                        data-total_harga="Rp <?= e(number_format($pemesanan['total_harga'], 0, ',', '.')) ?>"
                                        data-status_sekarang="<?= e($pemesanan['status']) ?>"
                                        data-tgl_pesan="<?= e(date('d F Y, H:i:s', strtotime($pemesanan['created_at']))) ?>"
                                        data-jenis_pemesanan="<?= e($pemesanan['jenis_pemesanan']) ?>"
                                        title="Lihat Detail & Update Status">
                                        <i class="fas fa-edit"></i> Detail/Status
                                    </button>
                                    <a href="<?= $base_url ?>admin/pemesanan_tiket/hapus_pemesanan.php?id=<?= e($pemesanan['id']) ?>" class="btn btn-danger btn-sm mb-1" title="Hapus Pemesanan"
                                        onclick="return confirm('Apakah Anda yakin ingin menghapus pemesanan tiket ID: <?= e($pemesanan['id']) ?> ini secara permanen?')">
                                        <i class="fas fa-trash-alt"></i> Hapus
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="10" class="text-center py-4">
                                <p class="mb-2 lead">Belum ada data pemesanan tiket.</p>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Detail Pemesanan dan Update Status -->
<div class="modal fade" id="detailPemesananModal" tabindex="-1" aria-labelledby="detailPemesananModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="detailPemesananModalLabel">Detail Pemesanan Tiket ID: <span id="modalPemesananId"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6">
                        <h6>Informasi Pemesan:</h6>
                        <p><strong>Nama:</strong> <span id="modalNamaPemesan"></span></p>
                        <p><strong>Jenis Pemesanan:</strong> <span id="modalJenisPemesanan"></span></p>
                        <hr>
                        <h6>Detail Kunjungan:</h6>
                        <p><strong>Destinasi:</strong> <span id="modalDestinasi"></span></p>
                        <p><strong>Item:</strong> <span id="modalItem"></span></p>
                        <p><strong>Jumlah:</strong> <span id="modalJumlah"></span> tiket</p>
                        <p><strong>Tanggal Kunjungan:</strong> <span id="modalTglKunjungan"></span></p>
                    </div>
                    <div class="col-md-6">
                        <h6>Informasi Pembayaran & Status:</h6>
                        <p><strong>Total Harga:</strong> <span id="modalTotalHarga" class="fw-bold text-success"></span></p>
                        <p><strong>Tanggal Pesan:</strong> <span id="modalTglPesan"></span></p>
                        <p><strong>Status Saat Ini:</strong> <span id="modalStatusSekarang" class="badge"></span></p>
                        <hr>
                        <h6>Update Status Pemesanan:</h6>
                        <form id="formUpdateStatus" action="<?= $base_url ?>admin/pemesanan_tiket/proses_update_status.php" method="POST">
                            <input type="hidden" name="pemesanan_id" id="inputPemesananId">
                            <div class="mb-3">
                                <label for="status_baru" class="form-label">Pilih Status Baru:</label>
                                <select class="form-select" id="status_baru" name="status_baru" required>
                                    <option value="pending">Pending</option>
                                    <option value="confirmed">Confirmed (Terkonfirmasi)</option>
                                    <option value="completed">Completed (Selesai)</option>
                                    <option value="cancelled">Cancelled (Dibatalkan)</option>
                                </select>
                            </div>
                            <button type="submit" class="btn btn-success"><i class="fas fa-save me-2"></i>Simpan Status</button>
                        </form>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>


<?php
include_once __DIR__ . '/../../template/footer_admin.php';
?>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        var detailPemesananModal = document.getElementById('detailPemesananModal');
        if (detailPemesananModal) {
            detailPemesananModal.addEventListener('show.bs.modal', function(event) {
                var button = event.relatedTarget;
                var modalTitle = detailPemesananModal.querySelector('.modal-title #modalPemesananId');
                var modalNamaPemesan = detailPemesananModal.querySelector('#modalNamaPemesan');
                var modalJenisPemesanan = detailPemesananModal.querySelector('#modalJenisPemesanan');
                var modalDestinasi = detailPemesananModal.querySelector('#modalDestinasi');
                var modalItem = detailPemesananModal.querySelector('#modalItem');
                var modalJumlah = detailPemesananModal.querySelector('#modalJumlah');
                var modalTglKunjungan = detailPemesananModal.querySelector('#modalTglKunjungan');
                var modalTotalHarga = detailPemesananModal.querySelector('#modalTotalHarga');
                var modalTglPesan = detailPemesananModal.querySelector('#modalTglPesan');
                var modalStatusSekarang = detailPemesananModal.querySelector('#modalStatusSekarang');
                var inputPemesananId = detailPemesananModal.querySelector('#inputPemesananId');
                var selectStatusBaru = detailPemesananModal.querySelector('#status_baru');

                var idPemesanan = button.getAttribute('data-id');
                modalTitle.textContent = idPemesanan;
                inputPemesananId.value = idPemesanan;

                modalNamaPemesan.textContent = button.getAttribute('data-nama_pemesan');
                modalJenisPemesanan.textContent = button.getAttribute('data-jenis_pemesanan');
                modalDestinasi.textContent = button.getAttribute('data-destinasi');
                modalItem.textContent = button.getAttribute('data-item');
                modalJumlah.textContent = button.getAttribute('data-jumlah');
                modalTglKunjungan.textContent = button.getAttribute('data-tgl_kunjungan');
                modalTotalHarga.textContent = button.getAttribute('data-total_harga');
                modalTglPesan.textContent = button.getAttribute('data-tgl_pesan');

                var statusSekarangText = button.getAttribute('data-status_sekarang');
                modalStatusSekarang.textContent = statusSekarangText.charAt(0).toUpperCase() + statusSekarangText.slice(1);
                modalStatusSekarang.className = 'badge'; // Reset class
                if (statusSekarangText === 'pending') modalStatusSekarang.classList.add('bg-warning', 'text-dark');
                else if (statusSekarangText === 'confirmed') modalStatusSekarang.classList.add('bg-success');
                else if (statusSekarangText === 'completed') modalStatusSekarang.classList.add('bg-info', 'text-dark');
                else if (statusSekarangText === 'cancelled') modalStatusSekarang.classList.add('bg-danger');
                else modalStatusSekarang.classList.add('bg-secondary');

                // Set selected value for status dropdown
                for (var i = 0; i < selectStatusBaru.options.length; i++) {
                    if (selectStatusBaru.options[i].value === statusSekarangText) {
                        selectStatusBaru.selectedIndex = i;
                        break;
                    }
                }
            });
        }
    });
</script>