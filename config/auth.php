<?php
session_start();

/*
|--------------------------------------------------------------------------
| CEK LOGIN
|--------------------------------------------------------------------------
*/
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

/*
|--------------------------------------------------------------------------
| CEK ROLE (ADMIN / KASIR)
|--------------------------------------------------------------------------
| Admin boleh akses semua halaman admin
| Kasir boleh (nanti bisa dibatasi kalau perlu)
*/
$allowed_roles = ['admin', 'kasir'];

if (!in_array($_SESSION['role'], $allowed_roles)) {
    echo "Akses ditolak.";
    exit;
}
