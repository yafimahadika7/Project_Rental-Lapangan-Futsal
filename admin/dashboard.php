<?php
require_once '../config/koneksi.php';

/* =====================
   AMBIL DATA STATISTIK
===================== */
$total = $koneksi->query("SELECT COUNT(*) AS total FROM booking")->fetch_assoc()['total'];

$aktif = $koneksi->query("
    SELECT COUNT(*) AS total 
    FROM booking 
    WHERE status IN ('DP','LUNAS')
")->fetch_assoc()['total'];

$gagal = $koneksi->query("
    SELECT COUNT(*) AS total 
    FROM booking 
    WHERE status = 'GAGAL'
")->fetch_assoc()['total'];

ob_start();
?>

<h3 class="fw-semibold mb-4">
    <i class="bi bi-speedometer2 me-2 text-success"></i>
    Dashboard Admin
</h3>

<div class="row g-4">

    <!-- TOTAL BOOKING -->
    <div class="col-md-4">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-body d-flex align-items-center">
                <div class="icon-box bg-primary text-white me-3">
                    <i class="bi bi-calendar-check"></i>
                </div>
                <div>
                    <div class="text-muted small">Total Booking</div>
                    <h3 class="fw-bold mb-0"><?= $total ?></h3>
                </div>
            </div>
        </div>
    </div>

    <!-- BOOKING AKTIF -->
    <div class="col-md-4">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-body d-flex align-items-center">
                <div class="icon-box bg-success text-white me-3">
                    <i class="bi bi-check-circle"></i>
                </div>
                <div>
                    <div class="text-muted small">Booking Aktif</div>
                    <h3 class="fw-bold text-success mb-0"><?= $aktif ?></h3>
                </div>
            </div>
        </div>
    </div>

    <!-- BOOKING GAGAL -->
    <div class="col-md-4">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-body d-flex align-items-center">
                <div class="icon-box bg-danger text-white me-3">
                    <i class="bi bi-x-circle"></i>
                </div>
                <div>
                    <div class="text-muted small">Booking Gagal</div>
                    <h3 class="fw-bold text-danger mb-0"><?= $gagal ?></h3>
                </div>
            </div>
        </div>
    </div>

</div>

<!-- STYLE KHUSUS DASHBOARD -->
<style>
    .icon-box {
        width: 60px;
        height: 60px;
        border-radius: 14px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.8rem;
    }
</style>

<?php
$content = ob_get_clean();
include 'layout.php';
