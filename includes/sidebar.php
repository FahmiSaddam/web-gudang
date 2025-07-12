<div class="d-flex flex-column flex-shrink-0 p-3 text-white bg-dark sidebar">
    <a href="dashboard.php" class="d-flex align-items-center mb-3 mb-md-0 me-md-auto text-white text-decoration-none">
        <i class="bi bi-box-seam fs-4 me-2"></i>
        <span class="fs-4">Gudang App</span>
    </a>
    <hr>
    <ul class="nav nav-pills flex-column mb-auto">
        <li class="nav-item">
            <a href="dashboard.php" class="nav-link text-white <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>">
                <i class="bi bi-speedometer2 me-2"></i> Dashboard
            </a>
        </li>
        
        <?php // Menu untuk semua peran ?>
        <li>
            <a href="penerimaan_barang.php" class="nav-link text-white <?php echo basename($_SERVER['PHP_SELF']) == 'penerimaan_barang.php' ? 'active' : ''; ?>">
                <i class="bi bi-box-arrow-in-down me-2"></i> Penerimaan Barang
            </a>
        </li>
        <li>
            <a href="pengeluaran_barang.php" class="nav-link text-white <?php echo basename($_SERVER['PHP_SELF']) == 'pengeluaran_barang.php' ? 'active' : ''; ?>">
                <i class="bi bi-box-arrow-up me-2"></i> Pengeluaran Barang
            </a>
        </li>

        <?php // Menu khusus untuk Supervisor dan Manajer ?>
        <?php if ($_SESSION['role'] === 'supervisor' || $_SESSION['role'] === 'manajer'): ?>
        <li>
            <a href="koreksi_stok.php" class="nav-link text-white <?php echo basename($_SERVER['PHP_SELF']) == 'koreksi_stok.php' ? 'active' : ''; ?>">
                <i class="bi bi-pencil-square me-2"></i> Koreksi Stok
            </a>
        </li>
        <?php endif; ?>

        <?php // Menu khusus untuk Manajer ?>
        <?php if ($_SESSION['role'] === 'manajer'): ?>
        <li>
            <a href="laporan_stok.php" class="nav-link text-white <?php echo basename($_SERVER['PHP_SELF']) == 'laporan_stok.php' ? 'active' : ''; ?>">
                <i class="bi bi-file-earmark-bar-graph me-2"></i> Laporan Stok
            </a>
        </li>
        <li>
            <a href="manajemen_pengguna.php" class="nav-link text-white <?php echo basename($_SERVER['PHP_SELF']) == 'manajemen_pengguna.php' ? 'active' : ''; ?>">
                <i class="bi bi-people me-2"></i> Manajemen Pengguna
            </a>
        </li>
        <?php endif; ?>
    </ul>
    <hr>
    <div class="dropdown">
        <a href="#" class="d-flex align-items-center text-white text-decoration-none dropdown-toggle" id="dropdownUser1" data-bs-toggle="dropdown" aria-expanded="false">
            <i class="bi bi-person-circle fs-4 me-2"></i>
            <strong><?php echo htmlspecialchars($_SESSION['nama_lengkap']); ?></strong>
        </a>
        <ul class="dropdown-menu dropdown-menu-dark text-small shadow" aria-labelledby="dropdownUser1">
            <li><a class="dropdown-item" href="#">Profil</a></li>
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item" href="logout.php">Logout</a></li>
        </ul>
    </div>
</div>