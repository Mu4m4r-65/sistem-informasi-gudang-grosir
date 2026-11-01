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
require_once "../config/bahasa.php";

$error = "";
$pesan = "";

function e($value)
{
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
    <title>Manajemen Supplier | Grosir App</title>
    <link rel="stylesheet" href="../assets/css/style.css">

</head>

<body>
    <div class="app-layout">
        <?php include "../config/navbar.php"; ?>

        <main class="main-content">
            <header class="page-header">
                <div>
                    <h1><?= e(t("manajemen_supplier")) ?></h1>
                    <p class="page-subtitle"><?= e(t("deskripsi_supplier")) ?></p>
                </div>
                <div class="user-badge"><?= e($_SESSION["nama"] ?? "Owner") ?></div>
            </header>

            <?php if ($pesan !== ""): ?>
                <div class="notice notice-success" role="status"><?= e($pesan) ?></div>
            <?php endif; ?>

            <?php if ($error !== ""): ?>
                <div class="notice notice-error" role="alert"><?= e($error) ?></div>
            <?php endif; ?>

            <section class="panel">
                <h2><?= e($editSupplier
                        ? t("edit_supplier")
                        : t("tambah_supplier")) ?></h2>
                <form method="POST">
                    <input type="hidden" name="aksi" value="<?= $editSupplier ? "edit" : "tambah" ?>">
                    <?php if ($editSupplier): ?>
                        <input type="hidden" name="id_supplier" value="<?= e($editSupplier["id_supplier"]) ?>">
                    <?php endif; ?>

                    <div class="form-grid">
                        <div class="form-group">
                            <label for="nama_supplier">
                                <b><?= e(t("nama_supplier")) ?></b> *
                            </label>

                            <input id="nama_supplier" type="text" name="nama_supplier" maxlength="100" required
                                value="<?= e($editSupplier["nama_supplier"] ?? "") ?>">
                        </div>
                        <div class="form-group">
                            <label for="telepon">
                                <b><?= e(t("nomor_telepon")) ?></b>
                            </label>
                            <input id="telepon" type="text" name="telepon" maxlength="20"
                                value="<?= e($editSupplier["telepon"] ?? "") ?>">
                        </div>
                        <div class="form-group full-width">
                            <label for="alamat">
                                <b><?= e(t("alamat")) ?></b>
                            </label>
                            <textarea id="alamat" name="alamat" rows="3"><?= e($editSupplier["alamat"] ?? "") ?></textarea>
                        </div>
                    </div>

                    <div style="display:flex;gap:10px;flex-wrap:wrap;margin-top:18px">
                        <button class="btn" type="submit">
                            <?= $editSupplier ? e(t("simpan")) : e(t("tambah_supplier")) ?>
                        </button>
                        <?php if ($editSupplier): ?>
                            <a class="btn btn-secondary" href="supplier.php">
                                <?= e(t("batal")) ?>
                            </a>
                        <?php endif; ?>
                    </div>
                </form>
            </section>

            <section class="panel">
                <h2><?= e(t("daftar_supplier")) ?> <span style="color:#999;font-size:14px;font-weight:400">(<?= count($supplierList) ?>)</span></h2>
                <div class="table-wrapper">
                    <table>
                        <thead>
                            <tr>
                                <th><?= e(t("id")) ?></th>
                                <th><?= e(t("nama_supplier")) ?></th>
                                <th><?= e(t("telepon")) ?></th>
                                <th><?= e(t("alamat")) ?></th>
                                <th><?= e(t("aksi")) ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!$supplierList): ?>
                                <tr>
                                    <td colspan="5" class="empty-state">
                                        <?= e(t("supplier_kosong")) ?>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($supplierList as $s): ?>
                                    <tr>
                                        <td><?= e($s["id_supplier"]) ?></td>
                                        <td><?= e($s["nama_supplier"]) ?></td>
                                        <td><?= e($s["telepon"]) ?></td>
                                        <td><?= e($s["alamat"]) ?></td>
                                        <td>
                                            <div class="actions">
                                                <a class="btn btn-secondary" href="supplier.php?edit=<?= e($s["id_supplier"]) ?>">
                                                    <?= e(t("edit")) ?>
                                                </a>
                                                <form method="POST"
                                                    onsubmit="return confirm(<?= htmlspecialchars(
                                                                                    json_encode(t("konfirmasi_hapus_supplier")),
                                                                                    ENT_QUOTES,
                                                                                    "UTF-8"
                                                                                ) ?>)"
                                                    style="margin:0">
                                                    <input type="hidden" name="aksi" value="hapus">
                                                    <input type="hidden" name="id_supplier" value="<?= e($s["id_supplier"]) ?>">
                                                    <button class="btn btn-danger" type="submit">
                                                        <?= e(t("hapus")) ?>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
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