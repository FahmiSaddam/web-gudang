<?php
// Pengaturan Database
$host = 'localhost';
$dbname = 'gudang_db';
$user = 'root'; // Ganti dengan username database Anda
$pass = '';     // Ganti dengan password database Anda
$charset = 'utf8mb4';

// Opsi untuk koneksi PDO
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

// Membuat instance PDO (koneksi ke database)
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=$charset", $user, $pass, $options);
} catch (\PDOException $e) {
    // Jika koneksi gagal, hentikan script dan tampilkan error
    throw new \PDOException($e->getMessage(), (int)$e->getCode());
}
?>