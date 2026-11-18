
<?php
require_once __DIR__ . "/auth.php";
require_once __DIR__ . "/config/database.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit;
}

$userId = (int) $_SESSION['user_id'];
$profileName = $_SESSION['nama'] ?? 'Pengguna';
$profileUsername = $_SESSION['username'] ?? '-';
$role = $_SESSION['role'] ?? '';
$roleLabel = [
    'admin' => 'Administrator',
    'petugas' => 'Petugas',
    'siswa' => 'Siswa'
];

$namaRole = $roleLabel[$role] ?? 'Pengguna';
$dashboardPath = $_SERVER['SCRIPT_NAME'] ?? 'dashboard.php';
$dashboardUrl = htmlspecialchars($dashboardPath, ENT_QUOTES, 'UTF-8');
$csrfToken = csrf_token();
$profileError = null;
$profileUpdated = isset($_GET['profile_updated']) && $_GET['profile_updated'] === '1';
$showProfileTab = isset($_GET['tab']) && $_GET['tab'] === 'informasi';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_profile'])) {
    $showProfileTab = true;
    $profileNameInput = $_POST['nama'] ?? '';
    $profileUsernameInput = $_POST['username'] ?? '';

    if (!valid_csrf_token($_POST['csrf_token'] ?? null)) {
        $profileError = 'Form kedaluwarsa. Muat ulang halaman lalu coba lagi.';
    } elseif (!is_string($profileNameInput) || !is_string($profileUsernameInput)) {
        $profileError = 'Data akun tidak valid.';
    } else {
        $profileNameInput = trim($profileNameInput);
        $profileUsernameInput = trim($profileUsernameInput);
        $nameLength = preg_match_all('/./us', $profileNameInput, $nameCharacters);
        $usernameLength = preg_match_all('/./us', $profileUsernameInput, $usernameCharacters);

        if ($nameLength === false || $usernameLength === false) {
            $profileError = 'Nama dan username harus menggunakan teks yang valid.';
        } elseif ($profileNameInput === '' || $profileUsernameInput === '') {
            $profileError = 'Nama lengkap dan username wajib diisi.';
        } elseif ($nameLength > 100) {
            $profileError = 'Nama lengkap maksimal 100 karakter.';
        } elseif ($usernameLength > 50) {
            $profileError = 'Username maksimal 50 karakter.';
        } else {
            $checkStmt = mysqli_prepare(
                $conn,
                "SELECT id FROM users WHERE username = ? AND id <> ? LIMIT 1"
            );

            if (!$checkStmt) {
                error_log("Gagal menyiapkan pemeriksaan username: " . mysqli_error($conn));
                $profileError = 'Profil tidak dapat diperbarui saat ini.';
            } else {
                mysqli_stmt_bind_param($checkStmt, "si", $profileUsernameInput, $userId);

                if (!mysqli_stmt_execute($checkStmt) || !mysqli_stmt_store_result($checkStmt)) {
                    error_log("Gagal memeriksa username: " . mysqli_stmt_error($checkStmt));
                    $profileError = 'Profil tidak dapat diperbarui saat ini.';
                } elseif (mysqli_stmt_num_rows($checkStmt) > 0) {
                    $profileError = 'Username tersebut sudah digunakan.';
                }

                mysqli_stmt_close($checkStmt);
            }

            if ($profileError === null) {
                $updateStmt = mysqli_prepare(
                    $conn,
                    "UPDATE users SET nama = ?, username = ? WHERE id = ?"
                );

                if (!$updateStmt) {
                    error_log("Gagal menyiapkan pembaruan profil: " . mysqli_error($conn));
                    $profileError = 'Profil tidak dapat diperbarui saat ini.';
                } else {
                    mysqli_stmt_bind_param(
                        $updateStmt,
                        "ssi",
                        $profileNameInput,
                        $profileUsernameInput,
                        $userId
                    );

                    if (!mysqli_stmt_execute($updateStmt)) {
                        if (mysqli_stmt_errno($updateStmt) === 1062) {
                            $profileError = 'Username tersebut sudah digunakan.';
                        } else {
                            error_log("Gagal memperbarui profil: " . mysqli_stmt_error($updateStmt));
                            $profileError = 'Profil tidak dapat diperbarui saat ini.';
                        }
                    } else {
                        $_SESSION['nama'] = $profileNameInput;
                        $_SESSION['username'] = $profileUsernameInput;
                        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

                        header("Location: " . $dashboardPath . "?profile_updated=1&tab=informasi");
                        exit;
                    }

                    mysqli_stmt_close($updateStmt);
                }
            }
        }
    }

    if ($profileError !== null) {
        if (is_string($profileNameInput)) {
            $profileName = trim($profileNameInput);
        }
        if (is_string($profileUsernameInput)) {
            $profileUsername = trim($profileUsernameInput);
        }
    }
}

