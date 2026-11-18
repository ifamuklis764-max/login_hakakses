
<?php
require_once "auth.php";

// Jika pengguna sudah login, arahkan sesuai role
if (isset($_SESSION['user_id'])) {

    if ($_SESSION['role'] === 'admin') {
        header("Location: admin/dashboard.php");

    } elseif ($_SESSION['role'] === 'petugas') {
        header("Location: petugas/dashboard.php");

    } elseif ($_SESSION['role'] === 'siswa') {
        header("Location: siswa/dashboard.php");

    } else {
        // Jika role tidak dikenali, keluarkan pengguna
        $_SESSION = [];
        session_destroy();

        header("Location: index.php");
    }

    exit;
}

$csrfToken = csrf_token();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>LoginKu - Login</title>

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
          rel="stylesheet">

    <!-- CSS LoginKu -->
    <link rel="stylesheet" href="assets/css/style.css?v=manrope">
</head>

<body class="login-page">

<main class="login-container">

    <section class="login-card login-layout">

        <div class="login-form-panel">

            <div class="brand-lockup">
                <div class="logo">
                    <img class="logo-icon"
                         src="assets/img/loginku-emblem.svg"
                         alt="Logo LoginKu">
                </div>

                <span class="brand-name">LoginKu</span>
            </div>

            <div class="login-form-content">

                <p class="login-kicker">PORTAL AKUN</p>

                <h1>Selamat Datang!</h1>

                <p class="subtitle">
                    Silakan masuk menggunakan akun Anda.
                </p>

                <?php if (isset($_GET['error'])): ?>
                    <div class="alert alert-danger">
                        <?php
                        if ($_GET['error'] === 'expired') {
                            echo "Form login kedaluwarsa. Silakan coba lagi.";
                        } else {
                            echo "Username atau password salah!";
                        }
                        ?>
                    </div>
                <?php endif; ?>

                <form class="login-form"
                      action="proses_login.php"
                      method="POST">

                    <input type="hidden"
                           name="csrf_token"
                           value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">

                    <label for="login-username">
                        Username
                    </label>

                    <input
                        id="login-username"
                        type="text"
                        name="username"
                        placeholder="Masukkan username"
                        autocomplete="username"
                        required
                    >

                    <label for="login-password">
                        Password
                    </label>

                    <div class="password-input-wrap">

                        <input
                            id="login-password"
                            type="password"
                            name="password"
                            placeholder="Masukkan password"
                            autocomplete="current-password"
                            required
                        >

                        <button
                            class="password-toggle"
                            type="button"
                            data-password-toggle="login-password"
                            aria-label="Tampilkan password"
                            aria-pressed="false">
                            Tampilkan
                        </button>

                    </div>

                    <button type="submit" name="login">
                        Masuk
                    </button>

                </form>

            </div>

            <p class="footer">
                LoginKu © 2026
            </p>

        </div>

        <aside class="login-visual-panel">

            <div class="visual-copy">

                <span class="visual-tag">
                    RUANG AKUN ANDA
                </span>

                <h2>
                    Satu akun,<br>
                    akses lebih mudah.
                </h2>

                <p>
                    Masuk dengan aman dan lanjutkan
                    aktivitas Anda di LoginKu.
                </p>

            </div>

            <img
                class="login-illustration"
                src="assets/img/login-illustration.svg"
                alt="Ilustrasi halaman login"
            >

            <div class="visual-caption">
                <span class="status-dot"></span>
                Akses aman untuk setiap peran
            </div>

        </aside>

    </section>

</main>

<script src="assets/js/password-toggle.js"></script>

</body>
</html>
