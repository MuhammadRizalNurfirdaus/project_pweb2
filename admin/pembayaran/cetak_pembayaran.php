<?php
// File: C:\xampp\htdocs\Cilengkrang-Web-Wisata\admin\pembayaran\cetak_pembayaran.php

if (!require_once __DIR__ . '/../../config/config.php') {
    http_response_code(503);
    error_log("FATAL: Gagal memuat config.php dari admin/pembayaran/cetak_pembayaran.php");
    exit("Kesalahan konfigurasi server.");
}

// require_admin(); // Pertimbangkan jika akses langsung harus dicegah

$pembayaran_id = isset($_GET['id']) ? filter_var($_GET['id'], FILTER_VALIDATE_INT) : null;

if (!$pembayaran_id || $pembayaran_id <= 0) {
    die("ID Pembayaran tidak valid.");
}

$data_lengkap = null;
if (class_exists('PembayaranController') && method_exists('PembayaranController', 'getDetailPembayaranLengkap')) {
    $data_lengkap = PembayaranController::getDetailPembayaranLengkap($pembayaran_id);
} else {
    die("Kesalahan sistem: Komponen pembayaran tidak tersedia.");
}

if (!$data_lengkap || empty($data_lengkap['pembayaran'])) {
    die("Data pembayaran untuk ID #" . e($pembayaran_id) . " tidak ditemukan.");
}

$info_pembayaran = $data_lengkap['pembayaran'];
$pemesanan_detail = $data_lengkap['pemesanan_detail'];
$header_pemesanan = $pemesanan_detail['header'] ?? null;
$detail_tiket_items = $pemesanan_detail['detail_tiket'] ?? [];
$detail_sewa_items = $pemesanan_detail['detail_sewa'] ?? [];

$nama_situs = defined('NAMA_SITUS') ? NAMA_SITUS : 'Nama Wisata Anda';
$pageTitleCetak = "Bukti Pembayaran #" . e($info_pembayaran['id']);
if ($header_pemesanan) {
    $pageTitleCetak .= " (Pemesanan " . e($header_pemesanan['kode_pemesanan']) . ")";
}

?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title><?= e($pageTitleCetak) ?> - <?= e($nama_situs) ?></title>
    <style>
        /* Salin CSS dari cetak_pesanan_tiket.php atau buat CSS cetak khusus */
        body {
            font-family: 'Arial', sans-serif;
            margin: 0;
            padding: 20px;
            font-size: 12pt;
            color: #333;
        }

        .invoice-box {
            max-width: 800px;
            margin: auto;
            padding: 20px;
            border: 1px solid #eee;
            box-shadow: 0 0 10px rgba(0, 0, 0, .1);
            font-size: 14px;
            line-height: 1.6;
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

        .invoice-box table tr.heading td {
            background: #f0f0f0;
            border-bottom: 1px solid #ddd;
            font-weight: bold;
        }

        .invoice-box table tr.item td {
            border-bottom: 1px solid #eee;
        }

        .invoice-box table tr.item.last td {
            border-bottom: none;
        }

        .invoice-box table tr.total td:nth-child(2) {
            font-weight: bold;
            text-align: right;
        }

        .text-right {
            text-align: right !important;
        }

        .bold {
            font-weight: bold;
        }

        .status-badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 4px;
            color: white;
            font-size: 0.9em;
        }

        .status-success,
        .status-paid,
        .status-confirmed {
            background-color: #28a745;
        }

        .status-pending,
        .status-awaiting_confirmation {
            background-color: #ffc107;
            color: #212529;
        }

        .status-failed,
        .status-expired,
        .status-cancelled,
        .status-refunded {
            background-color: #dc3545;
        }

        .section-title {
            font-size: 1.2em;
            font-weight: bold;
            margin-top: 20px;
            margin-bottom: 10px;
            border-bottom: 1px solid #ccc;
            padding-bottom: 5px;
        }

        .footer-print {
            margin-top: 30px;
            text-align: center;
            font-size: 10pt;
            color: #777;
        }

        .print-buttons {
            margin-top: 20px;
            text-align: center;
        }

        @media print {
            body {
                margin: 0.5cm;
                font-size: 10pt;
            }

            .invoice-box {
                max-width: 100%;
                margin: 0;
                padding: 0;
                border: 0;
                box-shadow: none;
            }

            .print-buttons {
                display: none;
            }
        }
    </style>
</head>