$nama = htmlspecialchars($profileName, ENT_QUOTES, 'UTF-8');
$username = htmlspecialchars($profileUsername, ENT_QUOTES, 'UTF-8');
$profileNameValue = htmlspecialchars($profileName, ENT_QUOTES, 'UTF-8');
$profileUsernameValue = htmlspecialchars($profileUsername, ENT_QUOTES, 'UTF-8');

?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Dashboard <?= htmlspecialchars($namaRole) ?> - LoginKu</title>

    <link rel="stylesheet" href="../assets/css/dashboard.css?v=profile-edit-link">
</head>

<body>

<div class="dashboard-wrapper">

    <!-- SIDEBAR -->
    <aside class="sidebar">

        <div class="brand">
            <img src="../assets/img/loginku-emblem.svg" alt="Logo LoginKu">
            <span>LoginKu</span>
        </div>

        <a href="<?= $dashboardUrl ?>" class="menu-item active">
            <span aria-hidden="true">▦</span>
            Dashboard
        </a>

        <a href="../ubah_password.php" class="menu-item">
            <span aria-hidden="true">⚙</span>
            Ubah Password
        </a>

        <div class="sidebar-bottom">
            <div class="sidebar-profile">
                <div class="avatar">
                    <?= strtoupper(substr($nama, 0, 1)) ?>
                </div>

                <div>
                    <strong><?= $nama ?></strong>
                    <small><?= $namaRole ?></small>
                </div>
            </div>

            <a href="../logout.php" class="logout-link">
                ↪ Keluar
            </a>
        </div>

    </aside>

    <!-- KONTEN UTAMA -->
    <main class="main-content">

        <header class="topbar">
            <div>
                <h1>Dashboard</h1>
            </div>
        </header>

        <nav class="dashboard-tabs" role="tablist" aria-label="Navigasi dashboard">
            <button class="tab-link<?= $showProfileTab ? '' : ' active' ?>"
                    id="tab-ringkasan" type="button" role="tab"
                    aria-controls="panel-ringkasan"
                    aria-selected="<?= $showProfileTab ? 'false' : 'true' ?>"
                    tabindex="<?= $showProfileTab ? '-1' : '0' ?>">
                Ringkasan
            </button>
            <button class="tab-link<?= $showProfileTab ? ' active' : '' ?>"
                    id="tab-informasi" type="button" role="tab"
                    aria-controls="panel-informasi"
                    aria-selected="<?= $showProfileTab ? 'true' : 'false' ?>"
                    tabindex="<?= $showProfileTab ? '0' : '-1' ?>">
                Informasi Akun
            </button>
            <button class="tab-link" id="tab-hak-akses" type="button" role="tab"
                    aria-controls="panel-hak-akses" aria-selected="false" tabindex="-1">
                Hak Akses
            </button>
        </nav>

        <section class="dashboard-tab-panel" id="panel-ringkasan" role="tabpanel"
                 aria-labelledby="tab-ringkasan" tabindex="0"<?= $showProfileTab ? ' hidden' : '' ?>>
          <div class="dashboard-grid">
            <div class="dashboard-primary">
                <article class="content-card account-card">
                    <div class="card-heading">
                        <div>
                            <h2>Ringkasan akun</h2>
                            <p>Informasi akun yang sedang digunakan</p>
                        </div>
                        <div class="card-heading-tools">
                            <span class="status-badge"><span aria-hidden="true">●</span> Aktif</span>
                            <a class="edit-profile-link" href="<?= $dashboardUrl ?>?tab=informasi">
                                Edit Profil
                            </a>
                        </div>
                    </div>

                    <div class="account-identity">
                        <div class="identity-avatar" aria-hidden="true">
                            <?= strtoupper(substr($nama, 0, 1)) ?>
                        </div>
                        <div class="identity-copy">
                            <span class="eyebrow">SELAMAT DATANG</span>
                            <h3><?= $nama ?></h3>
                            <p>@<?= $username ?></p>
                        </div>
                    </div>

                    <div class="info-list">
                        <div class="info-row">
                            <span>Nama Lengkap</span>
                            <strong><?= $nama ?></strong>
                        </div>
                        <div class="info-row">
                            <span>Username</span>
                            <strong><?= $username ?></strong>
                        </div>
                        <div class="info-row">
                            <span>Level Akses</span>
                            <strong><?= $namaRole ?></strong>
                        </div>
                    </div>
                </article>

                <article class="content-card access-card">
                    <div class="card-heading">
                        <div>
                            <h2>Hak akses Anda</h2>
                            <p>Ruang lingkup akun LoginKu</p>
                        </div>
                        <span class="role-badge"><?= $namaRole ?></span>
                    </div>

                    <div class="access-panel">
                        <div class="access-symbol" aria-hidden="true">✓</div>
                        <div>
                            <strong><?= $namaRole ?></strong>
                            <p>
                                <?php if ($role === 'admin'): ?>
                                    Memiliki akses administrator untuk mengelola sistem.
                                <?php elseif ($role === 'petugas'): ?>
                                    Memiliki akses untuk memantau informasi pengguna.
                                <?php else: ?>
                                    Memiliki akses untuk melihat informasi akun pribadi.
                                <?php endif; ?>
                            </p>
                        </div>
                    </div>
                </article>
            </div>

            <aside class="dashboard-secondary">
                <article class="content-card status-card">
                    <div class="card-heading">
                        <div>
                            <h2>Status akun</h2>
                            <p>Kondisi akun saat ini</p>
                        </div>
                    </div>

                    <div class="status-overview">
                        <div class="status-ring" aria-hidden="true"><span>✓</span></div>
                        <div>
                            <strong>Akun aktif</strong>
                            <p>Anda berhasil masuk ke LoginKu.</p>
                        </div>
                    </div>

                    <div class="status-note">
                        <span class="status-dot" aria-hidden="true"></span>
                        <span>Sesi Anda sedang berjalan</span>
                    </div>
                </article>

                <article class="content-card password-card">
                    <div class="card-heading">
                        <div>
                            <h2>Keamanan akun</h2>
                            <p>Jaga akun tetap aman</p>
                        </div>
                    </div>
                    <p class="password-description">
                        Perbarui password secara berkala untuk menjaga keamanan akun Anda.
                    </p>
                    <a href="../ubah_password.php" class="change-password-link">
                        Ubah password <span aria-hidden="true">→</span>
                    </a>
                </article>
            </aside>
          </div>
        </section>

        <section class="dashboard-tab-panel detail-tab-panel" id="panel-informasi" role="tabpanel"
                 aria-labelledby="tab-informasi" tabindex="0"<?= $showProfileTab ? '' : ' hidden' ?>>
            <article class="content-card account-card">
                <div class="card-heading">
                    <div>
                        <h2>Informasi akun</h2>
                        <p>Detail akun yang sedang Anda gunakan</p>
                    </div>
                    <span class="status-badge"><span aria-hidden="true">●</span> Aktif</span>
                </div>

                <?php if ($profileUpdated): ?>
                    <div class="profile-feedback success" role="status">
                        Profil berhasil diperbarui.
                    </div>
                <?php endif; ?>

                <?php if ($profileError !== null): ?>
                    <div class="profile-feedback error" role="alert">
                        <?= htmlspecialchars($profileError, ENT_QUOTES, 'UTF-8') ?>
                    </div>
                <?php endif; ?>

                <form class="profile-form" method="POST" action="<?= $dashboardUrl ?>?tab=informasi">
                    <input type="hidden" name="csrf_token"
                           value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">

                    <div class="profile-fields">
                        <div class="profile-field">
                            <label for="profile-name">Nama Lengkap</label>
                            <input id="profile-name" type="text" name="nama"
                                   value="<?= $profileNameValue ?>" maxlength="100"
                                   autocomplete="name" required>
                        </div>

                        <div class="profile-field">
                            <label for="profile-username">Username</label>
                            <input id="profile-username" type="text" name="username"
                                   value="<?= $profileUsernameValue ?>" maxlength="50"
                                   autocomplete="username" required>
                        </div>

                        <div class="profile-field">
                            <label for="profile-role">Level Akses</label>
                            <input id="profile-role" type="text" value="<?= htmlspecialchars($namaRole, ENT_QUOTES, 'UTF-8') ?>"
                                   aria-describedby="profile-role-note" readonly>
                            <small id="profile-role-note">
                                Level akses dikelola administrator dan tidak dapat diubah dari profil ini.
                            </small>
                        </div>
                    </div>

                    <button class="save-profile-button" type="submit" name="save_profile" value="1">
                        Simpan Perubahan
                    </button>
                </form>
            </article>
        </section>

        <section class="dashboard-tab-panel detail-tab-panel" id="panel-hak-akses" role="tabpanel"
                 aria-labelledby="tab-hak-akses" tabindex="0" hidden>
            <article class="content-card access-card">
                <div class="card-heading">
                    <div>
                        <h2>Hak akses Anda</h2>
                        <p>Ruang lingkup akun LoginKu</p>
                    </div>
                    <span class="role-badge"><?= $namaRole ?></span>
                </div>

                <div class="access-panel">
                    <div class="access-symbol" aria-hidden="true">✓</div>
                    <div>
                        <strong><?= $namaRole ?></strong>
                        <p>
                            <?php if ($role === 'admin'): ?>
                                Memiliki akses administrator untuk mengelola sistem.
                            <?php elseif ($role === 'petugas'): ?>
                                Memiliki akses untuk memantau informasi pengguna.
                            <?php else: ?>
                                Memiliki akses untuk melihat informasi akun pribadi.
                            <?php endif; ?>
                        </p>
                    </div>
                </div>
            </article>
        </section>

        <footer class="dashboard-footer">
            LoginKu · Sistem Login dan Hak Akses Pengguna
        </footer>

    </main>

