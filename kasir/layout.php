<?php
require_once '../config/auth.php';

if ($_SESSION['role'] !== 'kasir') {
    echo "Akses ditolak";
    exit;
}

if (!isset($page_title))
    $page_title = "Kasir Panel";
if (!isset($content))
    $content = "";
?>
<!doctype html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= $page_title ?> | Futsal Center</title>

    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

    <!-- Font -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">

    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: #f3f4f6;
        }

        .wrapper {
            display: flex;
            min-height: 100vh;
        }

        /* SIDEBAR */
        #sidebar {
            width: 250px;
            background: linear-gradient(180deg, #1e293b, #334155);
            color: #fff;
            padding: 1.2rem;
            transition: margin-left .3s ease;
        }

        #sidebar.collapsed {
            margin-left: -250px;
        }

        .sidebar-brand {
            text-align: center;
            margin-bottom: 1.5rem;
        }

        .sidebar-brand i {
            font-size: 2rem;
            color: #38bdf8;
        }

        #sidebar .nav-link {
            color: #e5e7eb;
            border-radius: 10px;
            padding: .7rem 1rem;
            margin-bottom: .4rem;
            display: flex;
            align-items: center;
            gap: .7rem;
        }

        #sidebar .nav-link.active,
        #sidebar .nav-link:hover {
            background: rgba(56, 189, 248, 0.2);
            color: #fff;
        }

        /* TOPBAR */
        .topbar {
            background: #0f172a;
        }

        #main-content {
            flex: 1;
            padding: 1.8rem;
        }

        .btn-logout {
            background: #ef4444;
            border-radius: 10px;
        }

        .btn-logout:hover {
            background: #dc2626;
        }

        @media (max-width: 768px) {
            #sidebar {
                position: fixed;
                top: 56px;
                height: calc(100vh - 56px);
                z-index: 1050;
            }
        }
    </style>
</head>

<body>

    <!-- TOPBAR -->
    <nav class="navbar navbar-dark topbar">
        <div class="container-fluid">
            <button class="btn btn-outline-light" id="sidebarToggle">
                <i class="bi bi-list"></i>
            </button>

            <span class="navbar-brand ms-3">FUTSAL CENTER</span>

            <span class="text-light small ms-auto">
                <i class="bi bi-person-badge me-1"></i>
                <?= $_SESSION['nama'] ?> (Kasir)
            </span>
        </div>
    </nav>

    <div class="wrapper">

        <!-- SIDEBAR -->
        <aside id="sidebar">
            <div class="sidebar-brand">
                <i class="bi bi-cash-stack"></i><br>
                <strong>KASIR PANEL</strong>
            </div>

            <ul class="nav flex-column">

                <li class="nav-item">
                    <a href="dashboard.php"
                        class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : '' ?>">
                        <i class="bi bi-speedometer2"></i>
                        Dashboard
                    </a>
                </li>

                <li class="nav-item">
                    <a href="transaksi.php"
                        class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'transaksi.php' ? 'active' : '' ?>">
                        <i class="bi bi-receipt-cutoff"></i>
                        Transaksi Booking
                    </a>
                </li>

            </ul>

            <hr class="text-secondary">

            <a href="../logout.php" class="btn btn-logout w-100 text-white">
                <i class="bi bi-box-arrow-right me-1"></i> Logout
            </a>
        </aside>

        <!-- CONTENT -->
        <main id="main-content">
            <?= $content ?>
        </main>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.getElementById('sidebarToggle').onclick = () =>
            document.getElementById('sidebar').classList.toggle('collapsed');
    </script>

</body>

</html>