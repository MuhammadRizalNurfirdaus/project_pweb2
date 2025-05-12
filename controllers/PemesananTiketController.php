<?php
// File: C:\xampp\htdocs\Cilengkrang-Web-Wisata\controllers\PemesananTiketController.php

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../models/PemesananTiket.php';
require_once __DIR__ . '/../models/DetailPemesananTiket.php'; // Diperlukan untuk menyimpan detail item tiket
require_once __DIR__ . '/../models/PemesananSewaAlat.php';    // Diperlukan untuk menyimpan detail item sewa
require_once __DIR__ . '/../models/Pembayaran.php';          // Diperlukan untuk membuat entri pembayaran awal
require_once __DIR__ . '/../models/JenisTiket.php';          // Untuk mengambil info harga jenis tiket
require_once __DIR__ . '/../models/SewaAlat.php';            // Untuk mengambil info harga sewa alat & update stok
require_once __DIR__ . '/../models/JadwalKetersediaanTiket.php'; // Jika menggunakan manajemen stok tiket

class PemesananTiketController
{
    /**
     * Membuat pemesanan tiket baru beserta detail tiket, detail sewa (jika ada),
     * dan entri pembayaran awal. Ini bertindak sebagai orkestrator.
     *
     * @param array $data_pemesan Informasi pemesan (user_id atau data tamu) dan tanggal kunjungan.
     *                           Kunci: ['user_id', 'nama_pemesan_tamu', 'email_pemesan_tamu', 'nohp_pemesan_tamu', 'tanggal_kunjungan', 'catatan_umum_pemesanan' (opsional)]
     * @param array $items_tiket Array dari item tiket yang dipesan.
     *                           Setiap item: ['jenis_tiket_id', 'jumlah']
     * @param array $items_sewa Array dari item alat yang disewa (opsional).
     *                          Setiap item: ['sewa_alat_id', 'jumlah', 'tanggal_mulai_sewa', 'tanggal_akhir_sewa_rencana']
     * @return string|false Kode pemesanan jika berhasil, false jika ada kegagalan.
     */
    public static function prosesPemesananLengkap($data_pemesan, $items_tiket = [], $items_sewa = [])
    {
        global $conn; // Diperlukan untuk transaksi database

        // --- Validasi Input Awal ---
        if (empty($data_pemesan['tanggal_kunjungan']) || !DateTime::createFromFormat('Y-m-d', $data_pemesan['tanggal_kunjungan'])) {
            set_flash_message('danger', 'Tanggal kunjungan tidak valid atau tidak diisi.');
            return false;
        }
        if (empty($items_tiket) || !is_array($items_tiket)) {
            set_flash_message('danger', 'Minimal harus ada satu item tiket yang dipesan.');
            return false;
        }
        $is_guest = !(isset($data_pemesan['user_id']) && !empty($data_pemesan['user_id']) && is_numeric($data_pemesan['user_id']));
        if ($is_guest) {
            if (empty($data_pemesan['nama_pemesan_tamu']) || empty($data_pemesan['email_pemesan_tamu']) || !filter_var($data_pemesan['email_pemesan_tamu'], FILTER_VALIDATE_EMAIL) || empty($data_pemesan['nohp_pemesan_tamu'])) {
                set_flash_message('danger', 'Untuk tamu, nama, email yang valid, dan nomor HP wajib diisi.');
                return false;
            }
        }

        $total_harga_semua_tiket = 0;
        $total_harga_semua_sewa = 0;
        $data_detail_tiket_to_save = [];
        $data_detail_sewa_to_save = [];

        // --- 1. Validasi dan Persiapan Data Item Tiket ---
        foreach ($items_tiket as $item_t) {
            if (empty($item_t['jenis_tiket_id']) || !isset($item_t['jumlah']) || (int)$item_t['jumlah'] <= 0) {
                set_flash_message('danger', 'Data salah satu item tiket tidak lengkap atau jumlah tidak valid.');
                return false;
            }
            $jenis_tiket_id = (int)$item_t['jenis_tiket_id'];
            $jumlah_tiket = (int)$item_t['jumlah'];

            $jenisTiketInfo = JenisTiket::getById($jenis_tiket_id);
            if (!$jenisTiketInfo || $jenisTiketInfo['aktif'] == 0) {
                set_flash_message('danger', "Jenis tiket yang dipilih (ID: {$jenis_tiket_id}) tidak valid atau tidak aktif.");
                return false;
            }

            // Cek Ketersediaan Stok Tiket dari Jadwal (jika diimplementasikan)
            if (class_exists('JadwalKetersediaanTiket') && method_exists('JadwalKetersediaanTiket', 'getActiveKetersediaan')) {
                $ketersediaan = JadwalKetersediaanTiket::getActiveKetersediaan($jenis_tiket_id, $data_pemesan['tanggal_kunjungan']);
                if (!$ketersediaan || $ketersediaan['jumlah_saat_ini_tersedia'] < $jumlah_tiket) {
                    set_flash_message('danger', 'Kuota untuk jenis tiket "' . e($jenisTiketInfo['nama_layanan_display']) . ' (' . e($jenisTiketInfo['tipe_hari']) . ')" pada tanggal ' . e($data_pemesan['tanggal_kunjungan']) . ' tidak mencukupi (tersisa: ' . ($ketersediaan['jumlah_saat_ini_tersedia'] ?? 0) . ', diminta: ' . $jumlah_tiket . ').');
                    return false;
                }
                // Simpan ID jadwal untuk update stok nanti
                $item_t['jadwal_id_to_update'] = $ketersediaan['id'] ?? null;
            }

            $harga_satuan_tiket = (int)$jenisTiketInfo['harga'];
            $subtotal_item_tiket = $harga_satuan_tiket * $jumlah_tiket;
            $total_harga_semua_tiket += $subtotal_item_tiket;

            $data_detail_tiket_to_save[] = [
                'jenis_tiket_id' => $jenis_tiket_id,
                'jumlah' => $jumlah_tiket,
                'harga_satuan_saat_pesan' => $harga_satuan_tiket,
                'subtotal_item' => $subtotal_item_tiket,
                'jadwal_id_to_update' => $item_t['jadwal_id_to_update'] ?? null
            ];
        }

        // --- 2. Validasi dan Persiapan Data Item Sewa Alat (jika ada) ---
        if (!empty($items_sewa) && is_array($items_sewa)) {
            foreach ($items_sewa as $item_s) {
                if (
                    empty($item_s['sewa_alat_id']) || !isset($item_s['jumlah']) || (int)$item_s['jumlah'] <= 0 ||
                    empty($item_s['tanggal_mulai_sewa']) || !DateTime::createFromFormat('Y-m-d H:i:s', $item_s['tanggal_mulai_sewa']) ||
                    empty($item_s['tanggal_akhir_sewa_rencana']) || !DateTime::createFromFormat('Y-m-d H:i:s', $item_s['tanggal_akhir_sewa_rencana']) ||
                    (new DateTime($item_s['tanggal_mulai_sewa']) >= new DateTime($item_s['tanggal_akhir_sewa_rencana']))
                ) {
                    set_flash_message('danger', 'Data salah satu item sewa tidak lengkap, jumlah, atau format/rentang tanggal sewa tidak valid.');
                    return false;
                }
                $sewa_alat_id = (int)$item_s['sewa_alat_id'];
                $jumlah_sewa = (int)$item_s['jumlah'];

                $alatInfo = SewaAlat::getById($sewa_alat_id);
                if (!$alatInfo || $alatInfo['stok_tersedia'] < $jumlah_sewa) {
                    set_flash_message('danger', 'Alat sewa "' . e($alatInfo['nama_item'] ?? 'ID: ' . $sewa_alat_id) . '" tidak tersedia atau stok tidak mencukupi (tersisa: ' . ($alatInfo['stok_tersedia'] ?? 0) . ', diminta: ' . $jumlah_sewa . ').');
                    return false;
                }

                // Logika perhitungan harga sewa berdasarkan durasi
                // Ini adalah contoh, sesuaikan dengan logika bisnis Anda
                $harga_satuan_alat = (int)$alatInfo['harga_sewa'];
                $durasi_harga_alat = (int)$alatInfo['durasi_harga_sewa'];
                $satuan_durasi_alat = $alatInfo['satuan_durasi_harga'];
                $subtotal_item_sewa = 0;

                $tglMulaiSewa = new DateTime($item_s['tanggal_mulai_sewa']);
                $tglAkhirSewa = new DateTime($item_s['tanggal_akhir_sewa_rencana']);
                $interval = $tglMulaiSewa->diff($tglAkhirSewa);

                $faktor_pengali_durasi = 1; // Default untuk 'Peminjaman'
                if ($satuan_durasi_alat == 'Hari') {
                    $faktor_pengali_durasi = $interval->days + ($interval->h > 0 || $interval->i > 0 || $interval->s > 0 ? 1 : 0); // Pembulatan ke atas hari
                    $faktor_pengali_durasi = ceil($faktor_pengali_durasi / $durasi_harga_alat);
                } elseif ($satuan_durasi_alat == 'Jam') {
                    $total_jam = ($interval->days * 24) + $interval->h + ($interval->i > 0 || $interval->s > 0 ? 1 : 0); // Pembulatan ke atas jam
                    $faktor_pengali_durasi = ceil($total_jam / $durasi_harga_alat);
                }
                $subtotal_item_sewa = $harga_satuan_alat * $jumlah_sewa * $faktor_pengali_durasi;

                $total_harga_semua_sewa += $subtotal_item_sewa;
                $data_detail_sewa_to_save[] = [
                    'sewa_alat_id' => $sewa_alat_id,
                    'jumlah' => $jumlah_sewa,
                    'harga_satuan_saat_pesan' => $harga_satuan_alat,
                    'durasi_satuan_saat_pesan' => $durasi_harga_alat,
                    'satuan_durasi_saat_pesan' => $satuan_durasi_alat,
                    'tanggal_mulai_sewa' => $item_s['tanggal_mulai_sewa'],
                    'tanggal_akhir_sewa_rencana' => $item_s['tanggal_akhir_sewa_rencana'],
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
            // 4. Buat Header Pemesanan Tiket
            $kode_pemesanan_unik = 'PT-' . strtoupper(uniqid() . bin2hex(random_bytes(3)));
            $data_header_pemesanan = [
                'user_id' => $is_guest ? null : (int)$data_pemesan['user_id'],
                'nama_pemesan_tamu' => $is_guest ? trim($data_pemesan['nama_pemesan_tamu']) : null,
                'email_pemesan_tamu' => $is_guest ? trim($data_pemesan['email_pemesan_tamu']) : null,
                'nohp_pemesan_tamu' => $is_guest ? trim($data_pemesan['nohp_pemesan_tamu']) : null,
                'kode_pemesanan' => $kode_pemesanan_unik,
                'tanggal_kunjungan' => $data_pemesan['tanggal_kunjungan'],
                'total_harga_akhir' => $grand_total_harga,
                'status_pemesanan' => 'pending', // Atau 'waiting_payment' jika langsung ke pembayaran
                'catatan_umum_pemesanan' => $data_pemesan['catatan_umum_pemesanan'] ?? null
            ];
            $pemesanan_tiket_id_baru = PemesananTiket::create($data_header_pemesanan);

            if (!$pemesanan_tiket_id_baru) {
                throw new Exception("Gagal membuat header pemesanan tiket.");
            }

            // 5. Simpan Detail Item Tiket
            foreach ($data_detail_tiket_to_save as $item_t_data) {
                $item_t_data['pemesanan_tiket_id'] = $pemesanan_tiket_id_baru;
                if (!DetailPemesananTiket::create($item_t_data)) {
                    throw new Exception("Gagal menyimpan detail item tiket.");
                }
                // Update stok tiket di JadwalKetersediaan
                if (isset($item_t_data['jadwal_id_to_update']) && class_exists('JadwalKetersediaanTiket')) {
                    if (!JadwalKetersediaanTiket::updateJumlahSaatIniTersedia($item_t_data['jadwal_id_to_update'], -$item_t_data['jumlah'])) {
                        throw new Exception("Gagal update stok jadwal tiket untuk jenis tiket ID: " . $item_t_data['jenis_tiket_id']);
                    }
                }
            }

            // 6. Simpan Detail Item Sewa Alat (jika ada)
            foreach ($data_detail_sewa_to_save as $item_s_data) {
                $item_s_data['pemesanan_tiket_id'] = $pemesanan_tiket_id_baru;
                if (!PemesananSewaAlat::create($item_s_data)) { // Model PemesananSewaAlat akan update stok alat
                    throw new Exception("Gagal menyimpan detail item sewa alat.");
                }
            }

            // 7. Buat Entri Pembayaran Awal
            $data_pembayaran = [
                'pemesanan_tiket_id' => $pemesanan_tiket_id_baru,
                'jumlah_dibayar' => $grand_total_harga,
                'status_pembayaran' => 'pending'
            ];
            if (!Pembayaran::create($data_pembayaran)) {
                throw new Exception("Gagal membuat entri pembayaran awal.");
            }

            mysqli_commit($conn);
            return $kode_pemesanan_unik; // Kembalikan kode pemesanan untuk ditampilkan ke pengguna

        } catch (Exception $e) {
            mysqli_rollback($conn);
            error_log("PemesananTiketController::prosesPemesananLengkap() - Exception: " . $e->getMessage());
            set_flash_message('danger', 'Terjadi kesalahan saat memproses pemesanan: ' . $e->getMessage());
            return false;
        }
    }


    public static function getAll()
    {
        return PemesananTiket::getAll();
    }

    public static function getById($id)
    {
        $id_val = filter_var($id, FILTER_VALIDATE_INT);
        if ($id_val === false || $id_val <= 0) {
            return null;
        }
        return PemesananTiket::getById($id_val);
    }

    public static function getDetailPemesananLengkap($pemesanan_tiket_id)
    {
        $id_val = filter_var($pemesanan_tiket_id, FILTER_VALIDATE_INT);
        if ($id_val === false || $id_val <= 0) {
            return null;
        }

        $data_pemesanan = [];
        $data_pemesanan['header'] = PemesananTiket::getById($id_val);
        if (!$data_pemesanan['header']) {
            return null;
        }

        $data_pemesanan['detail_tiket'] = DetailPemesananTiket::getByPemesananTiketId($id_val);
        $data_pemesanan['detail_sewa'] = PemesananSewaAlat::getByPemesananTiketId($id_val);
        $data_pemesanan['pembayaran'] = Pembayaran::getByPemesananTiketId($id_val); // Pastikan method ini ada di Pembayaran.php

        return $data_pemesanan;
    }

    public static function updateStatus($id, $status)
    {
        $id_val = filter_var($id, FILTER_VALIDATE_INT);
        // Validasi status bisa ditambahkan di sini atau di Model
        if ($id_val === false || $id_val <= 0 || empty(trim($status))) {
            return false;
        }
        return PemesananTiket::updateStatus($id_val, trim($status));
    }

    public static function delete($id)
    {
        $id_val = filter_var($id, FILTER_VALIDATE_INT);
        if ($id_val === false || $id_val <= 0) {
            return false;
        }
        // Pertimbangkan konsekuensi delete. Model akan menghapus header.
        // Jika FK diset ON DELETE CASCADE, detail akan ikut terhapus.
        // Stok alat/tiket perlu dikembalikan jika pemesanan dibatalkan/dihapus sebelum digunakan.
        // Logika pengembalian stok lebih baik ada di Model PemesananTiket::delete() atau method khusus pembatalan.
        return PemesananTiket::delete($id_val);
    }

    public static function getByUserId($user_id, $limit = null)
    {
        $id_val = filter_var($user_id, FILTER_VALIDATE_INT);
        if ($id_val === false || $id_val <= 0) {
            return [];
        }
        return PemesananTiket::getByUserId($id_val, null, $limit);
    }
    public static function countByStatus($status)
    {
        if (empty(trim($status))) {
            return 0;
        }
        return PemesananTiket::countByStatus(trim($status));
    }
}
