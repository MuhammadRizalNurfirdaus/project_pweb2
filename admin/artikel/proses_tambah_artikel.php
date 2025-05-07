<?php
include_once '../../config/Koneksi.php';
include_once '../../models/Artikel.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $judul = $_POST['judul'];
    $isi = $_POST['isi'];
    $penulis = $_POST['penulis'];
    $rating = $_POST['rating'];

    if (!empty($judul) && !empty($isi)) {
        $artikel = new Artikel($conn);
        $artikel->judul = $judul;
        $artikel->isi = $isi;
        $artikel->penulis = $penulis;
        $artikel->rating = $rating;

        if ($artikel->create()) {
            header("Location: kelola_artikel.php?success=1");
            exit();
        } else {
            echo "Gagal menambahkan artikel.";
        }
    } else {
        echo "Judul dan isi artikel wajib diisi.";
    }
}
