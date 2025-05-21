<?php
// File: C:\xampp\htdocs\Cilengkrang-Web-Wisata\user\cetak_tiket.php

// 1. Sertakan config.php pertama kali
if (!@require_once __DIR__ . '/../config/config.php') {
    http_response_code(503);
    error_log("FATAL: Gagal memuat config.php dari user/cetak_tiket.php");
    exit("Kesalahan konfigurasi server.");
}

// 2. Pastikan hanya pengguna yang login yang bisa akses
if (!function_exists('require_login')) {
    error_log("KRITIS cetak_tiket.php: Fungsi require_login() tidak ditemukan.");
    if (session_status() == PHP_SESSION_NONE) session_start();
    if (!isset($_SESSION['user_id'])) {
        // Jangan set flash message di sini karena akan hilang setelah redirect header
        $login_url = (defined('AUTH_URL') ? AUTH_URL : (defined('BASE_URL') ? BASE_URL . 'auth/' : '../auth/')) . 'login.php?redirect_to=' . rawurlencode($_SERVER['REQUEST_URI'] ?? '');
        header('Location: ' . $login_url);
        exit;
    }
} else {
    require_login();
}

// 3. Ambil ID Pemesanan dari URL
// Pastikan parameter adalah 'id' atau 'kode' sesuai dengan link yang Anda buat
$pemesanan_identifier = null;
$pemesanan_id = null; // Akan diisi jika identifier adalah ID
$kode_pemesanan_url = null; // Akan diisi jika identifier adalah kode

if (isset($_GET['id'])) {
    $pemesanan_id = filter_var($_GET['id'], FILTER_VALIDATE_INT);
    $pemesanan_identifier = $pemesanan_id;
} elseif (isset($_GET['kode'])) { // Tambahkan opsi untuk mengambil berdasarkan kode jika diperlukan
    $kode_pemesanan_url = trim(input('kode', null, 'GET')); // Gunakan fungsi input jika ada
    $pemesanan_identifier = $kode_pemesanan_url;
}

if (empty($pemesanan_identifier) || ($pemesanan_id !== null && $pemesanan_id <= 0)) {
    if (function_exists('set_flash_message')) set_flash_message('danger', 'ID atau Kode Pemesanan tidak valid untuk mencetak tiket.');
    if (function_exists('redirect')) redirect(USER_URL . 'riwayat_pemesanan.php');
    else header('Location: ' . (defined('USER_URL') ? USER_URL : './') . 'riwayat_pemesanan.php');
    exit;
}

// 4. Pastikan Controller dan Model yang dibutuhkan ada
if (
    !class_exists('PemesananTiketController') ||
    !method_exists('PemesananTiketController', 'getDetailPemesananLengkap') || // Untuk mengambil berdasarkan ID
    !method_exists('PemesananTiketController', 'getPemesananLengkapByKode') || // Untuk mengambil berdasarkan KODE
    !class_exists('User') || !method_exists('User', 'findById') ||
    !class_exists('PengaturanSitus') || !method_exists('PengaturanSitus', 'getPengaturan')
) {
    error_log("KRITIS cetak_tiket.php: Controller/Model penting tidak ditemukan.");
    die("Kesalahan sistem: Komponen untuk mencetak tiket tidak tersedia (CTCMP_NF). Silakan coba lagi nanti atau hubungi dukungan.");
}

// 5. Ambil data pemesanan lengkap
$data_pemesanan_lengkap = null;
if ($pemesanan_id) {
    $data_pemesanan_lengkap = PemesananTiketController::getDetailPemesananLengkap($pemesanan_id);
} elseif ($kode_pemesanan_url) {
    $data_pemesanan_lengkap = PemesananTiketController::getPemesananLengkapByKode($kode_pemesanan_url);
    if ($data_pemesanan_lengkap && isset($data_pemesanan_lengkap['header']['id'])) {
        $pemesanan_id = (int)$data_pemesanan_lengkap['header']['id']; // Set $pemesanan_id jika ditemukan via kode
    }
}


// 6. Validasi Kepemilikan Tiket dan Status
if (!$data_pemesanan_lengkap || empty($data_pemesanan_lengkap['header'])) {
    if (function_exists('set_flash_message')) set_flash_message('danger', 'Data pemesanan tiket tidak ditemukan.');
    if (function_exists('redirect')) redirect(USER_URL . 'riwayat_pemesanan.php');
    exit;
}

