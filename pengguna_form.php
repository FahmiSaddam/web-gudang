<?php
$page_title = 'Form Pengguna';
require 'config/database.php';
include 'includes/header.php';

// Proteksi Halaman
if ($_SESSION['role'] !== 'manajer') { /* ... (sama seperti sebelumnya) ... */ exit(); }

$user = ['id' => '', 'username' => '', 'nama_lengkap' => '', 'role' => 'operator'];
$is_edit = false;

if (isset($_GET['id']) && !empty($_GET['id'])) {
    $is_edit = true;
    $stmt = $pdo->prepare("SELECT id, username, nama_lengkap, role FROM users WHERE id = ?");
    $stmt->execute([$_GET['id']]);
    $user = $stmt->fetch();
    if (!$user) { /* ... user tidak ditemukan ... */ exit(); }
    $page_title = 'Edit Pengguna: ' . htmlspecialchars($user['nama_lengkap']);
} else {
    $page_title = 'Tambah Pengguna Baru';
}
?>

<h1 class="h3 mb-4 text-gray-800"><?php echo $page_title; ?></h1>
<div class="card shadow mb-4">
    <div class="card-body">
        <form action="pengguna_aksi.php" method="post" class="needs-validation" novalidate>
            <input type="hidden" name="id" value="<?php echo htmlspecialchars($user['id']); ?>">
            <input type="hidden" name="action" value="save">
            
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" class="form-control" name="username" value="<?php echo htmlspecialchars($user['username']); ?>" required>
            </div>
            <div class="form-group">
                <label for="nama_lengkap">Nama Lengkap</label>
                <input type="text" class="form-control" name="nama_lengkap" value="<?php echo htmlspecialchars($user['nama_lengkap']); ?>" required>
            </div>
            <div class="form-group">
                <label for="role">Peran (Role)</label>
                <select name="role" class="form-control" <?php echo ($user['role'] == 'manajer') ? 'disabled' : ''; ?>>
                    <option value="operator" <?php echo ($user['role'] == 'operator') ? 'selected' : ''; ?>>Operator</option>
                    <option value="supervisor" <?php echo ($user['role'] == 'supervisor') ? 'selected' : ''; ?>>Supervisor</option>
                    <?php if ($user['role'] == 'manajer'): ?>
                    <option value="manajer" selected>Manajer</option>
                    <?php endif; ?>
                </select>
                <?php if ($user['role'] == 'manajer'): ?>
                <small class="form-text text-muted">Peran Manajer tidak dapat diubah atau diturunkan.</small>
                <?php endif; ?>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" class="form-control" name="password" <?php echo $is_edit ? '' : 'required'; ?>>
                <?php if ($is_edit): ?>
                <small class="form-text text-muted">Biarkan kosong jika tidak ingin mengubah password.</small>
                <?php endif; ?>
            </div>
            <hr>
            <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
            <a href="manajemen_pengguna.php" class="btn btn-secondary">Batal</a>
        </form>
    </div>
</div>
<?php include 'includes/footer.php'; ?>