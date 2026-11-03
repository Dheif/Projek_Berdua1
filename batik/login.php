
<?php
session_start();
require_once "config/koneksi.php";

$error = "";

if (isset($_POST['login'])) {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();

    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();

        if (password_verify($password, $user['password'])) {
            session_regenerate_id(true);

            $_SESSION['id_user'] = $user['id_user'];
            $_SESSION['nama'] = $user['nama'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['role'] = $user['role'];

            // Jika login sebagai admin
            if ($user['role'] == 'admin') {

                unset($_SESSION['redirect_after_login']);

                header("Location: admin.php");
                exit;

            } else {

                // Mengambil tujuan halaman setelah login
                $tujuan = $_SESSION['redirect_after_login'] ?? "index.php";

                // Menghapus tujuan setelah digunakan
                unset($_SESSION['redirect_after_login']);

                // Memastikan tujuan hanya halaman pemesanan internal
                if (!preg_match('/^pemesanan\.php\?id=\d+$/', $tujuan)) {
                    $tujuan = "index.php";
                }

                header("Location: " . $tujuan);
                exit;
            }

        } else {
            $error = "Email atau password salah!";
        }

    } else {
        $error = "Email atau password salah!";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | BatikNusa</title>

    <link rel="stylesheet" href="assets/style.css">

</head>

<body class="auth-page">

    <div class="auth-card">

        <div class="brand">
            <h1>BatikNusa</h1>
            <p>Keindahan Batik Nusantara dalam Setiap Motif</p>
        </div>

        <h2>Masuk ke Akun</h2>

        <!-- Pesan jika pengguna belum login -->
        <?php if (isset($_GET['pesan']) && $_GET['pesan'] == 'harus_login'): ?>
            <div class="error">
                Silakan login terlebih dahulu untuk melakukan pemesanan.
            </div>
        <?php endif; ?>

        <!-- Pesan kesalahan login -->
        <?php if ($error != ""): ?>
            <div class="error">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST">

            <div class="form-group">
                <label>Email</label>
                <input
                    type="email"
                    name="email"
                    placeholder="Masukkan email"
                    required
                >
            </div>

            <div class="form-group">
                <label>Password</label>
                <input
                    type="password"
                    name="password"
                    placeholder="Masukkan password"
                    required
                >
            </div>

            <button type="submit" name="login" class="auth-button">
                Login
            </button>

        </form>

        <p class="auth-link">
            Belum punya akun?
            <a href="register.php">Daftar sekarang</a>
        </p>

        <a href="index.php" class="back-link">
            ← Kembali ke Beranda
        </a>

    </div>

</body>
</html>
