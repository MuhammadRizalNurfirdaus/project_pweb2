<?php
// File: C:\xampp\htdocs\Cilengkrang-Web-Wisata\controllers\PemesananTiketController.php

/**
 * PemesananTiketController
 * Bertanggung jawab untuk logika bisnis terkait pemesanan tiket.
 */

// Pemuatan model dan controller sudah dilakukan oleh config.php.

class PemesananTiketController
{
    /**
     * Memeriksa apakah semua model yang diperlukan tersedia.
     * @param array $model_names Nama-nama model yang harus ada.
     * @throws RuntimeException Jika salah satu model tidak ditemukan atau metode init/setDbConnection belum terpanggil.
     */
    private static function checkRequiredModels(array $model_names)
    {
        global $conn; // Untuk memeriksa apakah $conn sudah ada saat model membutuhkannya
        foreach ($model_names as $model_name) {
            if (!class_exists($model_name)) {
                $error_msg = "PemesananTiketController Fatal Error: Model {$model_name} tidak ditemukan atau tidak dimuat.";
                error_log($error_msg);
                throw new RuntimeException($error_msg);
            }
            // Opsional: Periksa apakah koneksi DB sudah diset ke model jika model membutuhkannya secara statis
            // Ini lebih relevan jika model tidak hanya bergantung pada $conn global
            // Misalnya, jika model memiliki metode statis seperti Model::$db->query()
            // Tapi karena model Anda menggunakan self::$db, pemanggilan setDbConnection di config.php sudah cukup.
        }
    }

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
        global $conn;

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

            // --- Validasi Input Awal Data Pemesan ---
            $tanggal_kunjungan_input = $data_pemesan['tanggal_kunjungan'] ?? '';
            if (empty($tanggal_kunjungan_input) || !DateTime::createFromFormat('Y-m-d', $tanggal_kunjungan_input)) {
                set_flash_message('danger', 'Tanggal kunjungan tidak valid atau tidak diisi.');
                return false;
            }
            $is_guest = !(isset($data_pemesan['user_id']) && !empty($data_pemesan['user_id']) && is_numeric($data_pemesan['user_id']));
            if ($is_guest) {
                if (empty($data_pemesan['nama_pemesan_tamu']) || empty($data_pemesan['email_pemesan_tamu']) || !filter_var($data_pemesan['email_pemesan_tamu'], FILTER_VALIDATE_EMAIL) || empty($data_pemesan['nohp_pemesan_tamu'])) {
                    set_flash_message('danger', 'Untuk tamu, nama, email yang valid, dan nomor HP wajib diisi.');
                    return false;
                }
            }
            if (empty($items_tiket) || !is_array($items_tiket)) {
                set_flash_message('danger', 'Minimal harus ada satu item tiket yang dipesan.');
                return false;
            }

            $total_harga_semua_tiket = 0;
            $total_harga_semua_sewa = 0;
            $data_detail_tiket_to_save = [];
            $data_detail_sewa_to_save = [];

            // --- 1. Validasi dan Persiapan Data Item Tiket ---
            foreach ($items_tiket as $key => $item_t) {
                if (empty($item_t['jenis_tiket_id']) || !isset($item_t['jumlah']) || !is_numeric($item_t['jumlah']) || (int)$item_t['jumlah'] <= 0) {
                    set_flash_message('danger', "Data item tiket ke-" . ($key + 1) . " tidak lengkap atau jumlah tidak valid.");
                    return false;
                }
                $jenis_tiket_id = (int)$item_t['jenis_tiket_id'];
                $jumlah_tiket = (int)$item_t['jumlah'];

                $jenisTiketInfo = JenisTiket::findById($jenis_tiket_id); // Asumsi findById ada di model JenisTiket
                if (!$jenisTiketInfo || (isset($jenisTiketInfo['aktif']) && $jenisTiketInfo['aktif'] == 0)) {
                    set_flash_message('danger', "Jenis tiket (ID: {$jenis_tiket_id}) tidak valid/aktif.");
                    return false;
                }

                // Asumsi getActiveKetersediaan ada di model JadwalKetersediaanTiket
                $ketersediaan = JadwalKetersediaanTiket::getActiveKetersediaan($jenis_tiket_id, $tanggal_kunjungan_input);
                if (!$ketersediaan || !isset($ketersediaan['jumlah_saat_ini_tersedia']) || $ketersediaan['jumlah_saat_ini_tersedia'] < $jumlah_tiket) {
                    set_flash_message('danger', 'Kuota tiket "' . e($jenisTiketInfo['nama_layanan_display'] ?? $jenisTiketInfo['nama_tiket'] ?? 'Tiket') . '" untuk tanggal ' . e(formatTanggalIndonesia($tanggal_kunjungan_input)) . ' tidak mencukupi.');
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
                    'jadwal_ketersediaan_id' => $ketersediaan['id'] ?? null,
                ];
            }

