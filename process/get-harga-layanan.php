<?php
include '../config/koneksi.php';

$id_layanan = $_POST['id_layanan'];

$result = $conn->query("SELECT harga FROM jenis_layanan WHERE id_layanan = '$id_layanan'");
$row = $result->fetch_assoc();

echo $row['harga'];
?>