<?php
session_start();

require_once "../config/bahasa.php";
require_once "../config/database.php";

if (
    !isset($_SESSION["id_user"]) ||
    !in_array($_SESSION["role"], ["owner", "gudang"], true)
) {
    header("Location: ../auth/login.php");
    exit;
}



$error = "";
$pesan = "";

function e($value)
{
    return htmlspecialchars((string)$value, ENT_QUOTES, "UTF-8");
}

// PROSES TRANSAKSI STOK
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Owner hanya boleh melihat riwayat, bukan mengubah stok.
    if ($_SESSION["role"] !== "gudang") {
        $error = "Hanya admin gudang yang boleh mengubah stok.";
    } else {
        $aksi = $_POST["aksi"] ?? "";
        $id_barang = filter_var(
            $_POST["id_barang"] ?? null,
            FILTER_VALIDATE_INT
        );
        $jumlah = filter_var(
            $_POST["jumlah"] ?? null,
            FILTER_VALIDATE_INT
        );
        $keterangan = trim($_POST["keterangan"] ?? "");

        if (
            !in_array($aksi, ["masuk", "keluar"], true) ||
            !$id_barang ||
            $jumlah === false ||
            $jumlah === null ||
            $jumlah <= 0
        ) {
            $error = "Pilih barang dan masukkan jumlah yang valid.";
        } else {
            try {
                $conn->beginTransaction();

                // Kunci baris barang untuk mencegah stok berubah bersamaan.
                $stmt = $conn->prepare(
                    "SELECT stok FROM barang
                     WHERE id_barang = :id
                     FOR UPDATE"
                );
                $stmt->execute([":id" => $id_barang]);
                $barang = $stmt->fetch();

                if (!$barang) {
                    throw new Exception("Barang tidak ditemukan.");
                }

                $stok_sekarang = (int)$barang["stok"];

                if ($aksi === "masuk") {
                    $id_supplier = filter_var(
                        $_POST["id_supplier"] ?? null,
                        FILTER_VALIDATE_INT
                    );

                    if (!$id_supplier) {
                        throw new Exception("Pilih supplier.");
                    }

                    $stmt = $conn->prepare(
                        "SELECT id_supplier FROM supplier
                         WHERE id_supplier = :id"
                    );
                    $stmt->execute([":id" => $id_supplier]);

                    if (!$stmt->fetch()) {
                        throw new Exception("Supplier tidak ditemukan.");
                    }

                    $stmt = $conn->prepare(
                        "INSERT INTO barang_masuk
                         (id_barang, id_supplier, id_user, jumlah, keterangan)
                         VALUES (:barang, :supplier, :user, :jumlah, :ket)"
                    );
                    $stmt->execute([
                        ":barang" => $id_barang,
                        ":supplier" => $id_supplier,
                        ":user" => $_SESSION["id_user"],
                        ":jumlah" => $jumlah,
                        ":ket" => $keterangan
                    ]);

                    $stmt = $conn->prepare(
                        "UPDATE barang
                         SET stok = stok + :jumlah
                         WHERE id_barang = :id"
                    );
                    $stmt->execute([
                        ":jumlah" => $jumlah,
                        ":id" => $id_barang
                    ]);
                } else {
                    if ($jumlah > $stok_sekarang) {
                        throw new Exception(
                            "Stok tidak cukup. Stok saat ini: " .
                                $stok_sekarang
                        );
                    }

                    $stmt = $conn->prepare(
                        "INSERT INTO barang_keluar
                         (id_barang, id_user, jumlah, keterangan)
                         VALUES (:barang, :user, :jumlah, :ket)"
                    );
                    $stmt->execute([
                        ":barang" => $id_barang,
                        ":user" => $_SESSION["id_user"],
                        ":jumlah" => $jumlah,
                        ":ket" => $keterangan
                    ]);

                    $stmt = $conn->prepare(
                        "UPDATE barang
                         SET stok = stok - :jumlah
                         WHERE id_barang = :id"
                    );
                    $stmt->execute([
                        ":jumlah" => $jumlah,
                        ":id" => $id_barang
                    ]);
                }

                $conn->commit();

                header("Location: stok.php?status=berhasil");
                exit;
            } catch (Throwable $ex) {
                if ($conn->inTransaction()) {
                    $conn->rollBack();
                }

                if ($ex instanceof PDOException) {
                    error_log($ex->getMessage());
                    $error = "Transaksi gagal disimpan. Periksa database.";
                } else {
                    $error = $ex->getMessage();
                }
            }
        }
    }
}

