<?php
// Memulai sesi jika belum ada. Ini penting untuk mengakses variabel $_SESSION.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Jika pengguna belum login (tidak ada user_id di sesi), paksa kembali ke halaman login.
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title><?php echo $page_title ?? 'Dashboard'; ?> - Gudang App</title>

    <!-- Font dan Ikon dari CDN -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i" rel="stylesheet">

    <!-- Stylesheet SB Admin 2 dari CDN -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/startbootstrap-sb-admin-2/4.1.4/css/sb-admin-2.min.css" rel="stylesheet">
</head>
<body id="page-top">
    <!-- Page Wrapper -->
    <div id="wrapper">
        <!-- Sidebar -->
        <ul class="navbar-nav bg-gradient-primary sidebar sidebar-dark accordion" id="accordionSidebar">
            <!-- Brand Logo dan Nama Aplikasi -->
            <a class="sidebar-brand d-flex align-items-center justify-content-center" href="dashboard.php">
                <div class="sidebar-brand-icon rotate-n-15"><i class="fas fa-box"></i></div>
                <div class="sidebar-brand-text mx-3">Gudang App</div>
            </a>
            <hr class="sidebar-divider my-0">
            
            <!-- Menu Dashboard (Dapat diakses semua peran) -->
            <li class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>">
                <a class="nav-link" href="dashboard.php"><i class="fas fa-fw fa-tachometer-alt"></i><span>Dashboard</span></a>
            </li>

            <!-- MENU OPERASIONAL (Untuk Operator, Supervisor, dan Manajer) -->
            <?php if (in_array($_SESSION['role'], ['operator', 'supervisor', 'manajer'])): ?>
            <hr class="sidebar-divider">
            <div class="sidebar-heading">Operasional</div>
            <li class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'penerimaan_barang.php' ? 'active' : ''; ?>">
                <a class="nav-link" href="penerimaan_barang.php"><i class="fas fa-fw fa-dolly-flatbed"></i><span>Penerimaan Barang</span></a>
            </li>
            <li class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'pengeluaran_barang.php' ? 'active' : ''; ?>">
                <a class="nav-link" href="pengeluaran_barang.php"><i class="fas fa-fw fa-truck-loading"></i><span>Pengeluaran Barang</span></a>
            </li>
            <?php endif; ?>

            <!-- MENU ADMINISTRASI & LAPORAN (Untuk Supervisor dan Manajer) -->
            <?php if (in_array($_SESSION['role'], ['supervisor', 'manajer'])): ?>
            <hr class="sidebar-divider">
            <div class="sidebar-heading">Administrasi & Laporan</div>
            <li class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'koreksi_stok.php' ? 'active' : ''; ?>">
                <a class="nav-link" href="koreksi_stok.php"><i class="fas fa-fw fa-edit"></i><span>Koreksi Stok</span></a>
            </li>
            <li class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'laporan_stok.php' ? 'active' : ''; ?>">
                <a class="nav-link" href="laporan_stok.php"><i class="fas fa-fw fa-chart-area"></i><span>Laporan Stok</span></a>
            </li>
            <li class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard_grafik.php' ? 'active' : ''; ?>">
                <a class="nav-link" href="dashboard_grafik.php"><i class="fas fa-fw fa-chart-bar"></i><span>Dashboard Grafik</span></a>
            </li>
            <li class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'histori_transaksi.php' ? 'active' : ''; ?>">
                <a class="nav-link" href="histori_transaksi.php"><i class="fas fa-fw fa-history"></i><span>Histori Transaksi</span></a>
            </li>
            <?php endif; ?>

            <!-- MENU SISTEM (Hanya untuk Manajer) -->
            <?php if ($_SESSION['role'] === 'manajer'): ?>
            <hr class="sidebar-divider">
            <div class="sidebar-heading">Sistem</div>
            <li class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'manajemen_pengguna.php' ? 'active' : ''; ?>">
                <a class="nav-link" href="manajemen_pengguna.php"><i class="fas fa-fw fa-users"></i><span>Manajemen Pengguna</span></a>
            </li>
            <?php endif; ?>
            
            <hr class="sidebar-divider d-none d-md-block">
            <!-- Tombol untuk minimize/maximize sidebar -->
            <div class="text-center d-none d-md-inline">
                <button class="rounded-circle border-0" id="sidebarToggle"></button>
            </div>
        </ul>
        <!-- End of Sidebar -->

        <!-- Content Wrapper -->
        <div id="content-wrapper" class="d-flex flex-column">
            <!-- Main Content -->
            <div id="content">
                <!-- Topbar -->
                <nav class="navbar navbar-expand navbar-light bg-white topbar mb-4 static-top shadow">
                    <!-- Tombol toggler sidebar untuk tampilan mobile -->
                    <button id="sidebarToggleTop" class="btn btn-link d-md-none rounded-circle mr-3"><i class="fa fa-bars"></i></button>
                    <!-- Topbar Navbar -->
                    <ul class="navbar-nav ml-auto">
                        <div class="topbar-divider d-none d-sm-block"></div>
                        <!-- Informasi Pengguna -->
                        <li class="nav-item dropdown no-arrow">
                            <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <span class="mr-2 d-none d-lg-inline text-gray-600 small"><?php echo htmlspecialchars($_SESSION['nama_lengkap']); ?></span>
                                <!-- Menampilkan foto profil dari database -->
                                <img class="img-profile rounded-circle"
                                    src="get_profile_pic.php?id=<?php echo $_SESSION['user_id']; ?>&t=<?php echo time(); ?>">
                            </a>
                            <!-- Dropdown Menu Pengguna -->
                            <div class="dropdown-menu dropdown-menu-right shadow animated--grow-in" aria-labelledby="userDropdown">
                                <!-- Menambahkan link ke halaman profil -->
                                <a class="dropdown-item" href="profil.php">
                                    <i class="fas fa-user fa-sm fa-fw mr-2 text-gray-400"></i>
                                    Profil
                                </a>
                                <div class="dropdown-divider"></div>
                                <a class="dropdown-item" href="#" data-toggle="modal" data-target="#logoutModal">
                                    <i class="fas fa-sign-out-alt fa-sm fa-fw mr-2 text-gray-400"></i>
                                    Logout
                                </a>
                            </div>
                        </li>
                    </ul>
                </nav>
                <!-- End of Topbar -->
                <!-- Begin Page Content -->
                <div class="container-fluid">
