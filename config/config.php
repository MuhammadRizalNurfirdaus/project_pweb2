<?php
// Mulai session
session_start();

// Base URL proyek kamu (ubah jika dipindah folder)
$base_url = "http://localhost/Cilengkrang-Web-Wisata/";

// Waktu default
date_default_timezone_set("Asia/Kuningan");
// Format tanggal dan waktu
$format_tanggal = "Y-m-d H:i:s";

// Include koneksi database
include_once "Koneksi.php";
