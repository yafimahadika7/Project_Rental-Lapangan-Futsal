<?php
session_start();
require 'config/koneksi.php';

$error = "";

// PROSES LOGIN
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    $stmt = $koneksi->prepare("
        SELECT id, nama, password, role, status
        FROM users
        WHERE username = ?
        LIMIT 1
    ");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res->num_rows === 1) {

        $user = $res->fetch_assoc();

        if ($user['status'] !== 'aktif') {
            $error = "Akun tidak aktif.";
        } elseif (password_verify($password, $user['password'])) {

            // SET SESSION
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['nama'] = $user['nama'];
            $_SESSION['role'] = $user['role'];

            // REDIRECT BERDASARKAN ROLE
            if ($user['role'] === 'admin') {
                header("Location: admin/dashboard.php");
            } elseif ($user['role'] === 'kasir') {
                header("Location: kasir/dashboard.php");
            } else {
                $error = "Role tidak dikenali.";
            }
            exit;

        } else {
            $error = "Username atau password salah.";
        }

    } else {
        $error = "Username atau password salah.";
    }
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Login | Futsal Center</title>

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Google Font -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">

    <style>
        * {
            font-family: 'Poppins', sans-serif;
        }

        body {
            min-height: 100vh;
            background:
                linear-gradient(rgba(0, 0, 0, .65), rgba(0, 0, 0, .65)),
                url('asset/img/bg-futsal.jpg') no-repeat center center;
            background-size: cover;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .login-card {
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(12px);
            border-radius: 16px;
            padding: 40px;
            width: 100%;
            max-width: 420px;
            box-shadow: 0 25px 50px rgba(0, 0, 0, .35);
            color: #fff;
        }

        .login-card h3 {
            font-weight: 600;
        }

        .form-control {
            background: rgba(255, 255, 255, .9);
            border: none;
            border-radius: 10px;
            padding: 12px;
        }

        .btn-login {
            border-radius: 10px;
            padding: 12px;
            font-weight: 600;
        }
    </style>
</head>

<body>

    <div class="login-card">

        <div class="text-center mb-4">
            <h3>FUTSAL CENTER</h3>
            <p class="mb-0">Admin & Kasir Panel</p>
        </div>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger">
                <?= $error ?>
            </div>
        <?php endif; ?>

        <form method="POST">

            <div class="mb-3">
                <label class="form-label">Username</label>
                <input type="text" name="username" class="form-control" required>
            </div>

            <div class="mb-4">
                <label class="form-label">Password</label>
                <input type="password" name="password" class="form-control" required>
            </div>

            <button type="submit" class="btn btn-primary w-100 btn-login">
                Login
            </button>

        </form>

        <div class="text-center mt-4">
            <small class="opacity-75">
                © <?= date('Y') ?> Futsal Center
            </small>
        </div>

    </div>

</body>

</html>