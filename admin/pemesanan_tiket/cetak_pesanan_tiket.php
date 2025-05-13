<?php
// File: C:\xampp\htdocs\Cilengkrang-Web-Wisata\admin\pemesanan_tiket\cetak_pesanan_tiket.php

if (!require_once __DIR__ . '/../../config/config.php') {
    exit("Config Error.");
}
// Tidak perlu otentikasi admin di sini jika link hanya dari halaman admin yang sudah aman.
// Namun, jika ingin lebih aman, tambahkan: require_admin();

if (!require_once __DIR__ . '/../../controllers/PemesananTiketController.php') {
    exit("Controller Error.");
}

$pemesanan_id = isset($_GET['id']) ? filter_var($_GET['id'], FILTER_VALIDATE_INT) : null;
$data_pemesanan_lengkap = null; // Untuk menampung header, detail tiket, detail sewa, pembayaran

if (!$pemesanan_id) {
    die("ID Pemesanan tidak valid atau tidak diberikan.");
}

if (class_exists('PemesananTiketController') && method_exists('PemesananTiketController', 'getDetailPemesananLengkap')) {
    $data_pemesanan_lengkap = PemesananTiketController::getDetailPemesananLengkap($pemesanan_id);
}

if (!$data_pemesanan_lengkap || !$data_pemesanan_lengkap['header']) {
    die("Detail pemesanan tiket dengan ID " . e($pemesanan_id) . " tidak ditemukan atau data tidak lengkap.");
}

$header = $data_pemesanan_lengkap['header'];
$detail_tiket_items = $data_pemesanan_lengkap['detail_tiket'] ?? [];
$detail_sewa_items = $data_pemesanan_lengkap['detail_sewa'] ?? [];
$pembayaran_info = $data_pemesanan_lengkap['pembayaran'] ?? null;

$nama_situs = defined('NAMA_SITUS') ? NAMA_SITUS : 'Nama Wisata Anda';
$pageTitleCetak = "Bukti Pesanan " . e($header['kode_pemesanan'] ?? $pemesanan_id);
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title><?= e($pageTitleCetak) ?> - <?= e($nama_situs) ?></title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            margin: 0;
            padding: 0;
            font-size: 11pt;
            color: #333;
        }

        .invoice-box {
            max-width: 800px;
            margin: auto;
            padding: 20px;
            border: 1px solid #eee;
            box-shadow: 0 0 10px rgba(0, 0, 0, .15);
            font-size: 14px;
            line-height: 20px;
        }

        .invoice-box table {
            width: 100%;
            line-height: inherit;
            text-align: left;
            border-collapse: collapse;
        }

        .invoice-box table td {
            padding: 5px;
            vertical-align: top;
        }

        .invoice-box table tr td:nth-child(2) {
            text-align: right;
        }

        .invoice-box table tr.top table td {
            padding-bottom: 20px;
        }

        .invoice-box table tr.top table td.title {
            font-size: 30px;
            line-height: 30px;
            color: #555;
        }

        .invoice-box table tr.information table td {
            padding-bottom: 20px;
        }

        .invoice-box table tr.heading td {
            background: #eee;
            border-bottom: 1px solid #ddd;
            font-weight: bold;
            text-align: left;
        }

        .invoice-box table tr.details td {
            padding-bottom: 15px;
        }

        .invoice-box table tr.item td {
            border-bottom: 1px solid #eee;
            text-align: left;
        }

        .invoice-box table tr.item.last td {
            border-bottom: none;
        }

        .invoice-box table tr.total td:nth-child(2) {
            border-top: 2px solid #eee;
            font-weight: bold;
        }

        .text-right {
            text-align: right !important;
        }

        .text-center {
            text-align: center !important;
        }

        .mt-20 {
            margin-top: 20px;
        }

        .mb-0 {
            margin-bottom: 0;
        }

        .bold {
            font-weight: bold;
        }

        .status-badge {
            padding: 3px 8px;
            border-radius: 4px;
            color: white;
            font-size: 0.9em;
        }

        .status-pending {
            background-color: #ffc107;
            color: #333;
        }

        /* Kuning */
        .status-paid,
        .status-confirmed,
        .status-success {
            background-color: #28a745;
        }

        /* Hijau */
        .status-waiting_payment {
            background-color: #17a2b8;
        }

        /* Biru Muda */
        .status-cancelled,
        .status-failed,
        .status-expired {
            background-color: #dc3545;
        }

        /* Merah */
        .status-completed {
            background-color: #343a40;
        }

        /* Abu Gelap */
        .footer-print {
            margin-top: 30px;
            text-align: center;
            font-size: 10pt;
            color: #777;
        }

        @media print {
            .invoice-box {
                max-width: 100%;
                margin: 0;
                padding: 0;
                border: 0;
                box-shadow: none;
                font-size: 10pt;
                line-height: 1.3;
            }

            .no-print {
                display: none;
            }

            body {
                margin: 0.5cm;
            }
        }
    </style>
</head>

