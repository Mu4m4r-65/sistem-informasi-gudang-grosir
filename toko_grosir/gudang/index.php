<?php
session_start();

if (
    !isset($_SESSION["id_user"]) ||
    $_SESSION["role"] !== "gudang"
) {
    header("Location: ../auth/login.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>
<body>
    <h1>Dashboard Admin Gudang</h1>
    <p>Selamat datang, <?= htmlspecialchars($_SESSION["nama"], ENT_QUOTES, "UTF-8") ?></p>
    <p><a href="stok.php">Manajemen Stok</a></p>
    <p><a href="barang.php">Manajemen Barang</a></p>
    <a href="../auth/logout.php">Logout</a>
</body>
</html>