// PESAN SUKSES
if (($_GET["status"] ?? "") === "berhasil") {
    $pesan = "Transaksi stok berhasil disimpan.";
}

// DATA BARANG
$stmt = $conn->query(
    "SELECT id_barang, nama_barang, satuan, stok
     FROM barang ORDER BY nama_barang"
);
$barangList = $stmt->fetchAll();

// DATA SUPPLIER
$stmt = $conn->query(
    "SELECT id_supplier, nama_supplier
     FROM supplier ORDER BY nama_supplier"
);
$supplierList = $stmt->fetchAll();

// RIWAYAT BARANG MASUK
$stmt = $conn->query(
    "SELECT bm.tanggal, b.nama_barang, s.nama_supplier,
            bm.jumlah, bm.keterangan, u.nama
     FROM barang_masuk bm
     JOIN barang b ON bm.id_barang = b.id_barang
     JOIN supplier s ON bm.id_supplier = s.id_supplier
     JOIN users u ON bm.id_user = u.id_user
     ORDER BY bm.id_masuk DESC
     LIMIT 50"
);
$riwayatMasuk = $stmt->fetchAll();

// RIWAYAT BARANG KELUAR
$stmt = $conn->query(
    "SELECT bk.tanggal, b.nama_barang, bk.jumlah,
            bk.keterangan, u.nama
     FROM barang_keluar bk
     JOIN barang b ON bk.id_barang = b.id_barang
     JOIN users u ON bk.id_user = u.id_user
     ORDER BY bk.id_keluar DESC
     LIMIT 50"
);
$riwayatKeluar = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="<?= e($_SESSION["bahasa"] ?? "id") ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e(t("manajemen_stok")) ?> | Grosir App</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>

