<?php
require_once '../config/koneksi.php';
$page_title = "Dashboard Kasir";

/* STATISTIK */
$menunggu = $koneksi->query("SELECT COUNT(*) j FROM booking WHERE status='MENUNGGU_DP'")->fetch_assoc()['j'];
$lunas = $koneksi->query("SELECT COUNT(*) j FROM booking WHERE status='LUNAS'")->fetch_assoc()['j'];
$hari_ini = $koneksi->query("
    SELECT COUNT(*) j FROM booking 
    WHERE tanggal = CURDATE()
")->fetch_assoc()['j'];

ob_start();
?>

<h4 class="mb-4">
    <i class="bi bi-speedometer2 me-2"></i> Dashboard Kasir
</h4>

<div class="row g-4">

    <div class="col-md-4">
        <div class="card shadow-sm border-0">
            <div class="card-body d-flex align-items-center">
                <i class="bi bi-hourglass-split fs-1 text-warning me-3"></i>
                <div>
                    <h6 class="mb-0">Menunggu DP</h6>
                    <h3 class="fw-bold"><?= $menunggu ?></h3>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card shadow-sm border-0">
            <div class="card-body d-flex align-items-center">
                <i class="bi bi-check-circle-fill fs-1 text-success me-3"></i>
                <div>
                    <h6 class="mb-0">Booking Lunas</h6>
                    <h3 class="fw-bold"><?= $lunas ?></h3>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card shadow-sm border-0">
            <div class="card-body d-flex align-items-center">
                <i class="bi bi-calendar-event fs-1 text-primary me-3"></i>
                <div>
                    <h6 class="mb-0">Booking Hari Ini</h6>
                    <h3 class="fw-bold"><?= $hari_ini ?></h3>
                </div>
            </div>
        </div>
    </div>

</div>

<?php
$content = ob_get_clean();
require 'layout.php';
