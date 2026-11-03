
<?php

class Produk {
    private $conn;

    // Constructor
    public function __construct($db) {
        $this->conn = $db;
    }

    // Menampilkan seluruh produk
    public function tampil() {
        $sql = "SELECT * FROM produk ORDER BY id_produk DESC";
        return $this->conn->query($sql);
    }

    // Menambahkan produk
    public function tambah($nama, $harga, $stok, $foto) {
        $sql = "INSERT INTO produk
                (nama_produk, harga, stok, foto)
                VALUES (?, ?, ?, ?)";

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("sdis", $nama, $harga, $stok, $foto);

        return $stmt->execute();
    }

    // Mengambil satu produk berdasarkan ID
    public function ambil($id) {
        $sql = "SELECT * FROM produk WHERE id_produk = ?";

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();

        return $stmt->get_result()->fetch_assoc();
    }

    // Mengubah data produk
    public function edit($id, $nama, $harga, $stok, $foto) {
        $sql = "UPDATE produk
                SET nama_produk = ?,
                    harga = ?,
                    stok = ?,
                    foto = ?
                WHERE id_produk = ?";

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param(
            "sdisi",
            $nama,
            $harga,
            $stok,
            $foto,
            $id
        );

        return $stmt->execute();
    }

    // Menghapus produk
    public function hapus($id) {
        $sql = "DELETE FROM produk WHERE id_produk = ?";

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $id);

        return $stmt->execute();
    }
}

?>
