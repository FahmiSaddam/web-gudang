<?php
$page_title = 'Penerimaan Barang';
require 'config/database.php';
include 'includes/header.php';

// Proteksi Halaman
if (!in_array($_SESSION['role'], ['operator', 'supervisor', 'manajer'])) {
    echo '<div class="alert alert-danger mx-3">Akses Ditolak. Anda tidak memiliki izin untuk mengakses halaman ini.</div>';
    include 'includes/footer.php';
    exit();
}

$success_message = '';
$error_message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $pdo->beginTransaction();
    try {
        $produk_id = null;
        $lokasi_id = null;

        if (isset($_POST['is_new_product']) && $_POST['is_new_product'] == 'on') {
            $sku = trim($_POST['new_sku']);
            $nama_produk = trim($_POST['new_nama_produk']);
            if (empty($sku) || empty($nama_produk)) throw new Exception("SKU dan Nama Produk baru tidak boleh kosong.");
            $stmt = $pdo->prepare("INSERT INTO produk (sku, nama_produk, deskripsi) VALUES (?, ?, ?)");
            $stmt->execute([$sku, $nama_produk, trim($_POST['new_deskripsi'])]);
            $produk_id = $pdo->lastInsertId();
        } else {
            $produk_id = $_POST['produk_id'];
            if(empty($produk_id)) throw new Exception("Produk yang sudah ada belum dipilih.");
        }

        if (isset($_POST['is_new_location']) && $_POST['is_new_location'] == 'on') {
            $kode_lokasi = trim($_POST['new_kode_lokasi']);
            if (empty($kode_lokasi)) throw new Exception("Kode Lokasi baru tidak boleh kosong.");
            $stmt = $pdo->prepare("INSERT INTO lokasi_penyimpanan (kode_lokasi, deskripsi) VALUES (?, ?)");
            $stmt->execute([$kode_lokasi, trim($_POST['new_deskripsi_lokasi'])]);
            $lokasi_id = $pdo->lastInsertId();
        } else {
            $lokasi_id = $_POST['lokasi_id'];
            if(empty($lokasi_id)) throw new Exception("Lokasi yang sudah ada belum dipilih.");
        }

        $pemasok = trim($_POST['pemasok']);
        $no_referensi = trim($_POST['no_referensi']);
        $jumlah = filter_var($_POST['jumlah'], FILTER_VALIDATE_INT);
        if ($jumlah === false || $jumlah <= 0) throw new Exception("Kuantitas tidak valid.");
        
        $stmt_trans = $pdo->prepare("INSERT INTO transaksi_penerimaan (nomor_referensi, pemasok, produk_id, jumlah, lokasi_id, user_id) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt_trans->execute([$no_referensi, $pemasok, $produk_id, $jumlah, $lokasi_id, $_SESSION['user_id']]);
        
        $stmt_cek = $pdo->prepare("SELECT id FROM stok WHERE produk_id = ? AND lokasi_id = ?");
        $stmt_cek->execute([$produk_id, $lokasi_id]);
        if ($stok_exist = $stmt_cek->fetch()) {
            $stmt_update = $pdo->prepare("UPDATE stok SET jumlah = jumlah + ? WHERE id = ?");
            $stmt_update->execute([$jumlah, $stok_exist['id']]);
        } else {
            $stmt_insert = $pdo->prepare("INSERT INTO stok (produk_id, lokasi_id, jumlah) VALUES (?, ?, ?)");
            $stmt_insert->execute([$produk_id, $lokasi_id, $jumlah]);
        }

        $aksi = "Penerimaan: {$jumlah} unit (Produk ID {$produk_id}) ke (Lokasi ID {$lokasi_id}). Ref: '{$no_referensi}'.";
        $stmt_audit = $pdo->prepare("INSERT INTO audit_trail (user_id, aksi) VALUES (?, ?)");
        $stmt_audit->execute([$_SESSION['user_id'], $aksi]);

        $pdo->commit();
        $success_message = "Penerimaan Barang Berhasil Disimpan!";
    } catch (Exception $e) {
        $pdo->rollBack();
        $error_message = "Terjadi Kesalahan: " . $e->getMessage();
    }
}

$produks = $pdo->query("SELECT id, sku, nama_produk FROM produk ORDER BY nama_produk")->fetchAll();
$lokasis = $pdo->query("SELECT id, kode_lokasi FROM lokasi_penyimpanan ORDER BY kode_lokasi")->fetchAll();
?>

<h1 class="h3 mb-4 text-gray-800">Form Penerimaan Barang</h1>
<?php if ($success_message): ?><div class="alert alert-success"><?php echo htmlspecialchars($success_message); ?></div><?php endif; ?>
<?php if ($error_message): ?><div class="alert alert-danger"><?php echo htmlspecialchars($error_message); ?></div><?php endif; ?>

