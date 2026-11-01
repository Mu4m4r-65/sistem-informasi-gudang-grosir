
<?php
session_start();

if (
    !isset($_SESSION["id_user"]) ||
    ($_SESSION["role"] ?? "") !== "toko"
) {
    header("Location: ../auth/login.php");
    exit;
}

require_once "../config/database.php";
require_once "../config/bahasa.php";

$error = "";
$pesan = "";

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, "UTF-8");
}

// PROSES PENJUALAN
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $id_barang = filter_var(
        $_POST["id_barang"] ?? null,
        FILTER_VALIDATE_INT
    );

    $jumlah = filter_var(
        $_POST["jumlah"] ?? null,
        FILTER_VALIDATE_INT
    );

    if (
        !$id_barang ||
        $jumlah === false ||
        $jumlah === null ||
        $jumlah <= 0
    ) {
        $error = t("penjualan_input_tidak_valid");
    } else {
        try {
            $conn->beginTransaction();

            // Ambil harga dan kunci stok barang.
            $stmt = $conn->prepare(
                "SELECT id_barang, nama_barang, harga_jual, stok
                 FROM barang
                 WHERE id_barang = :id
                 FOR UPDATE"
            );

            $stmt->execute([":id" => $id_barang]);
            $barang = $stmt->fetch();

            if (!$barang) {
                throw new Exception(t("barang_tidak_ditemukan"));
            }

            $stok = (int)$barang["stok"];
            $harga = (float)$barang["harga_jual"];

            if ($jumlah > $stok) {
                throw new Exception(
                    t("stok_tidak_mencukupi") . " " . $stok
                );
            }

            if ($harga < 0 || $harga * $jumlah > 999999999999.99) {
                throw new Exception(t("total_penjualan_tidak_valid"));
            }

            $total = round($harga * $jumlah, 2);

            // Simpan transaksi penjualan.
            $stmt = $conn->prepare(
                "INSERT INTO penjualan
                 (id_barang, id_user, jumlah, harga_satuan, total)
                 VALUES
                 (:barang, :user, :jumlah, :harga, :total)"
            );

            $stmt->execute([
                ":barang" => $id_barang,
                ":user" => $_SESSION["id_user"],
                ":jumlah" => $jumlah,
                ":harga" => $harga,
                ":total" => $total
            ]);

            // Kurangi stok hanya jika stok masih mencukupi.
            $stmt = $conn->prepare(
                "UPDATE barang
                 SET stok = stok - :jumlah
                 WHERE id_barang = :id
                   AND stok >= :jumlah"
            );

            $stmt->execute([
                ":jumlah" => $jumlah,
                ":id" => $id_barang
            ]);

            if ($stmt->rowCount() !== 1) {
                throw new Exception(
                    t("stok_berubah_coba_lagi")
                );
            }

            $conn->commit();

            header("Location: penjualan.php?status=berhasil");
            exit;

        } catch (Throwable $ex) {
            if ($conn->inTransaction()) {
                $conn->rollBack();
            }

            if ($ex instanceof PDOException) {
                error_log($ex->getMessage());
                $error = t("penjualan_gagal");
            } else {
                $error = $ex->getMessage();
            }
        }
    }
}

// PESAN SUKSES
if (($_GET["status"] ?? "") === "berhasil") {
    $pesan = t("penjualan_berhasil");
}

// DAFTAR BARANG
$stmt = $conn->query(
    "SELECT id_barang, nama_barang, satuan, harga_jual, stok
     FROM barang
     ORDER BY nama_barang"
);

$barangList = $stmt->fetchAll();

// RIWAYAT PENJUALAN
$stmt = $conn->prepare(
    "SELECT p.id_penjualan, p.tanggal, b.nama_barang,
            p.jumlah, p.harga_satuan, p.total, u.nama
     FROM penjualan p
     JOIN barang b ON p.id_barang = b.id_barang
     JOIN users u ON p.id_user = u.id_user
     WHERE p.id_user = :user
     ORDER BY p.id_penjualan DESC
     LIMIT 100"
);

