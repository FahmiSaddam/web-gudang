<?php
$page_title = 'Dashboard';
require 'config/database.php';
include 'includes/header.php'; // Header sudah berisi pengecekan login

// ===================================================================
// PENGAMBILAN DATA DINAMIS UNTUK KARTU DASBOR
// ===================================================================
try {
    // Query 1: Menghitung jumlah jenis produk unik yang ada di stok
    $stmt_jenis_produk = $pdo->query("SELECT COUNT(DISTINCT produk_id) FROM stok");
    $total_jenis_produk = $stmt_jenis_produk->fetchColumn();

    // Query 2: Menjumlahkan total kuantitas semua barang di stok
    $stmt_kuantitas_stok = $pdo->query("SELECT SUM(jumlah) FROM stok");
    $total_kuantitas_stok = $stmt_kuantitas_stok->fetchColumn();

} catch (PDOException $e) {
    // Jika terjadi error, set nilai default agar halaman tidak rusak
    $total_jenis_produk = 0;
    $total_kuantitas_stok = 0;
    // Bisa ditambahkan pencatatan error di sini jika perlu
}
?>

<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800">Dashboard</h1>
</div>

<div class="alert alert-info">
    Selamat datang kembali, <strong><?php echo htmlspecialchars($_SESSION['nama_lengkap']); ?></strong>!
    Anda login sebagai <strong><?php echo htmlspecialchars(ucfirst($_SESSION['role'])); ?></strong>.
</div>

<div class="row">

    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-primary shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                            Total Jenis Produk</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                            <?php echo number_format($total_jenis_produk ?? 0); ?> Produk
                        </div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-boxes fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-success shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                            Total Kuantitas Stok</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                            <?php echo number_format($total_kuantitas_stok ?? 0); ?> Unit
                        </div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-cubes fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-lg-12">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Tugas Anda Hari Ini</h6>
            </div>
            <div class="card-body">
                <?php if ($_SESSION['role'] === 'operator'): ?>
                    <p>Tugas utama Anda adalah mengelola penerimaan dan pengeluaran barang. Gunakan menu "Operasional" untuk mencatat setiap transaksi yang terjadi.</p>
                <?php elseif ($_SESSION['role'] === 'supervisor'): ?>
                    <p>Tugas utama Anda adalah melakukan tugas operasional, melakukan koreksi stok jika ada ketidaksesuaian data, dan memantau laporan. Gunakan menu "Operasional" dan "Administrasi & Laporan" untuk tugas Anda.</p>
                <?php elseif ($_SESSION['role'] === 'manajer'): ?>
                    <p>Anda memiliki akses penuh ke semua fitur sistem. Pantau operasional, administrasi, laporan, dan kelola pengguna melalui semua menu yang tersedia.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

</div>

<?php
include 'includes/footer.php';
?>