<?php
session_start();

// Proteksi halaman admin
if (
    !isset($_SESSION['id_user']) ||
    !isset($_SESSION['role']) ||
    $_SESSION['role'] !== 'admin'
) {
    header("Location: login.php");
    exit;
}

require_once "config/koneksi.php";
require_once "classes/produk.php";

$produk = new Produk($conn);
$pesan = "";
$jenis_pesan = "success";

// Fungsi upload foto produk
function uploadFoto($foto, $foto_lama = "") {
    // Jika tidak memilih foto baru, gunakan foto lama
    if (!isset($foto) || $foto['error'] === UPLOAD_ERR_NO_FILE) {
        return $foto_lama;
    }

    if ($foto['error'] !== UPLOAD_ERR_OK) {
        throw new Exception("Terjadi kesalahan saat mengunggah foto.");
    }

    // Batas ukuran foto 3 MB
    if ($foto['size'] > 3 * 1024 * 1024) {
        throw new Exception("Ukuran foto maksimal 3 MB.");
    }

    // Memeriksa jenis gambar
    $info = getimagesize($foto['tmp_name']);

    if ($info === false) {
        throw new Exception("File yang diunggah bukan gambar.");
    }

    $mime = $info['mime'];

    $ekstensi = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp'
    ];

    if (!isset($ekstensi[$mime])) {
        throw new Exception("Format foto harus JPG, PNG, atau WEBP.");
    }

    $folder = __DIR__ . "/assets/images/";

    if (!is_dir($folder)) {
        throw new Exception("Folder penyimpanan foto tidak ditemukan.");
    }

    // Membuat nama file unik
    $nama_file = bin2hex(random_bytes(8)) . "." . $ekstensi[$mime];

    if (!move_uploaded_file(
        $foto['tmp_name'],
        $folder . $nama_file
    )) {
        throw new Exception("Foto gagal disimpan.");
    }

    return $nama_file;
}

// Memproses tambah, edit, dan hapus produk
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $aksi = $_POST['aksi'] ?? '';

    try {

        // TAMBAH PRODUK
        if ($aksi === 'tambah') {
            $nama = trim($_POST['nama_produk']);
            $harga = (float) $_POST['harga'];
            $stok = (int) $_POST['stok'];

            if ($nama === '' || $harga <= 0 || $stok < 0) {
                throw new Exception("Data produk tidak valid.");
            }

            $foto = uploadFoto($_FILES['foto'] ?? null);

            if ($produk->tambah($nama, $harga, $stok, $foto)) {
                $pesan = "Produk berhasil ditambahkan.";
            } else {
                $pesan = "Produk gagal ditambahkan.";
                $jenis_pesan = "error";
            }
        }

        // EDIT PRODUK
        elseif ($aksi === 'edit') {
            $id = (int) $_POST['id_produk'];
            $nama = trim($_POST['nama_produk']);
            $harga = (float) $_POST['harga'];
            $stok = (int) $_POST['stok'];

            if ($id <= 0 || $nama === '' || $harga <= 0 || $stok < 0) {
                throw new Exception("Data produk tidak valid.");
            }

            $data_lama = $produk->ambil($id);

            if (!$data_lama) {
                throw new Exception("Produk tidak ditemukan.");
            }

            $foto_lama = $data_lama['foto'];
            $foto = uploadFoto($_FILES['foto'] ?? null, $foto_lama);

            if ($produk->edit($id, $nama, $harga, $stok, $foto)) {
                $pesan = "Produk berhasil diperbarui.";
            } else {
                $pesan = "Produk gagal diperbarui.";
                $jenis_pesan = "error";
            }
        }

        // HAPUS PRODUK
        elseif ($aksi === 'hapus') {
            $id = (int) $_POST['id_produk'];

            if ($produk->hapus($id)) {
                $pesan = "Produk berhasil dihapus.";
            } else {
                $pesan = "Produk tidak dapat dihapus. Produk mungkin sudah tercatat dalam pesanan.";
                $jenis_pesan = "error";
            }
        }

    } catch (Exception $e) {
        $pesan = $e->getMessage();
        $jenis_pesan = "error";
    }
}

