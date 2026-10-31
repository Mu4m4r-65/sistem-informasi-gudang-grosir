    <?php
    session_start();
    require_once "../config/bahasa.php";

    if (
        !isset($_SESSION["id_user"]) ||
        $_SESSION["role"] !== "owner"
    ) {
        header("Location: ../auth/login.php");
        exit;
    }

    require_once "../config/database.php";

    function e($value) {
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
    ?>

    <!DOCTYPE html>
    <html lang="id">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">

        <title>Dashboard Owner | Sistem Grosir</title>

        <link rel="stylesheet" href="../assets/css/style.css">

        <style>
            .dashboard {
                display: flex;
                min-height: 100vh;
            }

            .sidebar {
                width: 250px;
                padding: 28px 18px;
                flex-shrink: 0;
            }

            .brand {
                font-size: 17px;
                font-weight: bold;
                letter-spacing: 2px;
                margin: 0 10px 45px;
            }

            .brand span {
                color: #6E2B15;
            }

            .menu-label {
                margin: 25px 12px 12px;
                color: #888;
                font-size: 11px;
                letter-spacing: 1.5px;
            }

            .sidebar a {
                display: block;
                padding: 13px 14px;
                margin-bottom: 7px;
                border-radius: 8px;
                color: #CCC;
                text-decoration: none;
                font-size: 14px;
            }

            .sidebar a:hover,
            .sidebar a.active {
                background: #6E2B15;
                color: #FFF;
            }

            .main {
                flex: 1;
                min-width: 0;
                padding: 32px;
            }

            .topbar {
                display: flex;
                justify-content: space-between;
                align-items: center;
                gap: 16px;
                margin-bottom: 35px;
            }

            .topbar h1 {
                margin: 0 0 8px;
                font-size: 28px;
            }

            .muted {
                color: #AAA;
                font-size: 14px;
            }

            .user-badge {
                border: 1px solid #333;
                background: #151515;
                padding: 12px 16px;
                border-radius: 10px;
                font-size: 13px;
            }

            .stats {
                display: grid;
                grid-template-columns: repeat(4, minmax(0, 1fr));
                gap: 18px;
                margin-bottom: 28px;
            }

            .stat-card {
                background: linear-gradient(145deg, #191919, #0A0A0A);
                border: 1px solid #333;
                border-radius: 14px;
                padding: 22px;
            }

            .stat-card .label {
                color: #AAA;
                font-size: 13px;
                margin-bottom: 16px;
            }

            .stat-card .value {
                font-size: 27px;
                font-weight: bold;
                overflow-wrap: anywhere;
            }

            .stat-card .accent {
                width: 35px;
                height: 3px;
                background: #6E2B15;
                margin-bottom: 18px;
            }

            .content-card {
                background: #111;
                border: 1px solid #333;
                border-radius: 14px;
                padding: 24px;
                overflow-x: auto;
            }

            .content-heading {
                display: flex;
                justify-content: space-between;
                align-items: center;
                gap: 12px;
                margin-bottom: 22px;
            }

            .content-heading h2 {
                font-size: 18px;
                margin: 0;
            }

            .status {
                display: inline-block;
                padding: 6px 10px;
                border-radius: 6px;
                background: #35170D;
                color: #FFF;
                font-size: 12px;
            }

            .empty {
                color: #AAA;
                text-align: center;
                padding: 25px;
            }

            .logout {
                margin-top: 35px;
            }

            @media (max-width: 1100px) {
                .stats {
                    grid-template-columns: repeat(2, minmax(0, 1fr));
                }
            }

            @media (max-width: 750px) {
                .dashboard {
                    display: block;
                }

                .sidebar {
                    width: 100%;
                    padding: 18px;
                    border-right: none;
                    border-bottom: 1px solid #333;
                }

                .brand {
                    margin: 5px 8px 20px;
                }

                .menu-label {
                    margin-top: 15px;
                }

                .sidebar a {
                    display: inline-block;
                    margin: 3px;
                }

                .main {
                    padding: 20px 14px;
                }

                .topbar {
                    align-items: flex-start;
                    flex-direction: column;
                }

                .topbar h1 {
                    font-size: 23px;
                }

                .stats {
                    gap: 10px;
                }

                .stat-card {
                    padding: 15px;
                }

                .stat-card .value {
                    font-size: 21px;
                }

                .content-card {
                    padding: 15px;
                }
            }
        </style>
    </head>

    <body>

    <div class="dashboard">

        
    <aside class="sidebar">
        <div class="brand">GROSIR<span>.</span>APP</div>

        <div class="menu-label">
            <?= htmlspecialchars(t("MENU UTAMA")) ?>
        </div>

        <a href="index.php" class="active">
            <?= htmlspecialchars(t("dashboard")) ?>
        </a>

        <a href="../gudang/barang.php">
            <?= htmlspecialchars(t("Manajemen Barang")) ?>
        </a>

        <a href="supplier.php">
            <?= htmlspecialchars(t("Manajemen Supplier")) ?>
        </a>

        <div class="menu-label">
            <?= htmlspecialchars(t("PENGATURAN")) ?>
        </div>

        <form action="../config/ganti_bahasa.php" method="POST"
            style="margin: 10px 0;">
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

        <a href="../auth/logout.php" class="logout">
            <?= e(t("Keluar dari Sistem")) ?>
        </a>
    </aside>


        <main class="main">

            <header class="topbar">
                <div>
                    <h1><?= htmlspecialchars(t("dashboard")) ?></h1>
                    <div class="muted">
                        <?= htmlspecialchars(t("ringkasan_dashboard")) ?>
                    </div>
                </div>

                <div class="user-badge">
                    <?= htmlspecialchars(t("dashboard_owner")) ?> · <?= e($_SESSION["nama"]) ?>
                </div>
            </header>

            <section class="stats">

                <div class="stat-card">
                    <div class="accent"></div>
                    <div class="label"><?= htmlspecialchars(t("total_barang")) ?></div>
                    <div class="value">
                        <?= number_format($totalBarang, 0, ",", ".") ?>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="accent"></div>
                    <div class="label"><?= htmlspecialchars(t("total_stok")) ?></div>
                    <div class="value">
                        <?= number_format($totalStok, 0, ",", ".") ?>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="accent"></div>
                    <div class="label"><?= htmlspecialchars(t("stok_menipis")) ?></div>
                    <div class="value">
                        <?= number_format($stokMenipis, 0, ",", ".") ?>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="accent"></div>
                    <div class="label"><?= htmlspecialchars(t("total_penjualan")) ?></div>
                    <div class="value" style="font-size: 22px;">
                        Rp <?= number_format($totalPenjualan, 0, ",", ".") ?>
                    </div>
                </div>

            </section>

            <section class="content-card">

                <div class="content-heading">
                    <h2><?= htmlspecialchars(t("perhatian_stok")) ?></h2>
                    <a class="btn" href="../gudang/barang.php">
                        <?= htmlspecialchars(t("lihat_barang")) ?>
                    </a>
                </div>

                <table>
                    <thead>
                        <tr>
                            <th><?= htmlspecialchars(t("nama_barang")) ?></th>
                            <th><?= htmlspecialchars(t("stok_tersedia")) ?></th>
                            <th><?= htmlspecialchars(t("stok_minimum")) ?></th>
                            <th><?= htmlspecialchars(t("status")) ?></th>
                        </tr>
                    </thead>

                    <tbody>
                    <?php if (!$barangMenipis): ?>
                        <tr>
                            <td colspan="4" class="empty">
                                <?= htmlspecialchars(t("tidak_ada_stok_menipis")) ?>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($barangMenipis as $barang): ?>
                            <tr>
                                <td>
                                    <?= e($barang["nama_barang"]) ?>
                                </td>

                                <td>
                                    <?= number_format((int) $barang["stok"], 0, ",", ".") ?>
                                    <?= e($barang["satuan"]) ?>
                                </td>

                                <td>
                                    <?= number_format((int) $barang["stok_minimum"], 0, ",", ".") ?>
                                    <?= e($barang["satuan"]) ?>
                                </td>

                                <td>
                                    <span class="status">
                                        <?= e(t("Stok Menipis")) ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>

            </section>

        </main>

    </div>

    </body>
    </html>
