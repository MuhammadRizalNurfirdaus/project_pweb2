<?php
// models/Booking.php

class Booking
{
    private $conn;
    private $table = "booking";

    public function __construct($db)
    {
        $this->conn = $db;
    }

    public function create($data)
    {
        $query = "INSERT INTO " . $this->table . " (nama, email, no_hp, tanggal, jumlah, catatan) VALUES (:nama, :email, :no_hp, :tanggal, :jumlah, :catatan)";
        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(':nama', $data['nama']);
        $stmt->bindParam(':email', $data['email']);
        $stmt->bindParam(':no_hp', $data['no_hp']);
        $stmt->bindParam(':tanggal', $data['tanggal']);
        $stmt->bindParam(':jumlah', $data['jumlah']);
        $stmt->bindParam(':catatan', $data['catatan']);

        return $stmt->execute();
    }
}
