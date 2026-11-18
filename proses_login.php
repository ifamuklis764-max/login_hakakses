
<?php

require_once "auth.php";
require_once "config/database.php";

// Memeriksa metode pengiriman form
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['login'])) {
    header("Location: index.php");
    exit;
}

// Memeriksa CSRF Token
if (!valid_csrf_token($_POST['csrf_token'] ?? null)) {
    header("Location: index.php?error=expired");
    exit;
}

// Mengambil data login
$username = $_POST['username'] ?? '';
$password = $_POST['password'] ?? '';

if (!is_string($username) || !is_string($password)) {
    header("Location: index.php?error=1");
    exit;
}

$username = trim($username);

// Memeriksa username dan password
if ($username === '' || $password === '') {
    header("Location: index.php?error=1");
    exit;
}

// Mencari pengguna di database
$stmt = mysqli_prepare(
    $conn,
    "SELECT id, nama, username, password, role
     FROM users
     WHERE username = ?
     LIMIT 1"
);

if (!$stmt) {
    die("Query login gagal: " . mysqli_error($conn));
}

mysqli_stmt_bind_param($stmt, "s", $username);
mysqli_stmt_execute($stmt);

mysqli_stmt_bind_result(
    $stmt,
    $userId,
    $userName,
    $userUsername,
    $passwordHash,
    $userRole
);

$userFound = mysqli_stmt_fetch($stmt);

mysqli_stmt_close($stmt);

// Memeriksa role pengguna
$validRole = in_array(
    $userRole ?? '',
    ['admin', 'petugas', 'siswa'],
    true
);

// Memeriksa password
if ($userFound && $validRole && password_verify($password, $passwordHash)) {

    // Membuat session baru
    session_regenerate_id(true);

    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

    // Menyimpan data pengguna
    $_SESSION['user_id'] = $userId;
    $_SESSION['nama'] = $userName;
    $_SESSION['username'] = $userUsername;
    $_SESSION['role'] = $userRole;

    // Mengarahkan pengguna sesuai role
    if ($userRole === 'admin') {
        header("Location: admin/dashboard.php");

    } elseif ($userRole === 'petugas') {
        header("Location: petugas/dashboard.php");

    } elseif ($userRole === 'siswa') {
        header("Location: siswa/dashboard.php");
    }

    exit;
}

// Jika login gagal
header("Location: index.php?error=1");
exit;

?>
