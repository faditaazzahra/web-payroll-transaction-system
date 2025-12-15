<?php
header('Content-Type: application/json');
include '../config/koneksi.php';

date_default_timezone_set('Asia/Jakarta');
$current_time = date('Y-m-d H:i:s');
$fifteen_minutes_later = date('Y-m-d H:i:s', strtotime('+15 minutes'));

// Query untuk mengecek booking yang akan datang dalam 15 menit
$query = "SELECT bk.id_booking, bk.tanggal_booking, bk.jam_booking, 
          k.plat_nomor, k.merk, k.warna, k.no_hp
          FROM booking_kendaraan bk
          JOIN kendaraan k ON bk.id_kendaraan = k.id_kendaraan
          WHERE bk.status_reminder = 'belum'
          AND bk.status_booking = 'Menunggu'
          AND CONCAT(bk.tanggal_booking, ' ', bk.jam_booking) 
          BETWEEN ? AND ?";

$stmt = $conn->prepare($query);
$stmt->bind_param("ss", $current_time, $fifteen_minutes_later);
$stmt->execute();
$result = $stmt->get_result();

$reminders = [];
while ($row = $result->fetch_assoc()) {
    $reminders[] = $row;
    
    // Update status reminder menjadi 'sudah'
    $update_query = "UPDATE booking_kendaraan SET status_reminder = 'sudah' 
                    WHERE id_booking = ?";
    $update_stmt = $conn->prepare($update_query);
    $update_stmt->bind_param("i", $row['id_booking']);
    $update_stmt->execute();
}

echo json_encode(['reminders' => $reminders]);
?>