<body>
    <div class="invoice-box">
        <h1 style="text-align:center; margin-bottom:0;"><?= e($nama_situs) ?></h1>
        <h2 style="text-align:center; margin-top:5px; margin-bottom:20px; font-size:1.3em;">Bukti Pembayaran</h2>

        <div class="section-title">Detail Pembayaran</div>
        <table>
            <tr>
                <td>ID Pembayaran:</td>
                <td class="text-right bold">#<?= e($info_pembayaran['id']) ?></td>
            </tr>
            <tr>
                <td>Kode Pemesanan Terkait:</td>
                <td class="text-right"><?= e($header_pemesanan['kode_pemesanan'] ?? 'N/A') ?></td>
            </tr>
            <tr>
                <td>Metode Pembayaran:</td>
                <td class="text-right"><?= e($info_pembayaran['metode_pembayaran'] ?? 'N/A') ?></td>
            </tr>
            <tr>
                <td>Jumlah Dibayar:</td>
                <td class="text-right bold"><?= formatRupiah($info_pembayaran['jumlah_dibayar'] ?? 0) ?></td>
            </tr>
            <tr>
                <td>Status Pembayaran:</td>
                <td class="text-right"><?= getStatusBadgeClassHTML($info_pembayaran['status_pembayaran'] ?? 'unknown') ?></td>
            </tr>
            <tr>
                <td>Waktu Pembayaran:</td>
                <td class="text-right"><?= !empty($info_pembayaran['waktu_pembayaran']) ? e(formatTanggalIndonesia($info_pembayaran['waktu_pembayaran'], true)) : 'Belum ada' ?></td>
            </tr>
            <?php if (!empty($info_pembayaran['bukti_pembayaran'])): ?>
                <tr>
                    <td>Bukti:</td>
                    <td class="text-right"><a href="<?= e(ASSETS_URL . '/uploads/bukti_pembayaran/' . $info_pembayaran['bukti_pembayaran']) ?>" target="_blank">Lihat Bukti</a></td>
                </tr>
            <?php endif; ?>
            <tr>
                <td>ID Transaksi Gateway:</td>
                <td class="text-right"><?= e($info_pembayaran['id_transaksi_gateway'] ?? 'N/A') ?></td>
            </tr>
            <tr>
                <td>Nomor Virtual Account:</td>
                <td class="text-right"><?= e($info_pembayaran['nomor_virtual_account'] ?? 'N/A') ?></td>
            </tr>
        </table>

        <?php if ($header_pemesanan): ?>
            <div class="section-title">Ringkasan Pemesanan Terkait</div>
            <table>
                <tr>
                    <td>Pemesan:</td>
                    <td class="text-right"><?= e($header_pemesanan['user_nama_lengkap'] ?? $header_pemesanan['nama_pemesan_tamu'] ?? 'N/A') ?></td>
                </tr>
                <tr>
                    <td>Tanggal Kunjungan:</td>
                    <td class="text-right"><?= e(formatTanggalIndonesia($header_pemesanan['tanggal_kunjungan'] ?? null)) ?></td>
                </tr>
                <tr>
                    <td>Total Tagihan Pemesanan:</td>
                    <td class="text-right bold"><?= formatRupiah($header_pemesanan['total_harga_akhir'] ?? 0) ?></td>
                </tr>
                <tr>
                    <td>Status Pemesanan:</td>
                    <td class="text-right"><?= getStatusBadgeClassHTML($header_pemesanan['status'] ?? 'unknown') ?></td>
                </tr>
            </table>

            <?php if (!empty($detail_tiket_items)): ?>
                <div style="margin-top:15px;"><strong>Item Tiket:</strong></div>
                <table style="margin-top:5px;">
                    <tr class="heading">
                        <td>Deskripsi</td>
                        <td class="text-right">Jumlah</td>
                        <td class="text-right">Subtotal</td>
                    </tr>
                    <?php foreach ($detail_tiket_items as $item_t): ?>
                        <tr class="item">
                            <td><?= e($item_t['nama_layanan_display'] ?? 'N/A') ?></td>
                            <td class="text-right"><?= e($item_t['jumlah'] ?? 0) ?></td>
                            <td class="text-right"><?= formatRupiah($item_t['subtotal_item'] ?? 0) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            <?php endif; ?>

            <?php if (!empty($detail_sewa_items)): ?>
                <div style="margin-top:15px;"><strong>Item Sewa Alat:</strong></div>
                <table style="margin-top:5px;">
                    <tr class="heading">
                        <td>Deskripsi</td>
                        <td class="text-right">Jumlah</td>
                        <td class="text-right">Subtotal</td>
                    </tr>
                    <?php foreach ($detail_sewa_items as $item_s): ?>
                        <tr class="item">
                            <td><?= e($item_s['nama_alat'] ?? 'N/A') ?></td>
                            <td class="text-right"><?= e($item_s['jumlah'] ?? 0) ?></td>
                            <td class="text-right"><?= formatRupiah($item_s['total_harga_item'] ?? 0) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            <?php endif; ?>
        <?php endif; ?>

        <div class="footer-print">
            Ini adalah bukti pembayaran yang sah. Terima kasih.
            <br><?= e($nama_situs) ?> © <?= date('Y') ?>
        </div>
    </div>
    <div class="print-buttons">
        <button onclick="window.print()" class="btn-primary">Cetak</button>
        <button onclick="window.close()" class="btn-secondary">Tutup</button>
    </div>
</body>

</html>