$header_pemesanan = $data_pemesanan_lengkap['header'];
$detail_tiket_items = $data_pemesanan_lengkap['detail_tiket'] ?? [];
$detail_sewa_items = $data_pemesanan_lengkap['detail_sewa'] ?? [];
$info_pembayaran_terkait = $data_pemesanan_lengkap['pembayaran'] ?? null;

$current_user_id = get_current_user_id();
// Untuk pemesanan tamu (user_id null), lewati pengecekan kepemilikan ini.
// Anda mungkin perlu mekanisme validasi lain untuk tamu (misal, token unik di URL yang dikirim ke email mereka).
if (isset($header_pemesanan['user_id']) && $header_pemesanan['user_id'] != null && $header_pemesanan['user_id'] != $current_user_id) {
    if (function_exists('set_flash_message')) set_flash_message('danger', 'Anda tidak memiliki izin untuk melihat atau mencetak tiket ini.');
    if (function_exists('redirect')) redirect(USER_URL . 'riwayat_pemesanan.php');
    exit;
}

$status_pemesanan_valid_untuk_cetak = defined('PemesananTiket::SUCCESSFUL_PAYMENT_STATUSES')
    ? PemesananTiket::SUCCESSFUL_PAYMENT_STATUSES
    : ['paid', 'confirmed', 'success', 'completed'];

if (!in_array(strtolower($header_pemesanan['status'] ?? ''), $status_pemesanan_valid_untuk_cetak)) {
    $status_saat_ini = e(ucfirst($header_pemesanan['status'] ?? 'Tidak diketahui'));
    if (function_exists('set_flash_message')) set_flash_message('warning', "Tiket untuk pemesanan ini belum dapat dicetak. Status pemesanan saat ini: {$status_saat_ini}. Pastikan pembayaran telah lunas dan dikonfirmasi.");
    // Redirect ke detail pemesanan yang menggunakan ID, bukan kode, jika $pemesanan_id sudah ada
    $redirect_detail_url = $pemesanan_id ? USER_URL . 'detail_pemesanan.php?id=' . $pemesanan_id : USER_URL . 'riwayat_pemesanan.php';
    if (function_exists('redirect')) redirect($redirect_detail_url);
    exit;
}

// Ambil pengaturan situs
global $pengaturan_situs_global;
$nama_situs = $pengaturan_situs_global['nama_situs'] ?? 'Lembah Cilengkrang';
$logo_situs_filename = $pengaturan_situs_global['logo_situs'] ?? null;
$email_kontak_situs = $pengaturan_situs_global['email_kontak_situs'] ?? '-';
$telepon_kontak_situs = $pengaturan_situs_global['telepon_kontak_situs'] ?? '-';
$logo_situs_url = null;

if ($logo_situs_filename && defined('UPLOADS_SITUS_URL') && defined('UPLOADS_SITUS_PATH')) {
    $logo_path_check = rtrim(UPLOADS_SITUS_PATH, '/\\') . DIRECTORY_SEPARATOR . basename($logo_situs_filename);
    if (file_exists($logo_path_check) && is_file($logo_path_check)) {
        $logo_situs_url = rtrim(UPLOADS_SITUS_URL, '/') . '/' . rawurlencode(basename($logo_situs_filename));
    }
}
if (!$logo_situs_url && defined('ASSETS_URL')) {
    $default_logo_path = ROOT_PATH . '/public/img/logo.png';
    if (file_exists($default_logo_path)) {
        $logo_situs_url = ASSETS_URL . 'img/logo.png';
    }
}

