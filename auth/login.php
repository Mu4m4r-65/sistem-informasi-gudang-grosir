
<?php
session_start();

require_once "../config/database.php";

$error = "";

// Pengaturan bahasa untuk halaman login
if (isset($_GET['bahasa'])) {
    $bahasa = $_GET['bahasa'];

    if (in_array($bahasa, ['id', 'en'], true)) {
        $_SESSION['bahasa'] = $bahasa;
    }
}

require_once __DIR__ . '/../config/bahasa.php';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = trim($_POST["username"] ?? "");
    $password = $_POST["password"] ?? "";

    if ($username === "" || $password === "") {
        $error = "Username dan password wajib diisi.";
    } else {
        $sql = "SELECT id_user, nama, username, password, role, bahasa
                FROM users
                WHERE username = :username
                LIMIT 1";

        $stmt = $conn->prepare($sql);
        $stmt->execute([
            ":username" => $username
        ]);

        $user = $stmt->fetch();

        if ($user && password_verify($password, $user["password"])) {
            session_regenerate_id(true);

            $_SESSION["id_user"] = $user["id_user"];
            $_SESSION["nama"] = $user["nama"];
            $_SESSION["username"] = $user["username"];
            $_SESSION["role"] = $user["role"];
            $_SESSION["bahasa"] = $user["bahasa"];

            switch ($user["role"]) {
                case "owner":
                    header("Location: ../owner/index.php");
                    break;

                case "gudang":
                    header("Location: ../gudang/index.php");
                    break;

                case "toko":
                    header("Location: ../toko/index.php");
                    break;

                default:
                    session_unset();
                    session_destroy();
                    header("Location: login.php");
                    break;
            }

            exit;
        } else {
            $error = "Username atau password salah.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | Sistem Grosir</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/login.css">
    <link rel="stylesheet" href="../assets/css/ganti_bahasa.css">
</head>

<body>

<div class="login-layout">

    <section class="welcome">
        <div class="brand">
            <?= htmlspecialchars(t('GROSIR'), ENT_QUOTES, 'UTF-8') ?><span>.</span><?= htmlspecialchars(t('MANAGEMENT'), ENT_QUOTES, 'UTF-8') ?> 
        </div>

        <div class="accent-line"></div>

        <h1>
            <?= htmlspecialchars(t('hero_title'), ENT_QUOTES, 'UTF-8') ?>
        </h1>

        <p>
            <?= htmlspecialchars(t('hero_description'), ENT_QUOTES, 'UTF-8') ?>
        </p>
    </section>
    
    <section class="login-card">

        <div class="language-switcher">
            <form method="GET" action="login.php">
                <select name="bahasa" onchange="this.form.submit()">
                    <option value="id"
                        <?= ($_SESSION['bahasa'] ?? 'id') === 'id' ? 'selected' : '' ?>>
                        Indonesia
                    </option>

                    <option value="en"
                        <?= ($_SESSION['bahasa'] ?? 'id') === 'en' ? 'selected' : '' ?>>
                        English
                    </option>
                </select>
            </form>
        </div>

        <h2><?= htmlspecialchars(t('login_title'), ENT_QUOTES, 'UTF-8') ?></h2>

        <p>
            <?= htmlspecialchars(t('login_subtitle'), ENT_QUOTES, 'UTF-8') ?>
        </p>

        <?php if ($error !== ""): ?>
            <div class="error-message">
                <?= htmlspecialchars(t($error), ENT_QUOTES, "UTF-8") ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label for="username"><?= htmlspecialchars(t('Username')) ?></label>
                <input
                    type="text"
                    id="username"
                    name="username" 
                    placeholder="<?= htmlspecialchars(t('username_placeholder'), ENT_QUOTES, 'UTF-8') ?>"
                    maxlength="50"
                    autocomplete="username"
                    required
                >
            </div>

            <div class="form-group">
                <label for="password"><?= htmlspecialchars(t('Password')) ?></label>
                <input
                    type="password"
                    id="password"
                    name="password"
                    placeholder="<?= htmlspecialchars(t('password_placeholder'), ENT_QUOTES, 'UTF-8') ?>"
                    autocomplete="current-password"
                    required
                >
            </div>

            <button type="submit" class="login-button">
                <?= htmlspecialchars(t('login_button'), ENT_QUOTES, 'UTF-8') ?>
            </button>
        </form>

        <div class="security-note">
            <?= htmlspecialchars(t('login_footer'), ENT_QUOTES, 'UTF-8') ?>
        </div>
    </section>

</div>

</body>
</html>
