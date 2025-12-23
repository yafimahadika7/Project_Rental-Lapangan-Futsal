<?php
require_once '../config/auth.php';
require_once '../config/koneksi.php';

$page_title = "Manajemen User";

/* ==============================
   TAMBAH USER
============================== */
if (isset($_POST['tambah_user'])) {
    $nama = $_POST['nama'];
    $username = $_POST['username'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $role = $_POST['role'];

    $stmt = $koneksi->prepare("
        INSERT INTO users (nama, username, password, role)
        VALUES (?, ?, ?, ?)
    ");
    $stmt->bind_param("ssss", $nama, $username, $password, $role);
    $stmt->execute();

    header("Location: users.php");
    exit;
}

/* ==============================
   UPDATE ROLE USER
============================== */
if (isset($_POST['update_user'])) {

    $id = $_POST['id'];
    $nama = $_POST['nama'];
    $username = $_POST['username'];
    $role = $_POST['role'];
    $password = $_POST['password'];

    if (!empty($password)) {
        // jika password diisi → update password
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $koneksi->prepare("
            UPDATE users 
            SET nama=?, username=?, role=?, password=? 
            WHERE id=?
        ");
        $stmt->bind_param("ssssi", $nama, $username, $role, $hash, $id);
    } else {
        // jika password kosong → tidak diubah
        $stmt = $koneksi->prepare("
            UPDATE users 
            SET nama=?, username=?, role=? 
            WHERE id=?
        ");
        $stmt->bind_param("sssi", $nama, $username, $role, $id);
    }

    $stmt->execute();
    header("Location: users.php");
    exit;
}

/* ==============================
   HAPUS USER (KECUALI DIRI SENDIRI)
============================== */
if (isset($_GET['hapus'])) {
    $id = $_GET['hapus'];

    if ($id != $_SESSION['user_id']) {
        $stmt = $koneksi->prepare("DELETE FROM users WHERE id=?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
    }

    header("Location: users.php");
    exit;
}

/* ==============================
   AMBIL DATA USER
============================== */
$users = $koneksi->query("SELECT * FROM users ORDER BY id ASC");

ob_start();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h4><i class="bi bi-people me-2"></i> Manajemen User</h4>

    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalTambahUser">
        <i class="bi bi-plus-circle me-1"></i> Tambah User
    </button>
</div>

<div class="card shadow-sm">
    <div class="card-body p-0">
        <table class="table table-hover mb-0 align-middle">
            <thead class="table-light">
                <tr>
                    <th width="60">No</th>
                    <th>Nama</th>
                    <th>Username</th>
                    <th width="140">Role</th>
                    <th width="120" class="text-center">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php $no = 1;
                while ($u = $users->fetch_assoc()): ?>
                    <tr>
                        <td><?= $no++ ?></td>
                        <td><?= htmlspecialchars($u['nama']) ?></td>
                        <td><?= htmlspecialchars($u['username']) ?></td>
                        <td>
                            <span class="badge bg-<?= $u['role'] == 'admin' ? 'primary' : 'success' ?>">
                                <?= ucfirst($u['role']) ?>
                            </span>
                        </td>
                        <td class="text-center">
                            <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal"
                                data-bs-target="#modalEdit<?= $u['id'] ?>">
                                <i class="bi bi-pencil"></i>
                            </button>

                            <?php if ($u['id'] != $_SESSION['user_id']): ?>
                                <button class="btn btn-sm btn-outline-danger" data-bs-toggle="modal"
                                    data-bs-target="#modalHapus<?= $u['id'] ?>">
                                    <i class="bi bi-trash"></i>
                                </button>
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
   MODAL EDIT & HAPUS (DI LUAR TABLE!)
============================== */
$users->data_seek(0);
while ($u = $users->fetch_assoc()):
    ?>

    <!-- MODAL EDIT -->
    <!-- MODAL EDIT USER -->
    <div class="modal fade" id="modalEdit<?= $u['id'] ?>" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <form method="POST" class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="bi bi-pencil-square me-1"></i> Edit User
                    </h5>
                    <button class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <input type="hidden" name="id" value="<?= $u['id'] ?>">

                    <div class="mb-2">
                        <label class="form-label">Nama</label>
                        <input type="text" name="nama" class="form-control" value="<?= htmlspecialchars($u['nama']) ?>"
                            required>
                    </div>

                    <div class="mb-2">
                        <label class="form-label">Username</label>
                        <input type="text" name="username" class="form-control"
                            value="<?= htmlspecialchars($u['username']) ?>" required>
                    </div>

                    <div class="mb-2">
                        <label class="form-label">Password Baru</label>
                        <input type="password" name="password" class="form-control"
                            placeholder="Kosongkan jika tidak diubah">
                        <small class="text-muted">
                            Minimal 6 karakter
                        </small>
                    </div>

                    <div class="mb-2">
                        <label class="form-label">Role</label>
                        <select name="role" class="form-select" required>
                            <option value="admin" <?= $u['role'] == 'admin' ? 'selected' : '' ?>>
                                Admin
                            </option>
                            <option value="kasir" <?= $u['role'] == 'kasir' ? 'selected' : '' ?>>
                                Kasir
                            </option>
                        </select>
                    </div>
                </div>

                <div class="modal-footer">
                    <button class="btn btn-secondary" data-bs-dismiss="modal">
                        Batal
                    </button>
                    <button name="update_user" class="btn btn-primary">
                        Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL HAPUS -->
    <div class="modal fade" id="modalHapus<?= $u['id'] ?>" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title">Hapus User</h5>
                    <button class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    Yakin ingin menghapus user
                    <strong><?= $u['nama'] ?></strong>?
                </div>

                <div class="modal-footer">
                    <button class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <a href="?hapus=<?= $u['id'] ?>" class="btn btn-danger">
                        Ya, Hapus
                    </a>
                </div>
            </div>
        </div>
    </div>

<?php endwhile; ?>

<!-- MODAL TAMBAH USER -->
<div class="modal fade" id="modalTambahUser" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Tambah User</h5>
                <button class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                <input type="text" name="nama" class="form-control mb-2" placeholder="Nama" required>
                <input type="text" name="username" class="form-control mb-2" placeholder="Username" required>
                <input type="password" name="password" class="form-control mb-2" placeholder="Password" required>

                <select name="role" class="form-select" required>
                    <option value="">Pilih Role</option>
                    <option value="admin">Admin</option>
                    <option value="kasir">Kasir</option>
                </select>
            </div>

            <div class="modal-footer">
                <button class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <button name="tambah_user" class="btn btn-primary">Simpan</button>
            </div>
        </form>
    </div>
</div>

<?php
$content = ob_get_clean();
require 'layout.php';
