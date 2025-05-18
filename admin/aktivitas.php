<?php
// File: C:\xampp\htdocs\Cilengkrang-Web-Wisata\admin\aktivitas.php

if (ob_get_level()) {
    ob_end_clean();
}
header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

require_once __DIR__ . '/../config/config.php';

if (!function_exists('is_admin') || !is_admin()) {
    http_response_code(403);
    echo json_encode(['error' => true, 'message' => 'Akses ditolak.']);
    exit;
}

$aktivitas_list = [];
$limit_per_jenis = 3; // Ambil beberapa item dari setiap jenis untuk variasi
$total_limit_akhir = 7; // Total aktivitas yang ditampilkan

if (!function_exists('tambah_ke_aktivitas_ajax')) {
    function tambah_ke_aktivitas_ajax(&$list, $timestamp_str, $ikon, $teks, $detail_link = '#')
    {
        $dt = null;
        if (!empty($timestamp_str) && $timestamp_str !== '0000-00-00 00:00:00' && $timestamp_str !== '0000-00-00') {
            try {
                $app_timezone_str = date_default_timezone_get();
                $app_timezone = new DateTimeZone($app_timezone_str);
                $dt = new DateTime($timestamp_str, $app_timezone);
            } catch (Exception $e) {
                error_log("Error parsing timestamp '{$timestamp_str}' di tambah_ke_aktivitas_ajax: " . $e->getMessage());
                $app_timezone_str_fallback = date_default_timezone_get();
                $dt = new DateTime("now", new DateTimeZone($app_timezone_str_fallback));
            }
        } else {
            $app_timezone_str_fallback = date_default_timezone_get();
            $dt = new DateTime("now", new DateTimeZone($app_timezone_str_fallback));
        }
        $list[] = [
            'timestamp_obj' => $dt,
            'ikon' => $ikon,
            'teks' => $teks,
            'link' => $detail_link,
            'waktu_detail' => function_exists('formatTanggalIndonesia') ? formatTanggalIndonesia($dt, true) : $dt->format('Y-m-d H:i'),
            'waktu_singkat' => function_exists('time_elapsed_string') ? time_elapsed_string($dt->format('Y-m-d H:i:s')) : 'Baru saja',
        ];
    }
}

// 1. Pemesanan Tiket
if (class_exists('PemesananTiket') && method_exists('PemesananTiket', 'getAll')) {
    // Asumsi PemesananTiket::getAll() bisa menerima string order by dan limit
    $pemesanan_tiket_terbaru = PemesananTiket::getAll("pt.updated_at DESC LIMIT {$limit_per_jenis}");
    foreach ($pemesanan_tiket_terbaru as $pt) {
        $status_pt = strtolower($pt['status'] ?? 'baru');
        $link_pt = ADMIN_URL . 'pemesanan_tiket/detail_pemesanan.php?id=' . e($pt['id'] ?? '');
        $teks_pt = "Pemesanan Tiket #" . e($pt['kode_pemesanan'] ?? 'N/A') . " ";
        $ikon_pt = 'fas fa-ticket-alt';
        if (isset($pt['created_at']) && isset($pt['updated_at']) && $pt['created_at'] == $pt['updated_at']) {
            $teks_pt .= "baru dibuat.";
            $ikon_pt .= ' text-primary';
        } elseif (in_array($status_pt, PemesananTiket::SUCCESSFUL_PAYMENT_STATUSES)) {
            $teks_pt .= "telah dikonfirmasi/lunas.";
            $ikon_pt .= ' text-success';
        } elseif ($status_pt === 'awaiting_confirmation') {
            $teks_pt .= "menunggu konfirmasi.";
            $ikon_pt .= ' text-warning';
        } elseif (in_array($status_pt, ['cancelled', 'expired', 'failed'])) {
            $teks_pt .= "status menjadi '" . e(ucfirst($status_pt)) . "'.";
            $ikon_pt .= ' text-danger';
        } else {
            $teks_pt .= "statusnya '" . e(ucfirst($status_pt)) . "'.";
            $ikon_pt .= ' text-info';
        }
        tambah_ke_aktivitas_ajax($aktivitas_list, $pt['updated_at'] ?? $pt['created_at'] ?? date('Y-m-d H:i:s'), $ikon_pt, $teks_pt, $link_pt);
    }
}