<div class="card shadow mb-4">
    <div class="card-header py-3"><h6 class="m-0 font-weight-bold text-primary">Input Data Penerimaan</h6></div>
    <div class="card-body">
        <form action="penerimaan_barang.php" method="post" class="needs-validation" novalidate>
            <div class="form-row">
                <div class="form-group col-md-6"><label for="pemasok">Nama Pemasok</label><input type="text" class="form-control" id="pemasok" name="pemasok"></div>
                <div class="form-group col-md-6"><label for="no_referensi">Nomor Referensi <span class="text-danger">*</span></label><input type="text" class="form-control" id="no_referensi" name="no_referensi" required></div>
            </div><hr>
            <div class="form-group"><div class="custom-control custom-checkbox"><input type="checkbox" class="custom-control-input" id="is_new_product" name="is_new_product"><label class="custom-control-label" for="is_new_product">Produk Baru?</label></div></div>
            <div id="existing_product_section">
                <div class="form-group"><label for="produk_id">Produk (SKU)</label><select class="form-control" id="produk_id" name="produk_id"><option value="">Pilih Produk...</option><?php foreach($produks as $produk): ?><option value="<?php echo $produk['id']; ?>"><?php echo htmlspecialchars($produk['sku'] . ' - ' . $produk['nama_produk']); ?></option><?php endforeach; ?></select></div>
            </div>
            <div id="new_product_section" style="display: none;">
                <div class="form-row">
                    <div class="form-group col-md-4"><label for="new_sku">SKU Baru (Contoh: SKU-000)<span class="text-danger">*</span></label><input type="text" class="form-control" id="new_sku" name="new_sku"></div>
                    <div class="form-group col-md-8"><label for="new_nama_produk">Nama Produk Baru <span class="text-danger">*</span></label><input type="text" class="form-control" id="new_nama_produk" name="new_nama_produk"></div>
                </div>
                <div class="form-group"><label for="new_deskripsi">Deskripsi Produk</label><textarea class="form-control" id="new_deskripsi" name="new_deskripsi" rows="2"></textarea></div>
            </div><hr>
            <div class="form-group"><div class="custom-control custom-checkbox"><input type="checkbox" class="custom-control-input" id="is_new_location" name="is_new_location"><label class="custom-control-label" for="is_new_location">Lokasi Baru?</label></div></div>
            <div id="existing_location_section">
                <div class="form-group"><label for="lokasi_id">Simpan ke Lokasi</label><select class="form-control" id="lokasi_id" name="lokasi_id"><option value="">Pilih Lokasi...</option><?php foreach($lokasis as $lokasi): ?><option value="<?php echo $lokasi['id']; ?>"><?php echo htmlspecialchars($lokasi['kode_lokasi']); ?></option><?php endforeach; ?></select></div>
            </div>
            <div id="new_location_section" style="display: none;">
                <div class="form-row">
                    <div class="form-group col-md-8"><label for="new_kode_lokasi">Kode Lokasi Baru (Contoh: A1-R1-B1 >> Area A- Rak 1- Baris 1)<span class="text-danger">*</span></label><input type="text" class="form-control" id="new_kode_lokasi" name="new_kode_lokasi"></div>
                    <div class="form-group col-md-8"><label for="new_deskripsi_lokasi">Deskripsi Lokasi</label><input type="text" class="form-control" id="new_deskripsi_lokasi" name="new_deskripsi_lokasi"></div>
                </div>
            </div><hr>
            <div class="form-row"><div class="form-group col-md-6"><label for="jumlah">Kuantitas <span class="text-danger">*</span></label><input type="number" class="form-control" id="jumlah" name="jumlah" min="1" required></div></div>
            <button type="submit" class="btn btn-primary">Simpan Data</button>
        </form>
    </div>
</div>
<script>
function setupFormToggle(checkboxId, existingSectionId, newSectionId, requiredIds) {
    var checkbox = document.getElementById(checkboxId), existingSection = document.getElementById(existingSectionId), newSection = document.getElementById(newSectionId);
    function toggleSections() { var isChecked = checkbox.checked; existingSection.style.display = isChecked ? 'none' : 'block'; newSection.style.display = isChecked ? 'block' : 'none'; requiredIds.existing.forEach(id => document.getElementById(id).required = !isChecked); requiredIds.new.forEach(id => document.getElementById(id).required = isChecked); }
    checkbox.addEventListener('change', toggleSections); toggleSections();
}
setupFormToggle('is_new_product', 'existing_product_section', 'new_product_section', { existing: ['produk_id'], new: ['new_sku', 'new_nama_produk'] });
setupFormToggle('is_new_location', 'existing_location_section', 'new_location_section', { existing: ['lokasi_id'], new: ['new_kode_lokasi'] });
</script>
<?php include 'includes/footer.php'; ?>