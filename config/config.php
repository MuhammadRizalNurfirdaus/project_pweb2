<?php
// Mulai session
session_start();

// Base URL proyek kamu (ubah jika dipindah folder)
$base_url = "http://localhost/Cilengkrang-Web-Wisata/";

// Waktu default
date_default_timezone_set("Asia/Jakarta");

// Include koneksi database
include_once "Koneksi.php";
