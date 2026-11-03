<?php
session_start();

// Menghapus seluruh session pengguna
$_SESSION = [];
session_destroy();

// Kembali ke halaman Beranda
header("Location: index.php");
exit;
?>
