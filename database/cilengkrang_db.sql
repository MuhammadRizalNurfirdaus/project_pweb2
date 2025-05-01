 -- database/cilengkrang_db.sql

CREATE DATABASE IF NOT EXISTS cilengkrang_db;
USE cilengkrang_db;

CREATE TABLE admin (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    nama_lengkap VARCHAR(100) NOT NULL
);

CREATE TABLE user (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama_lengkap VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL
);

CREATE TABLE wisata (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama VARCHAR(100) NOT NULL,
    deskripsi TEXT NOT NULL,
    lokasi VARCHAR(255) NOT NULL,
    harga DECIMAL(10,2) NOT NULL,
    gambar VARCHAR(255) NOT NULL
);

CREATE TABLE booking (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    wisata_id INT NOT NULL,
    tanggal_booking DATE NOT NULL,
    jumlah_orang INT NOT NULL,
    status ENUM('pending','approved','rejected') DEFAULT 'pending',
    FOREIGN KEY (user_id) REFERENCES user(id) ON DELETE CASCADE,
    FOREIGN KEY (wisata_id) REFERENCES wisata(id) ON DELETE CASCADE
);

CREATE TABLE galeri (
    id INT AUTO_INCREMENT PRIMARY KEY,
    wisata_id INT NOT NULL,
    nama_file VARCHAR(255) NOT NULL,
    FOREIGN KEY (wisata_id) REFERENCES wisata(id) ON DELETE CASCADE
);

-- Dummy admin / user / wisata / galeri
INSERT INTO admin (username,password,nama_lengkap)
VALUES ('admin', '$2y$10$7adJ5owFZPI8H8fyqSy8ru8Ey68b8OR64fG04V7JhXwX4X35BnlJm', 'Admin Utama');

INSERT INTO user (nama_lengkap,email,password)
VALUES ('Contoh User','user@example.com','$2y$10$eImiTXuWVxfM37uY4JANj.QAo0JwIJ.\tp0bJY3elC/ts');

INSERT INTO wisata (nama,deskripsi,lokasi,harga,gambar) VALUES
('Curug Batu Templek','Air terjun alami dengan tebing batu...','Cilengkrang, Bandung',25000,'curug_batu.jpg'),
('Taman Wisata Cilengkrang','Taman rekreasi keluarga dengan area...','Cilengkrang, Bandung',20000,'taman.jpg');

INSERT INTO galeri (wisata_id,nama_file) VALUES
(1,'curug1.jpg'),
(1,'curug2.jpg'),
(2,'taman1.jpg'),
(2,'taman2.jpg');