// Mengambil data produk
$data_produk = $produk->tampil();

// Mengambil data pesanan pelanggan
$sql_pesanan = "
    SELECT
        p.id_pesanan,
        p.tanggal_pesanan,
        p.total_harga,
        pl.nama_pelanggan,
        pl.no_hp,
        pl.email,
        pl.alamat,
        GROUP_CONCAT(
            CONCAT(pr.nama_produk, ' (', dp.jumlah, ' pcs)')
            SEPARATOR ', '
        ) AS daftar_produk
    FROM pesanan p
    JOIN pelanggan pl
        ON p.id_pelanggan = pl.id_pelanggan
    JOIN detail_pesanan dp
        ON p.id_pesanan = dp.id_pesanan
    JOIN produk pr
        ON dp.id_produk = pr.id_produk
    GROUP BY
        p.id_pesanan,
        p.tanggal_pesanan,
        p.total_harga,
        pl.nama_pelanggan,
        pl.no_hp,
        pl.email,
        pl.alamat
    ORDER BY p.id_pesanan DESC
";

$data_pesanan = $conn->query($sql_pesanan);

// Mengambil data produk untuk form edit
$data_edit = null;

if (isset($_GET['edit'])) {
    $id_edit = (int) $_GET['edit'];
    $data_edit = $produk->ambil($id_edit);
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Admin - BatikNusa</title>

    <link rel="stylesheet" href="assets/style.css">
</head>

<body>

<!-- NAVIGASI -->
<nav class="admin-navbar">
    <h2>BatikNusa.</h2>

    <div>
        <a href="index.php">Beranda</a>
        <a href="logout.php">Logout</a>
    </div>
</nav>

<main class="admin-container">

    <h1>Halaman Admin</h1>
    <p>Selamat datang, <?= htmlspecialchars($_SESSION['nama']); ?>.</p>

    <?php if ($pesan !== ""): ?>
        <div class="pesan <?= $jenis_pesan; ?>">
            <?= htmlspecialchars($pesan); ?>
        </div>
    <?php endif; ?>


    <!-- FORM TAMBAH / EDIT PRODUK -->
    <section class="admin-section">

        <h2>
            <?= $data_edit ? "Edit Produk" : "Tambah Produk"; ?>
        </h2>

        <form
            method="POST"
            enctype="multipart/form-data"
            class="admin-form"
        >

            <input
                type="hidden"
                name="aksi"
                value="<?= $data_edit ? 'edit' : 'tambah'; ?>"
            >

            <?php if ($data_edit): ?>
                <input
                    type="hidden"
                    name="id_produk"
                    value="<?= (int) $data_edit['id_produk']; ?>"
                >
            <?php endif; ?>

            <label>Nama Produk</label>
            <input
                type="text"
                name="nama_produk"
                required
                value="<?= htmlspecialchars($data_edit['nama_produk'] ?? ''); ?>"
            >

            <label>Harga Produk (Rp)</label>
            <input
                type="number"
                name="harga"
                min="1"
                required
                value="<?= htmlspecialchars($data_edit['harga'] ?? ''); ?>"
            >

            <label>Stok Produk</label>
            <input
                type="number"
                name="stok"
                min="0"
                required
                value="<?= htmlspecialchars($data_edit['stok'] ?? ''); ?>"
            >

            <label>Foto Produk</label>
            <input
                type="file"
                name="foto"
                accept=".jpg,.jpeg,.png,.webp"
                <?= $data_edit ? '' : 'required'; ?>
            >

            <?php if ($data_edit && !empty($data_edit['foto'])): ?>
                <p>Foto saat ini:</p>
                <img
                    src="assets/images/<?= htmlspecialchars($data_edit['foto']); ?>"
                    alt="Foto produk"
                    class="foto-produk"
                >
            <?php endif; ?>

            <br><br>

            <button type="submit">
                <?= $data_edit ? "Simpan Perubahan" : "Tambah Produk"; ?>
            </button>

            <?php if ($data_edit): ?>
                <a href="admin.php">Batal Edit</a>
            <?php endif; ?>

        </form>

    </section>


    <!-- DAFTAR PRODUK -->
    <section class="admin-section">

        <h2>Kelola Produk</h2>

        <div class="table-wrapper">

            <table class="admin-table">

                <thead>
                    <tr>
                        <th>No.</th>
                        <th>Foto</th>
                        <th>Nama Produk</th>
                        <th>Harga</th>
                        <th>Stok</th>
                        <th>Aksi</th>
                    </tr>
                </thead>

                <tbody>

                <?php if ($data_produk && $data_produk->num_rows > 0): ?>

                    <?php $no = 1; ?>

                    <?php while ($row = $data_produk->fetch_assoc()): ?>

                        <tr>
                            <td><?= $no++; ?></td>

                            <td>
                                <?php if (!empty($row['foto'])): ?>
                                    <img
                                        src="assets/images/<?= htmlspecialchars($row['foto']); ?>"
                                        alt="Foto produk"
                                        class="foto-produk"
                                    >
                                <?php else: ?>
                                    Tidak ada foto
                                <?php endif; ?>
                            </td>

                            <td>
                                <?= htmlspecialchars($row['nama_produk']); ?>
                            </td>

                            <td>
                                Rp <?= number_format($row['harga'], 0, ',', '.'); ?>
                            </td>

                            <td>
                                <?= (int) $row['stok']; ?>
                            </td>

                            <td>
                                <a
                                    href="admin.php?edit=<?= (int) $row['id_produk']; ?>"
                                    class="btn-edit"
                                >
                                    Edit
                                </a>

                                <form
                                    method="POST"
                                    class="aksi-form"
                                    onsubmit="return confirm('Yakin ingin menghapus produk ini?');"
                                >
                                    <input
                                        type="hidden"
                                        name="aksi"
                                        value="hapus"
                                    >

                                    <input
                                        type="hidden"
                                        name="id_produk"
                                        value="<?= (int) $row['id_produk']; ?>"
                                    >

                                    <button
                                        type="submit"
                                        class="btn-delete"
                                    >
                                        Hapus
                                    </button>
                                </form>
                            </td>
                        </tr>

                    <?php endwhile; ?>

                <?php else: ?>

                    <tr>
                        <td colspan="6">Belum ada produk.</td>
                    </tr>

                <?php endif; ?>

                </tbody>

            </table>

        </div>

    </section>


    <!-- DAFTAR PESANAN -->
    <section class="admin-section">

        <h2>Pesanan Pelanggan</h2>

        <div class="table-wrapper">

            <table class="admin-table">

                <thead>
                    <tr>
                        <th>No.</th>
                        <th>Pelanggan</th>
                        <th>Kontak</th>
                        <th>Produk</th>
                        <th>Total Harga</th>
                        <th>Tanggal</th>
                        <th>Alamat</th>
                    </tr>
                </thead>

                <tbody>

                <?php if ($data_pesanan && $data_pesanan->num_rows > 0): ?>

                    <?php $no = 1; ?>

                    <?php while ($row = $data_pesanan->fetch_assoc()): ?>

                        <tr>
                            <td><?= $no++; ?></td>

                            <td>
                                <?= htmlspecialchars($row['nama_pelanggan']); ?>
                            </td>

                            <td>
                                <?= htmlspecialchars($row['no_hp']); ?>
                                <br>
                                <?= htmlspecialchars($row['email'] ?? '-'); ?>
                            </td>

                            <td>
                                <?= htmlspecialchars($row['daftar_produk']); ?>
                            </td>

                            <td>
                                Rp <?= number_format($row['total_harga'], 0, ',', '.'); ?>
                            </td>

                            <td>
                                <?= htmlspecialchars($row['tanggal_pesanan']); ?>
                            </td>

                            <td>
                                <?= htmlspecialchars($row['alamat']); ?>
                            </td>
                        </tr>

                    <?php endwhile; ?>

                <?php else: ?>

                    <tr>
                        <td colspan="7">Belum ada pesanan masuk.</td>
                    </tr>

                <?php endif; ?>

                </tbody>

            </table>

        </div>

    </section>

</main>

</body>
</html>
