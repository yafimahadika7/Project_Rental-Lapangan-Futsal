<?php
date_default_timezone_set('Asia/Jakarta');
require 'config/koneksi.php';

/* =========================================
   AUTO CANCEL BOOKING > 24 JAM
========================================= */
$koneksi->query("
    UPDATE booking
    SET status = 'GAGAL'
    WHERE status = 'MENUNGGU_DP'
    AND created_at <= NOW() - INTERVAL 24 HOUR
");

/* =========================================
   ENDPOINT AJAX CEK JAM TERBOOKING
========================================= */
if (isset($_GET['cek_jam'])) {

    $lapangan_id = $_GET['lapangan'];
    $tanggal = $_GET['tanggal'];

    $q = $koneksi->prepare("
        SELECT jam_mulai
        FROM booking
        WHERE lapangan_id = ?
        AND tanggal = ?
        AND status IN ('MENUNGGU_DP','DP','LUNAS')
    ");
    $q->bind_param("is", $lapangan_id, $tanggal);
    $q->execute();
    $res = $q->get_result();

    $jamBooked = [];
    while ($r = $res->fetch_assoc()) {
        $jamBooked[] = substr($r['jam_mulai'], 0, 5); // contoh: 22:00
    }

    echo json_encode($jamBooked);
    exit; // WAJIB
}

/* =========================================
   VARIABEL MODAL SUKSES
========================================= */
$showSuccessModal = false;
$success = [];

/* =========================================
   SIMPAN BOOKING PELANGGAN
========================================= */
if (isset($_POST['submit_booking'])) {

    $lapangan = $_POST['lapangan'];
    $tanggal = $_POST['tanggal'];
    $jamRange = $_POST['jam'];

    $jam = explode(' - ', $jamRange);
    $jam_mulai = $jam[0] . ':00';
    $jam_selesai = $jam[1] . ':00';

    $nama = $_POST['nama'];
    $hp = $_POST['hp'];
    $email = $_POST['email'];

    // Ambil ID lapangan
    $q = $koneksi->prepare("SELECT id FROM lapangan WHERE nama = ?");
    $q->bind_param("s", $lapangan);
    $q->execute();
    $lap = $q->get_result()->fetch_assoc();
    $lapangan_id = $lap['id'];

    // Cek bentrok jam
    $cek = $koneksi->prepare("
        SELECT id FROM booking
        WHERE lapangan_id = ?
        AND tanggal = ?
        AND jam_mulai = ?
        AND status IN ('MENUNGGU_DP','DP','LUNAS')
    ");
    $cek->bind_param("iss", $lapangan_id, $tanggal, $jam_mulai);
    $cek->execute();

    if ($cek->get_result()->num_rows == 0) {

        $kode = "FTS-" . date("Ymd") . "-" . rand(1000, 9999);
        $harga = 100000;       // disimpan backend
        $dp = $harga * 0.3;

        $stmt = $koneksi->prepare("
            INSERT INTO booking
            (kode_booking, lapangan_id, tanggal, jam_mulai, jam_selesai,
             nama_pelanggan, no_hp, email, dp_wajib)
            VALUES (?,?,?,?,?,?,?,?,?)
        ");
        $stmt->bind_param(
            "sissssssd",
            $kode,
            $lapangan_id,
            $tanggal,
            $jam_mulai,
            $jam_selesai,
            $nama,
            $hp,
            $email,
            $dp
        );
        $stmt->execute();

        // Data untuk modal sukses
        $showSuccessModal = true;
        $createdAt = date('Y-m-d H:i:s');
        $success = [
            'kode' => $kode,
            'total' => $harga,
            'dp' => $dp,
            'deadline' => date('Y-m-d H:i:s', strtotime($createdAt . ' +24 hours'))
        ];
    }
}

$cekResult = "";

if (isset($_POST['cek_booking'])) {

    $kode = trim($_POST['kode_booking']);

    $stmt = $koneksi->prepare("
        SELECT 
            b.kode_booking,
            b.tanggal,
            b.jam_mulai,
            b.jam_selesai,
            b.status,
            b.dp_wajib,
            b.created_at,
            l.nama AS lapangan
        FROM booking b
        JOIN lapangan l ON b.lapangan_id = l.id
        WHERE b.kode_booking = ?
    ");
    $stmt->bind_param("s", $kode);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res->num_rows == 0) {

        // ❌ KODE SALAH
        $cekResult = "
            <div class='alert alert-danger'>
                Kode booking tidak ditemukan.
            </div>
        ";

    } else {

        $row = $res->fetch_assoc();

        // ⏳ HITUNG COUNTDOWN
        $deadline = strtotime($row['created_at'] . ' +24 hours');
        $sisa = $deadline - time();

        if ($row['status'] == 'MENUNGGU_DP' && $sisa > 0) {
            $jam = floor($sisa / 3600);
            $menit = floor(($sisa % 3600) / 60);
            $detik = $sisa % 60;

            $countdown = "<b>$jam jam $menit menit $detik detik</b>";
        } else {
            $countdown = "-";
        }

        // ✅ HASIL VALID
        $cekResult = "
            <div class='border rounded p-3'>
                <p><b>Lapangan:</b> {$row['lapangan']}</p>
                <p><b>Tanggal:</b> {$row['tanggal']}</p>
                <p><b>Jam:</b> {$row['jam_mulai']} - {$row['jam_selesai']}</p>
                <p><b>Status:</b> <span class='badge bg-info'>{$row['status']}</span></p>
                <p><b>DP:</b> Rp " . number_format($row['dp_wajib'], 0, ',', '.') . "</p>
                <p><b>Sisa Waktu Pembayaran:</b> $countdown</p>
            </div>
        ";
    }
}

?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Futsal Booking</title>

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        html,
        body {
            height: 100%;
            margin: 0;
            overflow: hidden;
        }

        body {
            background:
                linear-gradient(rgba(0, 0, 0, .65), rgba(0, 0, 0, .65)),
                url('asset/img/bg-futsal.jpg');
            background-size: cover;
            background-position: center;
            color: #fff;
        }

        .navbar {
            background: rgba(0, 0, 0, .75);
        }

        .hero {
            height: 34vh;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
        }

        .lapangan-section {
            margin-top: -40px;
        }

        .card-lapangan {
            background: #fff;
            color: #000;
            border: none;
            cursor: pointer;
            transition: .3s;
        }

        .card-lapangan:hover {
            transform: translateY(-8px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, .35);
        }

        .card-lapangan img {
            height: 170px;
            object-fit: cover;
        }

        .modal-content {
            color: #000;
        }

        .jam-btn {
            min-width: 140px;
        }

        .jam-btn.active {
            background-color: #0d6efd;
            color: #fff;
        }

        /* JAM SUDAH DIBOOKING */
        .jam-btn.booked {
            background-color: #e9ecef;
            border-color: #ced4da;
            color: #6c757d;
            cursor: not-allowed;
        }

        .jam-btn.booked:hover {
            background-color: #e9ecef;
            color: #6c757d;
        }
    </style>
</head>

<body>

    <!-- NAVBAR -->
    <nav class="navbar navbar-dark">
        <div class="container">
            <span class="navbar-brand fw-bold">FUTSAL CENTER</span>

            <div class="d-flex align-items-center gap-2">
                <button class="btn btn-outline-light btn-sm" data-bs-toggle="modal" data-bs-target="#cekBookingModal">
                    Cek Booking
                </button>

                <a href="login.php" class="btn btn-outline-light btn-sm">
                    Login
                </a>
            </div>
        </div>
    </nav>

    <!-- HERO -->
    <section class="hero">
        <div class="container">
            <h1 class="fw-bold">Booking Lapangan Futsal</h1>
            <p class="text-light">
                Pilih lapangan, tentukan jadwal, dan mainkan pertandinganmu
            </p>
        </div>
    </section>

    <!-- LAPANGAN -->
    <section class="container lapangan-section">
        <div class="row g-4">

            <!-- Indoor -->
            <div class="col-md-4">
                <div class="card card-lapangan" onclick="openBooking('Futsal Indoor')">
                    <img src="asset/img/indoor.jpg" class="card-img-top" alt="Futsal Indoor">
                    <div class="card-body text-center">
                        <h5 class="fw-bold">Futsal Indoor</h5>
                        <p class="text-muted mb-0">Lapangan tertutup & nyaman</p>
                    </div>
                </div>
            </div>

            <!-- Outdoor -->
            <div class="col-md-4">
                <div class="card card-lapangan" onclick="openBooking('Futsal Outdoor')">
                    <img src="asset/img/outdoor.jpg" class="card-img-top" alt="Futsal Outdoor">
                    <div class="card-body text-center">
                        <h5 class="fw-bold">Futsal Outdoor</h5>
                        <p class="text-muted mb-0">Lapangan terbuka</p>
                    </div>
                </div>
            </div>

            <!-- Mini Soccer -->
            <div class="col-md-4">
                <div class="card card-lapangan" onclick="openBooking('Mini Soccer')">
                    <img src="asset/img/minisoccer.jpg" class="card-img-top" alt="Mini Soccer">
                    <div class="card-body text-center">
                        <h5 class="fw-bold">Mini Soccer</h5>
                        <p class="text-muted mb-0">Lapangan mini soccer</p>
                    </div>
                </div>
            </div>

        </div>
    </section>

    <!-- MODAL BOOKING -->
    <div class="modal fade" id="bookingModal" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">

                <div class="modal-header">
                    <h5 class="modal-title">Booking Lapangan</h5>
                    <button class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <form method="POST">
                    <div class="modal-body">

                        <!-- hidden penting -->
                        <input type="hidden" name="lapangan" id="lapangan">
                        <input type="hidden" name="jam" id="jamInput">

                        <div class="mb-3">
                            <label class="form-label">Lapangan</label>
                            <input type="text" id="lapanganText" class="form-control" readonly>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Tanggal</label>
                            <input type="date" name="tanggal" class="form-control" required onchange="renderJam()">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Jam Main</label>
                            <div id="jamContainer" class="d-flex flex-wrap gap-2"></div>
                        </div>

                        <hr>

                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <input type="text" name="nama" class="form-control" placeholder="Nama Lengkap" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <input type="text" name="hp" class="form-control" placeholder="No HP" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <input type="email" name="email" class="form-control" placeholder="Email">
                            </div>
                        </div>

                    </div>

                    <div class="modal-footer">
                        <button class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button class="btn btn-success" type="submit" name="submit_booking">
                            Booking
                        </button>
                    </div>
                </form>

            </div>
        </div>
    </div>

    <!-- MODAL BERHASIL -->
    <div class="modal fade" id="successModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">

                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title">Booking Berhasil</h5>
                    <button class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">

                    <p><strong>Kode Booking:</strong><br>
                        <?= $success['kode'] ?? '' ?>
                    </p>

                    <p>Total Harga:
                        Rp <?= number_format($success['total'] ?? 0, 0, ',', '.') ?>
                    </p>

                    <p><strong>DP 30%:</strong>
                        Rp <?= number_format($success['dp'] ?? 0, 0, ',', '.') ?>
                    </p>

                    <!-- 🔽 TAMBAHAN 1: COUNTDOWN -->
                    <div class="mb-2">
                        <strong>Sisa Waktu Pembayaran:</strong>
                        <span id="countdown" class="fw-bold text-danger ms-1">
                            --:--:--
                        </span>
                    </div>

                    <hr>

                    <!-- 🔽 TAMBAHAN 2: INFO TRANSFER -->
                    <p class="mb-1"><strong>Transfer DP ke:</strong></p>
                    <div class="border rounded p-3 mb-3" style="background:#f8f9fa;">
                        <div><strong>Bank:</strong> BCA</div>
                        <div><strong>No. Rekening:</strong>
                            <span class="fw-bold">8801588212</span>
                        </div>
                        <div><strong>Atas Nama:</strong> Mentari Agustina</div>
                    </div>

                    <!-- 🔽 ALERT LAMA (DIPERTAHANKAN) -->
                    <div class="alert alert-warning mb-0">
                        Silakan bayar DP maksimal <strong>24 jam</strong>,
                        jika tidak booking otomatis dibatalkan.
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const modal = new bootstrap.Modal(document.getElementById('bookingModal'));
        const jamBuka = 16;
        const jamTutup = 23;

        function openBooking(lap) {
            document.getElementById('lapangan').value = lap;
            document.getElementById('lapanganText').value = lap;
            renderJam();
            modal.show();
        }

        function renderJam() {

            const tanggal = document.querySelector('input[name="tanggal"]').value;
            if (!tanggal) return;

            const lapanganMap = {
                "Futsal Indoor": 1,
                "Futsal Outdoor": 2,
                "Mini Soccer": 3
            };

            const lapanganNama = document.getElementById('lapangan').value;
            const lapanganId = lapanganMap[lapanganNama];

            fetch(`index.php?cek_jam=1&lapangan=${lapanganId}&tanggal=${tanggal}`)
                .then(res => res.json())
                .then(jamBooked => {

                    const c = document.getElementById('jamContainer');
                    c.innerHTML = '';

                    for (let j = jamBuka; j < jamTutup; j++) {

                        const mulai = String(j).padStart(2, '0') + ':00';
                        const selesai = String(j + 1).padStart(2, '0') + ':00';

                        const b = document.createElement('button');
                        b.type = 'button';
                        b.className = 'btn btn-outline-primary jam-btn';
                        b.textContent = `${mulai} - ${selesai}`;

                        if (jamBooked.includes(mulai)) {
                            // 🔒 SUDAH DIBOOKING
                            b.classList.add('booked');
                            b.disabled = true;
                        } else {
                            b.onclick = () => {
                                document.querySelectorAll('.jam-btn')
                                    .forEach(x => x.classList.remove('active'));

                                b.classList.add('active');
                                document.getElementById('jamInput').value =
                                    `${mulai} - ${selesai}`;
                            };
                        }

                        c.appendChild(b);
                    }
                });
        }
    </script>

    <?php if ($showSuccessModal): ?>
        <script>
            new bootstrap.Modal(document.getElementById('successModal')).show();
        </script>
    <?php endif; ?>

    <?php if ($showSuccessModal): ?>
        <script>
            const deadline = new Date("<?= $success['deadline'] ?>").getTime();
            const countdownEl = document.getElementById('countdown');

            const timer = setInterval(() => {
                const now = new Date().getTime();
                const diff = deadline - now;

                if (diff <= 0) {
                    clearInterval(timer);
                    countdownEl.innerHTML = "Waktu habis";
                    countdownEl.classList.remove('text-danger');
                    countdownEl.classList.add('text-muted');
                    return;
                }

                const h = Math.floor(diff / (1000 * 60 * 60));
                const m = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
                const s = Math.floor((diff % (1000 * 60)) / 1000);

                countdownEl.innerHTML =
                    String(h).padStart(2, '0') + ':' +
                    String(m).padStart(2, '0') + ':' +
                    String(s).padStart(2, '0');
            }, 1000);
        </script>
    <?php endif; ?>

    <div class="modal fade" id="cekBookingModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">

                <div class="modal-header">
                    <h5 class="modal-title">Cek Status Booking</h5>
                    <button class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">

                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label">Kode Booking</label>
                            <input type="text" name="kode_booking" class="form-control"
                                placeholder="Contoh: FTS-20251223-1234" required>
                        </div>

                        <button type="submit" name="cek_booking" class="btn btn-primary w-100">
                            Cek Booking
                        </button>
                    </form>

                    <!-- HASIL CEK -->
                    <?php if (!empty($cekResult)): ?>
                        <hr>
                        <?= $cekResult ?>
                    <?php endif; ?>

                </div>

            </div>
        </div>
    </div>

    <?php if (isset($_POST['cek_booking'])): ?>
        <script>
            new bootstrap.Modal(document.getElementById('cekBookingModal')).show();
        </script>
    <?php endif; ?>

</body>

</html>