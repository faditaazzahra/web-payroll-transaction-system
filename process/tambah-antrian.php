<?php
include '../config/koneksi.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_kendaraan = $_POST['id_kendaraan'];
    $tanggal_booking = $_POST['tanggal_booking'];
    $jam_booking = $_POST['jam_booking'];

    $sql = "INSERT INTO booking_kendaraan (id_kendaraan, tanggal_booking, jam_booking, status_booking, status_reminder) VALUES (?, ?, ?, 'Menunggu', 'belum')";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('iss', $id_kendaraan, $tanggal_booking, $jam_booking);
    $stmt->execute();
    $stmt->close();

    header("Location: antrian.php");
    exit();
}
?>