</div>

<script>
    const dashboardTabs = Array.from(document.querySelectorAll('[role="tab"]'));
    const dashboardPanels = dashboardTabs.map((tab) =>
        document.getElementById(tab.getAttribute('aria-controls'))
    );

    function activateDashboardTab(activeIndex, moveFocus) {
        dashboardTabs.forEach((tab, index) => {
            const isActive = index === activeIndex;
            tab.classList.toggle('active', isActive);
            tab.setAttribute('aria-selected', String(isActive));
            tab.tabIndex = isActive ? 0 : -1;
            dashboardPanels[index].hidden = !isActive;
        });

        if (moveFocus) {
            dashboardTabs[activeIndex].focus();
        }
    }

    dashboardTabs.forEach((tab, index) => {
        tab.addEventListener('click', () => activateDashboardTab(index, false));
        tab.addEventListener('keydown', (event) => {
            let nextIndex = index;

            if (event.key === 'ArrowRight') {
                nextIndex = (index + 1) % dashboardTabs.length;
            } else if (event.key === 'ArrowLeft') {
                nextIndex = (index - 1 + dashboardTabs.length) % dashboardTabs.length;
            } else if (event.key === 'Home') {
                nextIndex = 0;
            } else if (event.key === 'End') {
                nextIndex = dashboardTabs.length - 1;
            } else {
                return;
            }

            event.preventDefault();
            activateDashboardTab(nextIndex, true);
        });
    });
</script>

</body>
</html>
