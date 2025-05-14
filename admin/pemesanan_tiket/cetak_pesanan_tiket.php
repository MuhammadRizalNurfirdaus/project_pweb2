<?php
// File: C:\xampp\htdocs\Cilengkrang-Web-Wisata\admin\pemesanan_tiket\cetak_pesanan_tiket.php

// 1. Sertakan config.php (memuat $conn, helpers, auth_helpers, konstanta, model, controller, dll)
// config.php sudah menjalankan session_start() di awal.
if (!require_once __DIR__ . '/../../config/config.php') {
    http_response_code(503);
    error_log("FATAL: Gagal memuat config.php dari admin/pemesanan_tiket/cetak_pesanan_tiket.php");
    exit("Kesalahan konfigurasi server. Aplikasi tidak dapat memuat file penting.");
}

// Otentikasi Admin (Opsional tapi direkomendasikan jika akses langsung mungkin terjadi)
// Jika file ini hanya bisa diakses dari link di halaman admin yang sudah aman,
// dan tidak ada cara untuk menebak URL dengan ID, ini bisa dilewati.
// Namun, untuk keamanan tambahan:
// require_admin(); // Uncomment jika ingin mewajibkan login admin untuk melihat halaman cetak

// Controller PemesananTiketController sudah dimuat oleh config.php
if (!class_exists('PemesananTiketController')) {
    error_log("FATAL: Class PemesananTiketController tidak ditemukan setelah config.php dimuat (cetak_pesanan_tiket.php).");
    die("Kesalahan sistem: Komponen inti untuk pencetakan tidak tersedia.");
}

$pemesanan_id = isset($_GET['id']) ? filter_var($_GET['id'], FILTER_VALIDATE_INT) : null;

if (!$pemesanan_id || $pemesanan_id <= 0) {
    // Sebaiknya tampilkan pesan yang lebih user-friendly atau redirect jika memungkinkan
    // Untuk halaman cetak, die() mungkin lebih sederhana jika tidak ada sesi flash message
    die("ID Pemesanan tidak valid atau tidak disertakan.");
}

$data_pemesanan_lengkap = null;
$error_message = null;

if (method_exists('PemesananTiketController', 'getDetailPemesananLengkap')) {
    try {
        $data_pemesanan_lengkap = PemesananTiketController::getDetailPemesananLengkap($pemesanan_id);
    } catch (Throwable $e) {
        $error_message = "Terjadi kesalahan saat mengambil data pemesanan.";
        if (defined('IS_DEVELOPMENT') && IS_DEVELOPMENT) {
            $error_message .= " Detail: " . $e->getMessage();
        }
        error_log("Error di cetak_pesanan_tiket.php saat getDetailPemesananLengkap (ID: {$pemesanan_id}): " . $e->getMessage() . "\n" . $e->getTraceAsString());
    }
} else {
    $error_message = "Kesalahan sistem: Fungsi untuk mendapatkan detail pemesanan tidak ditemukan.";
    error_log("FATAL: Method getDetailPemesananLengkap tidak ada di PemesananTiketController (cetak_pesanan_tiket.php).");
}

// Periksa jika ada error atau data tidak lengkap
if ($error_message) {
    die(e($error_message));
}

if (!$data_pemesanan_lengkap || empty($data_pemesanan_lengkap['header'])) {
    die("Detail pemesanan tiket dengan ID " . e($pemesanan_id) . " tidak ditemukan atau data tidak lengkap.");
}

$header = $data_pemesanan_lengkap['header'];
$detail_tiket_items = $data_pemesanan_lengkap['detail_tiket'] ?? [];
$detail_sewa_items = $data_pemesanan_lengkap['detail_sewa'] ?? [];
$pembayaran_info = $data_pemesanan_lengkap['pembayaran'] ?? null;

$nama_situs = defined('NAMA_SITUS') ? NAMA_SITUS : 'Nama Wisata Anda';
$pageTitleCetak = "Bukti Pesanan " . e($header['kode_pemesanan'] ?? $pemesanan_id);

