
<?php
session_start();

require_once "../config/database.php";

$error = "";

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

    <style>
        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
            background:
                radial-gradient(
                    circle at 15% 50%,
                    rgba(110, 43, 21, 0.25),
                    transparent 40%
                ),
                #000000;
        }

        .login-layout {
            display: grid;
            grid-template-columns: 1fr 420px;
            width: 100%;
            max-width: 1000px;
            gap: 70px;
            align-items: center;
        }

        .welcome h1 {
            font-size: clamp(32px, 5vw, 52px);
            line-height: 1.15;
            margin-bottom: 20px;
        }

        .brand {
            color: #FFFFFF;
            font-size: 14px;
            letter-spacing: 4px;
            font-weight: bold;
            margin-bottom: 60px;
        }

        .brand span {
            color: #6E2B15;
        }

        .welcome p {
            color: #BBBBBB;
            line-height: 1.8;
        }

        .login-card {
            background: rgba(21, 21, 21, 0.95);
            border: 1px solid #333333;
            border-radius: 20px;
            padding: 38px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
        }

        .login-card h2 {
            margin: 0 0 10px;
            font-size: 28px;
        }

        .subtitle {
            color: #AAAAAA;
            margin-bottom: 30px;
            font-size: 14px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 9px;
            font-size: 13px;
        }

        .form-group input {
            width: 100%;
            padding: 14px;
            background: #080808;
            color: #FFFFFF;
            border: 1px solid #333333;
            border-radius: 9px;
        }

        .form-group input:focus {
            outline: none;
            border-color: #6E2B15;
            box-shadow: 0 0 0 3px rgba(110, 43, 21, 0.2);
        }

        .login-button {
            width: 100%;
            margin-top: 8px;
            padding: 15px;
            background: #6E2B15;
            color: #FFFFFF;
            border: none;
            border-radius: 9px;
            font-size: 15px;
            font-weight: bold;
            cursor: pointer;
        }

        .login-button:hover {
            background: #87371D;
        }

        .error-message {
            padding: 12px;
            margin-bottom: 20px;
            background: #25120D;
            border: 1px solid #6E2B15;
            border-radius: 8px;
            color: #FFFFFF;
            font-size: 13px;
        }

        .security-note {
            text-align: center;
            color: #888888;
            font-size: 12px;
            margin-top: 25px;
        }

        .accent-line {
            width: 55px;
            height: 3px;
            background: #6E2B15;
            margin-bottom: 25px;
        }

        @media (max-width: 750px) {
            .login-layout {
                grid-template-columns: 1fr;
                max-width: 420px;
                gap: 30px;
            }

            .brand {
                margin-bottom: 25px;
            }

            .welcome h1 {
                font-size: 32px;
            }

            .welcome p {
                display: none;
            }

            .login-card {
                padding: 28px;
            }
        }
    </style>
</head>

<body>

<div class="login-layout">

    <section class="welcome">
        <div class="brand">
            GROSIR<span>.</span>MANAGEMENT
        </div>

        <div class="accent-line"></div>

        <h1>Kelola Bisnis.<br>Lebih Terarah.</h1>

        <p>
            Sistem Informasi Manajemen Gudang dan Toko Grosir.
            Pantau persediaan, kelola transaksi, dan
            akses laporan dalam satu sistem.
        </p>
    </section>

    <section class="login-card">
        <h2>Selamat Datang</h2>

        <p class="subtitle">
            Masuk untuk melanjutkan ke sistem.
        </p>

        <?php if ($error !== ""): ?>
            <div class="error-message">
                <?= htmlspecialchars($error, ENT_QUOTES, "UTF-8") ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label for="username">Username</label>
                <input
                    type="text"
                    id="username"
                    name="username"
                    placeholder="Masukkan username"
                    maxlength="50"
                    autocomplete="username"
                    required
                >
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input
                    type="password"
                    id="password"
                    name="password"
                    placeholder="Masukkan password"
                    autocomplete="current-password"
                    required
                >
            </div>

            <button type="submit" class="login-button">
                <?= htmlspecialchars(t('Masuk ke Sistem →')) ?>
            </button>
        </form>

        <div class="security-note">
            <?= htmlspecialchars(t('Akses terbatas untuk pengguna terdaftar.')) ?>
        </div>
    </section>

</div>

</body>
</html>
