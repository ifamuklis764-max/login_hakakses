
<?php
require_once "auth.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

require_once "config/database.php";

$csrfToken = csrf_token();
$error = null;
$updated = isset($_GET['updated']) && $_GET['updated'] === '1';

// Menentukan halaman dashboard berdasarkan role
$dashboardPath = 'siswa/dashboard.php';

if ($_SESSION['role'] === 'admin') {
    $dashboardPath = 'admin/dashboard.php';
} elseif ($_SESSION['role'] === 'petugas') {
    $dashboardPath = 'petugas/dashboard.php';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!valid_csrf_token($_POST['csrf_token'] ?? null)) {
        $error = 'Form kedaluwarsa. Muat ulang halaman lalu coba lagi.';
    } else {

        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        if (
            !is_string($currentPassword) ||
            !is_string($newPassword) ||
            !is_string($confirmPassword)
        ) {
            $error = 'Data password tidak valid.';

        } elseif (
            $currentPassword === '' ||
            $newPassword === '' ||
            $confirmPassword === ''
        ) {
            $error = 'Semua kolom password wajib diisi.';

        } elseif (strlen($newPassword) < 8) {
            $error = 'Password baru harus terdiri dari minimal 8 karakter.';

        } elseif ($newPassword !== $confirmPassword) {
            $error = 'Konfirmasi password baru tidak cocok.';

        } else {

            $userId = (int) $_SESSION['user_id'];

            $stmt = mysqli_prepare(
                $conn,
                "SELECT password FROM users WHERE id = ? LIMIT 1"
            );

            mysqli_stmt_bind_param($stmt, "i", $userId);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_bind_result($stmt, $currentPasswordHash);

            $userFound = mysqli_stmt_fetch($stmt);

            mysqli_stmt_close($stmt);

            if (
                !$userFound ||
                !password_verify($currentPassword, $currentPasswordHash)
            ) {
                $error = 'Password saat ini tidak benar.';

            } elseif (password_verify($newPassword, $currentPasswordHash)) {
                $error = 'Password baru harus berbeda dari password saat ini.';

            } else {

                $newPasswordHash = password_hash(
                    $newPassword,
                    PASSWORD_DEFAULT
                );

                $stmt = mysqli_prepare(
                    $conn,
                    "UPDATE users SET password = ? WHERE id = ?"
                );

                mysqli_stmt_bind_param(
                    $stmt,
                    "si",
                    $newPasswordHash,
                    $userId
                );

                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);

                session_regenerate_id(true);

                $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

                header("Location: ubah_password.php?updated=1");
                exit;
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Ubah Password - LoginKu</title>

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- CSS LoginKu -->
    <link rel="stylesheet" href="assets/css/style.css?v=manrope">
</head>

<body class="login-page">

<main class="login-container">
    <section class="login-card change-password-card">

        <div class="logo" aria-label="Logo LoginKu">
            <img
                class="logo-icon"
                src="assets/img/loginku-emblem.svg"
                alt="Logo LoginKu"
            >
        </div>

        <h1>Ubah Password</h1>

        <p class="subtitle">
            Gunakan password yang kuat dan mudah Anda ingat.
        </p>

        <?php if ($updated): ?>
            <div class="success-alert">
                Password berhasil diperbarui.
            </div>
        <?php endif; ?>

        <?php if ($error !== null): ?>
            <div class="alert">
                <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="ubah_password.php">

            <input
                type="hidden"
                name="csrf_token"
                value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>"
            >

            <label for="current-password">Password saat ini</label>

            <div class="password-input-wrap">
                <input
                    id="current-password"
                    type="password"
                    name="current_password"
                    autocomplete="current-password"
                    required
                >

                <button
                    class="password-toggle"
                    type="button"
                    data-password-toggle="current-password"
                    aria-label="Tampilkan password"
                    aria-pressed="false"
                >Tampilkan</button>
            </div>

            <label for="new-password">Password baru</label>

            <div class="password-input-wrap">
                <input
                    id="new-password"
                    type="password"
                    name="new_password"
                    autocomplete="new-password"
                    minlength="8"
                    required
                >

                <button
                    class="password-toggle"
                    type="button"
                    data-password-toggle="new-password"
                    aria-label="Tampilkan password"
                    aria-pressed="false"
                >Tampilkan</button>
            </div>

            <label for="confirm-password">Konfirmasi password baru</label>

            <div class="password-input-wrap">
                <input
                    id="confirm-password"
                    type="password"
                    name="confirm_password"
                    autocomplete="new-password"
                    minlength="8"
                    required
                >

                <button
                    class="password-toggle"
                    type="button"
                    data-password-toggle="confirm-password"
                    aria-label="Tampilkan password"
                    aria-pressed="false"
                >Tampilkan</button>
            </div>

            <button type="submit">
                Simpan Password Baru
            </button>

        </form>

        <div class="account-links">
            <a
                class="account-link"
                href="<?= htmlspecialchars($dashboardPath, ENT_QUOTES, 'UTF-8') ?>"
            >
                Kembali ke dashboard
            </a>
        </div>

    </section>
</main>

<script src="assets/js/password-toggle.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
