<?php
session_start();

if (
    !isset($_SESSION["id_user"]) ||
    ($_SESSION["role"] ?? "") !== "gudang"
) {
    header("Location: ../auth/login.php");
    exit;
}

require_once "../config/bahasa.php";

function e($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, "UTF-8");
}

$nama = $_SESSION["nama"] ?? "Admin Gudang";
?>

<!DOCTYPE html>
<html lang="<?= e($_SESSION["bahasa"] ?? "id") ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title><?= e(t("dashboard_gudang")) ?> | Grosir App</title>

    <link rel="stylesheet" href="../assets/css/style.css">

    <style>
        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 20px;
            margin-top: 28px;
        }

        .quick-card {
            display: block;
            padding: 26px;
            background: linear-gradient(145deg, #191919, #0A0A0A);
            border: 1px solid #333;
            border-radius: 14px;
            color: #FFF;
            text-decoration: none;
            transition: .2s ease;
        }

        .quick-card:hover {
            border-color: #6E2B15;
            transform: translateY(-3px);
        }

        .quick-card .accent {
            width: 35px;
            height: 3px;
            background: #6E2B15;
            margin-bottom: 20px;
        }

        .quick-card h2 {
            font-size: 18px;
            margin: 0 0 10px;
        }

        .quick-card p {
            color: #AAA;
            font-size: 14px;
            margin: 0;
            line-height: 1.6;
        }

        .welcome-panel {
            background: #111;
            border: 1px solid #333;
            border-radius: 14px;
            padding: 24px;
        }

        .welcome-panel p {
            color: #AAA;
            margin: 8px 0 0;
        }

        @media (max-width: 750px) {
            .dashboard-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>

<div class="app-layout">

    <aside class="sidebar">

        <div class="brand">GROSIR<span>.</span>APP</div>

        <div class="menu-label">
            <?= e(t("menu_utama")) ?>
        </div>

        <a href="index.php" class="nav-link active">
            <?= e(t("dashboard")) ?>
        </a>

        <a href="stok.php" class="nav-link">
            <?= e(t("manajemen_stok")) ?>
        </a>

        <a href="barang.php" class="nav-link">
            <?= e(t("manajemen_barang")) ?>
        </a>

        <div class="sidebar-spacer"></div>

        <div class="menu-label">
            <?= e(t("pengaturan")) ?>
        </div>

        <form action="../config/ganti_bahasa.php" method="POST"
              style="margin:10px 0;">

            <input type="hidden" name="kembali"
                   value="../gudang/index.php">

            <select name="bahasa" onchange="this.form.submit()"
                    aria-label="Pilih bahasa">

                <option value="id"
                    <?= ($_SESSION["bahasa"] ?? "id") === "id" ? "selected" : "" ?>>
                    Indonesia
                </option>

                <option value="en"
                    <?= ($_SESSION["bahasa"] ?? "id") === "en" ? "selected" : "" ?>>
                    English
                </option>

            </select>
        </form>

        <a href="../auth/logout.php" class="nav-link logout">
            <?= e(t("keluar_dari_sistem")) ?>
        </a>

    </aside>

    <main class="main-content">

        <header class="page-header">
            <div>
                <h1><?= e(t("dashboard_gudang")) ?></h1>
                <p class="page-subtitle">
                    <?= e(t("deskripsi_dashboard_gudang")) ?>
                </p>
            </div>

            <div class="user-badge">
                <?= e($nama) ?> · <?= e(t("admin_gudang")) ?>
            </div>
        </header>

        <section class="welcome-panel">
            <h2>
                <?= e(t("selamat_datang")) ?>, <?= e($nama) ?>
            </h2>

            <p>
                <?= e(t("pesan_dashboard_gudang")) ?>
            </p>
        </section>

        <section class="dashboard-grid">

            <a href="stok.php" class="quick-card">
                <div class="accent"></div>

                <h2><?= e(t("manajemen_stok")) ?></h2>

                <p>
                    <?= e(t("deskripsi_manajemen_stok")) ?>
                </p>
            </a>

            <a href="barang.php" class="quick-card">
                <div class="accent"></div>

                <h2><?= e(t("manajemen_barang")) ?></h2>

                <p>
                    <?= e(t("deskripsi_manajemen_barang")) ?>
                </p>
            </a>

        </section>

    </main>

</div>

</body>
</html>