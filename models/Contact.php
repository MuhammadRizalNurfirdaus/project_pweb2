<?php
// models/Contact.php
class Contact
{
    private $conn;
    private $table = "contact";

    public function __construct($db)
    {
        $this->conn = $db;
    }

    public function create($data)
    {
        $query = "INSERT INTO " . $this->table . " (nama, email, pesan) VALUES (:nama, :email, :pesan)";
        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(':nama', $data['nama']);
        $stmt->bindParam(':email', $data['email']);
        $stmt->bindParam(':pesan', $data['pesan']);

        return $stmt->execute();
    }
}
