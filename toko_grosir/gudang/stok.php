
<?php
session_start();

if (
    !isset($_SESSION["id_user"]) ||
    !in_array($_SESSION["role"], ["owner", "gudang"], true)
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
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Manajemen Stok</title>
</head>
<body>

<h1>Manajemen Stok Gudang</h1>

<p>
    Login sebagai:
    <?= e($_SESSION["nama"]) ?>
    (<?= e($_SESSION["role"]) ?>)
</p>

<a href="../<?= $_SESSION["role"] === "owner" ? "owner" : "gudang" ?>/index.php">
    Kembali ke Dashboard
</a>

<?php if ($pesan !== ""): ?>
    <p style="color:green"><?= e($pesan) ?></p>
<?php endif; ?>

<?php if ($error !== ""): ?>
    <p style="color:red"><?= e($error) ?></p>
<?php endif; ?>

<hr>

<h2>Stok Barang Saat Ini</h2>

<table border="1" cellpadding="8" cellspacing="0">
    <tr>
        <th>Nama Barang</th>
        <th>Satuan</th>
        <th>Stok</th>
    </tr>

    <?php foreach ($barangList as $b): ?>
        <tr>
            <td><?= e($b["nama_barang"]) ?></td>
            <td><?= e($b["satuan"]) ?></td>
            <td><?= e($b["stok"]) ?></td>
        </tr>
    <?php endforeach; ?>

    <?php if (!$barangList): ?>
        <tr><td colspan="3">Belum ada barang.</td></tr>
    <?php endif; ?>
</table>

<?php if ($_SESSION["role"] === "gudang"): ?>
<hr>

<h2>Catat Barang Masuk / Keluar</h2>

<form method="POST">
    <label>Jenis Transaksi</label><br>
    <select name="aksi" id="aksi" required>
        <option value="masuk">Barang Masuk</option>
        <option value="keluar">Barang Keluar</option>
    </select><br><br>

    <label>Barang</label><br>
    <select name="id_barang" required>
        <option value="">-- Pilih Barang --</option>
        <?php foreach ($barangList as $b): ?>
            <option value="<?= e($b["id_barang"]) ?>">
                <?= e($b["nama_barang"]) ?>
                (stok: <?= e($b["stok"]) ?>)
            </option>
        <?php endforeach; ?>
    </select><br><br>

    <div id="supplierField">
        <label>Supplier</label><br>
        <select name="id_supplier" id="id_supplier">
            <option value="">-- Pilih Supplier --</option>
            <?php foreach ($supplierList as $s): ?>
                <option value="<?= e($s["id_supplier"]) ?>">
                    <?= e($s["nama_supplier"]) ?>
                </option>
            <?php endforeach; ?>
        </select><br><br>
    </div>

    <label>Jumlah</label><br>
    <input type="number" name="jumlah" min="1" required><br><br>

    <label>Keterangan</label><br>
    <input type="text" name="keterangan" maxlength="255"><br><br>

    <button type="submit">Simpan Transaksi</button>
</form>

<script>
const aksi = document.getElementById("aksi");
const supplierField = document.getElementById("supplierField");
const supplierSelect = document.getElementById("id_supplier");

function aturSupplier() {
    const barangMasuk = aksi.value === "masuk";
    supplierField.style.display = barangMasuk ? "block" : "none";
    supplierSelect.required = barangMasuk;
}

aksi.addEventListener("change", aturSupplier);
aturSupplier();
</script>
<?php endif; ?>

<hr>

<h2>Riwayat Barang Masuk</h2>

<table border="1" cellpadding="8" cellspacing="0">
    <tr>
        <th>Tanggal</th>
        <th>Barang</th>
        <th>Supplier</th>
        <th>Jumlah</th>
        <th>Keterangan</th>
        <th>Petugas</th>
    </tr>

    <?php foreach ($riwayatMasuk as $r): ?>
        <tr>
            <td><?= e($r["tanggal"]) ?></td>
            <td><?= e($r["nama_barang"]) ?></td>
            <td><?= e($r["nama_supplier"]) ?></td>
            <td><?= e($r["jumlah"]) ?></td>
            <td><?= e($r["keterangan"]) ?></td>
            <td><?= e($r["nama"]) ?></td>
        </tr>
    <?php endforeach; ?>

    <?php if (!$riwayatMasuk): ?>
        <tr><td colspan="6">Belum ada transaksi barang masuk.</td></tr>
    <?php endif; ?>
</table>

<hr>

<h2>Riwayat Barang Keluar</h2>

<table border="1" cellpadding="8" cellspacing="0">
    <tr>
        <th>Tanggal</th>
        <th>Barang</th>
        <th>Jumlah</th>
        <th>Keterangan</th>
        <th>Petugas</th>
    </tr>

    <?php foreach ($riwayatKeluar as $r): ?>
        <tr>
            <td><?= e($r["tanggal"]) ?></td>
            <td><?= e($r["nama_barang"]) ?></td>
            <td><?= e($r["jumlah"]) ?></td>
            <td><?= e($r["keterangan"]) ?></td>
            <td><?= e($r["nama"]) ?></td>
        </tr>
    <?php endforeach; ?>

    <?php if (!$riwayatKeluar): ?>
        <tr><td colspan="5">Belum ada transaksi barang keluar.</td></tr>
    <?php endif; ?>
</table>

</body>
</html>
