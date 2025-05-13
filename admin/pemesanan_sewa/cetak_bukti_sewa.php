<?php
// File: C:\xampp\htdocs\Cilengkrang-Web-Wisata\admin\pemesanan_sewa\cetak_bukti_sewa.php

// 1. Sertakan config.php dan helpers
if (!require_once __DIR__ . '/../../config/config.php') {
    exit("Config Error");
}
// Tidak perlu otentikasi admin di sini jika link cetak hanya bisa diakses dari halaman detail yang sudah aman
// Tapi jika ingin lebih aman, tambahkan require_admin();

// 2. Sertakan Controller yang diperlukan
if (!require_once __DIR__ . '/../../controllers/PemesananSewaAlatController.php') {
    exit("Controller Error");
}

$pemesanan_sewa_id = isset($_GET['id']) ? filter_var($_GET['id'], FILTER_VALIDATE_INT) : null;
$detail_sewa = null;

if (!$pemesanan_sewa_id) {
    die("ID Pemesanan Sewa tidak valid atau tidak diberikan.");
}

if (class_exists('PemesananSewaAlatController') && method_exists('PemesananSewaAlatController', 'getDetailSewaByIdForAdmin')) {
    $detail_sewa = PemesananSewaAlatController::getDetailSewaByIdForAdmin($pemesanan_sewa_id);
}

if (!$detail_sewa) {
    die("Detail pemesanan sewa dengan ID " . e($pemesanan_sewa_id) . " tidak ditemukan.");
}

// Ambil nama situs dari config jika ada
$nama_situs = defined('NAMA_SITUS') ? NAMA_SITUS : 'Nama Wisata Anda';

?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Bukti Sewa Alat - ID: <?= e($detail_sewa['id']) ?> - <?= e($nama_situs) ?></title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            font-size: 12pt;
        }

        .container {
            width: 80mm;
            /* Kira-kira lebar struk thermal */
            margin: 20px auto;
            padding: 10px;
            border: 1px solid #ccc;
        }

        h1,
        h2,
        h3 {
            text-align: center;
            margin: 5px 0;
        }

        hr {
            border: none;
            border-top: 1px dashed #333;
            margin: 10px 0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }

        th,
        td {
            text-align: left;
            padding: 3px 0;
            vertical-align: top;
        }

        th {
            font-weight: bold;
        }

        .label {
            font-weight: bold;
            width: 40%;
        }

        .value {
            width: 60%;
        }

        .text-right {
            text-align: right;
        }

        .item-table th,
        .item-table td {
            border-bottom: 1px dashed #eee;
            padding: 5px 0;
        }

        .item-table th:last-child,
        .item-table td:last-child {
            text-align: right;
        }

        .total-section td {
            font-weight: bold;
        }

        .footer-text {
            text-align: center;
            font-size: 9pt;
            margin-top: 15px;
        }

        @media print {
            body {
                margin: 0;
                padding: 0;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            .container {
                width: 100%;
                margin: 0;
                padding: 0;
                border: none;
                box-shadow: none;
            }

            .no-print {
                display: none;
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <h2><?= e($nama_situs) ?></h2>
        <h3>Bukti Sewa Alat</h3>
        <hr>
        <table>
            <tr>
                <td class="label">ID Sewa:</td>
                <td class="value"><?= e($detail_sewa['id']) ?></td>
            </tr>
            <tr>
                <td class="label">Tgl. Pesan:</td>
                <td class="value"><?= e(formatTanggalIndonesia($detail_sewa['created_at'] ?? '', true)) ?></td>
            </tr>
            <tr>
                <td class="label">Pemesan:</td>
                <td class="value"><?= e($detail_sewa['nama_pemesan'] ?? 'N/A') ?></td>
            </tr>
            <?php if (isset($detail_sewa['kode_pemesanan_tiket'])): ?>
                <tr>
                    <td class="label">Kode Tiket:</td>
                    <td class="value"><?= e($detail_sewa['kode_pemesanan_tiket']) ?></td>
                </tr>
            <?php endif; ?>
        </table>
        <hr>
        <h4>Detail Alat Disewa:</h4>
        <table class="item-table">
            <thead>
                <tr>
                    <th>Nama Alat</th>
                    <th class="text-center">Jml</th>
                    <th class="text-right">Harga Satuan</th>
                    <th class="text-right">Subtotal</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><?= e($detail_sewa['nama_alat'] ?? 'N/A') ?></td>
                    <td class="text-center"><?= e($detail_sewa['jumlah'] ?? '-') ?></td>
                    <td class="text-right"><?= formatRupiah($detail_sewa['harga_satuan_saat_pesan'] ?? 0) ?></td>
                    <td class="text-right"><?= formatRupiah($detail_sewa['total_harga_item'] ?? 0) ?></td>
                </tr>
                <!-- Jika ada beberapa item sewa per pemesanan, Anda perlu loop di sini.
                      Namun, berdasarkan struktur saat ini, satu record pemesanan_sewa_alat adalah untuk satu jenis alat. -->
            </tbody>
        </table>
        <table>
            <tr>
                <td class="label">Periode Sewa:</td>
                <td class="value">
                    Mulai: <?= e(formatTanggalIndonesia($detail_sewa['tanggal_mulai_sewa'] ?? '', true)) ?><br>
                    Akhir: <?= e(formatTanggalIndonesia($detail_sewa['tanggal_akhir_sewa_rencana'] ?? '', true)) ?>
                </td>
            </tr>
            <tr>
                <td class="label">Status:</td>
                <td class="value"><strong><?= e(ucfirst($detail_sewa['status_item_sewa'] ?? '-')) ?></strong></td>
            </tr>
        </table>
        <hr>
        <table class="total-section">
            <tr>
                <td class="label">Total Harga Sewa:</td>
                <td class="value text-right"><?= formatRupiah($detail_sewa['total_harga_item'] ?? 0) ?></td>
            </tr>
            <?php if (isset($detail_sewa['denda']) && (float)$detail_sewa['denda'] > 0): ?>
                <tr>
                    <td class="label">Denda:</td>
                    <td class="value text-right"><?= formatRupiah($detail_sewa['denda']) ?></td>
                </tr>
                <tr>
                    <td class="label">Total Bayar (termasuk denda):</td>
                    <td class="value text-right"><?= formatRupiah(($detail_sewa['total_harga_item'] ?? 0) + ($detail_sewa['denda'] ?? 0)) ?></td>
                </tr>
            <?php endif; ?>
        </table>

        <?php if (!empty($detail_sewa['catatan_item_sewa'])): ?>
            <hr>
            <p><strong>Catatan:</strong><br><?= nl2br(e($detail_sewa['catatan_item_sewa'])) ?></p>
        <?php endif; ?>

        <hr>
        <p class="footer-text">Terima kasih telah menyewa alat kami.</p>
        <p class="footer-text">Harap kembalikan alat tepat waktu dan dalam kondisi baik.</p>
        <p class="footer-text no-print" style="margin-top: 20px;">
            <button onclick="window.print()">Cetak Bukti</button>
            <button onclick="window.close()">Tutup</button>
        </p>
    </div>

    <script>
        // Opsional: otomatis buka dialog print saat halaman dimuat
        // window.onload = function() {
        //     window.print();
        // }
    </script>
</body>

</html>