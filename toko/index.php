<?php
session_start();

if (
    !isset($_SESSION["id_user"]) ||
    ($_SESSION["role"] ?? "") !== "toko"
) {
    header("Location: ../auth/login.php");
    exit;
}

require_once "../config/bahasa.php";

$namaPengguna = htmlspecialchars(
    $_SESSION["nama"] ?? "Pengguna",
    ENT_QUOTES,
    "UTF-8"
);
?>

<!DOCTYPE html>
<html lang="<?= htmlspecialchars($_SESSION["bahasa"] ?? "id", ENT_QUOTES, "UTF-8") ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars(t("dashboard_toko"), ENT_QUOTES, "UTF-8") ?> | Grosir App</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>

<body>
<div class="app-layout">
    <?php include "../config/navbar.php"; ?>

    <main class="main">
        <header class="page-header">
            <div>
                <h1><?= htmlspecialchars(t("dashboard_toko"), ENT_QUOTES, "UTF-8") ?></h1>
                <p><?= htmlspecialchars(t("deskripsi_dashboard_toko"), ENT_QUOTES, "UTF-8") ?></p>
            </div>

            <div class="user-badge">
                <?= $namaPengguna ?>
            </div>
        </header>

        <section class="card">
            <h2><?= htmlspecialchars(t("selamat_datang"), ENT_QUOTES, "UTF-8") ?>, <?= $namaPengguna ?>!</h2>
            <p class="muted">
                <?= htmlspecialchars(t("dashboard_toko_intro"), ENT_QUOTES, "UTF-8") ?>
            </p>

            <div style="margin-top: 24px;">
                <a href="penjualan.php" class="btn">
                    <?= htmlspecialchars(t("transaksi_penjualan"), ENT_QUOTES, "UTF-8") ?>
                    &rarr;
                </a>
            </div>
        </section>
    </main>
</div>
</body>
</html>
