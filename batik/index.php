<?php
require_once "config/koneksi.php";
require_once "classes/produk.php";

$produk = new Produk($conn);
$data_produk = $produk->tampil();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BatikNusa | Toko Batik</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>

<header class="navbar">
    <div class="container nav-content">
        <h2 class="logo">BatikNusa<span>.</span></h2>

        <nav>
            <?php
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            ?>

            <nav>
                <a href="index.php">Beranda</a>

                <?php if (isset($_SESSION['id_user'])): ?>
                    <a href="logout.php">Logout</a>
                <?php else: ?>
                    <a href="login.php">Login</a>
                <?php endif; ?>
            </nav>
        </nav>
    </div>
</header>

<section class="hero">
    <div class="container">
        <p class="subtitle">WARISAN BUDAYA INDONESIA</p>
        <h1>Kenakan Batik,<br>Bangga dengan Budaya.</h1>
        <p>
            Temukan koleksi batik pilihan dengan motif
            khas Nusantara.
        </p>
        <a href="#produk" class="btn">Lihat Produk</a>
    </div>
</section>

<section class="produk-section container" id="produk">
    <div class="section-heading">
        <p class="subtitle">KOLEKSI KAMI</p>
        <h2>Produk Batik</h2>
        <p>Pilihan batik untuk melengkapi penampilanmu.</p>
    </div>

    <div class="produk-grid">

        <?php if ($data_produk && $data_produk->num_rows > 0): ?>

            <?php while ($row = $data_produk->fetch_assoc()): ?>

                <div class="produk-card">

                    <?php if (!empty($row['foto'])): ?>
                        <img
                            src="assets/images/<?php
                                echo htmlspecialchars($row['foto']);
                            ?>"
                            alt="<?php
                                echo htmlspecialchars($row['nama_produk']);
                            ?>"
                        >
                    <?php else: ?>
                        <div class="no-image">
                            Foto Produk
                        </div>
                    <?php endif; ?>

                    <div class="produk-info">
                        <h3>
                            <?php echo htmlspecialchars($row['nama_produk']); ?>
                        </h3>

                        <p class="harga">
                            Rp <?php echo number_format($row['harga'], 0, ',', '.'); ?>
                        </p>

                        <p class="stok">
                            Stok: <?php echo (int) $row['stok']; ?>
                        </p>

                        <?php if ($row['stok'] > 0): ?>
                            <a
                                href="pemesanan.php?id=<?php echo (int) $row['id_produk']; ?>"
                                class="btn btn-pesan"
                            >
                                Pesan Sekarang
                            </a>
                        <?php else: ?>
                            <button class="btn btn-habis" disabled>
                                Stok Habis
                            </button>
                        <?php endif; ?>
                    </div>

                </div>

            <?php endwhile; ?>

        <?php else: ?>

            <p class="kosong">
                Belum ada produk yang tersedia.
            </p>

        <?php endif; ?>

    </div>
</section>

<footer>
    <p>&copy; 2026 BatikNusa. Melestarikan Budaya Nusantara.</p><br>
    <p>Created by:</p>
    <p>Muhammad Dhaifi Hudain</p>
    <p>Muhammad Fiqri Fahreza</p>
</footer>

</body>
</html>