$stmt->execute([":user" => $_SESSION["id_user"]]);
$riwayat = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="<?= e($_SESSION["bahasa"] ?? "id") ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e(t("transaksi_penjualan")) ?> | Grosir App</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="app-layout">
    <?php include "../config/navbar.php"; ?>

    <main class="main">
        <header class="page-header">
            <div>
                <h1><?= e(t("transaksi_penjualan")) ?></h1>
                <p class="page-subtitle"><?= e(t("deskripsi_transaksi_penjualan")) ?></p>
            </div>
            <div class="user-badge"><?= e($_SESSION["nama"] ?? "Pengguna") ?></div>
        </header>

        <?php if ($pesan !== ""): ?>
            <div class="notice success"><?= e($pesan) ?></div>
        <?php endif; ?>

        <?php if ($error !== ""): ?>
            <div class="notice error"><?= e($error) ?></div>
        <?php endif; ?>
        <br>
        <section class="card">
            <div class="section-heading">
                <div>
                    <h2><?= e(t("buat_transaksi_penjualan")) ?></h2>
                    <h3 class="page-subtitle"><?= e(t("isi_form_penjualan")) ?></h3>
                    <br>
                </div>
            </div>

            <form method="POST" class="form-grid">
                <div class="form-group">
                    <label for="barang"><b><?= e(t("pilih_barang")) ?></b></label>
                    <select name="id_barang" id="barang" required>
                        <option value=""><?= e(t("pilih_barang")) ?></option>
                        <?php foreach ($barangList as $b): ?>
                            <option
                                value="<?= e($b["id_barang"]) ?>"
                                data-harga="<?= e($b["harga_jual"]) ?>"
                                data-stok="<?= e($b["stok"]) ?>"
                                <?= (int)$b["stok"] <= 0 ? "disabled" : "" ?>
                            >
                                <?= e($b["nama_barang"]) ?> — <?= e(t("stok")) ?>: <?= e($b["stok"]) ?> <?= e($b["satuan"]) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="jumlah"><b><?= e(t("jumlah")) ?></b></label>
                    <input
                        type="number"
                        name="jumlah"
                        id="jumlah"
                        min="1"
                        step="1"
                        placeholder="<?= e(t("masukkan_jumlah")) ?>"
                        required
                    >
                </div>

                <div class="form-group">
                    <label><b><?= e(t("harga_satuan")) ?></b></label>
                    <div class="value-display" id="harga">Rp 0</div>
                </div>

                <div class="form-group">
                    <label><b><?= e(t("estimasi_total")) ?></b></label>
                    <div class="value-display total-value" id="total">Rp 0</div>
                </div>

                <div class="actions">
                    <button type="submit" class="btn" <?= !$barangList ? "disabled" : "" ?>>
                        <?= e(t("simpan_penjualan")) ?>
                    </button>
                </div>
            </form>
        </section>
        
        <br>
        <section class="card">
            <div class="section-heading">
                <div>
                    <h2><?= e(t("daftar_barang_tersedia")) ?></h2>
                    <p class="page-subtitle"><?= e(t("deskripsi_daftar_barang_tersedia")) ?></p>
                </div>
                <span class="count"><?= count($barangList) ?> <?= e(t("jenis_barang")) ?></span>
            </div>
            <br>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th><?= e(t("nama_barang")) ?></th>
                            <th><?= e(t("satuan")) ?></th>
                            <th><?= e(t("harga_jual")) ?></th>
                            <th><?= e(t("stok_saat_ini")) ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($barangList as $b): ?>
                            <tr>
                                <td><?= e($b["nama_barang"]) ?></td>
                                <td><?= e($b["satuan"]) ?></td>
                                <td>Rp <?= number_format((float)$b["harga_jual"], 0, ",", ".") ?></td>
                                <td><?= e($b["stok"]) ?></td>
                            </tr>
                        <?php endforeach; ?>

                        <?php if (!$barangList): ?>
                            <tr>
                                <td colspan="4"><?= e(t("belum_ada_barang")) ?></td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
        <br>
        <section class="card">
            <div class="section-heading">
                <div>
                    <h2><?= e(t("riwayat_penjualan_saya")) ?></h2>
                    <p class="page-subtitle"><?= e(t("deskripsi_riwayat_penjualan")) ?></p>
                </div>
                <span class="count"><?= count($riwayat) ?> <?= e(t("transaksi")) ?></span>
            </div>
            <br>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th><?= e(t("tanggal")) ?></th>
                            <th><?= e(t("barang")) ?></th>
                            <th><?= e(t("jumlah")) ?></th>
                            <th><?= e(t("harga_satuan")) ?></th>
                            <th><?= e(t("nilai_total")) ?></th>
                            <th><?= e(t("petugas")) ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($riwayat as $r): ?>
                            <tr>
                                <td><?= e($r["id_penjualan"]) ?></td>
                                <td><?= e($r["tanggal"]) ?></td>
                                <td><?= e($r["nama_barang"]) ?></td>
                                <td><?= e($r["jumlah"]) ?></td>
                                <td>Rp <?= number_format((float)$r["harga_satuan"], 0, ",", ".") ?></td>
                                <td>Rp <?= number_format((float)$r["total"], 0, ",", ".") ?></td>
                                <td><?= e($r["nama"]) ?></td>
                            </tr>
                        <?php endforeach; ?>

                        <?php if (!$riwayat): ?>
                            <tr>
                                <td colspan="7"><?= e(t("belum_ada_transaksi_penjualan")) ?></td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>
</div>

<script>
const pilihanBarang = document.getElementById("barang");
const inputJumlah = document.getElementById("jumlah");
const hargaTampil = document.getElementById("harga");
const totalTampil = document.getElementById("total");

function rupiah(nilai) {
    return new Intl.NumberFormat("id-ID", {
        style: "currency",
        currency: "IDR",
        maximumFractionDigits: 0
    }).format(nilai);
}

function hitungTotal() {
    const opsi = pilihanBarang.selectedOptions[0];

    if (!opsi || !opsi.value) {
        hargaTampil.textContent = rupiah(0);
        totalTampil.textContent = rupiah(0);
        inputJumlah.removeAttribute("max");
        return;
    }

    const harga = Number(opsi.dataset.harga);
    const stok = Number(opsi.dataset.stok);
    const jumlah = Number(inputJumlah.value);

    inputJumlah.max = stok;
    hargaTampil.textContent = rupiah(harga);
    totalTampil.textContent = rupiah(harga * (jumlah > 0 ? jumlah : 0));
}

pilihanBarang.addEventListener("change", hitungTotal);
inputJumlah.addEventListener("input", hitungTotal);
</script>
</body>
</html>
