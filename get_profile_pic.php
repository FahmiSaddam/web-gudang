<?php
session_start();
require 'config/database.php';

// Ambil user ID dari URL, pastikan itu angka
$user_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

// Default gambar jika tidak ada gambar/user
$default_pic_path = 'img/undraw_profile.svg'; // Path ke gambar default dari template SB Admin 2

try {
    if ($user_id) {
        $stmt = $pdo->prepare("SELECT foto_profil FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();

        if ($user && !empty($user['foto_profil'])) {
            // Jika ada foto, kirim header gambar yang sesuai dan tampilkan datanya
            // (Kita asumsikan tipe gambar adalah jpeg, bisa dipercanggih dengan menyimpan mime type)
            header("Content-Type: image/jpeg"); 
            echo $user['foto_profil'];
            exit();
        }
    }
} catch (Exception $e) {
    // Jika ada error database, jangan tampilkan apa-apa, fallback ke default
}

// Jika tidak ada foto di database atau user tidak ditemukan, tampilkan gambar default
header("Content-Type: image/svg+xml");
readfile($default_pic_path);
exit();