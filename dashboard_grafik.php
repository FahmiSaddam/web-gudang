<?php
$page_title = 'Dashboard Grafik Interaktif';
require 'config/database.php';
include 'includes/header.php';

// Proteksi Halaman: Hanya Supervisor dan Manajer
if (!in_array($_SESSION['role'], ['supervisor', 'manajer'])) {
    echo '<div class="alert alert-danger mx-3">Akses Ditolak. Anda tidak memiliki izin untuk mengakses halaman ini.</div>';
    include 'includes/footer.php';
    exit();
}

// --- LOGIKA PENGATURAN PERIODE ---
$periode = $_GET['periode'] ?? '';
$tanggal_mulai_str = $_GET['tanggal_mulai'] ?? '';
$tanggal_akhir_str = $_GET['tanggal_akhir'] ?? '';
$is_custom = false;

// Tentukan rentang tanggal berdasarkan preset jika tidak ada rentang kustom
if (empty($tanggal_mulai_str) || empty($tanggal_akhir_str)) {
    switch ($periode) {
        case 'mingguan':
            $tanggal_mulai_str = date('Y-m-d', strtotime('-7 weeks'));
            $tanggal_akhir_str = date('Y-m-d');
            break;
        case 'bulanan':
            $tanggal_mulai_str = date('Y-m-d', strtotime('-11 months'));
            $tanggal_akhir_str = date('Y-m-d');
            break;
        case 'tahunan':
            // Ambil tahun paling awal dari transaksi
            $first_year = $pdo->query("SELECT MIN(YEAR(tanggal)) FROM (SELECT tgl_penerimaan as tanggal FROM transaksi_penerimaan UNION SELECT tgl_pengeluaran FROM transaksi_pengeluaran) as t")->fetchColumn();
            $tanggal_mulai_str = $first_year ? "$first_year-01-01" : date('Y-01-01');
            $tanggal_akhir_str = date('Y-m-d');
            break;
        case 'harian':
        default:
            $periode = 'harian'; // Pastikan default adalah harian
            $tanggal_mulai_str = date('Y-m-d', strtotime('-6 days'));
            $tanggal_akhir_str = date('Y-m-d');
            break;
    }
} else {
    // Jika ada rentang kustom, tandai dan hapus highlight dari tombol preset
    $is_custom = true;
    $periode = ''; 
}

// Validasi tanggal
try {
    $tanggal_mulai = new DateTime($tanggal_mulai_str);
    $tanggal_akhir = new DateTime($tanggal_akhir_str);
} catch (Exception $e) {
    // Jika tanggal tidak valid, kembali ke default harian
    $tanggal_mulai = new DateTime('-6 days');
    $tanggal_akhir = new DateTime();
}


// --- LOGIKA PENGAMBILAN DATA DINAMIS ---
$chart_title = 'Aktivitas dari ' . $tanggal_mulai->format('d M Y') . ' hingga ' . $tanggal_akhir->format('d M Y');
$labels = [];
$data_in = [];
$data_out = [];
$group_format = 'harian';

// Tentukan cara pengelompokan data secara otomatis
$diff_days = $tanggal_akhir->diff($tanggal_mulai)->days;
if ($diff_days > 90) { // Lebih dari 3 bulan, kelompokkan per bulan
    $group_format = 'bulanan';
    $sql_group_format = '%Y-%m';
    $php_label_format = 'M Y';
    $php_date_modifier = 'first day of this month';
} elseif ($diff_days > 14) { // Antara 2 minggu dan 3 bulan, kelompokkan per minggu
    $group_format = 'mingguan';
    $sql_group_format = '%Y-%u'; // %u = minggu dimulai dari Senin
    $php_label_format = 'W, Y';
} else { // Kurang dari 2 minggu, kelompokkan per hari
    $group_format = 'harian';
    $sql_group_format = '%Y-%m-%d';
    $php_label_format = 'd M';
}

