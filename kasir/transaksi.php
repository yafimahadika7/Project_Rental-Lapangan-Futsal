<?php
require_once '../config/auth.php';
require_once '../config/koneksi.php';

if ($_SESSION['role'] !== 'kasir') {
    echo "Akses ditolak";
    exit;
}

$page_title = "Transaksi Booking";

/* ==============================
   KONFIRMASI DP
============================== */
if (isset($_POST['konfirmasi_dp'])) {
    $id = $_POST['id'];

    $stmt = $koneksi->prepare("
        UPDATE booking 
        SET status='DP'
        WHERE id=? AND status='MENUNGGU_DP'
    ");
    $stmt->bind_param("i", $id);
    $stmt->execute();

    header("Location: transaksi.php");
    exit;
}

/* ==============================
   KONFIRMASI LUNAS
============================== */
if (isset($_POST['konfirmasi_lunas'])) {
    $id = $_POST['id'];

    $stmt = $koneksi->prepare("
        UPDATE booking 
        SET status='LUNAS'
        WHERE id=? AND status='DP'
    ");
    $stmt->bind_param("i", $id);
    $stmt->execute();

    header("Location: transaksi.php");
    exit;
}

/* ==============================
   FILTER & SEARCH
============================== */
$where = [];
$params = [];
$types = "";

$search = $_GET['search'] ?? '';
$dari = $_GET['dari'] ?? '';
$sampai = $_GET['sampai'] ?? '';

if ($search != '') {
    $where[] = "(b.kode_booking LIKE ? OR b.nama_pelanggan LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $types .= "ss";
}

if ($dari != '' && $sampai != '') {
    $where[] = "b.tanggal BETWEEN ? AND ?";
    $params[] = $dari;
    $params[] = $sampai;
    $types .= "ss";
}

$whereSQL = $where ? "WHERE " . implode(" AND ", $where) : "";

/* ==============================
   QUERY DATA BOOKING
============================== */
$sql = "
    SELECT b.*, l.nama AS lapangan
    FROM booking b
    JOIN lapangan l ON b.lapangan_id = l.id
    $whereSQL
    ORDER BY b.created_at DESC
";

$stmt = $koneksi->prepare($sql);
if ($params) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$data = $stmt->get_result();

/* ==============================
   TOTAL TRANSAKSI (DP + LUNAS)
============================== */
/* ==============================
   TOTAL TRANSAKSI (DP + LUNAS)
============================== */
$whereTotal = $where;
$whereTotal[] = "b.status IN ('DP','LUNAS')";

$whereTotalSQL = "WHERE " . implode(" AND ", $whereTotal);

$sqlTotal = "
    SELECT SUM(b.dp_wajib) AS total
    FROM booking b
    $whereTotalSQL
";

$stmtTotal = $koneksi->prepare($sqlTotal);

if ($params) {
    $stmtTotal->bind_param($types, ...$params);
}

$stmtTotal->execute();
$total = $stmtTotal->get_result()->fetch_assoc()['total'] ?? 0;

$stmtTotal = $koneksi->prepare($sqlTotal);
if ($params) {
    $stmtTotal->bind_param($types, ...$params);
}
$stmtTotal->execute();
$total = $stmtTotal->get_result()->fetch_assoc()['total'] ?? 0;

ob_start();
?>

<h4 class="mb-4">
    <i class="bi bi-receipt-cutoff me-2"></i> Transaksi Booking
</h4>

<!-- FORM FILTER -->
<form method="GET" class="row g-2 mb-3">

    <div class="col-md-4">
        <input type="text" name="search" class="form-control" placeholder="Cari kode / nama pelanggan"
            value="<?= htmlspecialchars($search) ?>">
    </div>

    <div class="col-md-3">
        <input type="date" name="dari" class="form-control" value="<?= $dari ?>">
    </div>

    <div class="col-md-3">
        <input type="date" name="sampai" class="form-control" value="<?= $sampai ?>">
    </div>

    <div class="col-md-2 d-grid">
        <button class="btn btn-primary">
            <i class="bi bi-search"></i> Cari
        </button>
    </div>

</form>

<!-- TOTAL -->
<div class="alert alert-info mb-3">
    <strong>Total Transaksi:</strong>
    Rp <?= number_format($total, 0, ',', '.') ?>
</div>

<!-- TABLE -->
<div class="card shadow-sm">
    <div class="card-body p-0">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Kode</th>
                    <th>Pelanggan</th>
                    <th>Lapangan</th>
                    <th>Tanggal</th>
                    <th>Jam</th>
                    <th>DP</th>
                    <th>Status</th>
                    <th class="text-center">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($b = $data->fetch_assoc()): ?>
                    <tr>
                        <td><strong><?= $b['kode_booking'] ?></strong></td>
                        <td><?= htmlspecialchars($b['nama_pelanggan']) ?></td>
                        <td><?= $b['lapangan'] ?></td>
                        <td><?= date('d M Y', strtotime($b['tanggal'])) ?></td>
                        <td>
                            <?= substr($b['jam_mulai'], 0, 5) ?> -
                            <?= substr($b['jam_selesai'], 0, 5) ?>
                        </td>
                        <td>Rp <?= number_format($b['dp_wajib'], 0, ',', '.') ?></td>
                        <td>
                            <?php
                            $badge = [
                                'MENUNGGU_DP' => 'warning',
                                'DP' => 'info',
                                'LUNAS' => 'success',
                                'GAGAL' => 'secondary'
                            ];
                            ?>
                            <span class="badge bg-<?= $badge[$b['status']] ?>">
                                <?= str_replace('_', ' ', $b['status']) ?>
                            </span>
                        </td>
                        <td class="text-center">

                            <?php if ($b['status'] === 'MENUNGGU_DP'): ?>
                                <button class="btn btn-sm btn-outline-warning" data-bs-toggle="modal"
                                    data-bs-target="#modalDP<?= $b['id'] ?>">
                                    Konfirmasi DP
                                </button>

                            <?php elseif ($b['status'] === 'DP'): ?>
                                <button class="btn btn-sm btn-outline-success" data-bs-toggle="modal"
                                    data-bs-target="#modalLunas<?= $b['id'] ?>">
                                    Lunasi
                                </button>
                            <?php else: ?>
                                <span class="text-muted small">-</span>
                            <?php endif; ?>

                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<?php
/* ==============================
   MODAL DP & LUNAS
============================== */
$data->data_seek(0);
while ($b = $data->fetch_assoc()):
    ?>

    <!-- MODAL DP -->
    <div class="modal fade" id="modalDP<?= $b['id'] ?>" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <form method="POST" class="modal-content">
                <div class="modal-header bg-warning">
                    <h5 class="modal-title text-dark">Konfirmasi DP</h5>
                    <button class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="id" value="<?= $b['id'] ?>">
                    <p><strong><?= $b['kode_booking'] ?></strong></p>
                    <p>DP: Rp <?= number_format($b['dp_wajib'], 0, ',', '.') ?></p>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button name="konfirmasi_dp" class="btn btn-warning">
                        DP Diterima
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL LUNAS -->
    <div class="modal fade" id="modalLunas<?= $b['id'] ?>" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <form method="POST" class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title">Konfirmasi Pelunasan</h5>
                    <button class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="id" value="<?= $b['id'] ?>">
                    <p><strong><?= $b['kode_booking'] ?></strong></p>
                    <p>Status akan menjadi <strong>LUNAS</strong></p>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button name="konfirmasi_lunas" class="btn btn-success">
                        Lunasi
                    </button>
                </div>
            </form>
        </div>
    </div>

<?php endwhile; ?>

<?php
$content = ob_get_clean();
require 'layout.php';
