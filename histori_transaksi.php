<?php
$page_title = 'Histori Transaksi';
require 'config/database.php';
include 'includes/header.php';

// Proteksi Halaman: Hanya Supervisor dan Manajer
if (!in_array($_SESSION['role'], ['supervisor', 'manajer'])) {
    echo '<div class="alert alert-danger mx-3">Akses Ditolak. Anda tidak memiliki izin untuk mengakses halaman ini.</div>';
    include 'includes/footer.php';
    exit();
}

try {
    // =================================================================================
    // PERUBAHAN QUERY SQL
    // 1. Menambahkan `t.pemasok` dari transaksi_penerimaan.
    // 2. Menambahkan `NULL` atau `''` pada tabel lain agar struktur kolom sama.
    // 3. Memisahkan `referensi` dan `alasan` menjadi kolom terpisah.
    // =================================================================================
    $sql = "
        (SELECT 
            'PENERIMAAN' as tipe, 
            t.tgl_penerimaan as tanggal, 
            p.sku, p.nama_produk, 
            t.jumlah, 
            l.kode_lokasi, 
            u.nama_lengkap as nama_pengguna, 
            t.pemasok,
            t.nomor_referensi as referensi,
            '' as alasan
         FROM transaksi_penerimaan t
         JOIN produk p ON t.produk_id = p.id 
         JOIN lokasi_penyimpanan l ON t.lokasi_id = l.id 
         JOIN users u ON t.user_id = u.id)
        UNION ALL
        (SELECT 
            'PENGELUARAN' as tipe, 
            t.tgl_pengeluaran as tanggal, 
            p.sku, p.nama_produk, 
            t.jumlah * -1, 
            l.kode_lokasi, 
            u.nama_lengkap, 
            '' as pemasok,
            t.nomor_referensi as referensi,
            '' as alasan
         FROM transaksi_pengeluaran t
         JOIN produk p ON t.produk_id = p.id 
         JOIN lokasi_penyimpanan l ON t.lokasi_id = l.id 
         JOIN users u ON t.user_id = u.id)
        UNION ALL
        (SELECT 
            'KOREKSI' as tipe, 
            t.tgl_koreksi as tanggal, 
            p.sku, p.nama_produk, 
            (t.jumlah_sesudah - t.jumlah_sebelum), 
            l.kode_lokasi, 
            u.nama_lengkap, 
            '' as pemasok,
            '' as referensi,
            t.alasan
         FROM transaksi_koreksi t
         JOIN produk p ON t.produk_id = p.id 
         JOIN lokasi_penyimpanan l ON t.lokasi_id = l.id 
         JOIN users u ON t.user_id = u.id)
        ORDER BY tanggal DESC 
        LIMIT 200;
    ";
    $histori = $pdo->query($sql)->fetchAll();
} catch (PDOException $e) {
    die("Error mengambil data: " . $e->getMessage());
}
?>

<h1 class="h3 mb-4 text-gray-800">Histori Seluruh Transaksi Gudang</h1>
<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">Log Aktivitas Barang</h6>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered table-hover" id="dataTable" width="100%" cellspacing="0">
                <thead class="thead-light">
                    <tr>
                        <th>Tipe</th>
                        <th>Tanggal</th>
                        <th>SKU</th>
                        <th>Produk</th>
                        <th>Jml</th>
                        <th>Lokasi</th>
                        <th>Nama Pengguna</th>
                        <th>Nama Pemasok</th>
                        <th>No. Referensi</th>
                        <th>Alasan Koreksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($histori)): ?>
                        <tr><td colspan="10" class="text-center">Belum ada histori transaksi.</td></tr>
                    <?php else: ?>
                        <?php foreach($histori as $item): ?>
                        <tr>
                            <td class="text-center"><?php $tipe = htmlspecialchars($item['tipe']); $badge = ['PENERIMAAN'=>'success', 'PENGELUARAN'=>'danger', 'KOREKSI'=>'warning']; echo "<span class='badge badge-{$badge[$tipe]}'>{$tipe}</span>"; ?></td>
                            <td><?php echo htmlspecialchars(date('d-m-Y H:i', strtotime($item['tanggal']))); ?></td>
                            <td><?php echo htmlspecialchars($item['sku']); ?></td>
                            <td><?php echo htmlspecialchars($item['nama_produk']); ?></td>
                            <td class="text-center font-weight-bold <?php echo intval($item['jumlah']) > 0 ? 'text-success' : 'text-danger'; ?>"><?php echo (intval($item['jumlah']) > 0 ? '+' : '') . htmlspecialchars($item['jumlah']); ?></td>
                            <td><?php echo htmlspecialchars($item['kode_lokasi']); ?></td>
                            <td><?php echo htmlspecialchars($item['nama_pengguna']); ?></td>
                            <td><?php echo !empty($item['pemasok']) ? htmlspecialchars($item['pemasok']) : '-'; ?></td>
                            <td><?php echo !empty($item['referensi']) ? htmlspecialchars($item['referensi']) : '-'; ?></td>
                            <td><?php echo !empty($item['alasan']) ? htmlspecialchars($item['alasan']) : '-'; ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>