<body>
    <div class="invoice-box">
        <table cellpadding="0" cellspacing="0">
            <tr class="top">
                <td colspan="4">
                    <table>
                        <tr>
                            <td class="title">
                                <?php if (defined('BASE_URL')): ?>
                                    <img src="<?= e(BASE_URL . 'public/img/logo.png') ?>" style="width:100%; max-width:150px;" alt="Logo <?= e($nama_situs) ?>">
                                <?php endif; ?>
                            </td>
                            <td class="text-right">
                                <h2 style="margin-bottom: 0;"><?= e($nama_situs) ?></h2>
                                Bukti Pemesanan<br>
                                Kode: <strong><?= e($header['kode_pemesanan'] ?? '-') ?></strong><br>
                                Tgl. Pesan: <?= e(formatTanggalIndonesia($header['created_at'] ?? '', true)) ?>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>

            <tr class="information">
                <td colspan="4">
                    <table>
                        <tr>
                            <td>
                                <strong>Kepada Yth:</strong><br>
                                <?php
                                $nama_pemesan_cetak = $header['user_nama'] ?? ($header['nama_pemesan_tamu'] ?? 'Tamu');
                                echo e($nama_pemesan_cetak);
                                if (!empty($header['user_email'])) {
                                    echo "<br>" . e($header['user_email']);
                                } elseif (!empty($header['email_pemesan_tamu'])) {
                                    echo "<br>" . e($header['email_pemesan_tamu']);
                                }
                                if (!empty($header['nohp_pemesan_tamu'])) {
                                    echo "<br>Telp: " . e($header['nohp_pemesan_tamu']);
                                } elseif (isset($header['user_id'])) {
                                    // Anda bisa ambil no hp user dari tabel users jika perlu
                                }
                                ?>
                            </td>
                            <td class="text-right">
                                <strong>Tanggal Kunjungan:</strong><br>
                                <?= e(formatTanggalIndonesia($header['tanggal_kunjungan'] ?? '', false, true)) ?> <!-- Dengan hari singkat -->
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>

            <tr class="heading">
                <td colspan="3">Deskripsi Item</td>
                <td class="text-right">Subtotal</td>
            </tr>

            <?php if (!empty($detail_tiket_items)): ?>
                <?php foreach ($detail_tiket_items as $item): ?>
                    <tr class="item">
                        <td colspan="2"><?= e($item['nama_layanan_display'] ?? 'Tiket') ?> (<?= e($item['tipe_hari'] ?? '-') ?>) x <?= e($item['jumlah'] ?? 0) ?></td>
                        <td class="text-right"><?= formatRupiah($item['harga_satuan_saat_pesan'] ?? 0) ?></td>
                        <td class="text-right"><?= formatRupiah($item['subtotal_item'] ?? 0) ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>

            <?php if (!empty($detail_sewa_items)): ?>
                <tr class="details">
                    <td colspan="4" style="padding-top: 15px; font-weight:bold;">Detail Sewa Alat:</td>
                </tr>
                <?php foreach ($detail_sewa_items as $item_sewa): ?>
                    <tr class="item">
                        <td colspan="2"><?= e($item_sewa['nama_alat'] ?? 'Alat Sewa') ?> x <?= e($item_sewa['jumlah'] ?? 0) ?></td>
                        <td class="text-right"><?= formatRupiah($item_sewa['harga_satuan_saat_pesan'] ?? 0) ?></td>
                        <td class="text-right"><?= formatRupiah($item_sewa['total_harga_item'] ?? 0) ?></td>
                    </tr>
                    <tr class="item last">
                        <td colspan="4" style="font-size:0.9em; color: #555;">
                            Periode Sewa: <?= e(formatTanggalIndonesia($item_sewa['tanggal_mulai_sewa'] ?? '', true)) ?> s/d <?= e(formatTanggalIndonesia($item_sewa['tanggal_akhir_sewa_rencana'] ?? '', true)) ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>

            <tr class="total">
                <td colspan="3" class="text-right bold">Total Keseluruhan:</td>
                <td class="text-right bold"><?= formatRupiah($header['total_harga_akhir'] ?? 0) ?></td>
            </tr>

            <tr class="details">
                <td colspan="4" class="mt-20">
                    Status Pemesanan:
                    <?php
                    $status_raw_cetak = $header['status_pemesanan'] ?? 'unknown';
                    $status_cetak = strtolower($status_raw_cetak);
                    $status_class_cetak = 'status-pending'; // default
                    if ($status_cetak == 'pending') $status_class_cetak = 'status-pending';
                    elseif ($status_cetak == 'waiting_payment') $status_class_cetak = 'status-waiting_payment';
                    elseif ($status_cetak == 'paid') $status_class_cetak = 'status-paid';
                    elseif ($status_cetak == 'confirmed') $status_class_cetak = 'status-confirmed';
                    elseif ($status_cetak == 'completed') $status_class_cetak = 'status-completed';
                    elseif ($status_cetak == 'cancelled' || $status_cetak == 'failed' || $status_cetak == 'expired') $status_class_cetak = 'status-cancelled';
                    ?>
                    <span class="status-badge <?= $status_class_cetak ?>"><?= e(ucfirst(str_replace('_', ' ', $status_raw_cetak))) ?></span>
                </td>
            </tr>
            <?php if ($pembayaran_info && $pembayaran_info['status_pembayaran'] == 'success' && !empty($pembayaran_info['metode_pembayaran'])): ?>
                <tr class="details">
                    <td colspan="4">Metode Pembayaran: <?= e($pembayaran_info['metode_pembayaran']) ?></td>
                </tr>
            <?php endif; ?>

            <?php if (!empty($header['catatan_umum_pemesanan'])): ?>
                <tr class="details">
                    <td colspan="4">
                        <strong>Catatan:</strong><br>
                        <?= nl2br(e($header['catatan_umum_pemesanan'])) ?>
                    </td>
                </tr>
            <?php endif; ?>
        </table>

        <div class="footer-print">
            Ini adalah bukti pemesanan yang sah. Harap tunjukkan bukti ini saat kedatangan.
            <br><?= e($nama_situs) ?> © <?= date('Y') ?>
        </div>
        <div class="no-print mt-20 text-center">
            <button onclick="window.print()" class="btn btn-primary">Cetak</button>
            <button onclick="window.close()" class="btn btn-secondary">Tutup</button>
        </div>
    </div>
</body>

</html>