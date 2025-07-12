<?php
$page_title = 'Pengeluaran Barang';
require 'config/database.php';
include 'includes/header.php';

// Ambil data stok yang tersedia untuk datalist
$stok_tersedia = $pdo->query("SELECT s.produk_id, s.lokasi_id, p.sku, p.nama_produk, l.kode_lokasi, s.jumlah 
                              FROM stok s
                              JOIN produk p ON s.produk_id = p.id
                              JOIN lokasi_penyimpanan l ON s.lokasi_id = l.id
                              WHERE s.jumlah > 0
                              ORDER BY p.nama_produk, l.kode_lokasi")->fetchAll();

// ... (Logika PHP untuk proses POST sama persis seperti sebelumnya) ...
$success_message = '';
$error_message = '';
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Cari produk_id dan lokasi_id dari input
    $stok_id_parts = explode('-', $_POST['produk_lokasi']);
    $produk_id = $stok_id_parts[0] ?? null;
    $lokasi_id = $stok_id_parts[1] ?? null;

    $tujuan = trim($_POST['tujuan']);
    $no_referensi = trim($_POST['no_referensi']);
    $jumlah = filter_var($_POST['jumlah'], FILTER_VALIDATE_INT);
    $user_id = $_SESSION['user_id'];

    if (empty($produk_id) || empty($lokasi_id)){
        $error_message = "Produk yang dipilih tidak valid. Harap pilih dari daftar.";
    } else {
        // ... Logika Transaksi (Cek Stok, Kurangi Stok, Catat Transaksi, Audit) sama seperti sebelumnya ...
        $pdo->beginTransaction();
        try {
            $stmt_cek = $pdo->prepare("SELECT jumlah FROM stok WHERE produk_id = ? AND lokasi_id = ? FOR UPDATE");
            $stmt_cek->execute([$produk_id, $lokasi_id]);
            $stok_saat_ini = $stmt_cek->fetchColumn();

            if ($stok_saat_ini === false || $stok_saat_ini < $jumlah) {
                throw new Exception("Stok tidak mencukupi! Stok tersedia: " . intval($stok_saat_ini) . ".");
            }
            $stmt_update = $pdo->prepare("UPDATE stok SET jumlah = jumlah - ? WHERE produk_id = ? AND lokasi_id = ?");
            $stmt_update->execute([$jumlah, $produk_id, $lokasi_id]);
            
            $stmt_trans = $pdo->prepare("INSERT INTO transaksi_pengeluaran (nomor_referensi, tujuan, produk_id, jumlah, lokasi_id, user_id) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt_trans->execute([$no_referensi, $tujuan, $produk_id, $jumlah, $lokasi_id, $user_id]);

            $aksi = "Pengeluaran barang: {$jumlah} unit produk ID {$produk_id} untuk '{$tujuan}'.";
            $stmt_audit = $pdo->prepare("INSERT INTO audit_trail (user_id, aksi) VALUES (?, ?)");
            $stmt_audit->execute([$user_id, $aksi]);

            $pdo->commit();
            $success_message = "Pengeluaran Barang Berhasil!";
        } catch (Exception $e) {
            $pdo->rollBack();
            $error_message = "Gagal: " . $e->getMessage();
        }
    }
}
?>

<h1 class="h3 mb-4 text-gray-800">Form Pengeluaran Barang</h1>
<?php if ($success_message): ?><div class="alert alert-success"><?php echo htmlspecialchars($success_message); ?></div><?php endif; ?>
<?php if ($error_message): ?><div class="alert alert-danger"><?php echo htmlspecialchars($error_message); ?></div><?php endif; ?>

<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">Input Data Pengeluaran</h6>
    </div>
    <div class="card-body">
        <form action="pengeluaran_barang.php" method="post">
            <div class="form-row">
                <div class="form-group col-md-6">
                    <label for="tujuan">Tujuan Pengiriman</label>
                    <input type="text" class="form-control" id="tujuan" name="tujuan">
                </div>
                <div class="form-group col-md-6">
                    <label for="no_referensi">Nomor Referensi (mis: No. DO) <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="no_referensi" name="no_referensi" required>
                </div>
            </div>
            <hr>
            <div class="form-row">
                <div class="form-group col-md-9">
                    <label for="produk_input">Cari Produk (SKU atau Nama) <span class="text-danger">*</span></label>
                    <input type="text" list="produk_list" class="form-control" id="produk_input" name="produk_pilihan" placeholder="Ketik untuk mencari..." required>
                    <datalist id="produk_list">
                        <?php foreach($stok_tersedia as $stok): ?>
                        <option data-value="<?php echo $stok['produk_id'] . '-' . $stok['lokasi_id']; ?>">
                            <?php echo htmlspecialchars($stok['sku'] . ' - ' . $stok['nama_produk'] . ' (Lokasi: ' . $stok['kode_lokasi'] . ', Stok: ' . $stok['jumlah'] . ')'); ?>
                        </option>
                        <?php endforeach; ?>
                    </datalist>
                    <input type="hidden" name="produk_lokasi" id="produk_lokasi_hidden">
                </div>
                 <div class="form-group col-md-3">
                    <label for="jumlah">Kuantitas <span class="text-danger">*</span></label>
                    <input type="number" class="form-control" id="jumlah" name="jumlah" min="1" required>
                </div>
            </div>
            <button type="submit" class="btn btn-primary">Proses Pengeluaran</button>
        </form>
    </div>
</div>

<script>
// Skrip untuk menangani datalist, agar mengirimkan value yang benar
document.querySelector('#produk_input').addEventListener('input', function(e) {
    var input = e.target,
        list = input.getAttribute('list'),
        options = document.querySelectorAll('#' + list + ' option');
        hiddenInput = document.getElementById('produk_lokasi_hidden');

    hiddenInput.value = ''; // Reset
    for(var i = 0; i < options.length; i++) {
        var option = options[i];
        if(option.innerText.trim() === input.value.trim()) {
            hiddenInput.value = option.getAttribute('data-value');
            break;
        }
    }
});
</script>

<?php include 'includes/footer.php'; ?>