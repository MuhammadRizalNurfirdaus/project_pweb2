<?php
// File: C:\xampp\htdocs\Cilengkrang-Web-Wisata\controllers\SewaAlatController.php

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../models/SewaAlat.php';

class SewaAlatController
{
    public static function create($data, $gambar_filename = null)
    {
        if (
            empty($data['nama_item']) || !isset($data['harga_sewa']) || (int)$data['harga_sewa'] < 0 ||
            !isset($data['durasi_harga_sewa']) || (int)$data['durasi_harga_sewa'] <= 0 ||
            !isset($data['stok_tersedia']) || (int)$data['stok_tersedia'] < 0
        ) {
            error_log("SewaAlatController::create() - Data input tidak valid.");
            set_flash_message('danger', 'Nama, Harga, Durasi Harga, dan Stok wajib diisi dengan benar.');
            return false;
        }

        $data_to_save = [
            'nama_item' => trim($data['nama_item']),
            'kategori_alat' => trim($data['kategori_alat'] ?? null),
            'deskripsi' => trim($data['deskripsi'] ?? ''),
            'harga_sewa' => (int)$data['harga_sewa'],
            'durasi_harga_sewa' => (int)$data['durasi_harga_sewa'],
            'satuan_durasi_harga' => trim($data['satuan_durasi_harga'] ?? 'Hari'),
            'stok_tersedia' => (int)$data['stok_tersedia'],
            'gambar_alat' => $gambar_filename,
            'kondisi_alat' => trim($data['kondisi_alat'] ?? 'Baik')
        ];
        return SewaAlat::create($data_to_save);
    }

    public static function getAll()
    {
        return SewaAlat::getAll();
    }

    public static function getById($id)
    {
        // ... (implementasi sama seperti controller wisata)
        $id_val = filter_var($id, FILTER_VALIDATE_INT);
        if ($id_val === false || $id_val <= 0) {
            return null;
        }
        return SewaAlat::getById($id_val);
    }

    public static function update($data, $new_image_filename = null, $old_image_filename = null)
    {
        if (
            empty($data['id']) || !is_numeric($data['id']) || (int)$data['id'] <= 0 ||
            empty($data['nama_item']) || !isset($data['harga_sewa']) || (int)$data['harga_sewa'] < 0 ||
            !isset($data['durasi_harga_sewa']) || (int)$data['durasi_harga_sewa'] <= 0 ||
            !isset($data['stok_tersedia']) || (int)$data['stok_tersedia'] < 0
        ) {
            error_log("SewaAlatController::update() - Data input tidak valid.");
            set_flash_message('danger', 'ID, Nama, Harga, Durasi Harga, dan Stok wajib diisi dengan benar.');
            return false;
        }
        $data_to_update = [
            'id' => (int)$data['id'],
            'nama_item' => trim($data['nama_item']),
            'kategori_alat' => trim($data['kategori_alat'] ?? null),
            'deskripsi' => trim($data['deskripsi'] ?? ''),
            'harga_sewa' => (int)$data['harga_sewa'],
            'durasi_harga_sewa' => (int)$data['durasi_harga_sewa'],
            'satuan_durasi_harga' => trim($data['satuan_durasi_harga'] ?? 'Hari'),
            'stok_tersedia' => (int)$data['stok_tersedia'],
            'kondisi_alat' => trim($data['kondisi_alat'] ?? 'Baik')
            // gambar akan dihandle oleh model
        ];
        return SewaAlat::update($data_to_update, $new_image_filename, $old_image_filename);
    }

    public static function delete($id)
    {
        // ... (implementasi sama seperti controller wisata, pastikan pesan flash sesuai)
        $id_val = filter_var($id, FILTER_VALIDATE_INT);
        if ($id_val === false || $id_val <= 0) {
            return false;
        }

        $result = SewaAlat::delete($id_val);
        if (!$result && !isset($_SESSION['flash_message'])) {
            set_flash_message('danger', 'Gagal menghapus item alat. Item mungkin masih terpakai dalam pemesanan.');
        }
        return $result;
    }
}
