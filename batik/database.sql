CREATE DATABASE IF NOT EXISTS db_batiknusa;
USE db_batiknusa;

-- Tabel Produk
CREATE TABLE IF NOT EXISTS produk (
    id_produk INT AUTO_INCREMENT PRIMARY KEY,
    nama_produk VARCHAR(100) NOT NULL,
    harga DECIMAL(12,2) NOT NULL,
    stok INT NOT NULL,
    foto VARCHAR(255)
) ENGINE=InnoDB;

-- Tabel Pelanggan
CREATE TABLE IF NOT EXISTS pelanggan (
    id_pelanggan INT AUTO_INCREMENT PRIMARY KEY,
    nama_pelanggan VARCHAR(100) NOT NULL,
    no_hp VARCHAR(20) NOT NULL,
    email VARCHAR(100),
    alamat TEXT NOT NULL
) ENGINE=InnoDB;

-- Tabel Pesanan
CREATE TABLE IF NOT EXISTS pesanan (
    id_pesanan INT AUTO_INCREMENT PRIMARY KEY,
    id_pelanggan INT NOT NULL,
    tanggal_pesanan DATETIME DEFAULT CURRENT_TIMESTAMP,
    total_harga DECIMAL(12,2) NOT NULL,
    CONSTRAINT fk_pesanan_pelanggan
        FOREIGN KEY (id_pelanggan)
        REFERENCES pelanggan(id_pelanggan)
) ENGINE=InnoDB;

-- Tabel Detail Pesanan
CREATE TABLE IF NOT EXISTS detail_pesanan (
    id_detail INT AUTO_INCREMENT PRIMARY KEY,
    id_pesanan INT NOT NULL,
    id_produk INT NOT NULL,
    jumlah INT NOT NULL,
    harga_satuan DECIMAL(12,2) NOT NULL,
    subtotal DECIMAL(12,2) NOT NULL,
    CONSTRAINT fk_detail_pesanan
        FOREIGN KEY (id_pesanan)
        REFERENCES pesanan(id_pesanan),
    CONSTRAINT fk_detail_produk
        FOREIGN KEY (id_produk)
        REFERENCES produk(id_produk)
) ENGINE=InnoDB;

-- Data Awal Produk
INSERT INTO produk (nama_produk, harga, stok, foto)
VALUES
('Batik Parang', 150000, 10, 'batik-parang.jpg'),
('Batik Mega Mendung', 175000, 8, 'mega-mendung.jpg'),
('Batik Kawung', 135000, 12, 'batik-kawung.jpg');