<?php
// File: C:\xampp\htdocs\Cilengkrang-Web-Wisata\controllers\PemesananTiketController.php

/**
 * PemesananTiketController
 * Bertanggung jawab untuk logika bisnis terkait pemesanan tiket.
 * 
 * PENTING:
 * - Diasumsikan config.php sudah memuat semua file Model yang diperlukan
 *   DAN sudah memanggil metode statis `ModelName::setDbConnection($conn)` atau `ModelName::init()` untuk setiap Model.
 * - Fungsi helper seperti set_flash_message(), e(), formatTanggalIndonesia() juga diasumsikan tersedia dari config.php.
 */

// Pemuatan model idealnya dilakukan oleh config.php atau autoloader.
// Jika tidak, uncomment require_once di bawah ini.
/*
if (!class_exists('PemesananTiket')) require_once __DIR__ . '/../models/PemesananTiket.php';
if (!class_exists('DetailPemesananTiket')) require_once __DIR__ . '/../models/DetailPemesananTiket.php';
if (!class_exists('PemesananSewaAlat')) require_once __DIR__ . '/../models/PemesananSewaAlat.php';
if (!class_exists('Pembayaran')) require_once __DIR__ . '/../models/Pembayaran.php';
if (!class_exists('JenisTiket')) require_once __DIR__ . '/../models/JenisTiket.php';
if (!class_exists('SewaAlat')) require_once __DIR__ . '/../models/SewaAlat.php';
if (!class_exists('JadwalKetersediaanTiket')) require_once __DIR__ . '/../models/JadwalKetersediaanTiket.php';
*/

