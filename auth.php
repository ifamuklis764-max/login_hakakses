
<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Membuat CSRF Token
function csrf_token() {

    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

// Memeriksa CSRF Token
function valid_csrf_token($token) {

    return is_string($token)
        && isset($_SESSION['csrf_token'])
        && hash_equals($_SESSION['csrf_token'], $token);
}

// Memeriksa apakah pengguna sudah login
function cek_login() {

    if (!isset($_SESSION['user_id'])) {
        header("Location: ../index.php");
        exit;
    }
}

// Memeriksa hak akses berdasarkan role
function cek_role($role) {

    cek_login();

    if ($_SESSION['role'] !== $role) {
        http_response_code(403);
        exit("403 - Anda tidak memiliki hak akses!");
    }
}

?>
