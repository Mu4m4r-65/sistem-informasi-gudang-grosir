
<?php
session_start();

if (
    !isset($_SESSION["id_user"]) ||
    $_SESSION["role"] !== "owner"
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

// TAMBAH SUPPLIER
if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    ($_POST["aksi"] ?? "") === "tambah"
) {
    $nama = trim($_POST["nama_supplier"] ?? "");
    $telepon = trim($_POST["telepon"] ?? "");
    $alamat = trim($_POST["alamat"] ?? "");

    if (
        $nama === "" ||
        mb_strlen($nama) > 100 ||
        mb_strlen($telepon) > 20
    ) {
        $error = "Nama supplier wajib diisi. Periksa panjang nama dan telepon.";
    } else {
        $stmt = $conn->prepare(
            "INSERT INTO supplier (nama_supplier, telepon, alamat)
             VALUES (:nama, :telepon, :alamat)"
        );

        $stmt->execute([
            ":nama" => $nama,
            ":telepon" => $telepon,
            ":alamat" => $alamat
        ]);

        header("Location: supplier.php?status=tambah");
        exit;
    }
}

// EDIT SUPPLIER
if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    ($_POST["aksi"] ?? "") === "edit"
) {
    $id = filter_var(
        $_POST["id_supplier"] ?? null,
        FILTER_VALIDATE_INT
    );

    $nama = trim($_POST["nama_supplier"] ?? "");
    $telepon = trim($_POST["telepon"] ?? "");
    $alamat = trim($_POST["alamat"] ?? "");

    if (
        !$id ||
        $nama === "" ||
        mb_strlen($nama) > 100 ||
        mb_strlen($telepon) > 20
    ) {
        $error = "Data supplier tidak valid.";
    } else {
        $stmt = $conn->prepare(
            "UPDATE supplier
             SET nama_supplier = :nama,
                 telepon = :telepon,
                 alamat = :alamat
             WHERE id_supplier = :id"
        );

        $stmt->execute([
            ":nama" => $nama,
            ":telepon" => $telepon,
            ":alamat" => $alamat,
            ":id" => $id
        ]);

        header("Location: supplier.php?status=edit");
        exit;
    }
}

// HAPUS SUPPLIER
if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    ($_POST["aksi"] ?? "") === "hapus"
) {
    $id = filter_var(
        $_POST["id_supplier"] ?? null,
        FILTER_VALIDATE_INT
    );

    if (!$id) {
        $error = "ID supplier tidak valid.";
    } else {
        try {
            $stmt = $conn->prepare(
                "DELETE FROM supplier WHERE id_supplier = :id"
            );

            $stmt->execute([":id" => $id]);

            header("Location: supplier.php?status=hapus");
            exit;
        } catch (PDOException $ex) {
            error_log($ex->getMessage());

            $error = "Supplier tidak dapat dihapus karena sudah digunakan dalam transaksi barang masuk.";
        }
    }
}

// DATA UNTUK EDIT
$editSupplier = null;

if (isset($_GET["edit"])) {
    $idEdit = filter_var(
        $_GET["edit"],
        FILTER_VALIDATE_INT
    );

    if ($idEdit) {
        $stmt = $conn->prepare(
            "SELECT * FROM supplier WHERE id_supplier = :id"
        );

        $stmt->execute([":id" => $idEdit]);
        $editSupplier = $stmt->fetch();
    }
}

// DAFTAR SUPPLIER
$stmt = $conn->query(
    "SELECT * FROM supplier ORDER BY id_supplier DESC"
);

$supplierList = $stmt->fetchAll();

// PESAN SUKSES
$status = $_GET["status"] ?? "";

