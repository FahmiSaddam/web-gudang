<?php
session_start();
require 'config/database.php';

// Proteksi Level 1: Harus login dan sebagai Manajer
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'manajer') {
    header('Location: dashboard.php');
    exit();
}
// Proteksi Level 2: Harus ada 'action'
if (!isset($_REQUEST['action'])) {
    header('Location: manajemen_pengguna.php');
    exit();
}

$action = $_REQUEST['action'];

try {
    // Aksi untuk Menyimpan (Tambah atau Edit)
    if ($action == 'save' && $_SERVER['REQUEST_METHOD'] == 'POST') {
        $id = $_POST['id'];
        $username = trim($_POST['username']);
        $nama_lengkap = trim($_POST['nama_lengkap']);
        $role = $_POST['role'];
        $password = $_POST['password'];

        if (empty($username) || empty($nama_lengkap)) throw new Exception("Username dan Nama Lengkap wajib diisi.");

        if (empty($id)) { // Logika TAMBAH Pengguna Baru
            if (empty($password)) throw new Exception("Password wajib diisi untuk pengguna baru.");
            $sql = "INSERT INTO users (username, nama_lengkap, password, role) VALUES (?, ?, ?, ?)";
            $pdo->prepare($sql)->execute([$username, $nama_lengkap, $password, $role]);
        } else { // Logika EDIT Pengguna
            // Periksa agar tidak mengubah peran manajer
            $stmt_cek = $pdo->prepare("SELECT role FROM users WHERE id = ?");
            $stmt_cek->execute([$id]);
            $current_role = $stmt_cek->fetchColumn();
            if ($current_role == 'manajer' && $role != 'manajer') {
                throw new Exception("Peran Manajer tidak dapat diubah.");
            }

            if (!empty($password)) { // Jika password diisi
                $sql = "UPDATE users SET username = ?, nama_lengkap = ?, password = ?, role = ? WHERE id = ?";
                $pdo->prepare($sql)->execute([$username, $nama_lengkap, $password, $role, $id]);
            } else { // Jika password dikosongkan
                $sql = "UPDATE users SET username = ?, nama_lengkap = ?, role = ? WHERE id = ?";
                $pdo->prepare($sql)->execute([$username, $nama_lengkap, $role, $id]);
            }
        }
    } 
    // Aksi untuk Menghapus
    elseif ($action == 'delete' && isset($_GET['id'])) {
        $id_to_delete = $_GET['id'];
        
        // Proteksi Kritis: Mencegah menghapus akun sendiri.
        if ($id_to_delete == $_SESSION['user_id']) {
            throw new Exception("Operasi Ilegal: Anda tidak dapat menghapus akun Anda sendiri.");
        }
        
        // Query penghapusan yang juga melindungi peran 'manajer'
        $sql = "DELETE FROM users WHERE id = ? AND role != 'manajer'";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$id_to_delete]);
        
        if($stmt->rowCount() === 0){
             throw new Exception("Gagal menghapus. Pengguna mungkin tidak ditemukan atau merupakan seorang Manajer.");
        }
    }
} catch (Exception $e) {
    // Untuk pengembangan, bisa tampilkan error. Untuk produksi, catat ke log.
    // die($e->getMessage()); 
    // Untuk sementara, kita redirect saja
    header('Location: manajemen_pengguna.php');
    exit();
}

// Redirect kembali ke halaman manajemen pengguna setelah aksi berhasil
header('Location: manajemen_pengguna.php');
exit();