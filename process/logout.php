<?php
// Mulai sesi
session_start();

// Hancurkan semua data sesi
$_SESSION = []; // Mengosongkan semua variabel sesi
session_destroy(); // Menghancurkan sesi

// Redirect ke halaman login
header('Location: ../index.php');
exit();
?>