$pageTitleCetak = "E-Tiket: " . e($header_pemesanan['kode_pemesanan'] ?? 'Tidak Diketahui') . " - " . e($nama_situs);
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title><?= e($pageTitleCetak) ?></title>
    <link href="https://fonts.googleapis.com/css?family=Libre+Barcode+39+Text&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.0/css/all.min.css">
    <style>
        body {
            font-family: 'Arial', sans-serif;
            margin: 0;
            padding: 0;
            font-size: 10pt;
            color: #333;
            background-color: #fff;
        }

        .ticket-wrapper {
            max-width: 820px;
            margin: 10px auto;
        }

        .ticket-container {
            border: 2px solid #333;
            margin-bottom: 20px;
            background-color: #fff;
            page-break-inside: avoid;
        }

        .ticket-header {
            background-color: #f0f0f0;
            padding: 15px;
            border-bottom: 2px solid #333;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .ticket-header .logo-container {
            display: flex;
            align-items: center;
        }

        .ticket-header img.logo {
            max-height: 50px;
            margin-right: 15px;
        }

        .ticket-header .site-title {
            font-size: 1.5em;
            font-weight: bold;
            color: #333;
            margin: 0;
        }

        .ticket-header .ticket-id {
            font-size: 1.1em;
            font-weight: bold;
            text-align: right;
        }

        .ticket-body {
            padding: 20px;
        }

        .section {
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px dashed #ccc;
        }

        .section:last-of-type {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }

        .section-title {
            font-size: 1.1em;
            font-weight: bold;
            color: #000;
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .info-table {
            width: 100%;
            margin-bottom: 10px;
        }

        .info-table td {
            padding: 3px 0;
            vertical-align: top;
        }

        .info-table td.label {
            font-weight: 600;
            width: 150px;
            color: #555;
        }

        .info-table td.value {
            font-weight: 500;
        }

        .info-table td.value.important {
            font-weight: bold;
            font-size: 1.1em;
        }

        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 5px;
            font-size: 0.95em;
        }

        .items-table th,
        .items-table td {
            border: 1px solid #ccc;
            padding: 6px 8px;
            text-align: left;
        }

        .items-table th {
            background-color: #f2f2f2;
            font-weight: bold;
        }

        .items-table td.qty {
            text-align: center;
        }

        .items-table td.price {
            text-align: right;
        }

        .qr-code-area {
            text-align: center;
            margin-top: 15px;
        }

        .qr-code-area img {
            max-width: 150px;
            border: 1px solid #ccc;
            padding: 5px;
        }

        .barcode-text {
            font-family: 'Libre Barcode 39 Text', cursive;
            font-size: 38px;
            display: block;
            text-align: center;
            margin: 10px 0 5px 0;
            letter-spacing: 2px;
        }

        .ticket-instructions {
            font-size: 0.85em;
            color: #444;
            margin-top: 15px;
            padding-left: 15px;
        }

        .ticket-instructions li {
            margin-bottom: 4px;
        }

        .ticket-footer-info {
            text-align: center;
            padding: 15px;
            font-size: 0.8em;
            color: #666;
            border-top: 2px solid #333;
            background-color: #f0f0f0;
        }

        .print-controls {
            padding: 15px;
            text-align: center;
            background-color: #e9ecef;
        }

        .print-btn {
            padding: 8px 20px;
            font-size: 1em;
            color: #fff;
            background-color: #007bff;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            margin: 0 5px;
        }

        .print-btn:hover {
            background-color: #0056b3;
        }

        .btn-back {
            background-color: #6c757d;
        }

        .btn-back:hover {
            background-color: #5a6268;
        }

        @media print {
            body {
                margin: 0.5cm;
                font-size: 9pt;
                background-color: #fff;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            .ticket-wrapper {
                margin: 0;
            }

            .ticket-container {
                border: 1px solid #000;
                box-shadow: none;
            }

            .print-controls {
                display: none !important;
            }

            .ticket-header,
            .ticket-items th,
            .ticket-footer-info {
                background-color: #f0f0f0 !important;
            }

            .qr-code-area img {
                border: 1px solid #000 !important;
            }
        }
    </style>
</head>

