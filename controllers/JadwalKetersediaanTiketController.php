<?php
// File: C:\xampp\htdocs\Cilengkrang-Web-Wisata\controllers\JadwalKetersediaanTiketController.php

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../models/JadwalKetersediaanTiket.php';
require_once __DIR__ . '/../models/JenisTiket.php'; // Untuk validasi jenis_tiket_id

class JadwalKetersediaanTiketController
{
    public static function create($data)
    {
        $jenis_tiket_id = isset($data['jenis_tiket_id']) ? filter_var($data['jenis_tiket_id'], FILTER_VALIDATE_INT) : null;
        $tanggal = trim($data['tanggal'] ?? '');
        $jumlah_total = isset($data['jumlah_total_tersedia']) ? filter_var($data['jumlah_total_tersedia'], FILTER_VALIDATE_INT) : null;
        $jumlah_saat_ini = isset($data['jumlah_saat_ini_tersedia']) ? filter_var($data['jumlah_saat_ini_tersedia'], FILTER_VALIDATE_INT) : $jumlah_total;
        $aktif = isset($data['aktif']) ? (int)$data['aktif'] : 1;

        if (!$jenis_tiket_id || $jenis_tiket_id <= 0) {
            set_flash_message('danger', 'Jenis Tiket harus dipilih.');
            return false;
        }
        if (!JenisTiket::getById($jenis_tiket_id)) {
            set_flash_message('danger', 'Jenis Tiket yang dipilih tidak valid atau tidak ditemukan.');
            return false;
        }
        if (empty($tanggal) || !DateTime::createFromFormat('Y-m-d', $tanggal) || $tanggal < date('Y-m-d')) {
            set_flash_message('danger', 'Format tanggal tidak valid (YYYY-MM-DD) atau tanggal sudah lewat.');
            return false;
        }
        if ($jumlah_total === null || $jumlah_total === false || $jumlah_total < 0) {
            set_flash_message('danger', 'Jumlah Total Tiket Tersedia harus angka non-negatif.');
            return false;
        }
        if ($jumlah_saat_ini === null || $jumlah_saat_ini === false || $jumlah_saat_ini < 0 || $jumlah_saat_ini > $jumlah_total) {
            set_flash_message('danger', 'Jumlah Tiket Saat Ini Tersedia tidak valid, tidak boleh negatif, atau melebihi Jumlah Total Tersedia.');
            return false;
        }

        // Pengecekan duplikasi sudah ada di Model, tapi bisa juga dicek di sini jika ingin pesan flash lebih awal.
        // Model akan return false jika duplikat dan log error.
        // Controller bisa menambahkan flash message berdasarkan return false dari model.

        $data_to_save = [
            'jenis_tiket_id' => $jenis_tiket_id,
            'tanggal' => $tanggal,
            'jumlah_total_tersedia' => $jumlah_total,
            'jumlah_saat_ini_tersedia' => $jumlah_saat_ini,
            'aktif' => $aktif
        ];

        $new_id = JadwalKetersediaanTiket::create($data_to_save);
        if ($new_id) {
            return $new_id;
        }
        // Jika Model create() return false karena duplikasi, pesan flash sudah di-set di Model.
        // Jika karena validasi lain di Model dan belum ada flash, set di sini.
        if (!isset($_SESSION['flash_message'])) {
            set_flash_message('danger', 'Gagal menyimpan jadwal ketersediaan. Periksa log untuk detail.');
        }
        return false;
    }

    public static function getAll()
    {
        return JadwalKetersediaanTiket::getAll();
    }

    public static function getById($id)
    {
        $id_val = filter_var($id, FILTER_VALIDATE_INT);
        if ($id_val === false || $id_val <= 0) {
            return null;
        }
        return JadwalKetersediaanTiket::getById($id_val);
    }

    public static function update($data)
    {
        $id = isset($data['id']) ? filter_var($data['id'], FILTER_VALIDATE_INT) : null;
        if (!$id || $id <= 0) {
            set_flash_message('danger', 'ID Jadwal tidak valid untuk pembaruan.');
            return false;
        }

        $jenis_tiket_id = isset($data['jenis_tiket_id']) ? filter_var($data['jenis_tiket_id'], FILTER_VALIDATE_INT) : null;
        $tanggal = trim($data['tanggal'] ?? '');
        $jumlah_total = isset($data['jumlah_total_tersedia']) ? filter_var($data['jumlah_total_tersedia'], FILTER_VALIDATE_INT) : null;
        $jumlah_saat_ini = isset($data['jumlah_saat_ini_tersedia']) ? filter_var($data['jumlah_saat_ini_tersedia'], FILTER_VALIDATE_INT) : null;
        $aktif = isset($data['aktif']) ? (int)$data['aktif'] : 0; // Jika checkbox tidak dikirim, value="0" dari hidden input

        if (!$jenis_tiket_id || $jenis_tiket_id <= 0) {
            set_flash_message('danger', 'Jenis Tiket harus dipilih.');
            return false;
        }
        if (!JenisTiket::getById($jenis_tiket_id)) {
            set_flash_message('danger', 'Jenis Tiket yang dipilih tidak valid atau tidak ditemukan.');
            return false;
        }
        if (empty($tanggal) || !DateTime::createFromFormat('Y-m-d', $tanggal)) { // Tidak perlu cek tanggal lampau saat update, admin mungkin perlu
            set_flash_message('danger', 'Format tanggal tidak valid (YYYY-MM-DD).');
            return false;
        }
        if ($jumlah_total === null || $jumlah_total === false || $jumlah_total < 0) {
            set_flash_message('danger', 'Jumlah Total Tiket Tersedia harus angka non-negatif.');
            return false;
        }
        if ($jumlah_saat_ini === null || $jumlah_saat_ini === false || $jumlah_saat_ini < 0 || $jumlah_saat_ini > $jumlah_total) {
            set_flash_message('danger', 'Jumlah Tiket Saat Ini Tersedia tidak valid, tidak boleh negatif, atau melebihi Jumlah Total Tersedia.');
            return false;
        }

        // Pengecekan duplikasi (kecuali untuk ID yg sama) ada di Model.
        // Controller bisa set flash message jika Model return false karena duplikasi.

        $data_to_update = [
            'id' => $id,
            'jenis_tiket_id' => $jenis_tiket_id,
            'tanggal' => $tanggal,
            'jumlah_total_tersedia' => $jumlah_total,
            'jumlah_saat_ini_tersedia' => $jumlah_saat_ini,
            'aktif' => $aktif
        ];

        $success = JadwalKetersediaanTiket::update($data_to_update);
        if (!$success && !isset($_SESSION['flash_message'])) {
            // Jika Model return false karena duplikasi, pesan flash sudah di-set di Model.
            set_flash_message('danger', 'Gagal memperbarui jadwal ketersediaan. Periksa log untuk detail.');
        }
        return $success;
    }
}
