<?php
// File: C:\xampp\htdocs\Cilengkrang-Web-Wisata\controllers\PemesananTiketController.php

class PemesananTiketController
{
    private static function checkRequiredModels(array $model_names)
    {
        foreach ($model_names as $model_name) {
            if (!class_exists($model_name)) {
                $error_msg = "PemesananTiketController Fatal Error: Model {$model_name} tidak ditemukan atau tidak dimuat.";
                error_log($error_msg);
                // Sebaiknya lempar exception yang lebih spesifik jika memungkinkan atau tangani error ini dengan cara lain
                throw new RuntimeException($error_msg);
            }
        }
    }

    public static function prosesPemesananLengkap($data_pemesan, $items_tiket = [], $items_sewa = [])
    {
        global $conn; // Pastikan $conn tersedia dan diinisialisasi dari config.php

        try {
            // Pengecekan model-model yang dibutuhkan
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
                // Jika $conn tidak ada atau error, ini seharusnya sudah ditangani oleh model saat checkDbConnection
                // Namun, pengecekan awal di sini juga baik.
                throw new RuntimeException("Koneksi database tidak tersedia untuk memproses pemesanan.");
            }

            // --- VALIDASI AWAL SEBELUM TRANSAKSI ---
            $tanggal_kunjungan_input = $data_pemesan['tanggal_kunjungan'] ?? '';
            if (empty($tanggal_kunjungan_input) || !($dtKunjungan = DateTime::createFromFormat('Y-m-d', $tanggal_kunjungan_input)) || $dtKunjungan->format('Y-m-d') !== $tanggal_kunjungan_input) {
                set_flash_message('danger', 'Tanggal kunjungan tidak valid atau tidak diisi (format YYYY-MM-DD).');
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
            $data_detail_tiket_to_save = [];

            // Validasi Ketersediaan Tiket SEBELUM Transaksi
            foreach ($items_tiket as $key => $item_t) {
                if (empty($item_t['jenis_tiket_id']) || !isset($item_t['jumlah']) || !is_numeric($item_t['jumlah']) || (int)$item_t['jumlah'] <= 0) {
                    set_flash_message('danger', "Data item tiket ke-" . ($key + 1) . " tidak lengkap atau jumlah tidak valid.");
                    return false;
                }
                $jenis_tiket_id = (int)$item_t['jenis_tiket_id'];
                $jumlah_tiket = (int)$item_t['jumlah'];

                $jenisTiketInfo = JenisTiket::findById($jenis_tiket_id);
                if (!$jenisTiketInfo || (isset($jenisTiketInfo['aktif']) && $jenisTiketInfo['aktif'] == 0)) {
                    set_flash_message('danger', "Jenis tiket (ID: {$jenis_tiket_id}) tidak valid atau tidak aktif.");
                    return false;
                }

                $ketersediaan = JadwalKetersediaanTiket::getActiveKetersediaan($jenis_tiket_id, $tanggal_kunjungan_input);
                if (!$ketersediaan || !isset($ketersediaan['jumlah_saat_ini_tersedia']) || $ketersediaan['jumlah_saat_ini_tersedia'] < $jumlah_tiket) {
                    $nama_tiket_error = e($jenisTiketInfo['nama_layanan_display'] ?? $jenisTiketInfo['nama_tiket'] ?? ('Tiket ID:' . $jenis_tiket_id));
                    $tanggal_error = function_exists('formatTanggalIndonesia') ? e(formatTanggalIndonesia($tanggal_kunjungan_input)) : e($tanggal_kunjungan_input);
                    $sisa_kuota_error = $ketersediaan['jumlah_saat_ini_tersedia'] ?? 0;
                    set_flash_message('danger', "Kuota tiket \"{$nama_tiket_error}\" untuk tanggal {$tanggal_error} tidak mencukupi (tersisa: {$sisa_kuota_error}, diminta: {$jumlah_tiket}).");
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

            $total_harga_semua_sewa = 0;
            $data_detail_sewa_to_save = [];

            if (!empty($items_sewa) && is_array($items_sewa)) {
                foreach ($items_sewa as $key_s => $item_s) {
                    if (empty($item_s['sewa_alat_id']) || !isset($item_s['jumlah']) || (int)$item_s['jumlah'] <= 0 || empty($item_s['tanggal_mulai_sewa']) || empty($item_s['tanggal_akhir_sewa_rencana'])) {
                        set_flash_message('danger', "Data item sewa ke-" . ($key_s + 1) . " tidak lengkap (alat, jumlah, atau tanggal).");
                        return false;
                    }

                    $tgl_mulai_str = $item_s['tanggal_mulai_sewa'];
                    $tgl_akhir_str = $item_s['tanggal_akhir_sewa_rencana'];

                    try {
                        $dtMulaiSewa = new DateTime($tgl_mulai_str);
                        $dtAkhirSewa = new DateTime($tgl_akhir_str);
                    } catch (Exception $e) {
                        set_flash_message('danger', "Format tanggal sewa item ke-" . ($key_s + 1) . " tidak valid.");
                        return false;
                    }

                    if ($dtMulaiSewa >= $dtAkhirSewa) {
                        set_flash_message('danger', "Tanggal mulai sewa item ke-" . ($key_s + 1) . " harus sebelum tanggal akhir.");
                        return false;
                    }

                    $alatInfo = SewaAlat::getById((int)$item_s['sewa_alat_id']);
                    if (!$alatInfo || !isset($alatInfo['stok_tersedia']) || $alatInfo['stok_tersedia'] < (int)$item_s['jumlah']) {
                        $nama_alat_error = e($alatInfo['nama_item'] ?? ('Alat ID:' . (int)$item_s['sewa_alat_id']));
                        $sisa_stok_error = $alatInfo['stok_tersedia'] ?? 0;
                        set_flash_message('danger', "Stok alat sewa \"{$nama_alat_error}\" tidak mencukupi (tersisa: {$sisa_stok_error}, diminta: " . (int)$item_s['jumlah'] . ").");
                        return false;
                    }

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
            // --- AKHIR VALIDASI AWAL ---

            $grand_total_harga = $total_harga_semua_tiket + $total_harga_semua_sewa;

            mysqli_begin_transaction($conn);

            $kode_pemesanan_unik = 'PT-' . date('Ymd') . strtoupper(bin2hex(random_bytes(4)));
            $data_header_pemesanan = [
                'user_id' => $is_guest ? null : (int)$data_pemesan['user_id'],
                'nama_pemesan_tamu' => $is_guest ? trim($data_pemesan['nama_pemesan_tamu']) : null,
                'email_pemesan_tamu' => $is_guest ? trim($data_pemesan['email_pemesan_tamu']) : null,
                'nohp_pemesan_tamu' => $is_guest ? trim($data_pemesan['nohp_pemesan_tamu']) : null,
                'kode_pemesanan' => $kode_pemesanan_unik, // Key ini dikirim ke Model
                'tanggal_kunjungan' => $tanggal_kunjungan_input,
                'total_harga_akhir' => $grand_total_harga,
                'status' => 'pending',
                'catatan_umum_pemesanan' => $data_pemesan['catatan_umum_pemesanan'] ?? null
            ];

            // error_log("Data Header Pemesanan ke Model: " . print_r($data_header_pemesanan, true)); // Untuk Debugging
            $pemesanan_tiket_id_baru = PemesananTiket::create($data_header_pemesanan);
            if (!$pemesanan_tiket_id_baru) {
                // Jika error terjadi di sini, PemesananTiket::getLastError() akan berisi pesan dari MySQL
                throw new Exception("Gagal membuat header pemesanan tiket. " . PemesananTiket::getLastError());
            }

            // Simpan detail tiket dan update kuota
            foreach ($data_detail_tiket_to_save as $item_t_data) {
                $item_t_data['pemesanan_tiket_id'] = $pemesanan_tiket_id_baru;
                if (!DetailPemesananTiket::create($item_t_data)) {
                    throw new Exception("Gagal menyimpan detail item tiket. " . DetailPemesananTiket::getLastError());
                }
                if (isset($item_t_data['jadwal_ketersediaan_id']) && $item_t_data['jadwal_ketersediaan_id'] !== null) {
                    if (!JadwalKetersediaanTiket::updateJumlahSaatIniTersedia($item_t_data['jadwal_ketersediaan_id'], -$item_t_data['jumlah'])) {
                        throw new Exception("Gagal mengurangi kuota jadwal tiket ID: " . $item_t_data['jadwal_ketersediaan_id'] . ". " . JadwalKetersediaanTiket::getLastError());
                    }
                }
            }
            // Simpan detail sewa (jika ada) dan update stok alat
            foreach ($data_detail_sewa_to_save as $item_s_data) {
                $item_s_data['pemesanan_tiket_id'] = $pemesanan_tiket_id_baru;
                if (!PemesananSewaAlat::create($item_s_data)) {
                    throw new Exception("Gagal menyimpan detail item sewa alat. " . PemesananSewaAlat::getLastError());
                }
            }

            // Buat entri pembayaran awal
            $data_pembayaran_awal = [
                'pemesanan_tiket_id' => $pemesanan_tiket_id_baru,
                'kode_pemesanan' => $kode_pemesanan_unik,
                'jumlah_dibayar' => 0,
                'status_pembayaran' => 'pending',
                'metode_pembayaran' => $data_pemesan['metode_pembayaran_pilihan'] ?? 'Belum Dipilih'
            ];
            if (!Pembayaran::create($data_pembayaran_awal)) {
                throw new Exception("Gagal membuat entri pembayaran awal. " . Pembayaran::getLastError());
            }

            mysqli_commit($conn);
            set_flash_message('success', 'Pemesanan Anda dengan kode ' . e($kode_pemesanan_unik) . ' berhasil dibuat. Silakan lanjutkan ke pembayaran.');
            return $kode_pemesanan_unik; // Sukses

        } catch (Exception $e) {
            $is_transaction_active = false;
            if (isset($conn) && $conn->thread_id && mysqli_errno($conn) === 0) {
                // Coba cek apakah transaksi aktif sebelum rollback (PHP 8+)
                if (mysqli_errno($conn) === 0) {
                    $is_transaction_active = true;
                } elseif (!function_exists('mysqli_get_transaction_state')) {
                    // Untuk PHP < 8, asumsikan aktif jika koneksi masih ada
                    $is_transaction_active = true;
                }
            }

            if ($is_transaction_active) {
                mysqli_rollback($conn);
            }
            $log_msg = "PemesananTiketController::prosesPemesananLengkap() - Exception Transaksi: " . $e->getMessage();
            if ($e->getPrevious()) $log_msg .= " | Previous: " . $e->getPrevious()->getMessage();
            error_log($log_msg);

            if (!isset($_SESSION['flash_message'])) { // Hanya set jika belum ada pesan dari validasi awal
                set_flash_message('danger', 'Terjadi kesalahan saat memproses pemesanan: ' . e($e->getMessage()));
            }
            return false; // Gagal
        }
    }

    public static function getDetailPemesananLengkap($pemesanan_tiket_id)
    {
        $id_val = filter_var($pemesanan_tiket_id, FILTER_VALIDATE_INT);
        // error_log("--- PemesananTiketController::getDetailPemesananLengkap START untuk ID: " . print_r($pemesanan_tiket_id, true) . " ---");

        if ($id_val === false || $id_val <= 0) {
            // error_log("PemesananTiketController::getDetailPemesananLengkap - ID Pemesanan TIDAK VALID setelah filter: " . print_r($pemesanan_tiket_id, true));
            return null;
        }
        // error_log("PemesananTiketController::getDetailPemesananLengkap - ID Pemesanan VALID: " . $id_val);

        try {
            self::checkRequiredModels(['PemesananTiket', 'DetailPemesananTiket', 'PemesananSewaAlat', 'Pembayaran']);
            $data_pemesanan = [
                'header' => null,
                'detail_tiket' => [],
                'detail_sewa' => [],
                'pembayaran' => null
            ];

            // error_log("Memanggil PemesananTiket::findById({$id_val})");
            $data_pemesanan['header'] = PemesananTiket::findById($id_val);

            if (!$data_pemesanan['header']) {
                // error_log("PemesananTiket::findById({$id_val}) GAGAL. Info Model: " . PemesananTiket::getLastError());
                // error_log("--- PemesananTiketController::getDetailPemesananLengkap END (Header Gagal) untuk ID: {$id_val} ---");
                return null;
            }
            // error_log("Header pemesanan ID {$id_val} DITEMUKAN.");

            // error_log("Memanggil DetailPemesananTiket::getByPemesananTiketId({$id_val})");
            $data_pemesanan['detail_tiket'] = DetailPemesananTiket::getByPemesananTiketId($id_val);

            // error_log("Memanggil PemesananSewaAlat::getByPemesananTiketId({$id_val})");
            $data_pemesanan['detail_sewa'] = PemesananSewaAlat::getByPemesananTiketId($id_val);

            // error_log("Memanggil Pembayaran::findByPemesananId({$id_val})");
            $data_pemesanan['pembayaran'] = Pembayaran::findByPemesananId($id_val);

            // error_log("--- PemesananTiketController::getDetailPemesananLengkap END (Sukses) untuk ID: {$id_val} ---");
            return $data_pemesanan;
        } catch (Exception $e) {
            error_log("PemesananTiketController::getDetailPemesananLengkap() - Exception untuk ID {$id_val}: " . $e->getMessage() . "\nTrace: " . $e->getTraceAsString());
            // error_log("--- PemesananTiketController::getDetailPemesananLengkap END (Exception) untuk ID: {$id_val} ---");
            return null;
        }
    }

    public static function getAllForAdmin()
    {
        try {
            self::checkRequiredModels(['PemesananTiket']);
            return PemesananTiket::getAll();
        } catch (Exception $e) {
            error_log("PemesananTiketController::getAllForAdmin() - Exception: " . $e->getMessage());
            return [];
        }
    }

    public static function updateStatusPemesanan($id, $status_baru)
    {
        $id_val = filter_var($id, FILTER_VALIDATE_INT);
        $status_val = trim(strtolower($status_baru));

        if ($id_val === false || $id_val <= 0 || empty($status_val) || !in_array($status_val, PemesananTiket::ALLOWED_STATUSES)) {
            set_flash_message('danger', 'Data update status pemesanan tidak valid.');
            error_log("PemesananTiketController::updateStatusPemesanan - Input tidak valid. ID: {$id}, Status: {$status_baru}");
            return false;
        }

        try {
            self::checkRequiredModels(['PemesananTiket', 'Pembayaran']);
            $current_pemesanan = PemesananTiket::findById($id_val);
            if (!$current_pemesanan) {
                set_flash_message('danger', 'Pemesanan tidak ditemukan untuk diupdate.');
                return false;
            }

            // Jika status diubah menjadi 'paid', idealnya cek status pembayaran juga
            if ($status_val === 'paid') {
                $pembayaran = Pembayaran::findByPemesananId($id_val);
                if (!$pembayaran || !in_array(strtolower($pembayaran['status_pembayaran'] ?? ''), Pembayaran::SUCCESSFUL_PAYMENT_STATUSES)) {
                    error_log("Peringatan: Pemesanan ID {$id_val} status diubah menjadi 'paid' tetapi status pembayaran adalah '" . ($pembayaran['status_pembayaran'] ?? 'N/A') . "' atau tidak ditemukan.");
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

    public static function deletePemesananById($id_pemesanan)
    {
        $id_val = filter_var($id_pemesanan, FILTER_VALIDATE_INT);
        if ($id_val === false || $id_val <= 0) {
            set_flash_message('danger', 'ID Pemesanan tidak valid untuk dihapus.');
            return false;
        }

        try {
            self::checkRequiredModels(['PemesananTiket']); // Model delete sudah handle relasi
            if (PemesananTiket::delete($id_val)) {
                // set_flash_message('success', 'Pemesanan berhasil dihapus.'); // Dihapus jika tidak ingin override pesan dari model
                return true;
            } else {
                if (!isset($_SESSION['flash_message'])) { // Hanya set jika model tidak set
                    set_flash_message('danger', 'Gagal menghapus pemesanan tiket. ' . PemesananTiket::getLastError());
                }
                return false;
            }
        } catch (Exception $e) {
            error_log("PemesananTiketController::deletePemesananById({$id_val}) - Exception: " . $e->getMessage());
            set_flash_message('danger', 'Gagal menghapus pemesanan: Terjadi kesalahan sistem. ' . e($e->getMessage()));
            return false;
        }
    }
}
// End of PemesananTiketController.php