<?php
include '../config/koneksi.php';

$id = $_POST['id_transaksi_kas'];
$tanggal = $_POST['tanggal'];
$jumlah = $_POST['jumlah'];
$id_kategori = $_POST['id_kategori'];
$deskripsi = $_POST['deskripsi'];
$jenis_transaksi = $_POST['jenis_transaksi'];

$sql = "UPDATE transaksi_kas SET 
        tanggal = '$tanggal', 
        jumlah = '$jumlah', 
        id_kategori = '$id_kategori', 
        deskripsi = '$deskripsi', 
        jenis_transaksi = '$jenis_transaksi' 
        WHERE id_transaksi_kas = '$id'";

if ($conn->query($sql) === TRUE) {
    echo "Transaksi berhasil diperbarui!";
    header("Location: transaksi-kas.php");
} else {
    echo "Error: " . $sql . "<br>" . $conn->error;
}

$conn->close();
?>
