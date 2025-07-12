<?php
$page_title = 'Manajemen Pengguna';
require 'config/database.php';
include 'includes/header.php';

// Proteksi Halaman
if ($_SESSION['role'] !== 'manajer') {
    echo '<div class="alert alert-danger">Akses ditolak.</div>';
    include 'includes/footer.php';
    exit();
}

$users = $pdo->query("SELECT id, username, nama_lengkap, role FROM users ORDER BY role, username")->fetchAll();
?>
<h1 class="h3 mb-4 text-gray-800">Manajemen Pengguna</h1>
<div class="card shadow mb-4">
    <div class="card-header py-3 d-flex justify-content-between align-items-center">
        <h6 class="m-0 font-weight-bold text-primary">Daftar Pengguna Sistem</h6>
        <a href="pengguna_form.php" class="btn btn-primary btn-sm"><i class="fas fa-plus fa-sm"></i> Tambah Pengguna</a>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered" width="100%" cellspacing="0">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Username</th>
                        <th>Nama Lengkap</th>
                        <th>Peran</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($users as $user): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($user['id']); ?></td>
                        <td><?php echo htmlspecialchars($user['username']); ?></td>
                        <td><?php echo htmlspecialchars($user['nama_lengkap']); ?></td>
                        <td><span class="badge badge-info"><?php echo htmlspecialchars(ucfirst($user['role'])); ?></span></td>
                        <td>
                            <a href="pengguna_form.php?id=<?php echo $user['id']; ?>" class="btn btn-warning btn-sm"><i class="fas fa-edit"></i></a>
                            <?php if ($_SESSION['user_id'] != $user['id'] && $user['role'] != 'manajer'): ?>
                            <a href="pengguna_aksi.php?action=delete&id=<?php echo $user['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Anda yakin ingin menghapus pengguna ini?');"><i class="fas fa-trash"></i></a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php include 'includes/footer.php'; ?>