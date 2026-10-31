
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

// Fungsi sanitasi output HTML
function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, "UTF-8");
}

// TAMBAH BARANG
if ($_SERVER["REQUEST_METHOD"] === "POST" &&
    ($_POST["aksi"] ?? "") === "tambah") {

    $nama = trim($_POST["nama_barang"] ?? "");
    $kategori = trim($_POST["kategori"] ?? "");
    $satuan = trim($_POST["satuan"] ?? "pcs");
    $harga_beli = filter_var($_POST["harga_beli"] ?? null, FILTER_VALIDATE_FLOAT);
    $harga_jual = filter_var($_POST["harga_jual"] ?? null, FILTER_VALIDATE_FLOAT);
    $stok = filter_var($_POST["stok"] ?? null, FILTER_VALIDATE_INT);
    $stok_minimum = filter_var($_POST["stok_minimum"] ?? null, FILTER_VALIDATE_INT);

    if (
        $nama === "" ||
        $satuan === "" ||
        $harga_beli === false || $harga_beli === null || $harga_beli < 0 ||
        $harga_jual === false || $harga_jual === null || $harga_jual < 0 ||
        $stok === false || $stok === null || $stok < 0 ||
        $stok_minimum === false || $stok_minimum === null || $stok_minimum < 0
    ) {
        $error = "Periksa kembali input. Harga dan stok harus bernilai valid dan tidak negatif.";
    } else {
        $sql = "INSERT INTO barang
                (nama_barang, kategori, satuan, harga_beli, harga_jual, stok, stok_minimum)
                VALUES
                (:nama, :kategori, :satuan, :harga_beli, :harga_jual, :stok, :stok_minimum)";

        $stmt = $conn->prepare($sql);
        $stmt->execute([
            ":nama" => $nama,
            ":kategori" => $kategori,
            ":satuan" => $satuan,
            ":harga_beli" => $harga_beli,
            ":harga_jual" => $harga_jual,
            ":stok" => $stok,
            ":stok_minimum" => $stok_minimum
        ]);

        header("Location: barang.php?status=tambah");
        exit;
    }
}

// EDIT BARANG
if ($_SERVER["REQUEST_METHOD"] === "POST" &&
    ($_POST["aksi"] ?? "") === "edit") {

    $id = filter_var($_POST["id_barang"] ?? null, FILTER_VALIDATE_INT);
    $nama = trim($_POST["nama_barang"] ?? "");
    $kategori = trim($_POST["kategori"] ?? "");
    $satuan = trim($_POST["satuan"] ?? "pcs");
    $harga_beli = filter_var($_POST["harga_beli"] ?? null, FILTER_VALIDATE_FLOAT);
    $harga_jual = filter_var($_POST["harga_jual"] ?? null, FILTER_VALIDATE_FLOAT);
    $stok_minimum = filter_var($_POST["stok_minimum"] ?? null, FILTER_VALIDATE_INT);

    if (
        !$id || $nama === "" || $satuan === "" ||
        $harga_beli === false || $harga_beli === null || $harga_beli < 0 ||
        $harga_jual === false || $harga_jual === null || $harga_jual < 0 ||
        $stok_minimum === false || $stok_minimum === null || $stok_minimum < 0
    ) {
        $error = "Data edit tidak valid.";
    } else {
        $sql = "UPDATE barang
                SET nama_barang = :nama,
                    kategori = :kategori,
                    satuan = :satuan,
                    harga_beli = :harga_beli,
                    harga_jual = :harga_jual,
                    stok_minimum = :stok_minimum
                WHERE id_barang = :id";

        $stmt = $conn->prepare($sql);
        $stmt->execute([
            ":nama" => $nama,
            ":kategori" => $kategori,
            ":satuan" => $satuan,
            ":harga_beli" => $harga_beli,
            ":harga_jual" => $harga_jual,
            ":stok_minimum" => $stok_minimum,
            ":id" => $id
        ]);

        header("Location: barang.php?status=edit");
        exit;
    }
}

