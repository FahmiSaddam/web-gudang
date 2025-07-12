<?php
$page_title = 'Laporan Stok';
require 'config/database.php';
include 'includes/header.php';

// Proteksi Halaman: Hanya Supervisor dan Manajer
if (!in_array($_SESSION['role'], ['supervisor', 'manajer'])) {
    echo '<div class="alert alert-danger mx-3">Akses Ditolak. Anda tidak memiliki izin untuk mengakses halaman ini.</div>';
    include 'includes/footer.php';
    exit();
}

// Ambil kata kunci pencarian dari URL
$search_term = $_GET['search'] ?? '';

try {
    // =================================================================================
    // PERUBAHAN UTAMA PADA QUERY SQL
    // 1. Menggunakan subquery untuk mengambil nama pemasok dari transaksi penerimaan terakhir
    //    untuk setiap produk.
    // 2. Membungkus query utama dalam sebuah "derived table" (bernama 'laporan')
    //    agar kita bisa melakukan pencarian (WHERE) pada kolom 'nama_pemasok'.
    // =================================================================================
    $sql = "
        SELECT * FROM (
            SELECT 
                p.sku, 
                p.nama_produk, 
                l.kode_lokasi, 
                s.jumlah,
                (SELECT tr.pemasok 
                 FROM transaksi_penerimaan tr 
                 WHERE tr.produk_id = s.produk_id 
                 ORDER BY tr.tgl_penerimaan DESC 
                 LIMIT 1) as nama_pemasok
            FROM stok s
            JOIN produk p ON s.produk_id = p.id
            JOIN lokasi_penyimpanan l ON s.lokasi_id = l.id
            WHERE s.jumlah > 0
        ) as laporan
    ";
    
    $params = [];
    if (!empty($search_term)) {
        // Menambahkan kondisi pencarian pada SKU, Nama Produk, ATAU Nama Pemasok
        $sql .= " WHERE (laporan.sku LIKE ? OR laporan.nama_produk LIKE ? OR laporan.nama_pemasok LIKE ?)";
        $params[] = "%" . $search_term . "%";
        $params[] = "%" . $search_term . "%";
        $params[] = "%" . $search_term . "%";
    }

    $sql .= " ORDER BY laporan.nama_produk, laporan.kode_lokasi";
    
    // Gunakan prepared statement untuk keamanan dari SQL Injection
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $laporan_stok = $stmt->fetchAll();

} catch (PDOException $e) {
    die("Error mengambil data: " . $e->getMessage());
}
?>

<h1 class="h3 mb-4 text-gray-800">Laporan Stok Barang</h1>

<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">Cari Stok</h6>
    </div>
    <div class="card-body">
        <form method="GET" action="laporan_stok.php">
            <div class="input-group">
                <input type="text" class="form-control" name="search" placeholder="Ketik SKU, Nama Barang, atau Nama Pemasok..." value="<?php echo htmlspecialchars($search_term); ?>">
                <div class="input-group-append">
                    <button class="btn btn-primary" type="submit">
                        <i class="fas fa-search fa-sm"></i> Cari
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">Daftar Stok Saat Ini</h6>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered table-hover" id="dataTable" width="100%" cellspacing="0">
                <thead class="thead-light">
                    <tr>
                        <th>SKU</th>
                        <th>Nama Produk</th>
                        <th>Nama Pemasok</th>
                        <th>Lokasi</th>
                        <th class="text-right">Jumlah</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($laporan_stok)): ?>
                        <tr>
                            <td colspan="5" class="text-center">
                                <?php if (!empty($search_term)): ?>
                                    Data dengan kata kunci "<?php echo htmlspecialchars($search_term); ?>" tidak ditemukan.
                                <?php else: ?>
                                    Tidak ada data stok yang tersedia.
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach($laporan_stok as $item): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($item['sku']); ?></td>
                            <td><?php echo htmlspecialchars($item['nama_produk']); ?></td>
                            <td><?php echo !empty($item['nama_pemasok']) ? htmlspecialchars($item['nama_pemasok']) : '-'; ?></td>
                            <td><?php echo htmlspecialchars($item['kode_lokasi']); ?></td>
                            <td class="text-right font-weight-bold"><?php echo htmlspecialchars($item['jumlah']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php
include 'includes/footer.php';
?>