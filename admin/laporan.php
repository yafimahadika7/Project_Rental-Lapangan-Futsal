<?php
require_once '../config/koneksi.php';

$tgl_awal = $_GET['tgl_awal'] ?? '';
$tgl_akhir = $_GET['tgl_akhir'] ?? '';

$where = "";
$params = [];

if ($tgl_awal && $tgl_akhir) {
    $where = "WHERE tanggal BETWEEN ? AND ?";
    $params = [$tgl_awal, $tgl_akhir];
}

$sql = "
    SELECT b.*, l.nama AS nama_lapangan
    FROM booking b
    JOIN lapangan l ON b.lapangan_id = l.id
    $where
    ORDER BY b.tanggal DESC, b.jam_mulai DESC
";

$stmt = $koneksi->prepare($sql);

if ($params) {
    $stmt->bind_param("ss", ...$params);
}

$stmt->execute();
$data = $stmt->get_result();

ob_start();
?>

<h3 class="fw-semibold mb-4">
    <i class="bi bi-bar-chart-line me-2 text-primary"></i>
    Laporan Booking
</h3>

<!-- FILTER -->
<div class="card shadow-sm border-0 mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3 align-items-end">
            <div class="col-md-4">
                <label class="form-label">Tanggal Awal</label>
                <input type="date" name="tgl_awal" class="form-control" value="<?= htmlspecialchars($tgl_awal) ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label">Tanggal Akhir</label>
                <input type="date" name="tgl_akhir" class="form-control" value="<?= htmlspecialchars($tgl_akhir) ?>">
            </div>
            <div class="col-md-4">
                <button class="btn btn-primary w-100">
                    <i class="bi bi-search me-1"></i> Tampilkan
                </button>
            </div>
        </form>
    </div>
</div>

<!-- TABEL LAPORAN -->
<div class="card shadow-sm border-0">
    <div class="card-body table-responsive">
        <table class="table table-bordered align-middle">
            <thead class="table-light">
                <tr>
                    <th>No</th>
                    <th>Kode Booking</th>
                    <th>Lapangan</th>
                    <th>Tanggal</th>
                    <th>Jam</th>
                    <th>Pelanggan</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($data->num_rows == 0): ?>
                    <tr>
                        <td colspan="7" class="text-center text-muted">
                            Tidak ada data booking
                        </td>
                    </tr>
                <?php endif; ?>

                <?php $no = 1;
                while ($row = $data->fetch_assoc()): ?>

                    <?php
                    switch ($row['status']) {
                        case 'MENUNGGU_DP':
                            $badge = 'warning';
                            break;

                        case 'DP':
                            $badge = 'info';
                            break;

                        case 'LUNAS':
                            $badge = 'success';
                            break;

                        case 'GAGAL':
                            $badge = 'danger';
                            break;

                        default:
                            $badge = 'secondary';
                            break;
                    }
                    ?>

                    <tr>
                        <td><?= $no++ ?></td>
                        <td><?= $row['kode_booking'] ?></td>
                        <td><?= $row['nama_lapangan'] ?></td>
                        <td><?= date('d/m/Y', strtotime($row['tanggal'])) ?></td>
                        <td>
                            <?= substr($row['jam_mulai'], 0, 5) ?> -
                            <?= substr($row['jam_selesai'], 0, 5) ?>
                        </td>
                        <td>
                            <?= $row['nama_pelanggan'] ?><br>
                            <small class="text-muted"><?= $row['no_hp'] ?></small>
                        </td>
                        <td>
                            <span class="badge bg-<?= $badge ?>">
                                <?= str_replace('_', ' ', $row['status']) ?>
                            </span>
                        </td>
                    </tr>

                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<?php
$content = ob_get_clean();
include 'layout.php';