// HAPUS BARANG
if ($_SERVER["REQUEST_METHOD"] === "POST" &&
    ($_POST["aksi"] ?? "") === "hapus") {

    $id = filter_var($_POST["id_barang"] ?? null, FILTER_VALIDATE_INT);

    if (!$id) {
        $error = "ID barang tidak valid.";
    } else {
        try {
            $stmt = $conn->prepare("DELETE FROM barang WHERE id_barang = :id");
            $stmt->execute([":id" => $id]);

            header("Location: barang.php?status=hapus");
            exit;
        } catch (PDOException $e) {
            $error = "Barang tidak dapat dihapus karena sudah digunakan dalam transaksi.";
        }
    }
}

// Ambil barang untuk form edit
$editBarang = null;

if (isset($_GET["edit"])) {
    $idEdit = filter_var($_GET["edit"], FILTER_VALIDATE_INT);

    if ($idEdit) {
        $stmt = $conn->prepare("SELECT * FROM barang WHERE id_barang = :id");
        $stmt->execute([":id" => $idEdit]);
        $editBarang = $stmt->fetch();
    }
}

// Ambil semua barang
$stmt = $conn->query("SELECT * FROM barang ORDER BY id_barang DESC");
$barangList = $stmt->fetchAll();

if (isset($_GET["status"])) {
    $status = $_GET["status"];
    $pesan = match ($status) {
        "tambah" => "Barang berhasil ditambahkan.",
        "edit" => "Data barang berhasil diperbarui.",
        "hapus" => "Barang berhasil dihapus.",
        default => ""
    };
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Manajemen Barang | Grosir App</title>

    <link rel="stylesheet" href="../assets/css/style.css">

    <style>
        :root {
            --primary: #6E2B15;
            --background: #000;
            --surface: #111;
            --border: #333;
            --text: #FFF;
            --muted: #AAA;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 35px;
            background: var(--background);
            color: var(--text);
            font-family: "Segoe UI", Arial, sans-serif;
        }

        h1 {
            font-size: 30px;
            margin: 0 0 12px;
        }

        h2 {
            font-size: 21px;
            margin-top: 28px;
            margin-bottom: 20px;
        }

        p {
            color: var(--muted);
        }

        a {
            color: #FFF;
            text-decoration: none;
        }

        a:hover {
            color: #C77A5D;
        }

        body > a,
        body > p a {
            display: inline-block;
            margin: 8px 12px 8px 0;
        }

        hr {
            border: 0;
            border-top: 1px solid var(--border);
            margin: 28px 0;
        }

        form {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 14px;
            padding: 24px;
            margin-bottom: 24px;
        }

        label {
            display: inline-block;
            color: #DDD;
            font-size: 13px;
            font-weight: 600;
            margin-bottom: 7px;
        }

        input[type="text"],
        input[type="number"] {
            width: 100%;
            max-width: 480px;
            padding: 12px 14px;
            margin: 0 0 18px;
            background: #080808;
            color: #FFF;
            border: 1px solid #444;
            border-radius: 8px;
            font-size: 14px;
        }

        input:focus {
            outline: 1px solid var(--primary);
            border-color: var(--primary);
        }

        button {
            padding: 11px 18px;
            background: var(--primary);
            color: #FFF;
            border: 1px solid var(--primary);
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
        }

        button:hover {
            background: #87371D;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 10px;
            overflow: hidden;
        }

        th,
        td {
            padding: 14px;
            text-align: left;
            border-bottom: 1px solid var(--border);
        }

        th {
            background: #202020;
            color: #FFF;
            font-size: 13px;
        }

        td {
            color: #DDD;
            font-size: 14px;
        }

        tr:hover td {
            background: #191919;
        }

        td a {
            display: inline-block;
            padding: 7px 11px;
            margin-right: 5px;
            border: 1px solid var(--border);
            border-radius: 6px;
        }

        td form {
            display: inline;
            padding: 0;
            margin: 0;
            background: transparent;
            border: 0;
        }

        td form button {
            padding: 7px 11px;
        }

        @media (max-width: 768px) {
            body {
                padding: 18px 12px;
            }

            h1 {
                font-size: 24px;
            }

            table {
                min-width: 750px;
            }

            body {
                overflow-x: auto;
            }
        }
    </style>
</head>

<body>

<h1>Manajemen Barang</h1>
<p>Login sebagai: <?= e($_SESSION["nama"]) ?></p>
<p><a href="../gudang/barang.php">Manajemen Barang</a></p>

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

<h2><?= $editBarang ? "Edit Barang" : "Tambah Barang" ?></h2>

<form method="POST">
    <input type="hidden" name="aksi" value="<?= $editBarang ? "edit" : "tambah" ?>">

    <?php if ($editBarang): ?>
        <input type="hidden" name="id_barang" value="<?= e($editBarang["id_barang"]) ?>">
    <?php endif; ?>

    <label>Nama Barang</label><br>
    <input type="text" name="nama_barang" maxlength="100" required
           value="<?= e($editBarang["nama_barang"] ?? "") ?>"><br><br>

    <label>Kategori</label><br>
    <input type="text" name="kategori" maxlength="50"
           value="<?= e($editBarang["kategori"] ?? "") ?>"><br><br>

    <label>Satuan</label><br>
    <input type="text" name="satuan" maxlength="20" required
           value="<?= e($editBarang["satuan"] ?? "pcs") ?>"><br><br>

    <label>Harga Beli</label><br>
    <input type="number" name="harga_beli" min="0" step="0.01" required
           value="<?= e($editBarang["harga_beli"] ?? "0") ?>"><br><br>

    <label>Harga Jual</label><br>
    <input type="number" name="harga_jual" min="0" step="0.01" required
           value="<?= e($editBarang["harga_jual"] ?? "0") ?>"><br><br>

    <?php if (!$editBarang): ?>
        <label>Stok Awal</label><br>
        <input type="number" name="stok" min="0" required value="0"><br><br>
    <?php endif; ?>

    <label>Stok Minimum</label><br>
    <input type="number" name="stok_minimum" min="0" required
           value="<?= e($editBarang["stok_minimum"] ?? "5") ?>"><br><br>

    <button type="submit">
        <?= $editBarang ? "Simpan Perubahan" : "Tambah Barang" ?>
    </button>

    <?php if ($editBarang): ?>
        <a href="barang.php">Batal Edit</a>
    <?php endif; ?>
</form>

<hr>

<h2>Daftar Barang</h2>

<table border="1" cellpadding="8" cellspacing="0">
    <thead>
        <tr>
            <th>ID</th>
            <th>Nama</th>
            <th>Kategori</th>
            <th>Satuan</th>
            <th>Harga Beli</th>
            <th>Harga Jual</th>
            <th>Stok</th>
            <th>Aksi</th>
        </tr>
    </thead>
    <tbody>
    <?php if (!$barangList): ?>
        <tr>
            <td colspan="8">Belum ada data barang.</td>
        </tr>
    <?php else: ?>
        <?php foreach ($barangList as $barang): ?>
        <tr>
            <td><?= e($barang["id_barang"]) ?></td>
            <td><?= e($barang["nama_barang"]) ?></td>
            <td><?= e($barang["kategori"]) ?></td>
            <td><?= e($barang["satuan"]) ?></td>
            <td><?= number_format((float)$barang["harga_beli"], 0, ",", ".") ?></td>
            <td><?= number_format((float)$barang["harga_jual"], 0, ",", ".") ?></td>
            <td><?= e($barang["stok"]) ?></td>
            <td>
                <a href="barang.php?edit=<?= e($barang["id_barang"]) ?>">Edit</a>

                <form method="POST" style="display:inline"
                      onsubmit="return confirm('Yakin ingin menghapus barang ini?')">
                    <input type="hidden" name="aksi" value="hapus">
                    <input type="hidden" name="id_barang" value="<?= e($barang["id_barang"]) ?>">
                    <button type="submit">Hapus</button>
                </form>
            </td>
        </tr>
        <?php endforeach; ?>
    <?php endif; ?>
    </tbody>
</table>

</body>
</html>
