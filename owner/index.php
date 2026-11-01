<?php
session_start();


if (
    !isset($_SESSION["id_user"]) ||
    ($_SESSION["role"] ?? "") !== "owner"
) {
    header("Location: ../auth/login.php");
    exit;
}

require_once "../config/database.php";
require_once "../config/bahasa.php";

function e($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, "UTF-8");
}

// Statistik barang
$totalBarang = (int) $conn
    ->query("SELECT COUNT(*) FROM barang")
    ->fetchColumn();

$totalStok = (int) $conn
    ->query("SELECT COALESCE(SUM(stok), 0) FROM barang")
    ->fetchColumn();

$stokMenipis = (int) $conn
    ->query("SELECT COUNT(*) FROM barang WHERE stok <= stok_minimum")
    ->fetchColumn();

// Total penjualan
$totalPenjualan = (float) $conn
    ->query("SELECT COALESCE(SUM(total), 0) FROM penjualan")
    ->fetchColumn();

// Barang yang perlu diperhatikan
$stmt = $conn->query(
    "SELECT id_barang, nama_barang, stok, stok_minimum, satuan
    FROM barang
    WHERE stok <= stok_minimum
    ORDER BY stok ASC
    LIMIT 5"
);

$barangMenipis = $stmt->fetchAll();


// Ringkasan dan laporan penjualan dari Admin Toko
$totalTransaksi = (int) $conn
    ->query("SELECT COUNT(*) FROM penjualan")
    ->fetchColumn();

$totalUnitTerjual = (int) $conn
    ->query("SELECT COALESCE(SUM(jumlah), 0) FROM penjualan")
    ->fetchColumn();

$stmt = $conn->query(
    "SELECT p.id_penjualan, p.tanggal, b.nama_barang,
            p.jumlah, b.satuan, p.harga_satuan, p.total, u.nama AS nama_petugas
    FROM penjualan p
    JOIN barang b ON p.id_barang = b.id_barang
    JOIN users u ON p.id_user = u.id_user
    ORDER BY p.id_penjualan DESC
    LIMIT 10"
);

$laporanPenjualan = $stmt->fetchAll();
?>


<!DOCTYPE html>
<html lang="<?= e($_SESSION["bahasa"] ?? "id") ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e(t("dashboard_owner")) ?> | Sistem Grosir</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>

<body>
    <div class="dashboard">
        <?php include "../config/navbar.php"; ?>

        <main class="main">
            <header class="topbar">
                <div>
                    <h1><?= e(t("dashboard")) ?></h1>
                    <div class="muted"><?= e(t("ringkasan_dashboard")) ?></div>
                </div>
                <div class="user-badge"><?= e($_SESSION["nama"] ?? "Owner") ?></div>
            </header>

            <section class="stats">
                <div class="stat-card">
                    <div class="accent"></div>
                    <div class="label"><?= e(t("total_barang")) ?></div>
                    <div class="value"><?= number_format($totalBarang, 0, ",", ".") ?></div>
                </div>

                <div class="stat-card">
                    <div class="accent"></div>
                    <div class="label"><?= e(t("total_stok")) ?></div>
                    <div class="value"><?= number_format($totalStok, 0, ",", ".") ?></div>
                </div>

                <div class="stat-card">
                    <div class="accent"></div>
                    <div class="label"><?= e(t("stok_menipis")) ?></div>
                    <div class="value"><?= number_format($stokMenipis, 0, ",", ".") ?></div>
                </div>

                <div class="stat-card">
                    <div class="accent"></div>
                    <div class="label"><?= e(t("total_penjualan")) ?></div>
                    <div class="value" style="font-size: 22px;">
                        Rp <?= number_format($totalPenjualan, 0, ",", ".") ?>
                    </div>
                </div>
            </section>

            <section class="content-card">
                <div class="content-heading">
                    <h2><?= e(t("perhatian_stok")) ?></h2>
                    <a class="btn" href="../gudang/barang.php"><?= e(t("lihat_barang")) ?></a>
                </div>

                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th><?= e(t("nama_barang")) ?></th>
                                <th><?= e(t("stok_tersedia")) ?></th>
                                <th><?= e(t("stok_minimum")) ?></th>
                                <th><?= e(t("status")) ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!$barangMenipis): ?>
                                <tr>
                                    <td colspan="4" class="empty"><?= e(t("tidak_ada_stok_menipis")) ?></td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($barangMenipis as $barang): ?>
                                    <tr>
                                        <td><?= e($barang["nama_barang"]) ?></td>
                                        <td><?= number_format((int) $barang["stok"], 0, ",", ".") ?> <?= e($barang["satuan"]) ?></td>
                                        <td><?= number_format((int) $barang["stok_minimum"], 0, ",", ".") ?> <?= e($barang["satuan"]) ?></td>
                                        <td><span class="status"><?= e(t("stok_menipis_status")) ?></span></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>
            <br>
            <section class="content-card">
                <div class="content-heading">
                    <div>
                        <h2><?= e(t("laporan_penjualan_toko")) ?></h2>
                        <div class="muted"><?= e(t("deskripsi_laporan_penjualan_toko")) ?></div>
                    </div>
                    <span class="count"><?= number_format($totalTransaksi, 0, ",", ".") ?> <?= e(t("total_transaksi")) ?></span>
                </div>

                <div class="stats" style="margin-bottom: 20px;">
                    <div class="stat-card">
                        <div class="accent"></div>
                        <div class="label"><?= e(t("total_transaksi")) ?></div>
                        <div class="value"><?= number_format($totalTransaksi, 0, ",", ".") ?></div>
                    </div>
                    <div class="stat-card">
                        <div class="accent"></div>
                        <div class="label"><?= e(t("total_unit_terjual")) ?></div>
                        <div class="value"><?= number_format($totalUnitTerjual, 0, ",", ".") ?></div>
                    </div>
                    <div class="stat-card">
                        <div class="accent"></div>
                        <div class="label"><?= e(t("total_penjualan")) ?></div>
                        <div class="value" style="font-size: 22px;">Rp <?= number_format($totalPenjualan, 0, ",", ".") ?></div>
                    </div>
                </div>

                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th><?= e(t("tanggal")) ?></th>
                                <th><?= e(t("nama_barang")) ?></th>
                                <th><?= e(t("jumlah")) ?></th>
                                <th><?= e(t("harga_satuan")) ?></th>
                                <th><?= e(t("nilai_total")) ?></th>
                                <th><?= e(t("petugas")) ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!$laporanPenjualan): ?>
                                <tr>
                                    <td colspan="7" class="empty"><?= e(t("belum_ada_laporan_penjualan")) ?></td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($laporanPenjualan as $transaksi): ?>
                                    <tr>
                                        <td><?= e($transaksi["id_penjualan"]) ?></td>
                                        <td><?= e($transaksi["tanggal"]) ?></td>
                                        <td><?= e($transaksi["nama_barang"]) ?></td>
                                        <td><?= number_format((int) $transaksi["jumlah"], 0, ",", ".") ?> <?= e($transaksi["satuan"]) ?></td>
                                        <td>Rp <?= number_format((float) $transaksi["harga_satuan"], 0, ",", ".") ?></td>
                                        <td>Rp <?= number_format((float) $transaksi["total"], 0, ",", ".") ?></td>
                                        <td><?= e($transaksi["nama_petugas"]) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        </main>
    </div>
</body>

</html>