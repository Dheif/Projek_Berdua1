<?php
session_start();
require_once "config/koneksi.php";

$error = "";

if (isset($_POST['register'])) {
    $nama = trim($_POST['nama']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $konfirmasi = $_POST['konfirmasi'];

    if ($password !== $konfirmasi) {
        $error = "Konfirmasi password tidak sesuai!";
    } elseif (strlen($password) < 8) {
        $error = "Password minimal 8 karakter!";
    } else {
        $cek = $conn->prepare("SELECT id_user FROM users WHERE email = ?");
        $cek->bind_param("s", $email);
        $cek->execute();

        if ($cek->get_result()->num_rows > 0) {
            $error = "Email sudah terdaftar!";
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $role = "pelanggan";

            $stmt = $conn->prepare(
                "INSERT INTO users (nama, email, password, role)
                 VALUES (?, ?, ?, ?)"
            );

            $stmt->bind_param("ssss", $nama, $email, $hash, $role);

            if ($stmt->execute()) {
                header("Location: login.php");
                exit;
            } else {
                $error = "Registrasi gagal. Silakan coba lagi!";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register | BatikNusa</title>
    <link rel="stylesheet" href="assets/style.css">
</head>

<body class="auth-page">
    <div class="auth-card">

        <div class="brand">
            <h1>BatikNusa</h1>
            <p>Keindahan Batik Nusantara dalam Setiap Motif</p>
        </div>

        <h2>Buat Akun Baru</h2>

        <?php if ($error != ""): ?>
            <div class="error">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST">

            <div class="form-group">
                <label>Nama Lengkap</label>
                <input type="text" name="nama" required>
            </div>

            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" required>
            </div>

            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" minlength="8" required>
            </div>

            <div class="form-group">
                <label>Konfirmasi Password</label>
                <input type="password" name="konfirmasi" required>
            </div>

            <button type="submit" name="register" class="auth-button">
                Daftar Sekarang
            </button>

        </form>

        <p class="auth-link">
            Sudah punya akun?
            <a href="login.php">Login di sini</a>
        </p>

        <a href="index.php" class="back-link">
            ← Kembali ke Beranda
        </a>

    </div>
</body>
</html>