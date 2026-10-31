
<?php
session_start();

if (
    !isset($_SESSION["id_user"]) ||
    $_SESSION["role"] !== "toko"
) {
    header("Location: ../auth/login.php");
    exit;
}

require_once "../config/database.php";

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
        $error = "Pilih barang dan masukkan jumlah yang valid.";
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
                throw new Exception("Barang tidak ditemukan.");
            }

            $stok = (int)$barang["stok"];
            $harga = (float)$barang["harga_jual"];

            if ($jumlah > $stok) {
                throw new Exception(
                    "Stok tidak mencukupi. Stok tersedia: " . $stok
                );
            }

            if ($harga < 0 || $harga * $jumlah > 999999999999.99) {
                throw new Exception("Harga atau total penjualan tidak valid.");
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
                    "Stok berubah atau tidak mencukupi. Silakan coba lagi."
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
                $error = "Penjualan gagal disimpan. Silakan periksa database.";
            } else {
                $error = $ex->getMessage();
            }
        }
    }
}

// PESAN SUKSES
if (($_GET["status"] ?? "") === "berhasil") {
    $pesan = "Transaksi penjualan berhasil disimpan.";
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
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Transaksi Penjualan</title>
</head>
<body>

<h1>Transaksi Penjualan Toko</h1>

<p>
    Login sebagai:
    <?= e($_SESSION["nama"]) ?>
</p>

<a href="index.php">Kembali ke Dashboard</a>

<?php if ($pesan !== ""): ?>
    <p style="color:green"><?= e($pesan) ?></p>
<?php endif; ?>

<?php if ($error !== ""): ?>
    <p style="color:red"><?= e($error) ?></p>
<?php endif; ?>

<hr>

<h2>Daftar Barang</h2>

<table border="1" cellpadding="8" cellspacing="0">
    <thead>
        <tr>
            <th>Nama Barang</th>
            <th>Satuan</th>
            <th>Harga Jual</th>
            <th>Stok</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($barangList as $b): ?>
            <tr>
                <td><?= e($b["nama_barang"]) ?></td>
                <td><?= e($b["satuan"]) ?></td>
                <td>
                    Rp <?= number_format(
                        (float)$b["harga_jual"], 0, ",", "."
                    ) ?>
                </td>
                <td><?= e($b["stok"]) ?></td>
            </tr>
        <?php endforeach; ?>

        <?php if (!$barangList): ?>
            <tr>
                <td colspan="4">Belum ada barang.</td>
            </tr>
        <?php endif; ?>
    </tbody>
</table>

<hr>

<h2>Buat Transaksi Penjualan</h2>

<form method="POST">
    <label>Barang</label><br>

    <select name="id_barang" id="barang" required>
        <option value="">-- Pilih Barang --</option>

        <?php foreach ($barangList as $b): ?>
            <option
                value="<?= e($b["id_barang"]) ?>"
                data-harga="<?= e($b["harga_jual"]) ?>"
                data-stok="<?= e($b["stok"]) ?>"
                <?= (int)$b["stok"] <= 0 ? "disabled" : "" ?>
            >
                <?= e($b["nama_barang"]) ?>
                - Stok: <?= e($b["stok"]) ?>
            </option>
        <?php endforeach; ?>
    </select>

    <br><br>

    <label>Jumlah</label><br>
    <input
        type="number"
        name="jumlah"
        id="jumlah"
        min="1"
        step="1"
        required
    >

    <br><br>

    <p>Harga satuan: <strong id="harga">Rp 0</strong></p>
    <p>Estimasi total: <strong id="total">Rp 0</strong></p>

    <button type="submit">Simpan Penjualan</button>
</form>

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

    totalTampil.textContent = rupiah(
        harga * (jumlah > 0 ? jumlah : 0)
    );
}

pilihanBarang.addEventListener("change", hitungTotal);
inputJumlah.addEventListener("input", hitungTotal);
</script>

<hr>

<h2>Riwayat Penjualan Saya</h2>

<table border="1" cellpadding="8" cellspacing="0">
    <thead>
        <tr>
            <th>ID</th>
            <th>Tanggal</th>
            <th>Barang</th>
            <th>Jumlah</th>
            <th>Harga Satuan</th>
            <th>Total</th>
            <th>Petugas</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($riwayat as $r): ?>
            <tr>
                <td><?= e($r["id_penjualan"]) ?></td>
                <td><?= e($r["tanggal"]) ?></td>
                <td><?= e($r["nama_barang"]) ?></td>
                <td><?= e($r["jumlah"]) ?></td>
                <td>
                    Rp <?= number_format(
                        (float)$r["harga_satuan"], 0, ",", "."
                    ) ?>
                </td>
                <td>
                    Rp <?= number_format(
                        (float)$r["total"], 0, ",", "."
                    ) ?>
                </td>
                <td><?= e($r["nama"]) ?></td>
            </tr>
        <?php endforeach; ?>

        <?php if (!$riwayat): ?>
            <tr>
                <td colspan="7">Belum ada transaksi penjualan.</td>
            </tr>
        <?php endif; ?>
    </tbody>
</table>

</body>
</html>
