<?php
session_start();
require_once "../config/bahasa.php";

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
function e($value)
{
  return htmlspecialchars((string)$value, ENT_QUOTES, "UTF-8");
}

// TAMBAH BARANG
if (
  $_SERVER["REQUEST_METHOD"] === "POST" &&
  ($_POST["aksi"] ?? "") === "tambah"
) {

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
if (
  $_SERVER["REQUEST_METHOD"] === "POST" &&
  ($_POST["aksi"] ?? "") === "edit"
) {

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
if (
  $_SERVER["REQUEST_METHOD"] === "POST" &&
  ($_POST["aksi"] ?? "") === "hapus"
) {

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
    "tambah" => t("tambah_barang_berhasil"),
    "edit" => t("edit_barang_berhasil"),
    "hapus" => t("hapus_barang_berhasil"),
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
</head>

<body>
  <div class="app-layout">
    <?php include "../config/navbar.php"; ?>

    <main class="main">
      <header class="page-header">
        <div>
          <h1><?= e(t("manajemen_barang")) ?></h1>
          <p class="page-subtitle"><?= e(t("deskripsi_barang")) ?></p>
        </div>                
        <div class="user-badge"><?= e($_SESSION["nama"] ?? "Pengguna") ?></div>
      </header>

      <?php if ($pesan !== ""): ?><div class="notice"><?= e($pesan) ?></div><?php endif; ?>
      <?php if ($error !== ""): ?><div class="notice error"><?= e($error) ?></div><?php endif; ?>

      <section class="card">
        <h2><?= $editBarang ? e(t("edit_barang")) : e(t("tambah_barang")) ?></h2>

        <form method="POST">
          <input type="hidden" name="aksi" value="<?= $editBarang ? "edit" : "tambah" ?>">
          <?php if ($editBarang): ?>
            <input type="hidden" name="id_barang" value="<?= e($editBarang["id_barang"]) ?>">
          <?php endif; ?>
          <div class="form-grid">
            <div class="form-group">
              <label for="nama_barang"><b><?= e(t("nama_barang")) ?></b> *</label>
              <input id="nama_barang" type="text" name="nama_barang" maxlength="100" required value="<?= e($editBarang["nama_barang"] ?? "") ?>">
            </div>

            <div class="form-group">
              <label for="kategori"><b><?= e(t("kategori")) ?></b></label>
              <input id="kategori" type="text" name="kategori" maxlength="50" value="<?= e($editBarang["kategori"] ?? "") ?>">
            </div>

            <div class="form-group">
              <label for="satuan"><b><?= e(t("satuan")) ?></b>*</label>
              <input id="satuan" type="text" name="satuan" maxlength="20" required value="<?= e($editBarang["satuan"] ?? "pcs") ?>">
            </div>

            <div class="form-group">
              <label for="stok_minimum"><b><?= e(t("stok_minimum")) ?></b> *</label>
              <input id="stok_minimum" type="number" name="stok_minimum" min="0" required value="<?= e($editBarang["stok_minimum"] ?? "5") ?>">
            </div>

            <div class="form-group">
              <label for="harga_beli"><b><?= e(t("harga_beli")) ?></b>*</label>
              <input id="harga_beli" type="number" name="harga_beli" min="0" step="0.01" required value="<?= e($editBarang["harga_beli"] ?? "0") ?>">
            </div>

            <div class="form-group">
              <label for="harga_jual"><b><?= e(t("harga_jual")) ?></b>*</label>
              <input id="harga_jual" type="number" name="harga_jual" min="0" step="0.01" required value="<?= e($editBarang["harga_jual"] ?? "0") ?>">
            </div>

            <?php if (!$editBarang): ?>
              <div class="form-group">
                <label for="stok"><b><?= e(t("stok_awal")) ?></b>*</label>
                <input id="stok" type="number" name="stok" min="0" required value="0">
              </div>
            <?php endif; ?>

          </div>
          <br>
          <div class="actions">
            <button class="btn" type="submit">
                <?= $editBarang ? e(t("simpan")) : e(t("tambah_barang")) ?>
            </button>

            <?php if ($editBarang): ?>
                <a class="btn btn-secondary" href="barang.php">
                    <?= e(t("batal")) ?>
                </a>
            <?php endif; ?>
          </div>
        </form>
      </section>

      <br>

      <section class="card">
        <h2>
            <?= e(t("daftar_barang")) ?>
            <span class="count">(<?= count($barangList) ?>)</span>
        </h2>

        <div class="table-wrap">
          <table>
            <thead>
              <tr>
                <th>ID</th>
                <th><?= e(t("nama_barang")) ?></th>
                <th><?= e(t("kategori")) ?></th>
                <th><?= e(t("satuan")) ?></th>
                <th><?= e(t("harga_beli")) ?></th>
                <th><?= e(t("harga_jual")) ?></th>
                <th><?= e(t("stok_saat_ini")) ?></th>
                <th><?= e(t("aksi")) ?></th>
              </tr>
            </thead>
            <tbody>
              <?php if (!$barangList): ?>
                <tr>
                  <td colspan="8" style="text-align:center;color:#aaa;padding:28px">
                      <?= e(t("belum_ada_barang")) ?>
                  </td>
                </tr>
                <?php else: foreach ($barangList as $barang): ?>
                  <tr>
                    <td><?= e($barang["id_barang"]) ?></td>
                    <td><?= e($barang["nama_barang"]) ?></td>
                    <td><?= e($barang["kategori"]) ?></td>
                    <td><?= e($barang["satuan"]) ?></td>
                    <td>Rp <?= number_format((float)$barang["harga_beli"], 0, ",", ".") ?></td>
                    <td>Rp <?= number_format((float)$barang["harga_jual"], 0, ",", ".") ?></td>
                    <td><?= e($barang["stok"]) ?></td>
                    <td>
                      <div class="row-actions">
                        <a class="btn btn-secondary" href="barang.php?edit=<?= e($barang["id_barang"]) ?>">
                            <?= e(t("edit")) ?>
                        </a>
                        <form method="POST"
                          onsubmit="return confirm(<?= htmlspecialchars(
                              json_encode(t("konfirmasi_hapus_barang")),
                              ENT_QUOTES,
                              "UTF-8"
                          ) ?>)">
                          <input type="hidden" name="aksi" value="hapus">
                          <input type="hidden" name="id_barang" value="<?= e($barang["id_barang"]) ?>">
                          <br>
                          <button class="btn btn-danger" type="submit">
                              <?= e(t("hapus")) ?>
                          </button>
                        </form>
                      </div>
                    </td>
                  </tr>
              <?php endforeach;
              endif; ?>
            </tbody>
          </table>
        </div>
      </section>
    </main>
  </div>
</body>

</html>