// 2. Pemesanan Sewa Alat
if (class_exists('PemesananSewaAlat') && method_exists('PemesananSewaAlat', 'getAll')) {
    // PemesananSewaAlat::getAll() di model Anda tidak menerima parameter. Kita limit di PHP.
    $semua_pemesanan_sewa = PemesananSewaAlat::getAll(); // Ini mengambil semua, lalu kita slice
    $pemesanan_sewa_terbaru = array_slice($semua_pemesanan_sewa, 0, $limit_per_jenis);
    foreach ($pemesanan_sewa_terbaru as $psa) {
        $status_psa = strtolower($psa['status_item_sewa'] ?? 'baru');
        $link_psa = ADMIN_URL . 'pemesanan_sewa/detail_pemesanan_sewa.php?id=' . e($psa['id'] ?? '');
        $teks_psa = "Pemesanan Sewa #" . e($psa['id'] ?? 'N/A') . " (" . e(excerpt($psa['nama_alat'] ?? 'Alat', 15)) . ") ";
        $ikon_psa = 'fas fa-box-open';
        if (isset($psa['created_at']) && isset($psa['updated_at']) && $psa['created_at'] == $psa['updated_at']) {
            $teks_psa .= "baru dibuat.";
            $ikon_psa .= ' text-primary';
        } elseif (in_array($status_psa, ['diambil', 'dikembalikan'])) {
            $teks_psa .= "status menjadi '" . e(ucfirst($status_psa)) . "'.";
            $ikon_psa .= ' text-success';
        } elseif (in_array($status_psa, ['hilang', 'rusak', 'dibatalkan_sewa', 'dibatalkan'])) {
            $teks_psa .= "status menjadi '" . e(ucfirst($status_psa)) . "'.";
            $ikon_psa .= ' text-danger';
        } else {
            $teks_psa .= "statusnya '" . e(ucfirst($status_psa)) . "'.";
            $ikon_psa .= ' text-info';
        }
        tambah_ke_aktivitas_ajax($aktivitas_list, $psa['updated_at'] ?? $psa['created_at'] ?? date('Y-m-d H:i:s'), $ikon_psa, $teks_psa, $link_psa);
    }
}

// 3. Artikel Baru
if (class_exists('Artikel') && method_exists('Artikel', 'getLatest')) {
    $artikel_terbaru = Artikel::getLatest($limit_per_jenis);
    foreach ($artikel_terbaru as $artikel) {
        $link_artikel = ADMIN_URL . 'artikel/edit_artikel.php?id=' . e($artikel['id'] ?? '');
        tambah_ke_aktivitas_ajax($aktivitas_list, $artikel['created_at'] ?? date('Y-m-d H:i:s'), 'fas fa-newspaper text-info', 'Artikel baru "' . e(excerpt($artikel['judul'] ?? 'Tanpa Judul', 30)) . '" ditambahkan.', $link_artikel);
    }
}

// 4. Pembayaran yang signifikan
if (class_exists('Pembayaran') && method_exists('Pembayaran', 'findAllWithKodePemesanan')) {
    $pembayaran_updates = Pembayaran::findAllWithKodePemesanan("p.updated_at DESC LIMIT {$limit_per_jenis}");
    foreach ($pembayaran_updates as $p) {
        $status_pembayaran_lower = strtolower($p['status_pembayaran'] ?? '');
        $link_pembayaran = ADMIN_URL . 'pembayaran/detail_pembayaran.php?id=' . e($p['id'] ?? '');
        $teks_pembayaran = '';
        $ikon_pembayaran = '';
        if (in_array($status_pembayaran_lower, Pembayaran::SUCCESSFUL_PAYMENT_STATUSES)) {
            $teks_pembayaran = 'Pembayaran lunas untuk pesanan #' . e($p['kode_pemesanan_tiket'] ?? $p['kode_pemesanan'] ?? 'N/A') . '.';
            $ikon_pembayaran = 'fas fa-check-circle text-success';
        } elseif ($status_pembayaran_lower === 'awaiting_confirmation') {
            $teks_pembayaran = 'Konfirmasi pembayaran diterima untuk pesanan #' . e($p['kode_pemesanan_tiket'] ?? $p['kode_pemesanan'] ?? 'N/A') . '.';
            $ikon_pembayaran = 'fas fa-hourglass-half text-warning';
        }
        if (!empty($teks_pembayaran)) {
            tambah_ke_aktivitas_ajax($aktivitas_list, $p['updated_at'] ?? $p['created_at'] ?? date('Y-m-d H:i:s'), $ikon_pembayaran, $teks_pembayaran, $link_pembayaran);
        }
    }
}

