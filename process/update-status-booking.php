<?php
include '../config/koneksi.php';

if (isset($_GET['id']) && isset($_GET['status'])) {
    $id_booking = $_GET['id'];
    $status = $_GET['status'];

    // Update status booking
    $sql = "UPDATE booking_kendaraan SET status_booking = ?, updated_at = NOW() WHERE id_booking = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('si', $status, $id_booking);
    $stmt->execute();
    $stmt->close();

    header("Location: antrian.php");
    exit();
}
?>