switch ($status) {
    case "tambah":
        $pesan = "Supplier berhasil ditambahkan.";
        break;

    case "edit":
        $pesan = "Data supplier berhasil diperbarui.";
        break;

    case "hapus":
        $pesan = "Supplier berhasil dihapus.";
        break;

    default:
        $pesan = "";
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Manajemen Supplier</title>

    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 25px;
            background: #f4f6f9;
            color: #222;
        }

        h1, h2 {
            color: #17365d;
        }

        .panel {
            background: white;
            padding: 20px;
            margin: 20px 0;
            border-radius: 8px;
        }

        input, textarea {
            width: 100%;
            max-width: 450px;
            padding: 9px;
            margin: 6px 0 15px;
            box-sizing: border-box;
        }

        button, .tombol {
            padding: 9px 13px;
            cursor: pointer;
        }

        .tombol {
            display: inline-block;
            background: #17365d;
            color: white;
            text-decoration: none;
            border-radius: 4px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
        }

        th, td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: left;
        }

        th {
            background: #17365d;
            color: white;
        }

        .sukses {
            color: green;
        }

        .error {
            color: #b42318;
        }

        @media (max-width: 600px) {
            body {
                margin: 12px;
            }
        }
    </style>
</head>

<body>

<h1>Manajemen Supplier</h1>

<p>Login sebagai: <?= e($_SESSION["nama"]) ?></p>

<a class="tombol" href="index.php">Kembali ke Dashboard</a>

<?php if ($pesan !== ""): ?>
    <p class="sukses"><?= e($pesan) ?></p>
<?php endif; ?>

<?php if ($error !== ""): ?>
    <p class="error"><?= e($error) ?></p>
<?php endif; ?>

<div class="panel">

    <h2>
        <?= $editSupplier ? "Edit Supplier" : "Tambah Supplier" ?>
    </h2>

    <form method="POST">
        <input
            type="hidden"
            name="aksi"
            value="<?= $editSupplier ? "edit" : "tambah" ?>"
        >

        <?php if ($editSupplier): ?>
            <input
                type="hidden"
                name="id_supplier"
                value="<?= e($editSupplier["id_supplier"]) ?>"
            >
        <?php endif; ?>

        <label>Nama Supplier</label><br>

        <input
            type="text"
            name="nama_supplier"
            maxlength="100"
            required
            value="<?= e($editSupplier["nama_supplier"] ?? "") ?>"
        >

        <br>

        <label>Nomor Telepon</label><br>

        <input
            type="text"
            name="telepon"
            maxlength="20"
            value="<?= e($editSupplier["telepon"] ?? "") ?>"
        >

        <br>

        <label>Alamat</label><br>

        <textarea
            name="alamat"
            rows="3"
        ><?= e($editSupplier["alamat"] ?? "") ?></textarea>

        <br>

        <button type="submit">
            <?= $editSupplier ? "Simpan Perubahan" : "Tambah Supplier" ?>
        </button>

        <?php if ($editSupplier): ?>
            <a href="supplier.php">Batal Edit</a>
        <?php endif; ?>
    </form>

</div>

<div class="panel">

    <h2>Daftar Supplier</h2>

    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Nama Supplier</th>
                <th>Telepon</th>
                <th>Alamat</th>
                <th>Aksi</th>
            </tr>
        </thead>

        <tbody>
        <?php if (!$supplierList): ?>
            <tr>
                <td colspan="5">Belum ada data supplier.</td>
            </tr>
        <?php else: ?>
            <?php foreach ($supplierList as $s): ?>
                <tr>
                    <td><?= e($s["id_supplier"]) ?></td>
                    <td><?= e($s["nama_supplier"]) ?></td>
                    <td><?= e($s["telepon"]) ?></td>
                    <td><?= e($s["alamat"]) ?></td>
                    <td>
                        <a href="supplier.php?edit=<?= e($s["id_supplier"]) ?>">
                            Edit
                        </a>

                        <form
                            method="POST"
                            style="display:inline"
                            onsubmit="return confirm('Hapus supplier ini?')"
                        >
                            <input type="hidden" name="aksi" value="hapus">

                            <input
                                type="hidden"
                                name="id_supplier"
                                value="<?= e($s["id_supplier"]) ?>"
                            >

                            <button type="submit">Hapus</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
    </table>

</div>

</body>
</html>
