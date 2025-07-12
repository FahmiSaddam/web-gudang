<?php
session_start();
session_unset(); // Menghapus semua variabel session
session_destroy(); // Menghancurkan session
header("Location: index.php"); // Arahkan ke halaman login
exit();
?>