// 5. Pengguna Baru Terdaftar
// ASUMSI: Anda akan membuat metode User::getRecent($limit) atau User::getAll(['orderBy' => 'created_at DESC', 'limit' => $limit])
if (class_exists('User') && method_exists('User', 'getAll')) {
    // Sesuaikan pemanggilan ini dengan metode yang ada di Model User Anda
    $user_baru_list = User::getAll(['limit' => $limit_per_jenis, 'orderBy' => 'created_at DESC']);
    if (is_array($user_baru_list)) { // Pastikan hasilnya array
        foreach ($user_baru_list as $user) {
            $link_user = ADMIN_URL . 'users/edit_user.php?id=' . e($user['id'] ?? '');
            tambah_ke_aktivitas_ajax($aktivitas_list, $user['created_at'] ?? date('Y-m-d H:i:s'), 'fas fa-user-plus text-primary', 'Pengguna baru "' . e($user['nama_lengkap'] ?? $user['nama'] ?? 'N/A') . '" telah terdaftar.', $link_user);
        }
    }
}

// 6. Feedback Baru Diterima
// ASUMSI: Anda akan membuat metode Feedback::getRecent($limit) atau menyesuaikan Feedback::getAll()
if (class_exists('Feedback') && method_exists('Feedback', 'getAll')) {
    // Sesuaikan pemanggilan ini
    $semua_feedback = Feedback::getAll(); // Model Anda tidak ada limit
    $feedback_list = array_slice($semua_feedback, 0, $limit_per_jenis); // Limit di PHP
    foreach ($feedback_list as $fb) {
        $nama_pengirim_fb = $fb['user_nama'] ?? 'Pengunjung';
        $link_feedback = ADMIN_URL . 'feedback/kelola_feedback.php#feedback-' . e($fb['id'] ?? '');
        tambah_ke_aktivitas_ajax($aktivitas_list, $fb['created_at'] ?? date('Y-m-d H:i:s'), 'fas fa-comments text-warning', 'Feedback baru diterima dari ' . e($nama_pengirim_fb) . '.', $link_feedback);
    }
}

// 7. Pesan Kontak Baru
// ASUMSI: Anda akan membuat metode Contact::getRecent($limit) atau menyesuaikan Contact::getAll()
if (class_exists('Contact') && method_exists('Contact', 'getAll')) {
    // Sesuaikan pemanggilan ini
    $semua_kontak = Contact::getAll(); // Model Anda tidak ada limit
    $kontak_list = array_slice($semua_kontak, 0, $limit_per_jenis); // Limit di PHP
    foreach ($kontak_list as $kontak) {
        $link_kontak = ADMIN_URL . 'contact/kelola_contact.php#contact-' . e($kontak['id'] ?? '');
        tambah_ke_aktivitas_ajax($aktivitas_list, $kontak['created_at'] ?? date('Y-m-d H:i:s'), 'fas fa-envelope-open-text text-info', 'Pesan kontak baru dari ' . e($kontak['nama'] ?? 'Pengirim') . '.', $link_kontak);
    }
}


if (!empty($aktivitas_list)) {
    usort($aktivitas_list, function ($a, $b) {
        if (!($a['timestamp_obj'] instanceof DateTimeInterface) && !($b['timestamp_obj'] instanceof DateTimeInterface)) return 0;
        if (!($a['timestamp_obj'] instanceof DateTimeInterface)) return 1;
        if (!($b['timestamp_obj'] instanceof DateTimeInterface)) return -1;
        if ($a['timestamp_obj'] == $b['timestamp_obj']) return 0;
        return ($a['timestamp_obj'] < $b['timestamp_obj']) ? 1 : -1;
    });
}

$aktivitas_final = array_slice($aktivitas_list, 0, $total_limit_akhir);
foreach ($aktivitas_final as $key => $item) {
    unset($aktivitas_final[$key]['timestamp_obj']);
}

echo json_encode($aktivitas_final);
exit;
