<?php
// config/Koneksi.php
require_once 'config.php';
class Koneksi
{
    private static $instance = null;
    private $conn;
    private function __construct()
    {
        $this->conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        if ($this->conn->connect_error) {
            die('Koneksi gagal: ' . $this->conn->connect_error);
        }
    }
    public static function getInstance()
    {
        if (!self::$instance) self::$instance = new Koneksi();
        return self::$instance->conn;
    }
}