// Asumsikan helper e() dan formatTanggalIndonesia() tersedia dari config.php
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitleCetak) ?> - <?= e($nama_situs) ?></title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            margin: 0;
            padding: 0;
            font-size: 11pt;
            color: #333;
            background-color: #f4f4f4;
            /* Latar belakang untuk area di luar kotak invoice */
        }

        .invoice-box {
            max-width: 800px;
            margin: 20px auto;
            /* Memberi jarak dari atas dan tengah */
            padding: 30px;
            /* Padding lebih besar */
            border: 1px solid #ddd;
            /* Border lebih soft */
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.05);
            /* Shadow lebih soft */
            background-color: #fff;
            /* Latar belakang invoice putih */
            font-size: 14px;
            line-height: 1.6;
            /* Line height lebih nyaman dibaca */
        }

        .invoice-box table {
            width: 100%;
            line-height: inherit;
            text-align: left;
            border-collapse: collapse;
        }

        .invoice-box table td {
            padding: 8px;
            /* Padding cell lebih besar */
            vertical-align: top;
        }

        .invoice-box table tr.top table td {
            padding-bottom: 20px;
        }

        .invoice-box table tr.top table td.title img {
            max-width: 150px;
            /* Batasi lebar logo */
            height: auto;
        }

        .invoice-box table tr.top table td.invoice-details {
            text-align: right;
        }

        .invoice-box table tr.information table td {
            padding-bottom: 30px;
            /* Jarak lebih besar */
        }

        .invoice-box table tr.heading td {
            background: #f8f8f8;
            /* Background heading lebih soft */
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

        .invoice-box table tr.total td {
            /* Target semua td di baris total */
            border-top: 2px solid #eee;
            font-weight: bold;
        }

        .invoice-box table tr.total td:last-child {
            /* Hanya kolom terakhir di baris total */
            text-align: right;
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

        .mt-30 {
            margin-top: 30px;
        }

        .mb-0 {
            margin-bottom: 0;
        }

        .bold {
            font-weight: bold;
        }

        .status-badge {
            display: inline-block;
            /* Agar padding dan margin berlaku benar */
            padding: 5px 10px;
            /* Padding badge lebih besar */
            border-radius: 0.25rem;
            /* Bootstrap-like border radius */
            color: white;
            font-size: 0.85em;
            /* Ukuran font badge sedikit lebih kecil */
            font-weight: 600;
            line-height: 1;
        }

        .status-pending {
            background-color: #ffc107;
            color: #212529;
        }

        .status-waiting_payment {
            background-color: #17a2b8;
            color: white;
        }

        .status-paid,
        .status-success {
            background-color: #28a745;
            color: white;
        }

        .status-confirmed {
            background-color: #007bff;
            color: white;
        }

        /* Biru untuk confirmed */
        .status-completed {
            background-color: #6c757d;
            color: white;
        }

        /* Abu-abu untuk completed */
        .status-cancelled,
        .status-failed,
        .status-expired {
            background-color: #dc3545;
            color: white;
        }

        .footer-print {
            margin-top: 40px;
            /* Jarak lebih besar */
            padding-top: 20px;
            /* Padding atas */
            border-top: 1px solid #eee;
            /* Garis pemisah */
            text-align: center;
            font-size: 10pt;
            color: #777;
        }

        .print-buttons {
            margin-top: 30px;
            text-align: center;
        }

        .print-buttons button {
            padding: 10px 20px;
            margin: 0 5px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
        }

        .print-buttons .btn-primary {
            background-color: #007bff;
            color: white;
        }

        .print-buttons .btn-secondary {
            background-color: #6c757d;
            color: white;
        }

        @media print {
            body {
                margin: 0;
                padding: 0;
                background-color: #fff;
                /* Hilangkan background abu saat print */
                font-size: 10pt;
            }

            .invoice-box {
                max-width: 100%;
                margin: 0;
                padding: 0;
                border: 0;
                box-shadow: none;
                font-size: 10pt;
                /* Sesuaikan lagi jika perlu lebih kecil untuk print */
                line-height: 1.4;
            }

            .print-buttons {
                display: none;
            }

            .footer-print {
                margin-top: 20px;
            }
        }
    </style>
</head>

<body>
    <div class="invoice-box">
        <table cellpadding="0" cellspacing="0">
            <tr class="top">
                <td colspan="4"> <!-- Dibuat colspan 4 agar sesuai dengan jumlah kolom di item -->
                    <table>
                        <tr>
                            <td class="title">
                                <?php if (defined('ASSETS_URL')): // Menggunakan ASSETS_URL dari config 
                                ?>
                                    <img src="<?= e(ASSETS_URL . '/img/logo.png') ?>" alt="Logo <?= e($nama_situs) ?>">
                                <?php else: ?>
                                    <?= e($nama_situs) ?> <!-- Fallback jika logo tidak ada -->
                                <?php endif; ?>
                            </td>
                            <td class="invoice-details">
                                <h2 style="margin-bottom: 0; font-size: 1.5em;"><?= e($nama_situs) ?></h2>
                                Bukti Pemesanan<br>
                                Kode: <strong><?= e($header['kode_pemesanan'] ?? '-') ?></strong><br>
                                Tgl. Pesan: <?= e(formatTanggalIndonesia($header['created_at'] ?? null, true)) ?>
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
                                // Pastikan key 'user_nama_lengkap', 'nama_pemesan_tamu', dll, sesuai dengan yang dikembalikan controller
                                $nama_pemesan_cetak = $header['user_nama_lengkap'] ?? ($header['nama_pemesan_tamu'] ?? 'Tamu');
                                echo e($nama_pemesan_cetak);

                                if (!empty($header['user_email'])) {
                                    echo "<br>" . e($header['user_email']);
                                } elseif (!empty($header['email_pemesan_tamu'])) {
                                    echo "<br>" . e($header['email_pemesan_tamu']);
                                }

                                if (!empty($header['user_no_hp'])) {
                                    echo "<br>Telp: " . e($header['user_no_hp']);
                                } elseif (!empty($header['nohp_pemesan_tamu'])) {
                                    echo "<br>Telp: " . e($header['nohp_pemesan_tamu']);
                                }
                                ?>
                            </td>
                            <td class="text-right">
                                <strong>Tanggal Kunjungan:</strong><br>
                                <?= e(formatTanggalIndonesia($header['tanggal_kunjungan'] ?? null, false, true)) ?>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>

            <tr class="heading">
                <td>Deskripsi Item</td>
                <td class="text-center">Jumlah</td>
                <td class="text-right">Harga Satuan</td>
                <td class="text-right">Subtotal</td>
            </tr>

            <?php if (!empty($detail_tiket_items)): ?>
                <?php foreach ($detail_tiket_items as $item): ?>
                    <tr class="item">
                        <td>
                            <?= e($item['nama_layanan_display'] ?? $item['jenis_tiket_nama'] ?? 'Tiket') ?>
                            <?= isset($item['tipe_hari']) ? ' (' . e($item['tipe_hari']) . ')' : '' ?>
                            <?php if (!empty($item['nama_wisata_terkait'])): ?>
                                <br><small style="color:#555;font-size:0.9em;">Destinasi: <?= e($item['nama_wisata_terkait']) ?></small>
                            <?php endif; ?>
                        </td>
                        <td class="text-center"><?= e($item['jumlah'] ?? 0) ?></td>
                        <td class="text-right"><?= formatRupiah($item['harga_satuan_saat_pesan'] ?? 0) ?></td>
                        <td class="text-right"><?= formatRupiah($item['subtotal_item'] ?? ($item['jumlah'] * $item['harga_satuan_saat_pesan'])) ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>

            <?php if (!empty($detail_sewa_items)): ?>
                <tr class="item"> <!-- Bisa juga heading terpisah -->
                    <td colspan="4" style="padding-top: 15px; font-weight:bold; border-bottom: 1px solid #eee;">Detail Sewa Alat:</td>
                </tr>
                <?php foreach ($detail_sewa_items as $idx_sewa => $item_sewa): ?>
                    <tr class="item <?= ($idx_sewa === count($detail_sewa_items) - 1 && empty($header['catatan_umum_pemesanan'])) ? 'last' : '' ?>">
                        <td>
                            <?= e($item_sewa['nama_alat'] ?? $item_sewa['nama_item'] ?? 'Alat Sewa') ?>
                            <br><small style="color:#555;font-size:0.9em;">
                                Periode: <?= e(formatTanggalIndonesia($item_sewa['tanggal_mulai_sewa'] ?? null, false)) ?> - <?= e(formatTanggalIndonesia($item_sewa['tanggal_akhir_sewa_rencana'] ?? null, false)) ?>
                            </small>
                        </td>
                        <td class="text-center"><?= e($item_sewa['jumlah'] ?? 0) ?></td>
                        <td class="text-right"><?= formatRupiah($item_sewa['harga_satuan_saat_pesan'] ?? 0) ?></td>
                        <td class="text-right"><?= formatRupiah($item_sewa['total_harga_item'] ?? 0) ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>

            <?php if (!empty($header['catatan_umum_pemesanan'])): ?>
                <tr class="item last"> <!-- Pastikan ini item terakhir jika ada -->
                    <td colspan="4" style="padding-top: 15px;">
                        <strong>Catatan Pemesan:</strong><br>
                        <p style="white-space: pre-wrap; margin-top:5px; font-size:0.9em; color:#444;"><?= nl2br(e($header['catatan_umum_pemesanan'])) ?></p>
                    </td>
                </tr>
            <?php endif; ?>


            <tr class="total">
                <td colspan="3" class="text-right bold">Total Keseluruhan:</td>
                <td class="text-right bold"><?= formatRupiah($header['total_harga_akhir'] ?? 0) ?></td>
            </tr>

            <tr>
                <td colspan="4" class="mt-30">
                    Status Pemesanan:
                    <?php
                    // Gunakan kolom 'status' dari tabel pemesanan_tiket yang ada di $header
                    $status_raw_cetak = $header['status'] ?? 'unknown';
                    $status_cetak = strtolower($status_raw_cetak);
                    $status_class_cetak = 'status-pending'; // default

                    if ($status_cetak == 'pending') $status_class_cetak = 'status-pending';
                    elseif ($status_cetak == 'waiting_payment') $status_class_cetak = 'status-waiting_payment';
                    elseif ($status_cetak == 'paid') $status_class_cetak = 'status-paid';
                    elseif ($status_cetak == 'confirmed') $status_class_cetak = 'status-confirmed';
                    elseif ($status_cetak == 'completed') $status_class_cetak = 'status-completed';
                    elseif (in_array($status_cetak, ['cancelled', 'failed', 'expired', 'refunded'])) $status_class_cetak = 'status-cancelled';
                    ?>
                    <span class="status-badge <?= $status_class_cetak ?>"><?= e(ucfirst(str_replace('_', ' ', $status_raw_cetak))) ?></span>
                </td>
            </tr>

            <?php if ($pembayaran_info && isset($pembayaran_info['status_pembayaran']) && strtolower($pembayaran_info['status_pembayaran']) === 'success' && !empty($pembayaran_info['metode_pembayaran'])): ?>
                <tr>
                    <td colspan="4" style="padding-top:10px;">Metode Pembayaran: <?= e($pembayaran_info['metode_pembayaran']) ?></td>
                </tr>
            <?php endif; ?>
        </table>

        <div class="footer-print">
            Ini adalah bukti pemesanan yang sah. Harap tunjukkan bukti ini saat kedatangan atau saat pengambilan alat sewa.
            <br>Terima kasih telah melakukan pemesanan di <?= e($nama_situs) ?>.
            <br><?= e($nama_situs) ?> © <?= date('Y') ?>
        </div>
    </div>
    <div class="print-buttons">
        <button onclick="window.print()" class="btn-primary">Cetak Bukti Pemesanan</button>
        <button onclick="window.close()" class="btn-secondary">Tutup Jendela</button>
    </div>
</body>

</html>