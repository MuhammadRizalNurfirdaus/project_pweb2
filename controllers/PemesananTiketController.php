<?php
// File: C:\xampp\htdocs\Cilengkrang-Web-Wisata\controllers\PemesananTiketController.php

class PemesananTiketController
{
    private static function checkRequiredModels(array $model_names)
    {
        foreach ($model_names as $model_name) {
            if (!class_exists($model_name)) {
                $error_msg = get_called_class() . " Fatal Error: Model {$model_name} tidak ditemukan atau tidak dimuat.";
                error_log($error_msg);
                throw new RuntimeException($error_msg);
            }
            // Anda bisa menambahkan pengecekan metode di sini jika diperlukan untuk setiap model
            // if (isset($methods_to_check[$model_name])) {
            //     foreach ($methods_to_check[$model_name] as $method) {
            //         if (!method_exists($model_name, $method)) {
            //              throw new RuntimeException("Metode {$model_name}::{$method} tidak ditemukan.");
            //         }
            //     }
            // }
        }
    }

    public static function prosesPemesananLengkap($data_pemesan, $items_tiket = [], $items_sewa = [])
    {
        global $conn; // Pastikan $conn di-scope dengan benar atau di-pass ke Model
        $kode_pemesanan_unik_untuk_log = "BELUM_TERGENERASI";

        try {
            self::checkRequiredModels([
                'PemesananTiket',
                'DetailPemesananTiket',
                'PemesananSewaAlat',
                'Pembayaran',
                'JenisTiket',
                'SewaAlat',
                'JadwalKetersediaanTiket'
            ]);

            if (!$conn || ($conn instanceof mysqli && $conn->connect_error)) {
                throw new RuntimeException("Koneksi database tidak tersedia untuk memproses pemesanan.");
            }

            // Validasi Awal Data Pemesan
            $tanggal_kunjungan_input = $data_pemesan['tanggal_kunjungan'] ?? '';
            if (empty($tanggal_kunjungan_input) || !($dtKunjungan = DateTime::createFromFormat('Y-m-d', $tanggal_kunjungan_input)) || $dtKunjungan->format('Y-m-d') !== $tanggal_kunjungan_input) {
                if (function_exists('set_flash_message')) set_flash_message('danger', 'Tanggal kunjungan tidak valid atau tidak diisi (format YYYY-MM-DD).');
                return false;
            }
            // Validasi tanggal kunjungan tidak boleh hari ini atau hari sebelumnya
            $today = new DateTime();
            $today->setTime(0, 0, 0); // Set waktu ke awal hari untuk perbandingan tanggal saja
            if ($dtKunjungan < $today) {
                if (function_exists('set_flash_message')) set_flash_message('danger', 'Tanggal kunjungan minimal adalah hari ini.');
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

            // Validasi dan Persiapan Detail Tiket
            $total_harga_semua_tiket = 0;
            $data_detail_tiket_to_save = [];
            foreach ($items_tiket as $key => $item_t) {
                if (empty($item_t['jenis_tiket_id']) || !isset($item_t['jumlah']) || !is_numeric($item_t['jumlah']) || (int)$item_t['jumlah'] <= 0) {
                    if (function_exists('set_flash_message')) set_flash_message('danger', "Data item tiket ke-" . ($key + 1) . " tidak lengkap atau jumlah tidak valid.");
                    return false;
                }
                $jenis_tiket_id = (int)$item_t['jenis_tiket_id'];
                $jumlah_tiket = (int)$item_t['jumlah'];

                $jenisTiketInfo = JenisTiket::findById($jenis_tiket_id);
                if (!$jenisTiketInfo || (isset($jenisTiketInfo['aktif']) && $jenisTiketInfo['aktif'] == 0)) {
                    if (function_exists('set_flash_message')) set_flash_message('danger', "Jenis tiket \"" . e($jenisTiketInfo['nama_layanan_display'] ?? "ID: {$jenis_tiket_id}") . "\" tidak ditemukan atau tidak aktif.");
                    return false;
                }

                $ketersediaan = JadwalKetersediaanTiket::getActiveKetersediaan($jenis_tiket_id, $tanggal_kunjungan_input);
                if (!$ketersediaan || !isset($ketersediaan['jumlah_saat_ini_tersedia']) || (int)$ketersediaan['jumlah_saat_ini_tersedia'] < $jumlah_tiket) {
                    $nama_layanan_display = $jenisTiketInfo['nama_layanan_display'] ?? "Tiket ID: {$jenis_tiket_id}";
                    $sisa_kuota = $ketersediaan['jumlah_saat_ini_tersedia'] ?? 0;
                    if (function_exists('set_flash_message')) set_flash_message('danger', "Kuota untuk \"" . e($nama_layanan_display) . "\" pada tanggal " . e(formatTanggalIndonesia($tanggal_kunjungan_input)) . " tidak mencukupi (tersisa: {$sisa_kuota}, diminta: {$jumlah_tiket}).");
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
                    'jadwal_ketersediaan_id' => $ketersediaan['id'] ?? null, // Simpan ID jadwal untuk update stok
                    'tanggal_kunjungan' => $tanggal_kunjungan_input // Untuk DetailPemesananTiketController
                ];
            }

            // Validasi dan Persiapan Detail Sewa Alat
            $total_harga_semua_sewa = 0;
            $data_detail_sewa_to_save = [];
            if (!empty($items_sewa) && is_array($items_sewa)) {
                foreach ($items_sewa as $key_s => $item_s) {
                    if (empty($item_s['sewa_alat_id']) || !isset($item_s['jumlah']) || (int)$item_s['jumlah'] <= 0 || empty($item_s['tanggal_mulai_sewa']) || empty($item_s['tanggal_akhir_sewa_rencana'])) {
                        if (function_exists('set_flash_message')) set_flash_message('danger', "Data item sewa ke-" . ($key_s + 1) . " tidak lengkap atau jumlah tidak valid.");
                        return false;
                    }
                    try {
                        $dtMulaiSewa = new DateTime($item_s['tanggal_mulai_sewa']);
                        $dtAkhirSewa = new DateTime($item_s['tanggal_akhir_sewa_rencana']);
                    } catch (Exception $dateEx) {
                        if (function_exists('set_flash_message')) set_flash_message('danger', "Format tanggal sewa item ke-" . ($key_s + 1) . " tidak valid.");
                        return false;
                    }

                    if ($dtMulaiSewa >= $dtAkhirSewa) {
                        if (function_exists('set_flash_message')) set_flash_message('danger', "Tanggal mulai sewa item ke-" . ($key_s + 1) . " harus sebelum tanggal akhir sewa.");
                        return false;
                    }
                    $alatInfo = SewaAlat::getById((int)$item_s['sewa_alat_id']);
                    if (!$alatInfo || !isset($alatInfo['stok_tersedia']) || (int)$alatInfo['stok_tersedia'] < (int)$item_s['jumlah']) {
                        $nama_alat_display = $alatInfo['nama_item'] ?? "Alat ID: " . $item_s['sewa_alat_id'];
                        $sisa_stok_alat = $alatInfo['stok_tersedia'] ?? 0;
                        if (function_exists('set_flash_message')) set_flash_message('danger', "Stok alat sewa \"" . e($nama_alat_display) . "\" tidak mencukupi (tersisa: {$sisa_stok_alat}, diminta: " . (int)$item_s['jumlah'] . ").");
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
                        // total_harga_item akan dihitung oleh PemesananSewaAlat::create()
                        'status_item_sewa' => 'Dipesan',
                        'catatan_item_sewa' => $item_s['catatan_item_sewa'] ?? null
                    ];
                }
            }

            $grand_total_harga = $total_harga_semua_tiket + $total_harga_semua_sewa;
            if ($grand_total_harga <= 0 && (count($items_tiket) > 0 || count($items_sewa) > 0)) {
                if (function_exists('set_flash_message')) set_flash_message('danger', 'Total harga pemesanan tidak boleh nol atau negatif jika ada item yang dipesan.');
                return false;
            }


            mysqli_begin_transaction($conn);
            error_log("PTC::prosesPemesananLengkap - Transaksi DIMULAI.");

            try {
                $kode_pemesanan_unik = 'PT-' . date('Ymd') . strtoupper(bin2hex(random_bytes(4)));
            } catch (Exception $e) {
                $kode_pemesanan_unik = 'PT-' . date('YmdHis') . mt_rand(1000, 9999);
                error_log("PTC::prosesPemesananLengkap - Peringatan: random_bytes gagal, menggunakan fallback kode. Error: " . $e->getMessage());
            }
            $kode_pemesanan_unik_untuk_log = $kode_pemesanan_unik;

            $data_header_pemesanan = [
                'user_id' => $is_guest ? null : (int)$data_pemesan['user_id'],
                'nama_pemesan_tamu' => $is_guest ? trim($data_pemesan['nama_pemesan_tamu']) : null,
                'email_pemesan_tamu' => $is_guest ? trim($data_pemesan['email_pemesan_tamu']) : null,
                'nohp_pemesan_tamu' => $is_guest ? trim($data_pemesan['nohp_pemesan_tamu']) : null,
                'kode_pemesanan' => $kode_pemesanan_unik,
                'tanggal_kunjungan' => $tanggal_kunjungan_input,
                'total_harga_akhir' => $grand_total_harga,
                'status' => 'pending',
                'catatan_umum_pemesanan' => $data_pemesan['catatan_umum_pemesanan'] ?? null
            ];

            error_log("PTC::prosesPemesananLengkap - Data untuk PemesananTiket::create(): " . print_r($data_header_pemesanan, true));
            $pemesanan_tiket_id_baru = PemesananTiket::create($data_header_pemesanan);
            if (!$pemesanan_tiket_id_baru) {
                throw new Exception("Gagal membuat header pemesanan tiket. Model Error: " . (PemesananTiket::getLastError() ?: 'Tidak ada detail error dari model.'));
            }
            error_log("PTC::prosesPemesananLengkap - Header Pemesanan ID {$pemesanan_tiket_id_baru} DIBUAT untuk kode: {$kode_pemesanan_unik}.");

            // Simpan Detail Tiket dan Update Stok Jadwal
            foreach ($data_detail_tiket_to_save as $item_t_data) {
                $item_t_data_for_create = $item_t_data; // Buat salinan untuk create
                $item_t_data_for_create['pemesanan_tiket_id'] = $pemesanan_tiket_id_baru;

                // tanggal_kunjungan sudah ada di $item_t_data_for_create dari loop validasi sebelumnya
                // dan DetailPemesananTiketController::create akan menggunakannya

                // Memanggil DetailPemesananTiketController::create yang sudah menghandle update stok
                if (!DetailPemesananTiketController::create($item_t_data_for_create)) {
                    // Pesan flash seharusnya sudah diset oleh DetailPemesananTiketController
                    throw new Exception("Gagal menyimpan detail item tiket atau update stok. Controller Error.");
                }
            }
            error_log("PTC::prosesPemesananLengkap - Semua Detail Tiket DIBUAT dan stok diupdate untuk Header ID {$pemesanan_tiket_id_baru}.");


            // Simpan Detail Sewa Alat (Model PemesananSewaAlat::create sudah handle update stok alat)
            foreach ($data_detail_sewa_to_save as $item_s_data) {
                $item_s_data['pemesanan_tiket_id'] = $pemesanan_tiket_id_baru;
                if (!PemesananSewaAlat::create($item_s_data)) { // Ini memanggil Model, bukan controller
                    throw new Exception("Gagal menyimpan detail item sewa alat. Model Error: " . (PemesananSewaAlat::getLastError() ?: 'Tidak ada detail error dari model.'));
                }
            }
            if (!empty($data_detail_sewa_to_save)) error_log("PTC::prosesPemesananLengkap - Semua Detail Sewa DIBUAT dan stok diupdate untuk Header ID {$pemesanan_tiket_id_baru}.");


            $data_pembayaran_awal = [
                'pemesanan_tiket_id' => $pemesanan_tiket_id_baru,
                'kode_pemesanan' => $kode_pemesanan_unik,
                'jumlah_dibayar' => 0.00,
                'status_pembayaran' => 'pending',
                'metode_pembayaran' => $data_pemesan['metode_pembayaran_pilihan'] ?? 'Belum Dipilih'
            ];
            if (!Pembayaran::create($data_pembayaran_awal)) {
                throw new Exception("Gagal membuat entri pembayaran awal. Model Error: " . (Pembayaran::getLastError() ?: 'Tidak ada detail error dari model.'));
            }
            error_log("PTC::prosesPemesananLengkap - Entri Pembayaran DIBUAT untuk Header ID {$pemesanan_tiket_id_baru}.");

            mysqli_commit($conn);
            error_log("PTC::prosesPemesananLengkap - Transaksi BERHASIL DI-COMMIT untuk kode: {$kode_pemesanan_unik}.");

            if (function_exists('set_flash_message')) set_flash_message('success', 'Pemesanan Anda dengan kode ' . e($kode_pemesanan_unik) . ' berhasil dibuat. Silakan lanjutkan ke pembayaran.');
            return $kode_pemesanan_unik;
        } catch (Exception $e) {
            if (isset($conn) && $conn->thread_id && mysqli_get_connection_stats($conn)['connect_success']) { // Cek koneksi masih valid
                mysqli_rollback($conn);
                error_log("PTC::prosesPemesananLengkap - Transaksi DI-ROLLBACK untuk kode (potensial): {$kode_pemesanan_unik_untuk_log}. Alasan: " . $e->getMessage());
            }
            $log_msg = "PTC::prosesPemesananLengkap() - EXCEPTION DITANGKAP. Pesan: " . $e->getMessage();
            if ($e->getPrevious()) $log_msg .= " | Previous: " . $e->getPrevious()->getMessage();
            error_log($log_msg);

            if (function_exists('set_flash_message') && !isset($_SESSION['flash_message'])) {
                set_flash_message('danger', 'Terjadi kesalahan internal saat memproses pemesanan: ' . e($e->getMessage()));
            }
            return false;
        }
    }

    public static function getDetailPemesananLengkap($pemesanan_tiket_id)
    {
        $id_val = filter_var($pemesanan_tiket_id, FILTER_VALIDATE_INT);
        if ($id_val === false || $id_val <= 0) {
            error_log(get_called_class() . "::getDetailPemesananLengkap - ID Pemesanan tidak valid: " . print_r($pemesanan_tiket_id, true));
            return null;
        }

        try {
            self::checkRequiredModels(['PemesananTiket', 'DetailPemesananTiket', 'PemesananSewaAlat', 'Pembayaran']);
            $data_pemesanan = [
                'header' => null,
                'detail_tiket' => [],
                'detail_sewa' => [],
                'pembayaran' => null
            ];

            $data_pemesanan['header'] = PemesananTiket::findById($id_val);
            if (!$data_pemesanan['header']) {
                error_log(get_called_class() . "::getDetailPemesananLengkap - Header pemesanan tidak ditemukan untuk ID: {$id_val}. Model Error: " . PemesananTiket::getLastError());
                return null;
            }

            // Gunakan DetailPemesananTiketController untuk mengambil detail tiket jika ada logika tambahan di sana
            $data_pemesanan['detail_tiket'] = class_exists('DetailPemesananTiketController') && method_exists('DetailPemesananTiketController', 'getByPemesananTiketId')
                ? DetailPemesananTiketController::getByPemesananTiketId($id_val)
                : DetailPemesananTiket::getByPemesananTiketId($id_val);

            // Gunakan Model PemesananSewaAlat langsung
            $data_pemesanan['detail_sewa'] = PemesananSewaAlat::getByPemesananTiketId($id_val);
            $data_pemesanan['pembayaran'] = Pembayaran::findByPemesananId($id_val);

            return $data_pemesanan;
        } catch (Exception $e) {
            error_log(get_called_class() . "::getDetailPemesananLengkap() - Exception untuk ID {$id_val}: " . $e->getMessage());
            return null;
        }
    }

    public static function getPemesananLengkapByKode($kode_pemesanan)
    {
        $kode_pemesanan_clean = trim((string)$kode_pemesanan);
        if (empty($kode_pemesanan_clean)) {
            error_log(get_called_class() . "::getPemesananLengkapByKode() - Kode pemesanan kosong.");
            return null;
        }

        try {
            self::checkRequiredModels(['PemesananTiket']);
            $headerPemesanan = PemesananTiket::getByKodePemesanan($kode_pemesanan_clean);

            if (!$headerPemesanan || !isset($headerPemesanan['id'])) {
                error_log(get_called_class() . "::getPemesananLengkapByKode() - Header pemesanan tidak ditemukan untuk kode: " . e($kode_pemesanan_clean) . ". Model Error: " . PemesananTiket::getLastError());
                return null;
            }
            return self::getDetailPemesananLengkap((int)$headerPemesanan['id']);
        } catch (Exception $e) {
            error_log(get_called_class() . "::getPemesananLengkapByKode() - Exception untuk kode " . e($kode_pemesanan_clean) . ": " . $e->getMessage());
            return null;
        }
    }

    public static function getAllForAdmin()
    {
        try {
            self::checkRequiredModels(['PemesananTiket']);
            // Metode getAll di Model PemesananTiket sudah tidak memfilter dihapus_oleh_user, jadi ini benar untuk admin
            return PemesananTiket::getAll();
        } catch (Exception $e) {
            error_log(get_called_class() . "::getAllForAdmin() - Exception: " . $e->getMessage());
            return [];
        }
    }

    public static function updateStatusPemesanan($id, $status_baru)
    {
        $id_val = filter_var($id, FILTER_VALIDATE_INT);
        $status_val = trim(strtolower($status_baru));

        $allowed_statuses = defined('PemesananTiket::ALLOWED_STATUSES') ? PemesananTiket::ALLOWED_STATUSES : ['pending', 'waiting_payment', 'paid', 'confirmed', 'completed', 'cancelled', 'expired', 'refunded', 'awaiting_confirmation'];

        if ($id_val === false || $id_val <= 0 || empty($status_val) || !in_array($status_val, $allowed_statuses)) {
            if (function_exists('set_flash_message')) set_flash_message('danger', 'Data update status pemesanan tidak valid.');
            error_log(get_called_class() . "::updateStatusPemesanan - Input tidak valid. ID: {$id}, Status: {$status_baru}");
            return false;
        }

        try {
            self::checkRequiredModels(['PemesananTiket', 'Pembayaran']); // Pembayaran mungkin diperlukan untuk logika status terkait
            $current_pemesanan = PemesananTiket::findById($id_val);
            if (!$current_pemesanan) {
                if (function_exists('set_flash_message')) set_flash_message('danger', 'Pemesanan tidak ditemukan untuk diupdate.');
                return false;
            }

            // Contoh logika tambahan: Jika status pemesanan diubah jadi 'paid',
            // cek apakah pembayaran terkait sudah 'success' atau 'paid'.
            $successful_payment_statuses = defined('Pembayaran::SUCCESSFUL_PAYMENT_STATUSES') ? Pembayaran::SUCCESSFUL_PAYMENT_STATUSES : ['success', 'paid', 'confirmed'];
            if ($status_val === 'paid' || $status_val === 'confirmed') {
                $pembayaran = Pembayaran::findByPemesananId($id_val);
                if (!$pembayaran || !in_array(strtolower($pembayaran['status_pembayaran'] ?? ''), $successful_payment_statuses)) {
                    error_log(get_called_class() . "::updateStatusPemesanan - PERINGATAN: Pemesanan ID {$id_val} status diubah menjadi '{$status_val}' tetapi status pembayaran adalah '" . ($pembayaran['status_pembayaran'] ?? 'N/A') . "' atau tidak ditemukan pembayaran lunas.");
                    // Anda bisa memilih untuk menghentikan proses atau hanya memberi peringatan
                    // set_flash_message('warning', "Status pemesanan diubah ke {$status_val} namun pembayaran terkait belum lunas/terkonfirmasi.");
                }
            }

            $updateBerhasil = PemesananTiket::updateStatusPemesanan($id_val, $status_val);
            if (!$updateBerhasil) {
                if (function_exists('set_flash_message')) set_flash_message('danger', 'Gagal memperbarui status pemesanan di database. ' . PemesananTiket::getLastError());
            }
            return $updateBerhasil;
        } catch (Exception $e) {
            error_log(get_called_class() . "::updateStatusPemesanan({$id_val}, {$status_val}) - Exception: " . $e->getMessage());
            if (function_exists('set_flash_message')) set_flash_message('danger', 'Terjadi kesalahan sistem saat update status pemesanan.');
            return false;
        }
    }

    /**
     * Menghapus pemesanan secara permanen (hard delete). Hanya untuk admin.
     */
    public static function deletePemesananById($id_pemesanan)
    {
        $id_val = filter_var($id_pemesanan, FILTER_VALIDATE_INT);
        if ($id_val === false || $id_val <= 0) {
            if (function_exists('set_flash_message')) set_flash_message('danger', 'ID Pemesanan tidak valid untuk dihapus.');
            return false;
        }

        try {
            self::checkRequiredModels(['PemesananTiket']); // Metode delete di Model PemesananTiket sudah handle anak-anaknya
            if (PemesananTiket::delete($id_val)) { // Ini adalah hard delete
                if (function_exists('set_flash_message')) set_flash_message('success', 'Pemesanan tiket dan semua data terkait berhasil dihapus secara permanen.');
                return true;
            } else {
                if (function_exists('set_flash_message') && !isset($_SESSION['flash_message'])) { // Jangan timpa flash dari model jika ada
                    set_flash_message('danger', 'Gagal menghapus pemesanan tiket. ' . PemesananTiket::getLastError());
                }
                return false;
            }
        } catch (Exception $e) {
            error_log(get_called_class() . "::deletePemesananById({$id_val}) - Exception: " . $e->getMessage());
            if (function_exists('set_flash_message')) set_flash_message('danger', 'Gagal menghapus pemesanan: Terjadi kesalahan sistem. ' . e($e->getMessage()));
            return false;
        }
    }

    /**
     * Menangani permintaan soft delete pemesanan dari pengguna.
     * @param int $pemesanan_id ID pemesanan tiket.
     * @param int $current_user_id ID pengguna yang sedang login.
     * @return bool True jika berhasil, false jika gagal.
     */
    public static function handleSoftDeleteByUser(int $pemesanan_id, int $current_user_id): bool
    {
        try {
            self::checkRequiredModels(['PemesananTiket']);

            if ($pemesanan_id <= 0 || $current_user_id <= 0) {
                if (function_exists('set_flash_message')) set_flash_message('danger', 'ID pemesanan atau pengguna tidak valid untuk aksi ini.');
                return false;
            }

            if (!method_exists('PemesananTiket', 'softDeleteByUser')) {
                error_log(get_called_class() . "::handleSoftDeleteByUser() - Metode PemesananTiket::softDeleteByUser tidak ditemukan.");
                if (function_exists('set_flash_message')) set_flash_message('danger', 'Kesalahan sistem: Fungsi hapus riwayat tidak tersedia.');
                return false;
            }

            if (PemesananTiket::softDeleteByUser($pemesanan_id, $current_user_id)) {
                if (function_exists('set_flash_message')) set_flash_message('success', 'Pemesanan berhasil dihapus dari riwayat Anda.');
                return true;
            } else {
                $error_model = PemesananTiket::getLastError();
                $pesan_error = 'Gagal menghapus pemesanan dari riwayat Anda.';
                if ($error_model && strpos(strtolower($error_model), 'tidak ada pemesanan yang diupdate') !== false) {
                    $pesan_error = 'Anda tidak berhak menghapus pemesanan ini atau pemesanan sudah dihapus dari riwayat.';
                } elseif ($error_model && strpos(strtolower($error_model), 'tidak ditemukan') !== false) {
                    $pesan_error = 'Pemesanan yang akan dihapus tidak ditemukan.';
                } elseif ($error_model) {
                    $pesan_error .= ' Detail Sistem: ' . e($error_model);
                }
                if (function_exists('set_flash_message')) set_flash_message('danger', $pesan_error);
                return false;
            }
        } catch (Exception $e) {
            error_log(get_called_class() . "::handleSoftDeleteByUser() - Exception: " . $e->getMessage());
            if (function_exists('set_flash_message')) set_flash_message('danger', 'Terjadi kesalahan sistem saat mencoba menghapus riwayat.');
            return false;
        }
    }
}
// End of PemesananTiketController.php