<body>
    <div class="app-layout">
        <?php include "../config/navbar.php"; ?>

        <main class="main">
            <header class="page-header">
                <div>
                    <h1><?= e(t("manajemen_stok")) ?></h1>
                    <p class="page-subtitle"><?= e(t("deskripsi_stok")) ?></p>
                </div>
                <div class="user-badge"><?= e($_SESSION["nama"] ?? "Pengguna") ?></div>
            </header>

            <?php if ($pesan !== ""): ?>
                <div class="notice"><?= e($pesan) ?></div>
            <?php endif; ?>

            <?php if ($error !== ""): ?>
                <div class="notice error"><?= e($error) ?></div>
            <?php endif; ?>

            <section class="card">
                <h2><?= e(t("stok_barang_saat_ini")) ?></h2>
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th><?= e(t("nama_barang")) ?></th>
                                <th><?= e(t("satuan")) ?></th>
                                <th><?= e(t("stok")) ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!$barangList): ?>
                                <tr>
                                    <td colspan="3" style="text-align:center;color:#aaa;padding:28px">
                                        <?= e(t("belum_ada_barang")) ?>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($barangList as $b): ?>
                                    <tr>
                                        <td><?= e($b["nama_barang"]) ?></td>
                                        <td><?= e($b["satuan"]) ?></td>
                                        <td><strong><?= e(number_format((int)$b["stok"], 0, ",", ".")) ?></strong></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>

            <?php if ($_SESSION["role"] === "gudang"): ?>
                <br>
                <section class="card">
                    <h2><?= e(t("catat_transaksi_stok")) ?></h2>

                    <form method="POST">
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="aksi"><b><?= e(t("jenis_transaksi")) ?></b> *</label>
                                <select name="aksi" id="aksi" required>
                                    <option value="masuk"><?= e(t("barang_masuk")) ?></option>
                                    <option value="keluar"><?= e(t("barang_keluar")) ?></option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="id_barang"><b><?= e(t("nama_barang")) ?></b> *</label>
                                <select name="id_barang" id="id_barang" required>
                                    <option value=""><?= e(t("pilih_barang")) ?></option>
                                    <?php foreach ($barangList as $b): ?>
                                        <option value="<?= e($b["id_barang"]) ?>">
                                            <?= e($b["nama_barang"]) ?> — <?= e(t("stok")) ?>: <?= e($b["stok"]) ?> <?= e($b["satuan"]) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="form-group" id="supplierField">
                                <label for="id_supplier"><b><?= e(t("supplier")) ?></b> *</label>
                                <select name="id_supplier" id="id_supplier">
                                    <option value=""><?= e(t("pilih_supplier")) ?></option>
                                    <?php foreach ($supplierList as $s): ?>
                                        <option value="<?= e($s["id_supplier"]) ?>">
                                            <?= e($s["nama_supplier"]) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="jumlah"><b><?= e(t("jumlah")) ?></b> *</label>
                                <input type="number" name="jumlah" id="jumlah" min="1" required>
                            </div>

                            <div class="form-group" style="grid-column:1 / -1">
                                <label for="keterangan"><b><?= e(t("keterangan")) ?></b></label>
                                <textarea name="keterangan" id="keterangan" rows="3" maxlength="255"></textarea>
                            </div>
                        </div>

                        <br>
                        <div class="actions">
                            <button class="btn" type="submit">
                                <?= e(t("simpan_transaksi")) ?>
                            </button>
                        </div>
                    </form>
                </section>

                <script>
                    const aksiTransaksi = document.getElementById("aksi");
                    const supplierField = document.getElementById("supplierField");
                    const supplierSelect = document.getElementById("id_supplier");

                    function aturSupplier() {
                        const barangMasuk = aksiTransaksi.value === "masuk";
                        supplierField.hidden = !barangMasuk;
                        supplierSelect.required = barangMasuk;

                        if (!barangMasuk) {
                            supplierSelect.value = "";
                        }
                    }

                    aksiTransaksi.addEventListener("change", aturSupplier);
                    aturSupplier();
                </script>
            <?php endif; ?>

            <br>
            <section class="card">
                <h2>
                    <?= e(t("riwayat_barang_masuk")) ?>
                    <span class="count">(<?= count($riwayatMasuk) ?>)</span>
                </h2>

                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th><?= e(t("tanggal")) ?></th>
                                <th><?= e(t("nama_barang")) ?></th>
                                <th><?= e(t("supplier")) ?></th>
                                <th><?= e(t("jumlah")) ?></th>
                                <th><?= e(t("keterangan")) ?></th>
                                <th><?= e(t("petugas")) ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!$riwayatMasuk): ?>
                                <tr>
                                    <td colspan="6" style="text-align:center;color:#aaa;padding:28px">
                                        <?= e(t("belum_ada_transaksi_masuk")) ?>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($riwayatMasuk as $r): ?>
                                    <tr>
                                        <td><?= e($r["tanggal"]) ?></td>
                                        <td><?= e($r["nama_barang"]) ?></td>
                                        <td><?= e($r["nama_supplier"]) ?></td>
                                        <td><?= e($r["jumlah"]) ?></td>
                                        <td><?= e($r["keterangan"] ?: "—") ?></td>
                                        <td><?= e($r["nama"]) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>

            <br>
            <section class="card">
                <h2>
                    <?= e(t("riwayat_barang_keluar")) ?>
                    <span class="count">(<?= count($riwayatKeluar) ?>)</span>
                </h2>

                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th><?= e(t("tanggal")) ?></th>
                                <th><?= e(t("nama_barang")) ?></th>
                                <th><?= e(t("jumlah")) ?></th>
                                <th><?= e(t("keterangan")) ?></th>
                                <th><?= e(t("petugas")) ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!$riwayatKeluar): ?>
                                <tr>
                                    <td colspan="5" style="text-align:center;color:#aaa;padding:28px">
                                        <?= e(t("belum_ada_transaksi_keluar")) ?>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($riwayatKeluar as $r): ?>
                                    <tr>
                                        <td><?= e($r["tanggal"]) ?></td>
                                        <td><?= e($r["nama_barang"]) ?></td>
                                        <td><?= e($r["jumlah"]) ?></td>
                                        <td><?= e($r["keterangan"] ?: "—") ?></td>
                                        <td><?= e($r["nama"]) ?></td>
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