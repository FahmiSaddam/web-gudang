<?php
$page_title = 'Koreksi Stok';
require 'config/database.php';
include 'includes/header.php';

// Proteksi Halaman: Hanya Supervisor dan Manajer yang boleh mengakses
if ($_SESSION['role'] !== 'supervisor' && $_SESSION['role'] !== 'manajer') {
    // Tampilkan pesan error dan hentikan script
    echo '<div class="alert alert-danger">Anda tidak memiliki hak akses untuk membuka halaman ini.</div>';
    include 'includes/footer.php';
    exit();
}

// Ambil data stok yang ada untuk dropdown
try {
    $stmt = $pdo->query("SELECT s.produk_id, s.lokasi_id, p.sku, p.nama_produk, l.kode_lokasi, s.jumlah
                         FROM stok s
                         JOIN produk p ON s.produk_id = p.id
                         JOIN lokasi_penyimpanan l ON s.lokasi_id = l.id
                         ORDER BY p.nama_produk, l.kode_lokasi");
    $stok_list = $stmt->fetchAll();
} catch (PDOException $e) {
    die("Error mengambil data: " . $e->getMessage());
}


$success_message = '';
$error_message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $stok_id_parts = explode('-', $_POST['stok_id']);
    $produk_id = $stok_id_parts[0];
    $lokasi_id = $stok_id_parts[1];
    $jumlah_baru = filter_var($_POST['jumlah_baru'], FILTER_VALIDATE_INT);
    $alasan = trim($_POST['alasan']);
    $user_id = $_SESSION['user_id'];

    if ($jumlah_baru === false || $jumlah_baru < 0) {
        $error_message = "Jumlah baru harus berupa angka (0 atau lebih).";
    } elseif (empty($produk_id) || empty($lokasi_id) || empty($alasan)) {
        $error_message = "Data tidak lengkap. Pastikan semua field terisi.";
    } else {
        $pdo->beginTransaction();
        try {
            // 1. Ambil jumlah stok saat ini untuk logging
            $stmt_cek = $pdo->prepare("SELECT jumlah FROM stok WHERE produk_id = ? AND lokasi_id = ?");
            $stmt_cek->execute([$produk_id, $lokasi_id]);
            $jumlah_sebelum = $stmt_cek->fetchColumn();

            // 2. Update stok dengan jumlah baru
            $stmt_update = $pdo->prepare("UPDATE stok SET jumlah = ? WHERE produk_id = ? AND lokasi_id = ?");
            $stmt_update->execute([$jumlah_baru, $produk_id, $lokasi_id]);

            // 3. Catat transaksi koreksi
            $stmt_koreksi = $pdo->prepare("INSERT INTO transaksi_koreksi (produk_id, lokasi_id, jumlah_sebelum, jumlah_sesudah, alasan, user_id) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt_koreksi->execute([$produk_id, $lokasi_id, $jumlah_sebelum, $jumlah_baru, $alasan, $user_id]);

            // 4. Catat ke audit trail
            $aksi = "Koreksi stok produk ID {$produk_id} dari {$jumlah_sebelum} menjadi {$jumlah_baru}. Alasan: {$alasan}";
            $stmt_audit = $pdo->prepare("INSERT INTO audit_trail (user_id, aksi) VALUES (?, ?)");
            $stmt_audit->execute([$user_id, $aksi]);

            $pdo->commit();
            $success_message = "Koreksi Stok Berhasil!";
        } catch (Exception $e) {
            $pdo->rollBack();
            $error_message = "Gagal melakukan koreksi: " . $e->getMessage();
        }
    }
}

?>
<h1 class="mb-4">Form Koreksi Stok</h1>
<?php if ($success_message): ?><div class="alert alert-success"><?php echo htmlspecialchars($success_message); ?></div><?php endif; ?>
<?php if ($error_message): ?><div class="alert alert-danger"><?php echo htmlspecialchars($error_message); ?></div><?php endif; ?>

<div class="card">
    <div class="card-header">Penyesuaian Jumlah Stok</div>
    <div class="card-body">
        <form action="koreksi_stok.php" method="post">
            <div class="row g-3">
                <div class="col-12">
                    <label for="stok_id" class="form-label">Produk & Lokasi <span class="text-danger">*</span></label>
                    <select class="form-select" name="stok_id" required>
                        <option value="">Pilih produk yang akan dikoreksi...</option>
                        <?php foreach($stok_list as $stok): ?>
                        <option value="<?php echo $stok['produk_id'] . '-' . $stok['lokasi_id']; ?>">
                             <?php echo htmlspecialchars($stok['sku'] . ' - ' . $stok['nama_produk'] . ' (Lokasi: ' . $stok['kode_lokasi'] . ', Stok Saat Ini: ' . $stok['jumlah'] . ')'); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                 <div class="col-md-4">
                    <label for="jumlah_baru" class="form-label">Jumlah Seharusnya (Jumlah Baru) <span class="text-danger">*</span></label>
                    <input type="number" class="form-control" name="jumlah_baru" min="0" required>
                </div>
                <div class="col-md-8">
                    <label for="alasan" class="form-label">Alasan Koreksi <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" name="alasan" required>
                </div>
                 <div class="col-12 mt-4">
                    <button type="submit" class="btn btn-warning">Simpan Koreksi</button>
                </div>
            </div>
        </form>
    </div>
</div>

<?php include 'includes/footer.php'; ?>