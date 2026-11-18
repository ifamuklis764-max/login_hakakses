
<?php

require_once "config/database.php";

$users = [
    [
        "Administrator",
        "admin",
        "admin123",
        "admin"
    ],
    [
        "Petugas Sekolah",
        "petugas",
        "petugas123",
        "petugas"
    ],
    [
        "Siswa SMK",
        "siswa",
        "siswa123",
        "siswa"
    ]
];

$sql = "INSERT IGNORE INTO users
        (nama, username, password, role)
        VALUES (?, ?, ?, ?)";

$stmt = mysqli_prepare($conn, $sql);

foreach ($users as $user) {

    $nama = $user[0];
    $username = $user[1];

    $password = password_hash(
        $user[2],
        PASSWORD_DEFAULT
    );

    $role = $user[3];

    mysqli_stmt_bind_param(
        $stmt,
        "ssss",
        $nama,
        $username,
        $password,
        $role
    );

    mysqli_stmt_execute($stmt);
}

mysqli_stmt_close($stmt);

echo "Data pengguna berhasil dibuat!";

?>