try {
    $temp_data = [];
    $current_date = clone $tanggal_mulai;
    while ($current_date <= $tanggal_akhir) {
        $key = '';
        if ($group_format == 'bulanan') {
            $key = $current_date->format('Y-m');
            $labels[$key] = $current_date->format($php_label_format);
            $current_date->modify('+1 month');
        } elseif ($group_format == 'mingguan') {
            $key = $current_date->format('o-W'); // ISO-8601 format
            $labels[$key] = "W" . $current_date->format('W, Y');
            $current_date->modify('+1 week');
        } else { // harian
            $key = $current_date->format('Y-m-d');
            $labels[$key] = $current_date->format($php_label_format);
            $current_date->modify('+1 day');
        }
        $temp_data[$key] = ['in' => 0, 'out' => 0];
    }
    $labels = array_values(array_unique($labels));

    $sql_select_group = ($group_format == 'mingguan') ? "DATE_FORMAT(tanggal, '%x-%v')" : "DATE_FORMAT(tanggal, '{$sql_group_format}')";

    $sql_data = "
        SELECT grp, tipe, SUM(jumlah) as total FROM (
            SELECT " . ($group_format == 'mingguan' ? 
                "DATE_FORMAT(tgl_penerimaan, '%x-%v')" : 
                "DATE_FORMAT(tgl_penerimaan, '{$sql_group_format}')") . " as grp, 
                'in' as tipe, jumlah 
            FROM transaksi_penerimaan 
            WHERE DATE(tgl_penerimaan) BETWEEN ? AND ?
            
            UNION ALL
            
            SELECT " . ($group_format == 'mingguan' ? 
                "DATE_FORMAT(tgl_pengeluaran, '%x-%v')" : 
                "DATE_FORMAT(tgl_pengeluaran, '{$sql_group_format}')") . " as grp, 
                'out' as tipe, jumlah 
            FROM transaksi_pengeluaran 
            WHERE DATE(tgl_pengeluaran) BETWEEN ? AND ?
        ) as t
        GROUP BY grp, tipe
    ";
    
    $stmt = $pdo->prepare($sql_data);
    $stmt->execute([$tanggal_mulai->format('Y-m-d'), $tanggal_akhir->format('Y-m-d'), $tanggal_mulai->format('Y-m-d'), $tanggal_akhir->format('Y-m-d')]);

    while ($row = $stmt->fetch()) {
        $key = $row['grp'];
        if ($group_format == 'mingguan') { // Sesuaikan format key untuk mingguan
            $year = substr($key, 0, 4);
            $week = substr($key, 5, 2);
            $key = "$year-$week";
        }
        if (isset($temp_data[$key])) {
            $temp_data[$key][$row['tipe']] = (int)$row['total'];
        }
    }
    
    foreach ($temp_data as $data) {
        $data_in[] = $data['in'];
        $data_out[] = $data['out'];
    }

} catch (Exception $e) {
    echo '<div class="alert alert-danger mx-3">Gagal mengambil data untuk grafik: ' . htmlspecialchars($e->getMessage()) . '</div>';
}

?>

<h1 class="h3 mb-4 text-gray-800">Dashboard Grafik Interaktif</h1>

<!-- Form Filter Periode -->
<div class="card shadow mb-4">
    <div class="card-body">
        <form method="GET" action="dashboard_grafik.php" class="form-inline">
            <div class="form-group mb-2">
                <label for="tanggal_mulai" class="sr-only">Tanggal Mulai</label>
                <input type="date" class="form-control" id="tanggal_mulai" name="tanggal_mulai" value="<?php echo $tanggal_mulai->format('Y-m-d'); ?>">
            </div>
            <div class="form-group mx-sm-3 mb-2">
                <label for="tanggal_akhir" class="sr-only">Tanggal Akhir</label>
                <input type="date" class="form-control" id="tanggal_akhir" name="tanggal_akhir" value="<?php echo $tanggal_akhir->format('Y-m-d'); ?>">
            </div>
            <button type="submit" class="btn btn-success mb-2"><i class="fas fa-filter fa-sm"></i> Terapkan</button>
            <div class="ml-auto">
                <a href="?periode=harian" class="btn <?php echo $periode == 'harian' ? 'btn-primary' : 'btn-outline-primary'; ?> mb-2">Harian</a>
                <a href="?periode=mingguan" class="btn <?php echo $periode == 'mingguan' ? 'btn-primary' : 'btn-outline-primary'; ?> mb-2">Mingguan</a>
                <a href="?periode=bulanan" class="btn <?php echo $periode == 'bulanan' ? 'btn-primary' : 'btn-outline-primary'; ?> mb-2">Bulanan</a>
                <a href="?periode=tahunan" class="btn <?php echo $periode == 'tahunan' ? 'btn-primary' : 'btn-outline-primary'; ?> mb-2">Tahunan</a>
            </div>
        </form>
    </div>
</div>

<!-- Kartu untuk Menampilkan Grafik -->
<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary"><?php echo htmlspecialchars($chart_title); ?></h6>
    </div>
    <div class="card-body">
        <div class="chart-container" style="position: relative; height:40vh; width:100%">
            <canvas id="activityChart"></canvas>
        </div>
    </div>
</div>

<!-- Memuat Pustaka Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener("DOMContentLoaded", function() {
    const labels = <?php echo json_encode($labels); ?>;
    if (labels && labels.length > 0) {
        var ctx = document.getElementById('activityChart').getContext('2d');
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: "Barang Masuk",
                    backgroundColor: "#4e73df",
                    data: <?php echo json_encode($data_in); ?>,
                    borderRadius: 4
                }, {
                    label: "Barang Keluar",
                    backgroundColor: "#e74a3b",
                    data: <?php echo json_encode($data_out); ?>,
                    borderRadius: 4
                }]
            },
            options: {
                maintainAspectRatio: false,
                responsive: true,
                scales: {
                    x: { grid: { display: false } },
                    y: { ticks: { beginAtZero: true } }
                },
                plugins: {
                    legend: { position: 'top' },
                    tooltip: { mode: 'index', intersect: false }
                }
            }
        });
    } else {
        document.querySelector('.chart-container').innerHTML = '<div class="text-center p-5">Tidak ada data transaksi untuk rentang tanggal yang dipilih.</div>';
    }
});
</script>

<?php
include 'includes/footer.php';
?>