class PemesananTiketController
{
    /**
     * Membuat pemesanan tiket baru beserta detail item tiket dan item sewa.
     * Melakukan validasi ketersediaan dan mengelola transaksi database.
     *
     * @param array $data_pemesan Informasi pemesan.
     * @param array $items_tiket Array berisi item tiket yang dipesan.
     * @param array $items_sewa Array berisi item sewa alat yang dipesan.
     * @return string|false Kode pemesanan unik jika berhasil, false jika gagal.
     */
    public static function prosesPemesananLengkap($data_pemesan, $items_tiket = [], $items_sewa = [])
    {
        // Pengecekan ketersediaan semua kelas Model yang dibutuhkan
        $required_models_for_process = [
            'PemesananTiket',
            'DetailPemesananTiket',
            'PemesananSewaAlat',
            'Pembayaran',
            'JenisTiket',
            'SewaAlat',
            'JadwalKetersediaanTiket'
        ];
        foreach ($required_models_for_process as $model_chk) {
            if (!class_exists($model_chk)) {
                error_log("PemesananTiketController::prosesPemesananLengkap() - Model {$model_chk} tidak ditemukan/dimuat.");
                if (function_exists('set_flash_message')) set_flash_message('danger', 'Kesalahan sistem: Komponen pemesanan inti tidak lengkap.');
                return false;
            }
        }

        // $conn global diperlukan untuk mysqli_begin_transaction, commit, rollback
        global $conn;
        if (!$conn || ($conn instanceof mysqli && $conn->connect_error)) {
            if (function_exists('set_flash_message')) set_flash_message('danger', 'Koneksi database tidak tersedia untuk memproses pemesanan.');
            error_log("PemesananTiketController::prosesPemesananLengkap() - Koneksi DB global gagal atau tidak diset untuk transaksi.");
            return false;
        }

        // --- Validasi Input Awal Data Pemesan ---
        // (Logika validasi Anda dari revisi sebelumnya sudah baik, pastikan tetap ada)
        $tanggal_kunjungan_input = $data_pemesan['tanggal_kunjungan'] ?? '';
        if (empty($tanggal_kunjungan_input) || !DateTime::createFromFormat('Y-m-d', $tanggal_kunjungan_input)) {
            if (function_exists('set_flash_message')) set_flash_message('danger', 'Tanggal kunjungan tidak valid atau tidak diisi.');
            return false;
        }
        $is_guest = !(isset($data_pemesan['user_id']) && !empty($data_pemesan['user_id']) && is_numeric($data_pemesan['user_id']));
        if ($is_guest) {
            if (empty($data_pemesan['nama_pemesan_tamu']) || empty($data_pemesan['email_pemesan_tamu']) || !filter_var($data_pemesan['email_pemesan_tamu'], FILTER_VALIDATE_EMAIL) || empty($data_pemesan['nohp_pemesan_tamu'])) {
                if (function_exists('set_flash_message')) set_flash_message('danger', 'Untuk tamu, nama, email yang valid, dan nomor HP wajib diisi.');
                return false;
            }
        }
        if (empty($items_tiket) || !is_array($items_tiket)) {
            if (function_exists('set_flash_message')) set_flash_message('danger', 'Minimal harus ada satu item tiket yang dipesan.');
            return false;
        }


        $total_harga_semua_tiket = 0;
        $total_harga_semua_sewa = 0;
        $data_detail_tiket_to_save = [];
        $data_detail_sewa_to_save = [];

        // --- 1. Validasi dan Persiapan Data Item Tiket ---
        // (Logika validasi item tiket dan ketersediaan jadwal dari revisi sebelumnya)
        foreach ($items_tiket as $key => $item_t) {
            if (empty($item_t['jenis_tiket_id']) || !isset($item_t['jumlah']) || !is_numeric($item_t['jumlah']) || (int)$item_t['jumlah'] <= 0) {
                if (function_exists('set_flash_message')) set_flash_message('danger', "Data item tiket ke-" . ($key + 1) . " tidak lengkap atau jumlah tidak valid.");
                return false;
            }
            $jenis_tiket_id = (int)$item_t['jenis_tiket_id'];
            $jumlah_tiket = (int)$item_t['jumlah'];
            $jenisTiketInfo = JenisTiket::findById($jenis_tiket_id);
            if (!$jenisTiketInfo || (isset($jenisTiketInfo['aktif']) && $jenisTiketInfo['aktif'] == 0)) {
                if (function_exists('set_flash_message')) set_flash_message('danger', "Jenis tiket (ID: {$jenis_tiket_id}) tidak valid/aktif.");
                return false;
            }
            $ketersediaan = JadwalKetersediaanTiket::getActiveKetersediaan($jenis_tiket_id, $tanggal_kunjungan_input);
            if (!$ketersediaan || !isset($ketersediaan['jumlah_saat_ini_tersedia']) || $ketersediaan['jumlah_saat_ini_tersedia'] < $jumlah_tiket) {
                if (function_exists('set_flash_message')) set_flash_message('danger', 'Kuota tiket "' . e($jenisTiketInfo['nama_layanan_display'] ?? '') . '" tidak mencukupi.');
                return false;
            }
            $harga_satuan_tiket = (float)($jenisTiketInfo['harga'] ?? 0);
            $subtotal_item_tiket = $harga_satuan_tiket * $jumlah_tiket;
            $total_harga_semua_tiket += $subtotal_item_tiket;
            $data_detail_tiket_to_save[] = [
                'jenis_tiket_id' => $jenis_tiket_id,
                'jumlah' => $jumlah_tiket,
                'harga_satuan_saat_pesan' => $harga_satuan_tiket,
                'subtotal_item' => $subtotal_item_tiket,
                'jadwal_ketersediaan_id' => $ketersediaan['id'] ?? null
            ];
        }


        // --- 2. Validasi dan Persiapan Data Item Sewa Alat (jika ada) ---
        // (Logika validasi item sewa dan ketersediaan stok dari revisi sebelumnya)
        if (!empty($items_sewa) && is_array($items_sewa)) {
            foreach ($items_sewa as $key_s => $item_s) {
                // ... (Validasi lengkap item sewa seperti sebelumnya) ...
                if (empty($item_s['sewa_alat_id']) || !isset($item_s['jumlah']) || (int)$item_s['jumlah'] <= 0 || empty($item_s['tanggal_mulai_sewa']) || empty($item_s['tanggal_akhir_sewa_rencana'])) {
                    if (function_exists('set_flash_message')) set_flash_message('danger', "Data item sewa ke-" . ($key_s + 1) . " tidak lengkap.");
                    return false;
                }
                $dtMulaiSewa = DateTime::createFromFormat('Y-m-d H:i:s', $item_s['tanggal_mulai_sewa']) ?: DateTime::createFromFormat('Y-m-d', $item_s['tanggal_mulai_sewa']);
                $dtAkhirSewa = DateTime::createFromFormat('Y-m-d H:i:s', $item_s['tanggal_akhir_sewa_rencana']) ?: DateTime::createFromFormat('Y-m-d', $item_s['tanggal_akhir_sewa_rencana']);
                if (!$dtMulaiSewa || !$dtAkhirSewa || $dtMulaiSewa >= $dtAkhirSewa) {
                    if (function_exists('set_flash_message')) set_flash_message('danger', "Tanggal sewa item ke-" . ($key_s + 1) . " tidak valid.");
                    return false;
                }
                $alatInfo = SewaAlat::getById((int)$item_s['sewa_alat_id']);
                if (!$alatInfo || $alatInfo['stok_tersedia'] < (int)$item_s['jumlah']) {
                    if (function_exists('set_flash_message')) set_flash_message('danger', 'Stok alat sewa "' . e($alatInfo['nama_item'] ?? '') . '" tidak mencukupi.');
                    return false;
                }
                $subtotal_item_sewa = PemesananSewaAlat::calculateSubtotalItem((int)$item_s['jumlah'], (float)($alatInfo['harga_sewa'] ?? 0), (int)($alatInfo['durasi_harga_sewa'] ?? 1), $alatInfo['satuan_durasi_harga'] ?? 'Peminjaman', $dtMulaiSewa->format('Y-m-d H:i:s'), $dtAkhirSewa->format('Y-m-d H:i:s'));
                $total_harga_semua_sewa += $subtotal_item_sewa;
                $data_detail_sewa_to_save[] = [
                    'sewa_alat_id' => (int)$item_s['sewa_alat_id'],
                    'jumlah' => (int)$item_s['jumlah'],
                    'harga_satuan_saat_pesan' => (float)($alatInfo['harga_sewa'] ?? 0),
                    'durasi_satuan_saat_pesan' => (int)($alatInfo['durasi_harga_sewa'] ?? 1),
                    'satuan_durasi_saat_pesan' => $alatInfo['satuan_durasi_harga'] ?? 'Peminjaman',
                    'tanggal_mulai_sewa' => $dtMulaiSewa->format('Y-m-d H:i:s'),
                    'tanggal_akhir_sewa_rencana' => $dtAkhirSewa->format('Y-m-d H:i:s'),
                    'total_harga_item' => $subtotal_item_sewa,
                    'status_item_sewa' => 'Dipesan',
                    'catatan_item_sewa' => $item_s['catatan_item_sewa'] ?? null
                ];
            }
        }

        $grand_total_harga = $total_harga_semua_tiket + $total_harga_semua_sewa;

        // --- Transaksi Database ---
        mysqli_begin_transaction($conn);
        try {
            $kode_pemesanan_unik = 'PT-' . date('Ymd') . strtoupper(bin2hex(random_bytes(4)));
            $data_header_pemesanan = [
                'user_id' => $is_guest ? null : (int)$data_pemesan['user_id'],
                'nama_pemesan_tamu' => $is_guest ? trim($data_pemesan['nama_pemesan_tamu']) : null,
                'email_pemesan_tamu' => $is_guest ? trim($data_pemesan['email_pemesan_tamu']) : null,
                'nohp_pemesan_tamu' => $is_guest ? trim($data_pemesan['nohp_pemesan_tamu']) : null,
                'kode_pemesanan' => $kode_pemesanan_unik,
                'tanggal_kunjungan' => $tanggal_kunjungan_input,
                'total_harga_akhir' => $grand_total_harga,
                'status' => 'pending', // Kolom status di tabel pemesanan_tiket
                'catatan_umum_pemesanan' => $data_pemesan['catatan_umum_pemesanan'] ?? null
            ];
            $pemesanan_tiket_id_baru = PemesananTiket::create($data_header_pemesanan);
            if (!$pemesanan_tiket_id_baru) {
                throw new Exception("Gagal membuat header pemesanan tiket. " . PemesananTiket::getLastError());
            }

            foreach ($data_detail_tiket_to_save as $item_t_data) {
                $item_t_data['pemesanan_tiket_id'] = $pemesanan_tiket_id_baru;
                if (!DetailPemesananTiket::create($item_t_data)) {
                    throw new Exception("Gagal menyimpan detail item tiket. " . DetailPemesananTiket::getLastError());
                }
                if (isset($item_t_data['jadwal_ketersediaan_id']) && $item_t_data['jadwal_ketersediaan_id'] !== null) {
                    if (!JadwalKetersediaanTiket::updateJumlahSaatIniTersedia($item_t_data['jadwal_ketersediaan_id'], -$item_t_data['jumlah'])) {
                        throw new Exception("Gagal mengurangi stok jadwal ketersediaan tiket ID: " . $item_t_data['jadwal_ketersediaan_id'] . ". " . JadwalKetersediaanTiket::getLastError());
                    }
                }
            }

            foreach ($data_detail_sewa_to_save as $item_s_data) {
                $item_s_data['pemesanan_tiket_id'] = $pemesanan_tiket_id_baru;
                if (!PemesananSewaAlat::create($item_s_data)) {
                    throw new Exception("Gagal menyimpan detail item sewa alat. " . PemesananSewaAlat::getLastError());
                }
            }

            $data_pembayaran = [
                'pemesanan_tiket_id' => $pemesanan_tiket_id_baru,
                'kode_pemesanan' => $kode_pemesanan_unik,
                'jumlah_dibayar' => $grand_total_harga,
                'status_pembayaran' => 'pending',
                'metode_pembayaran' => $data_pemesan['metode_pembayaran_pilihan'] ?? 'Belum Dipilih'
            ];
            if (!Pembayaran::create($data_pembayaran)) {
                throw new Exception("Gagal membuat entri pembayaran awal. " . Pembayaran::getLastError());
            }

            mysqli_commit($conn);
            if (function_exists('set_flash_message')) set_flash_message('success', 'Pemesanan Anda dengan kode ' . e($kode_pemesanan_unik) . ' berhasil dibuat. Silakan lanjutkan ke pembayaran.');
            return $kode_pemesanan_unik;
        } catch (Exception $e) {
            mysqli_rollback($conn);
            error_log("PemesananTiketController::prosesPemesananLengkap() - Exception Transaksi: " . $e->getMessage());
            if (function_exists('set_flash_message')) set_flash_message('danger', 'Terjadi kesalahan saat memproses pemesanan: ' . e($e->getMessage()));
            return false;
        }
    }

