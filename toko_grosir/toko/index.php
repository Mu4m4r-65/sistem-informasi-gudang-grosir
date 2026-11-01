
<?php
session_start();

if (
    !isset($_SESSION["id_user"]) ||
    $_SESSION["role"] !== "toko"
) {
    header("Location: ../auth/login.php");
    exit;
}
?>

<h1>Dashboard Admin Toko</h1>
<p>Selamat datang, <?= htmlspecialchars($_SESSION["nama"], ENT_QUOTES, "UTF-8") ?></p>
<p>
    <a href="penjualan.php">Transaksi Penjualan</a>
</p>
<a href="../auth/logout.php">Logout</a>
