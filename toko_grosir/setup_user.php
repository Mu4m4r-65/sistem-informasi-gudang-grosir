<!-- ```php
<?php
require_once "config/database.php";

$users = [
    [
        "nama" => "Owner",
        "username" => "owner",
        "password" => "Owner123!",
        "role" => "owner"
    ],
    [
        "nama" => "Admin Gudang",
        "username" => "gudang",
        "password" => "Gudang123!",
        "role" => "gudang"
    ],
    [
        "nama" => "Admin Toko",
        "username" => "toko",
        "password" => "Toko123!",
        "role" => "toko"
    ]
];

try {
    $sql = "INSERT INTO users (nama, username, password, role)
            VALUES (:nama, :username, :password, :role)";

    $stmt = $conn->prepare($sql);

    foreach ($users as $user) {
        $passwordHash = password_hash(
            $user["password"],
            PASSWORD_DEFAULT
        );

        $stmt->execute([
            ":nama" => $user["nama"],
            ":username" => $user["username"],
            ":password" => $passwordHash,
            ":role" => $user["role"]
        ]);
    }

    echo "Ketiga akun berhasil dibuat.";

} catch (PDOException $e) {
    echo "Gagal membuat akun. Pastikan username belum digunakan.";
} -->
