
<?php
session_start();

if (!isset($_SESSION["id_user"])) {
    header("Location: ../auth/login.php");
    exit;
}

require_once "database.php";

$bahasa = $_POST["bahasa"] ?? "";

if (!in_array($bahasa, ["id", "en"], true)) {
    header("Location: ../" . $_SESSION["role"] . "/index.php");
    exit;
}

$stmt = $conn->prepare(
    "UPDATE users SET bahasa = :bahasa WHERE id_user = :id"
);

$stmt->execute([
    ":bahasa" => $bahasa,
    ":id" => $_SESSION["id_user"]
]);

$_SESSION["bahasa"] = $bahasa;

$kembali = $_POST["kembali"] ?? "../" . $_SESSION["role"] . "/index.php";

// Hanya izinkan kembali ke halaman lokal dalam aplikasi.
$halamanDiizinkan = [
    "../owner/index.php",
    "../owner/supplier.php",
    "../gudang/index.php",
    "../gudang/barang.php",
    "../gudang/stok.php",
    "../toko/index.php",
    "../toko/penjualan.php"
];

if (!in_array($kembali, $halamanDiizinkan, true)) {
    $kembali = "../" . $_SESSION["role"] . "/index.php";
}

header("Location: " . $kembali);
exit;
