<?php
require_once __DIR__ . "/../config/bahasa.php";

$role = $_SESSION["role"] ?? "";
$halaman = basename($_SERVER["PHP_SELF"]);

$dashboardUrl = match ($role) {
    "owner" => "../owner/index.php",
    "gudang" => "../gudang/index.php",
    "toko" => "../toko/index.php",
    default => "../auth/login.php"
};

$dashboardAktif = $halaman === "index.php";
$barangAktif = $halaman === "barang.php";
$stokAktif = $halaman === "stok.php";
$supplierAktif = $halaman === "supplier.php";
$penjualanAktif = $halaman === "penjualan.php";

if ($role === "owner") {
    $halamanKembali = match ($halaman) {
        "supplier.php" => "../owner/supplier.php",
        "barang.php" => "../gudang/barang.php",
        default => "../owner/index.php"
    };
} elseif ($role === "gudang") {
    $halamanKembali = match ($halaman) {
        "barang.php" => "../gudang/barang.php",
        "stok.php" => "../gudang/stok.php",
        default => "../gudang/index.php"
    };
} elseif ($role === "toko") {
    $halamanKembali = $halaman === "penjualan.php"
        ? "../toko/penjualan.php"
        : "../toko/index.php";
} else {
    $halamanKembali = "../auth/login.php";
}
?>

<aside class="sidebar">
    <div class="brand">
        <b>GROSIR<span>.</span>APP</b>
    </div>

    <div class="menu-label">
        <?= htmlspecialchars(t("menu_utama"), ENT_QUOTES, "UTF-8") ?>
    </div>

    <a href="<?= htmlspecialchars($dashboardUrl, ENT_QUOTES, "UTF-8") ?>"
       class="nav-link <?= $dashboardAktif ? "active" : "" ?>">
        <?= htmlspecialchars(t("dashboard"), ENT_QUOTES, "UTF-8") ?>
    </a>

    <?php if ($role === "owner"): ?>
        <a href="../gudang/barang.php"
           class="nav-link <?= $barangAktif ? "active" : "" ?>">
            <?= htmlspecialchars(t("manajemen_barang"), ENT_QUOTES, "UTF-8") ?>
        </a>

        <a href="../owner/supplier.php"
           class="nav-link <?= $supplierAktif ? "active" : "" ?>">
            <?= htmlspecialchars(t("manajemen_supplier"), ENT_QUOTES, "UTF-8") ?>
        </a>

    <?php elseif ($role === "gudang"): ?>
        <a href="barang.php"
           class="nav-link <?= $barangAktif ? "active" : "" ?>">
            <?= htmlspecialchars(t("manajemen_barang"), ENT_QUOTES, "UTF-8") ?>
        </a>

        <a href="stok.php"
           class="nav-link <?= $stokAktif ? "active" : "" ?>">
            <?= htmlspecialchars(t("manajemen_stok"), ENT_QUOTES, "UTF-8") ?>
        </a>

    <?php elseif ($role === "toko"): ?>
        <a href="penjualan.php"
           class="nav-link <?= $penjualanAktif ? "active" : "" ?>">
            <?= htmlspecialchars(t("transaksi_penjualan"), ENT_QUOTES, "UTF-8") ?>
        </a>
    <?php endif; ?>

    <div class="menu-label">
        <?= htmlspecialchars(t("pengaturan"), ENT_QUOTES, "UTF-8") ?>
    </div>

    <form action="../config/ganti_bahasa.php" method="POST" style="margin: 10px 0;">
        <input type="hidden" name="kembali"
               value="<?= htmlspecialchars($halamanKembali, ENT_QUOTES, "UTF-8") ?>">

        <select name="bahasa" onchange="this.form.submit()">
            <option value="id" <?= ($_SESSION["bahasa"] ?? "id") === "id" ? "selected" : "" ?>>
                Indonesia
            </option>
            <option value="en" <?= ($_SESSION["bahasa"] ?? "id") === "en" ? "selected" : "" ?>>
                English
            </option>
        </select>
    </form>

    <a href="../auth/logout.php" class="nav-link logout">
        <?= htmlspecialchars(t("logout"), ENT_QUOTES, "UTF-8") ?>
    </a>
</aside>