    // ... (Metode getDetailPemesananLengkap, getAllForAdmin, getByIdForAdmin, updateStatusPemesanan) ...
    // Pastikan metode-metode ini juga memanggil ModelName::getLastError() jika terjadi kegagalan dan perlu menampilkan error DB.
    // Contoh untuk getDetailPemesananLengkap:
    public static function getDetailPemesananLengkap($pemesanan_tiket_id)
    {
        $id_val = filter_var($pemesanan_tiket_id, FILTER_VALIDATE_INT);
        if ($id_val === false || $id_val <= 0) {
            throw new InvalidArgumentException("ID Pemesanan tidak valid untuk getDetailPemesananLengkap.");
        }

        $required_models_methods = [
            'PemesananTiket' => ['findById', 'getLastError'],
            'DetailPemesananTiket' => ['getByPemesananTiketId', 'getLastError'],
            'PemesananSewaAlat' => ['getByPemesananTiketId', 'getLastError'],
            'Pembayaran' => ['findByPemesananId', 'getLastError']
        ];
        foreach ($required_models_methods as $model => $methods) {
            if (!class_exists($model)) throw new RuntimeException("Model {$model} tidak ditemukan.");
            foreach ($methods as $method) {
                if (!method_exists($model, $method)) throw new RuntimeException("Metode {$model}::{$method} tidak ditemukan.");
            }
        }

        try {
            $data_pemesanan = [];
            $data_pemesanan['header'] = PemesananTiket::findById($id_val);
            if (!$data_pemesanan['header']) {
                error_log("PemesananTiketController::getDetailPemesananLengkap() - Header pemesanan ID {$id_val} tidak ditemukan. DB Error: " . PemesananTiket::getLastError());
                return null;
            }

            $data_pemesanan['detail_tiket'] = DetailPemesananTiket::getByPemesananTiketId($id_val);
            $data_pemesanan['detail_sewa'] = PemesananSewaAlat::getByPemesananTiketId($id_val);
            $data_pemesanan['pembayaran'] = Pembayaran::findByPemesananId($id_val);

            return $data_pemesanan;
        } catch (Exception $e) {
            error_log("PemesananTiketController::getDetailPemesananLengkap() - Exception: " . $e->getMessage());
            if (function_exists('set_flash_message')) set_flash_message('danger', 'Gagal memuat detail lengkap: Terjadi kesalahan sistem.');
            return null;
        }
    }
    public static function getAllForAdmin()
    {
        if (!class_exists('PemesananTiket') || !method_exists('PemesananTiket', 'getAll')) {
            error_log("PemesananTiketController: PemesananTiket::getAll() tidak ada.");
            return [];
        }
        return PemesananTiket::getAll();
    }
    public static function getByIdForAdmin($id)
    {
        $id_val = filter_var($id, FILTER_VALIDATE_INT);
        if ($id_val === false || $id_val <= 0) return null;
        if (!class_exists('PemesananTiket') || !method_exists('PemesananTiket', 'findById')) {
            error_log("PemesananTiketController: PemesananTiket::findById() tidak ada.");
            return null;
        }
        return PemesananTiket::findById($id_val);
    }
    public static function updateStatusPemesanan($id, $status)
    {
        $id_val = filter_var($id, FILTER_VALIDATE_INT);
        if ($id_val === false || $id_val <= 0 || empty(trim($status))) return false;
        if (!class_exists('PemesananTiket') || !method_exists('PemesananTiket', 'updateStatusPemesanan')) {
            error_log("PemesananTiketController: PemesananTiket::updateStatusPemesanan() tidak ada.");
            return false;
        }
        return PemesananTiket::updateStatusPemesanan($id_val, trim($status));
    }
} // End of class PemesananTiketController