            // --- 2. Validasi dan Persiapan Data Item Sewa Alat (jika ada) ---
            if (!empty($items_sewa) && is_array($items_sewa)) {
                foreach ($items_sewa as $key_s => $item_s) {
                    if (empty($item_s['sewa_alat_id']) || !isset($item_s['jumlah']) || (int)$item_s['jumlah'] <= 0 || empty($item_s['tanggal_mulai_sewa']) || empty($item_s['tanggal_akhir_sewa_rencana'])) {
                        set_flash_message('danger', "Data item sewa ke-" . ($key_s + 1) . " tidak lengkap.");
                        return false;
                    }
                    // Format tanggal dari input form mungkin Y-m-d, perlu disesuaikan jika perlu jam
                    $dtMulaiSewa = DateTime::createFromFormat('Y-m-d', $item_s['tanggal_mulai_sewa']);
                    $dtAkhirSewa = DateTime::createFromFormat('Y-m-d', $item_s['tanggal_akhir_sewa_rencana']);
                    if (!$dtMulaiSewa || !$dtAkhirSewa || $dtMulaiSewa >= $dtAkhirSewa) {
                        set_flash_message('danger', "Tanggal sewa item ke-" . ($key_s + 1) . " tidak valid.");
                        return false;
                    }
                    // Set jam default jika tidak ada (misal: mulai jam 14:00, selesai jam 12:00)
                    $dtMulaiSewa->setTime(14, 0, 0);
                    $dtAkhirSewa->setTime(12, 0, 0);


                    $alatInfo = SewaAlat::getById((int)$item_s['sewa_alat_id']);
                    if (!$alatInfo || (isset($alatInfo['stok_tersedia']) && $alatInfo['stok_tersedia'] < (int)$item_s['jumlah'])) {
                        set_flash_message('danger', 'Stok alat sewa "' . e($alatInfo['nama_item'] ?? 'Alat') . '" tidak mencukupi.');
                        return false;
                    }
                    // Asumsi PemesananSewaAlat::calculateSubtotalItem sudah ada
                    $subtotal_item_sewa = PemesananSewaAlat::calculateSubtotalItem(
                        (int)$item_s['jumlah'],
                        (float)($alatInfo['harga_sewa'] ?? 0),
                        (int)($alatInfo['durasi_harga_sewa'] ?? 1),
                        $alatInfo['satuan_durasi_harga'] ?? 'Peminjaman',
                        $dtMulaiSewa->format('Y-m-d H:i:s'),
                        $dtAkhirSewa->format('Y-m-d H:i:s')
                    );
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

            $kode_pemesanan_unik = 'PT-' . date('Ymd') . strtoupper(bin2hex(random_bytes(4)));
            $data_header_pemesanan = [
                'user_id' => $is_guest ? null : (int)$data_pemesan['user_id'],
                'nama_pemesan_tamu' => $is_guest ? trim($data_pemesan['nama_pemesan_tamu']) : null,
                'email_pemesan_tamu' => $is_guest ? trim($data_pemesan['email_pemesan_tamu']) : null,
                'nohp_pemesan_tamu' => $is_guest ? trim($data_pemesan['nohp_pemesan_tamu']) : null,
                'kode_pemesanan' => $kode_pemesanan_unik,
                'tanggal_kunjungan' => $tanggal_kunjungan_input,
                'total_harga_akhir' => $grand_total_harga,
                'status' => 'pending', // Status awal dari tabel pemesanan_tiket
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
                    // Asumsi updateJumlahSaatIniTersedia ada di JadwalKetersediaanTiket Model
                    if (!JadwalKetersediaanTiket::updateJumlahSaatIniTersedia($item_t_data['jadwal_ketersediaan_id'], -$item_t_data['jumlah'])) {
                        throw new Exception("Gagal mengurangi kuota jadwal ketersediaan tiket ID: " . $item_t_data['jadwal_ketersediaan_id'] . ". " . JadwalKetersediaanTiket::getLastError());
                    }
                }
            }

            foreach ($data_detail_sewa_to_save as $item_s_data) {
                $item_s_data['pemesanan_tiket_id'] = $pemesanan_tiket_id_baru;
                if (!PemesananSewaAlat::create($item_s_data)) { // Model PemesananSewaAlat akan mengurangi stok di method create-nya
                    throw new Exception("Gagal menyimpan detail item sewa alat. " . PemesananSewaAlat::getLastError());
                }
            }

            // Data untuk tabel pembayaran
            $data_pembayaran = [
                'pemesanan_tiket_id' => $pemesanan_tiket_id_baru, // Ini adalah FK ke pemesanan_tiket.id
                'kode_pemesanan' => $kode_pemesanan_unik, // Bisa disimpan untuk referensi cepat
                'jumlah_dibayar' => $grand_total_harga, // Seharusnya ini jumlah yang HARUS dibayar, bukan yang sudah dibayar
                // Mungkin lebih baik 'jumlah_tagihan' atau diisi 0 jika belum bayar
                'status_pembayaran' => 'pending', // Status dari tabel pembayaran
                'metode_pembayaran' => $data_pemesan['metode_pembayaran_pilihan'] ?? 'Belum Dipilih'
            ];
            // Di tabel pembayaran, kolom `jumlah_dibayar` seharusnya diisi saat pembayaran dikonfirmasi, bukan saat pemesanan dibuat.
            // Untuk create awal, `jumlah_dibayar` harusnya 0.
            $data_pembayaran_awal = [
                'pemesanan_tiket_id' => $pemesanan_tiket_id_baru,
                'kode_pemesanan' => $kode_pemesanan_unik,
                'jumlah_dibayar' => 0, // Awalnya 0
                'status_pembayaran' => 'pending',
                'metode_pembayaran' => $data_pemesan['metode_pembayaran_pilihan'] ?? 'Belum Dipilih'
                // 'jumlah_tagihan' => $grand_total_harga, // Jika ada kolom ini
            ];
            if (!Pembayaran::create($data_pembayaran_awal)) {
                throw new Exception("Gagal membuat entri pembayaran awal. " . Pembayaran::getLastError());
            }

            mysqli_commit($conn);
            set_flash_message('success', 'Pemesanan Anda dengan kode ' . e($kode_pemesanan_unik) . ' berhasil dibuat. Silakan lanjutkan ke pembayaran.');
            return $kode_pemesanan_unik;
        } catch (Exception $e) {
            if (isset($conn) && $conn->thread_id) {
                mysqli_rollback($conn);
            }
            $log_msg = "PemesananTiketController::prosesPemesananLengkap() - Exception Transaksi: " . $e->getMessage();
            if ($e->getPrevious()) {
                $log_msg .= " | Previous: " . $e->getPrevious()->getMessage();
            }
            error_log($log_msg);
            set_flash_message('danger', 'Terjadi kesalahan saat memproses pemesanan: ' . e($e->getMessage()));
            return false;
        }
    }

    /**
     * Mengambil detail lengkap pemesanan tiket untuk ditampilkan.
     * @param int $pemesanan_tiket_id ID pemesanan tiket.
     * @return array|null Data lengkap atau null jika tidak ditemukan/error.
     */
    public static function getDetailPemesananLengkap($pemesanan_tiket_id)
    {
        $id_val = filter_var($pemesanan_tiket_id, FILTER_VALIDATE_INT);
        if ($id_val === false || $id_val <= 0) {
            error_log("PemesananTiketController::getDetailPemesananLengkap - ID Pemesanan tidak valid: " . print_r($pemesanan_tiket_id, true));
            return null;
        }

        try {
            // Model sudah di-load oleh config.php
            // self::checkRequiredModels(['PemesananTiket', 'DetailPemesananTiket', 'PemesananSewaAlat', 'Pembayaran']);

            $data_pemesanan = [
                'header' => null,
                'detail_tiket' => [],
                'detail_sewa' => [],
                'pembayaran' => null
            ];

            // Model PemesananTiket::findById() sudah melakukan JOIN dengan users
            $data_pemesanan['header'] = PemesananTiket::findById($id_val);

            if (!$data_pemesanan['header']) {
                error_log("PemesananTiketController::getDetailPemesananLengkap() - Header pemesanan ID {$id_val} TIDAK DITEMUKAN. Model Error: " . PemesananTiket::getLastError());
                return null; // Ini akan menyebabkan pesan "data tidak lengkap" di view
            }
            // Logging jika header ditemukan
            error_log("PemesananTiketController::getDetailPemesananLengkap() - Header pemesanan ID {$id_val} DITEMUKAN: " . print_r($data_pemesanan['header'], true));


            // Model DetailPemesananTiket::getByPemesananTiketId() sudah JOIN dengan jenis_tiket & wisata
            $data_pemesanan['detail_tiket'] = DetailPemesananTiket::getByPemesananTiketId($id_val);
            error_log("PemesananTiketController::getDetailPemesananLengkap() - Detail tiket untuk ID {$id_val}: " . print_r($data_pemesanan['detail_tiket'], true));


            // Model PemesananSewaAlat::getByPemesananTiketId() sudah JOIN dengan sewa_alat
            $data_pemesanan['detail_sewa'] = PemesananSewaAlat::getByPemesananTiketId($id_val);
            error_log("PemesananTiketController::getDetailPemesananLengkap() - Detail sewa untuk ID {$id_val}: " . print_r($data_pemesanan['detail_sewa'], true));


            // Model Pembayaran::findByPemesananId() akan mengambil berdasarkan pemesanan_tiket_id
            $data_pemesanan['pembayaran'] = Pembayaran::findByPemesananId($id_val);
            error_log("PemesananTiketController::getDetailPemesananLengkap() - Pembayaran untuk ID {$id_val}: " . print_r($data_pemesanan['pembayaran'], true));

            return $data_pemesanan;
        } catch (Exception $e) {
            error_log("PemesananTiketController::getDetailPemesananLengkap() - Exception untuk ID {$id_val}: " . $e->getMessage() . "\nTrace: " . $e->getTraceAsString());
            return null; // Biarkan view yang menangani null jika terjadi exception tak terduga
        }
    }

    public static function getAllForAdmin()
    {
        try {
            // Model PemesananTiket::getAll() sudah JOIN dengan users
            return PemesananTiket::getAll();
        } catch (Exception $e) {
            error_log("PemesananTiketController::getAllForAdmin() - Exception: " . $e->getMessage());
            return [];
        }
    }

    // Tidak perlu getByIdForAdmin jika PemesananTiket::findById sudah cukup detail.
    // public static function getByIdForAdmin($id) { ... }


    public static function updateStatusPemesanan($id, $status_baru)
    {
        global $conn;
        $id_val = filter_var($id, FILTER_VALIDATE_INT);
        $status_val = trim(strtolower($status_baru));
        // Ambil ALLOWED_STATUSES dari model PemesananTiket jika ada atau definisikan di sini
        $allowed_statuses = PemesananTiket::ALLOWED_STATUSES ?? ['pending', 'waiting_payment', 'paid', 'confirmed', 'completed', 'cancelled', 'expired', 'refunded'];


        if ($id_val === false || $id_val <= 0 || empty($status_val) || !in_array($status_val, $allowed_statuses)) {
            set_flash_message('danger', 'Data update status pemesanan tidak valid.');
            error_log("PemesananTiketController::updateStatusPemesanan - Input tidak valid. ID: {$id}, Status: {$status_baru}");
            return false;
        }

        try {
            // self::checkRequiredModels(['PemesananTiket', 'Pembayaran']);

            $current_pemesanan = PemesananTiket::findById($id_val);
            if (!$current_pemesanan) {
                set_flash_message('danger', 'Pemesanan tidak ditemukan untuk diupdate.');
                return false;
            }

            // Logika tambahan jika diperlukan, misalnya:
            // Jika status baru 'paid', dan pembayaran belum 'success', mungkin ada notifikasi atau tindakan lain.
            if ($status_val === 'paid') {
                $pembayaran = Pembayaran::findByPemesananId($id_val);
                if (!$pembayaran || !in_array(strtolower($pembayaran['status_pembayaran']), Pembayaran::SUCCESSFUL_PAYMENT_STATUSES ?? ['success', 'paid', 'confirmed'])) {
                    // Opsi:
                    // 1. Gagal update status pesanan jika pembayaran belum lunas
                    // set_flash_message('warning', "Status pemesanan tidak bisa 'Paid' jika pembayaran belum berhasil. Update status pembayaran terlebih dahulu.");
                    // return false;
                    // 2. Biarkan update, tapi log sebagai peringatan
                    error_log("Peringatan: Pemesanan ID {$id_val} status diubah menjadi 'paid' tetapi status pembayaran adalah '" . ($pembayaran['status_pembayaran'] ?? 'N/A') . "'");
                }
            }

            $updateBerhasil = PemesananTiket::updateStatusPemesanan($id_val, $status_val);
            if (!$updateBerhasil) {
                set_flash_message('danger', 'Gagal memperbarui status pemesanan di database. ' . PemesananTiket::getLastError());
            }
            return $updateBerhasil;
        } catch (Exception $e) {
            error_log("PemesananTiketController::updateStatusPemesanan({$id_val}, {$status_val}) - Exception: " . $e->getMessage());
            set_flash_message('danger', 'Terjadi kesalahan sistem saat update status pemesanan.');
            return false;
        }
    }

    /**
     * Menghapus pemesanan tiket dan semua data terkaitnya (detail tiket, sewa, pembayaran).
     * Memanggil PemesananTiket::delete() yang sudah menangani transaksi.
     * @param int $id_pemesanan ID pemesanan tiket yang akan dihapus.
     * @return bool True jika berhasil, false jika gagal.
     */
    public static function deletePemesananById($id_pemesanan)
    {
        $id_val = filter_var($id_pemesanan, FILTER_VALIDATE_INT);
        if ($id_val === false || $id_val <= 0) {
            set_flash_message('danger', 'ID Pemesanan tidak valid untuk dihapus.');
            return false;
        }

        try {
            // Model PemesananTiket::delete() sudah menangani transaksi dan penghapusan berjenjang
            if (PemesananTiket::delete($id_val)) {
                return true;
            } else {
                // Model PemesananTiket::delete() seharusnya sudah set_flash_message atau log error jika gagal
                if (!isset($_SESSION['flash_message'])) { // Jaga-jaga jika model tidak set
                    set_flash_message('danger', 'Gagal menghapus pemesanan tiket. Operasi di model tidak berhasil.');
                }
                return false;
            }
        } catch (Exception $e) {
            error_log("PemesananTiketController::deletePemesananById({$id_val}) - Exception: " . $e->getMessage());
            set_flash_message('danger', 'Gagal menghapus pemesanan: Terjadi kesalahan. ' . e($e->getMessage()));
            return false;
        }
    }
} // End of class PemesananTiketController