<body>
    <div class="print-controls">
        <button onclick="window.print()" class="print-btn"><i class="fas fa-print"></i> Cetak E-Tiket</button>
        <button onclick="window.location.href='<?= e($pemesanan_id ? (USER_URL . 'detail_pemesanan.php?id=' . $pemesanan_id) : USER_URL . 'riwayat_pemesanan.php') ?>'" class="print-btn btn-back">Kembali</button>
    </div>

    <div class="ticket-wrapper">
        <div class="ticket-container">
            <div class="ticket-header">
                <div class="logo-container">
                    <?php if ($logo_situs_url): ?>
                        <img src="<?= e($logo_situs_url) ?>" alt="Logo <?= e($nama_situs) ?>" class="logo">
                    <?php endif; ?>
                    <h1 class="site-title"><?= e(strtoupper($nama_situs)) ?></h1>
                </div>
                <div class="ticket-id">
                    E-TIKET<br>
                    <span class="barcode"><?= e($header_pemesanan['kode_pemesanan'] ?? 'N/A') ?></span>
                </div>
            </div>

            <div class="ticket-body">
                <div class="section">
                    <div class="section-title">Detail Pemesan & Kunjungan</div>
                    <table class="info-table">
                        <tr>
                            <td class="label">Nama Pemesan:</td>
                            <td class="value"><?= e(isset($header_pemesanan['user_id']) && $header_pemesanan['user_id'] ? ($header_pemesanan['user_nama_lengkap'] ?? $header_pemesanan['user_nama'] ?? 'N/A') : ($header_pemesanan['nama_pemesan_tamu'] ?? 'Tamu')) ?></td>
                        </tr>
                        <tr>
                            <td class="label">Email:</td>
                            <td class="value"><?= e(isset($header_pemesanan['user_id']) && $header_pemesanan['user_id'] ? ($header_pemesanan['user_email'] ?? '-') : ($header_pemesanan['email_pemesan_tamu'] ?? '-')) ?></td>
                        </tr>
                        <tr>
                            <td class="label">No. HP:</td>
                            <td class="value"><?= e(isset($header_pemesanan['user_id']) && $header_pemesanan['user_id'] ? ($header_pemesanan['user_no_hp'] ?? '-') : ($header_pemesanan['nohp_pemesan_tamu'] ?? '-')) ?></td>
                        </tr>
                        <tr>
                            <td class="label">Tanggal Kunjungan:</td>
                            <td class="value important"><?= e(formatTanggalIndonesia($header_pemesanan['tanggal_kunjungan'] ?? null, false, true)) ?></td>
                        </tr>
                        <tr>
                            <td class="label">Status Pemesanan:</td>
                            <td class="value"><?= getStatusBadgeClassHTML($header_pemesanan['status'] ?? 'tidak diketahui', 'Tidak Diketahui') ?></td>
                        </tr>
                        <tr>
                            <td class="label">Tanggal Pemesanan:</td>
                            <td class="value"><?= e(formatTanggalIndonesia($header_pemesanan['created_at'] ?? null, true, false)) ?></td>
                        </tr>
                    </table>
                </div>

                <?php if (!empty($detail_tiket_items)): ?>
                    <div class="section">
                        <div class="section-title">Rincian Tiket Masuk</div>
                        <table class="items-table">
                            <thead>
                                <tr>
                                    <th>Jenis Tiket/Layanan</th>
                                    <th class="qty">Jumlah</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $total_pengunjung = 0;
                                foreach ($detail_tiket_items as $item_t):
                                    $total_pengunjung += (int)($item_t['jumlah'] ?? 0);
                                ?>
                                    <tr>
                                        <td>
                                            <?= e($item_t['nama_layanan_display'] ?? 'Tiket Tidak Diketahui') ?>
                                            <?php if (!empty($item_t['nama_wisata_terkait'])): ?>
                                                <br><small style="color:#555;">(Area: <?= e($item_t['nama_wisata_terkait']) ?>)</small>
                                            <?php endif; ?>
                                            <?php if (!empty($item_t['deskripsi_jenis_tiket'])): ?>
                                                <br><small style="color:#777; font-size:0.8em;"><?= e(excerpt($item_t['deskripsi_jenis_tiket'], 50)) ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td class="qty"><?= e($item_t['jumlah'] ?? 0) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <?php if ($total_pengunjung > 0): ?>
                            <p style="text-align:right; margin-top:10px; font-weight:bold;">Total Pengunjung (dari tiket): <?= $total_pengunjung ?> Orang</p>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($detail_sewa_items)): ?>
                    <div class="section">
                        <div class="section-title">Alat Sewa Tambahan</div>
                        <table class="items-table">
                            <thead>
                                <tr>
                                    <th>Nama Alat</th>
                                    <th class="qty">Jumlah</th>
                                    <th>Durasi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($detail_sewa_items as $item_s): ?>
                                    <tr>
                                        <td><?= e($item_s['nama_alat'] ?? 'Alat Tidak Diketahui') ?></td>
                                        <td class="qty"><?= e($item_s['jumlah'] ?? 0) ?></td>
                                        <td>
                                            Mulai: <?= e(formatTanggalIndonesia($item_s['tanggal_mulai_sewa'] ?? null, true, false)) ?><br>
                                            Selesai: <?= e(formatTanggalIndonesia($item_s['tanggal_akhir_sewa_rencana'] ?? null, true, false)) ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>

            <div class="ticket-footer-info">
                Harap simpan E-Tiket ini dengan baik. Tiket hanya berlaku untuk satu kali masuk sesuai tanggal kunjungan.
                <br><?= e($nama_situs) ?> | Email: <?= e($email_kontak_situs) ?> | Telp: <?= e($telepon_kontak_situs) ?>
            </div>
        </div>
    </div>

</body>

</html>