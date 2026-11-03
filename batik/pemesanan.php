
<?php
session_start();

// Memeriksa apakah pengguna sudah login
if (!isset($_SESSION['id_user'])) {

    // Menyimpan ID produk yang ingin dipesan
    $id_produk = isset($_GET['id']) ? (int) $_GET['id'] : 0;

    // Menyimpan tujuan setelah login
    $_SESSION['redirect_after_login'] =
        "pemesanan.php?id=" . $id_produk;

    // Mengarahkan pengguna ke halaman login
    header("Location: login.php?pesan=harus_login");
    exit;
}

require_once "config/koneksi.php";

$id_produk = isset($_GET['id']) ? (int) $_GET['id'] : 0;

// Mengambil produk berdasarkan ID
$stmt = $conn->prepare(
    "SELECT * FROM produk WHERE id_produk = ?"
);
$stmt->bind_param("i", $id_produk);
$stmt->execute();

$produk = $stmt->get_result()->fetch_assoc();

$pesan_error = "";
$pesan_sukses = "";

// Memproses pemesanan
if ($_SERVER["REQUEST_METHOD"] == "POST" && $produk) {

    $nama = trim($_POST['nama']);
    $no_hp = trim($_POST['no_hp']);
    $email = trim($_POST['email']);
    $alamat = trim($_POST['alamat']);
    $jumlah = (int) $_POST['jumlah'];

    if (
        $nama == "" ||
        $no_hp == "" ||
        $alamat == "" ||
        $jumlah < 1
    ) {
        $pesan_error = "Lengkapi data pemesanan dengan benar.";

    } elseif ($jumlah > (int) $produk['stok']) {
        $pesan_error = "Jumlah pesanan melebihi stok yang tersedia.";

    } elseif ($email != "" && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $pesan_error = "Format email tidak valid.";

    } else {

        $harga = (float) $produk['harga'];
        $total = $harga * $jumlah;

        try {
            $conn->begin_transaction();

            // Menyimpan data pelanggan
            $stmt = $conn->prepare(
                "INSERT INTO pelanggan
                (nama_pelanggan, no_hp, email, alamat)
                VALUES (?, ?, ?, ?)"
            );

            $stmt->bind_param(
                "ssss",
                $nama,
                $no_hp,
                $email,
                $alamat
            );

            $stmt->execute();

            $id_pelanggan = $conn->insert_id;

            // Menyimpan data pesanan
            $stmt = $conn->prepare(
                "INSERT INTO pesanan
                (id_pelanggan, total_harga)
                VALUES (?, ?)"
            );

            $stmt->bind_param("id", $id_pelanggan, $total);
            $stmt->execute();

            $id_pesanan = $conn->insert_id;

            // Menyimpan detail pesanan
            $subtotal = $harga * $jumlah;

            $stmt = $conn->prepare(
                "INSERT INTO detail_pesanan
                (id_pesanan, id_produk, jumlah, harga_satuan, subtotal)
                VALUES (?, ?, ?, ?, ?)"
            );

            $stmt->bind_param(
                "iiidd",
                $id_pesanan,
                $id_produk,
                $jumlah,
                $harga,
                $subtotal
            );

            $stmt->execute();

            // Mengurangi stok produk
            $stmt = $conn->prepare(
                "UPDATE produk
                SET stok = stok - ?
                WHERE id_produk = ? AND stok >= ?"
            );

            $stmt->bind_param(
                "iii",
                $jumlah,
                $id_produk,
                $jumlah
            );

            $stmt->execute();

            if ($stmt->affected_rows == 0) {
                throw new Exception("Stok produk tidak mencukupi.");
            }

            $conn->commit();

            $pesan_sukses = "Pesanan berhasil dikirim! Total pembayaran: Rp " .
                number_format($total, 0, ',', '.');

            // Memperbarui stok yang ditampilkan
            $produk['stok'] -= $jumlah;

        } catch (Throwable $e) {
            $conn->rollback();
            $pesan_error = "Pesanan gagal diproses. Silakan coba kembali.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pemesanan | BatikNusa</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>

<header class="navbar">
    <div class="container nav-content">
        <h2 class="logo">BatikNusa<span>.</span></h2>

        <nav>
            <a href="index.php">Beranda</a>

            <?php if (isset($_SESSION['id_user'])): ?>
                <a href="logout.php">Logout</a>
            <?php else: ?>
                <a href="login.php">Login</a>
            <?php endif; ?>
        </nav>
    </div>
</header>

<section class="form-section container">

    <div class="form-heading">
        <p class="subtitle">PEMESANAN PRODUK</p>
        <h1>Formulir Pemesanan</h1>
        <p>Lengkapi informasi berikut untuk memesan batik pilihanmu.</p>
    </div>

    <?php if ($pesan_sukses != ""): ?>
        <div class="success-message">
            <?php echo htmlspecialchars($pesan_sukses); ?>
            <p>
                <a href="index.php">Kembali ke Beranda</a>
            </p>
        </div>

    <?php elseif ($pesan_error != ""): ?>
        <div class="error-message">
            <?php echo htmlspecialchars($pesan_error); ?>
        </div>
    <?php endif; ?>

    <?php if ($produk && $pesan_sukses == ""): ?>

        <div class="form-card">

            <div class="selected-product">
                <h3>Produk yang dipilih</h3>

                <p>
                    <?php echo htmlspecialchars($produk['nama_produk']); ?>
                </p>

                <strong>
                    Rp <?php echo number_format($produk['harga'], 0, ',', '.'); ?>
                </strong>

                <p>Stok tersedia: <?php echo (int) $produk['stok']; ?></p>
            </div>

            <form method="POST"
                  action="pemesanan.php?id=<?php echo $id_produk; ?>">

                <label>Nama Lengkap</label>
                <input type="text" name="nama" required
                       value="<?php echo htmlspecialchars($_POST['nama'] ?? ''); ?>">

                <label>Nomor HP</label>
                <input type="tel" name="no_hp" required
                       value="<?php echo htmlspecialchars($_POST['no_hp'] ?? ''); ?>">

                <label>Email</label>
                <input type="email" name="email"
                       value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">

                <label>Alamat Pengiriman</label>
                <textarea name="alamat" rows="3" required><?php
                    echo htmlspecialchars($_POST['alamat'] ?? '');
                ?></textarea>

                <label>Jumlah Pembelian</label>
                <input type="number" name="jumlah" min="1"
                       max="<?php echo (int) $produk['stok']; ?>"
                       value="<?php echo htmlspecialchars($_POST['jumlah'] ?? '1'); ?>"
                       required>

                <button type="submit" class="btn form-button">
                    Kirim Pesanan
                </button>

            </form>
        </div>

    <?php elseif (!$produk): ?>

        <div class="error-message">
            Produk tidak ditemukan.
            <a href="index.php">Kembali ke Beranda</a>
        </div>

    <?php endif; ?>

</section>

<footer>
    <p>&copy; 2026 BatikNusa. Melestarikan Budaya Nusantara.</p>
</footer>

</body>
</html>
