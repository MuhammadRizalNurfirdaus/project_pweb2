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
        }
    }

    public static function prosesPemesananLengkap($data_pemesan, $items_tiket = [], $items_sewa = [])
    {
        global $conn;
        $kode_pemesanan_unik_untuk_log = "BELUM_TERGENERASI"; // Untuk logging jika gagal sebelum generate kode

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

            // --- VALIDASI AWAL SEBELUM TRANSAKSI ---
            // ... (Validasi Anda sudah cukup baik di sini) ...
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
            // ...(Sisa validasi item tiket dan sewa alat)...
            // (Kode validasi item tiket dan sewa alat Anda di sini sudah baik)
            // Pastikan semua set_flash_message diikuti return false.

            $total_harga_semua_tiket = 0;
            $data_detail_tiket_to_save = [];
            foreach ($items_tiket as $key => $item_t) {
                if (empty($item_t['jenis_tiket_id']) || !isset($item_t['jumlah']) || !is_numeric($item_t['jumlah']) || (int)$item_t['jumlah'] <= 0) {
                    set_flash_message('danger', "Data item tiket ke-" . ($key + 1) . " tidak lengkap.");
                    return false;
                }
                $jenis_tiket_id = (int)$item_t['jenis_tiket_id'];
                $jumlah_tiket = (int)$item_t['jumlah'];
                $jenisTiketInfo = JenisTiket::findById($jenis_tiket_id);
                if (!$jenisTiketInfo || (isset($jenisTiketInfo['aktif']) && $jenisTiketInfo['aktif'] == 0)) {
                    set_flash_message('danger', "Jenis tiket (ID: {$jenis_tiket_id}) tidak aktif.");
                    return false;
                }
                $ketersediaan = JadwalKetersediaanTiket::getActiveKetersediaan($jenis_tiket_id, $tanggal_kunjungan_input);
                if (!$ketersediaan || ($ketersediaan['jumlah_saat_ini_tersedia'] ?? 0) < $jumlah_tiket) {
                    set_flash_message('danger', "Kuota tiket \"" . e($jenisTiketInfo['nama_layanan_display'] ?? 'Tiket') . "\" tidak cukup.");
                    return false;
                }
                $harga_satuan_tiket = (float)($jenisTiketInfo['harga'] ?? 0);
                $subtotal_item_tiket = $harga_satuan_tiket * $jumlah_tiket;
                $total_harga_semua_tiket += $subtotal_item_tiket;
                $data_detail_tiket_to_save[] = ['jenis_tiket_id' => $jenis_tiket_id, 'jumlah' => $jumlah_tiket, 'harga_satuan_saat_pesan' => $harga_satuan_tiket, 'subtotal_item' => $subtotal_item_tiket, 'jadwal_ketersediaan_id' => $ketersediaan['id'] ?? null];
            }

            $total_harga_semua_sewa = 0;
            $data_detail_sewa_to_save = [];
            if (!empty($items_sewa) && is_array($items_sewa)) {
                foreach ($items_sewa as $key_s => $item_s) {
                    if (empty($item_s['sewa_alat_id']) || !isset($item_s['jumlah']) || (int)$item_s['jumlah'] <= 0 || empty($item_s['tanggal_mulai_sewa']) || empty($item_s['tanggal_akhir_sewa_rencana'])) {
                        set_flash_message('danger', "Data item sewa ke-" . ($key_s + 1) . " tidak lengkap.");
                        return false;
                    }
                    $dtMulaiSewa = new DateTime($item_s['tanggal_mulai_sewa']);
                    $dtAkhirSewa = new DateTime($item_s['tanggal_akhir_sewa_rencana']);
                    if ($dtMulaiSewa >= $dtAkhirSewa) {
                        set_flash_message('danger', "Tanggal mulai sewa item ke-" . ($key_s + 1) . " harus sebelum akhir.");
                        return false;
                    }
                    $alatInfo = SewaAlat::getById((int)$item_s['sewa_alat_id']);
                    if (!$alatInfo || ($alatInfo['stok_tersedia'] ?? 0) < (int)$item_s['jumlah']) {
                        set_flash_message('danger', "Stok alat sewa \"" . e($alatInfo['nama_item'] ?? 'Alat') . "\" tidak cukup.");
                        return false;
                    }
                    $subtotal_item_sewa = PemesananSewaAlat::calculateSubtotalItem((int)$item_s['jumlah'], (float)($alatInfo['harga_sewa'] ?? 0), (int)($alatInfo['durasi_harga_sewa'] ?? 1), $alatInfo['satuan_durasi_harga'] ?? 'Peminjaman', $dtMulaiSewa->format('Y-m-d H:i:s'), $dtAkhirSewa->format('Y-m-d H:i:s'));
                    $total_harga_semua_sewa += $subtotal_item_sewa;
                    $data_detail_sewa_to_save[] = ['sewa_alat_id' => (int)$item_s['sewa_alat_id'], 'jumlah' => (int)$item_s['jumlah'], 'harga_satuan_saat_pesan' => (float)($alatInfo['harga_sewa'] ?? 0), 'durasi_satuan_saat_pesan' => (int)($alatInfo['durasi_harga_sewa'] ?? 1), 'satuan_durasi_saat_pesan' => $alatInfo['satuan_durasi_harga'] ?? 'Peminjaman', 'tanggal_mulai_sewa' => $dtMulaiSewa->format('Y-m-d H:i:s'), 'tanggal_akhir_sewa_rencana' => $dtAkhirSewa->format('Y-m-d H:i:s'), 'total_harga_item' => $subtotal_item_sewa, 'status_item_sewa' => 'Dipesan', 'catatan_item_sewa' => $item_s['catatan_item_sewa'] ?? null];
                }
            }
            // --- AKHIR VALIDASI AWAL ---

            $grand_total_harga = $total_harga_semua_tiket + $total_harga_semua_sewa;

            mysqli_begin_transaction($conn);
            error_log("PTC::prosesPemesananLengkap - Transaksi DIMULAI.");

            try {
                $kode_pemesanan_unik = 'PT-' . date('Ymd') . strtoupper(bin2hex(random_bytes(4)));
            } catch (Exception $e) {
                $kode_pemesanan_unik = 'PT-' . date('YmdHis') . mt_rand(1000, 9999);
                error_log("PTC::prosesPemesananLengkap - Peringatan: random_bytes gagal, menggunakan fallback kode. Error: " . $e->getMessage());
            }
            $kode_pemesanan_unik_untuk_log = $kode_pemesanan_unik; // Untuk logging di blok catch utama

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

            foreach ($data_detail_tiket_to_save as $item_t_data) {
                $item_t_data['pemesanan_tiket_id'] = $pemesanan_tiket_id_baru;
                if (!DetailPemesananTiket::create($item_t_data)) {
                    throw new Exception("Gagal menyimpan detail item tiket. Model Error: " . (DetailPemesananTiket::getLastError() ?: 'Tidak ada detail error dari model.'));
                }
                if (isset($item_t_data['jadwal_ketersediaan_id']) && $item_t_data['jadwal_ketersediaan_id'] !== null) {
                    if (!JadwalKetersediaanTiket::updateJumlahSaatIniTersedia($item_t_data['jadwal_ketersediaan_id'], -$item_t_data['jumlah'])) {
                        throw new Exception("Gagal mengurangi kuota jadwal tiket ID: " . $item_t_data['jadwal_ketersediaan_id'] . ". Model Error: " . (JadwalKetersediaanTiket::getLastError() ?: 'Tidak ada detail error dari model.'));
                    }
                }
            }
            error_log("PTC::prosesPemesananLengkap - Semua Detail Tiket DIBUAT untuk Header ID {$pemesanan_tiket_id_baru}.");

            foreach ($data_detail_sewa_to_save as $item_s_data) {
                $item_s_data['pemesanan_tiket_id'] = $pemesanan_tiket_id_baru;
                if (!PemesananSewaAlat::create($item_s_data)) {
                    throw new Exception("Gagal menyimpan detail item sewa alat. Model Error: " . (PemesananSewaAlat::getLastError() ?: 'Tidak ada detail error dari model.'));
                }
            }
            if (!empty($data_detail_sewa_to_save)) error_log("PTC::prosesPemesananLengkap - Semua Detail Sewa DIBUAT untuk Header ID {$pemesanan_tiket_id_baru}.");


            $data_pembayaran_awal = [
                'pemesanan_tiket_id' => $pemesanan_tiket_id_baru,
                'kode_pemesanan' => $kode_pemesanan_unik,
                'jumlah_dibayar' => 0.00, // Pastikan float
                'status_pembayaran' => 'pending',
                'metode_pembayaran' => $data_pemesan['metode_pembayaran_pilihan'] ?? 'Belum Dipilih'
            ];
            if (!Pembayaran::create($data_pembayaran_awal)) {
                throw new Exception("Gagal membuat entri pembayaran awal. Model Error: " . (Pembayaran::getLastError() ?: 'Tidak ada detail error dari model.'));
            }
            error_log("PTC::prosesPemesananLengkap - Entri Pembayaran DIBUAT untuk Header ID {$pemesanan_tiket_id_baru}.");

            mysqli_commit($conn);
            error_log("PTC::prosesPemesananLengkap - Transaksi BERHASIL DI-COMMIT untuk kode: {$kode_pemesanan_unik}.");

            set_flash_message('success', 'Pemesanan Anda dengan kode ' . e($kode_pemesanan_unik) . ' berhasil dibuat. Silakan lanjutkan ke pembayaran.');
            return $kode_pemesanan_unik;
        } catch (Exception $e) {
            if (isset($conn) && $conn->thread_id && mysqli_errno($conn) === 0) {
                mysqli_rollback($conn);
                error_log("PTC::prosesPemesananLengkap - Transaksi DI-ROLLBACK untuk kode (potensial): {$kode_pemesanan_unik_untuk_log}.");
            }
            $log_msg = "PTC::prosesPemesananLengkap() - EXCEPTION DITANGKAP. Pesan: " . $e->getMessage();
            if ($e->getPrevious()) $log_msg .= " | Previous: " . $e->getPrevious()->getMessage();
            error_log($log_msg);

            if (!isset($_SESSION['flash_message'])) {
                set_flash_message('danger', 'Terjadi kesalahan internal saat memproses pemesanan: ' . e($e->getMessage()));
            }
            return false;
        }
    }

    /**
     * Mengambil detail pemesanan lengkap berdasarkan ID PEMESANAN TIKET.
     * @param int $pemesanan_tiket_id ID dari tabel pemesanan_tiket.
     * @return array|null Data pemesanan lengkap atau null jika tidak ditemukan/error.
     */
    public static function getDetailPemesananLengkap($pemesanan_tiket_id) // Nama metode ini benar untuk mengambil berdasarkan ID
    {
        $id_val = filter_var($pemesanan_tiket_id, FILTER_VALIDATE_INT);
        error_log(get_called_class() . "::getDetailPemesananLengkap dipanggil dengan ID: " . print_r($pemesanan_tiket_id, true));


        if ($id_val === false || $id_val <= 0) {
            error_log(get_called_class() . "::getDetailPemesananLengkap - ID Pemesanan tidak valid setelah filter: " . print_r($pemesanan_tiket_id, true));
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
                error_log(get_called_class() . "::getDetailPemesananLengkap - Header pemesanan tidak ditemukan untuk ID: {$id_val}. Error Model: " . PemesananTiket::getLastError());
                return null;
            }

            $data_pemesanan['detail_tiket'] = DetailPemesananTiket::getByPemesananTiketId($id_val);
            $data_pemesanan['detail_sewa'] = PemesananSewaAlat::getByPemesananTiketId($id_val);
            $data_pemesanan['pembayaran'] = Pembayaran::findByPemesananId($id_val); // Mengambil pembayaran berdasarkan pemesanan_tiket_id

            return $data_pemesanan;
        } catch (Exception $e) {
            error_log(get_called_class() . "::getDetailPemesananLengkap() - Exception untuk ID {$id_val}: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Mengambil detail pemesanan lengkap berdasarkan KODE PEMESANAN.
     * @param string $kode_pemesanan Kode unik pemesanan.
     * @return array|null Data pemesanan lengkap atau null jika tidak ditemukan.
     */
    public static function getPemesananLengkapByKode($kode_pemesanan) // Metode ini sudah ada dan benar
    {
        $kode_pemesanan_clean = trim((string)$kode_pemesanan);
        error_log(get_called_class() . "::getPemesananLengkapByKode dipanggil dengan KODE: " . $kode_pemesanan_clean);

        if (empty($kode_pemesanan_clean)) {
            error_log(get_called_class() . "::getPemesananLengkapByKode() - Kode pemesanan kosong.");
            return null;
        }

        try {
            self::checkRequiredModels(['PemesananTiket']);
            $headerPemesanan = PemesananTiket::getByKodePemesanan($kode_pemesanan_clean);

            if (!$headerPemesanan || !isset($headerPemesanan['id'])) {
                error_log(get_called_class() . "::getPemesananLengkapByKode() - Header pemesanan tidak ditemukan untuk kode: " . e($kode_pemesanan_clean) . ". Error Model: " . PemesananTiket::getLastError());
                return null; // Penting untuk return null jika header tidak ditemukan
            }

            // Jika header ditemukan, gunakan ID-nya untuk memanggil getDetailPemesananLengkap
            return self::getDetailPemesananLengkap((int)$headerPemesanan['id']);
        } catch (Exception $e) {
            error_log(get_called_class() . "::getPemesananLengkapByKode() - Exception untuk kode " . e($kode_pemesanan_clean) . ": " . $e->getMessage());
            return null;
        }
    }

    // ... (Sisa metode: getAllForAdmin, updateStatusPemesanan, deletePemesananById sudah baik) ...
    public static function getAllForAdmin()
    {
        try {
            self::checkRequiredModels(['PemesananTiket']);
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

        // Pastikan konstanta ALLOWED_STATUSES ada di model PemesananTiket
        $allowed_statuses = defined('PemesananTiket::ALLOWED_STATUSES') ? PemesananTiket::ALLOWED_STATUSES : ['pending', 'waiting_payment', 'paid', 'confirmed', 'completed', 'cancelled', 'expired', 'refunded'];


        if ($id_val === false || $id_val <= 0 || empty($status_val) || !in_array($status_val, $allowed_statuses)) {
            set_flash_message('danger', 'Data update status pemesanan tidak valid.');
            error_log(get_called_class() . "::updateStatusPemesanan - Input tidak valid. ID: {$id}, Status: {$status_baru}");
            return false;
        }

        try {
            self::checkRequiredModels(['PemesananTiket', 'Pembayaran']);
            $current_pemesanan = PemesananTiket::findById($id_val);
            if (!$current_pemesanan) {
                set_flash_message('danger', 'Pemesanan tidak ditemukan untuk diupdate.');
                return false;
            }

            $successful_payment_statuses = defined('Pembayaran::SUCCESSFUL_PAYMENT_STATUSES') ? Pembayaran::SUCCESSFUL_PAYMENT_STATUSES : ['success', 'paid', 'confirmed'];
            if ($status_val === 'paid') {
                $pembayaran = Pembayaran::findByPemesananId($id_val);
                if (!$pembayaran || !in_array(strtolower($pembayaran['status_pembayaran'] ?? ''), $successful_payment_statuses)) {
                    error_log("Peringatan: Pemesanan ID {$id_val} status diubah menjadi 'paid' tetapi status pembayaran adalah '" . ($pembayaran['status_pembayaran'] ?? 'N/A') . "' atau tidak ditemukan.");
                }
            }

            $updateBerhasil = PemesananTiket::updateStatusPemesanan($id_val, $status_val);
            if (!$updateBerhasil) {
                set_flash_message('danger', 'Gagal memperbarui status pemesanan di database. ' . PemesananTiket::getLastError());
            }
            return $updateBerhasil;
        } catch (Exception $e) {
            error_log(get_called_class() . "::updateStatusPemesanan({$id_val}, {$status_val}) - Exception: " . $e->getMessage());
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
            self::checkRequiredModels(['PemesananTiket']);
            if (PemesananTiket::delete($id_val)) {
                return true;
            } else {
                if (!isset($_SESSION['flash_message'])) {
                    set_flash_message('danger', 'Gagal menghapus pemesanan tiket. ' . PemesananTiket::getLastError());
                }
                return false;
            }
        } catch (Exception $e) {
            error_log(get_called_class() . "::deletePemesananById({$id_val}) - Exception: " . $e->getMessage());
            set_flash_message('danger', 'Gagal menghapus pemesanan: Terjadi kesalahan sistem. ' . e($e->getMessage()));
            return false;
        }
    }
}
// End of PemesananTiketController.php