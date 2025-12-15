<?php
header('Content-Type: application/json');
include_once '../config/koneksi.php';

// Set timezone
date_default_timezone_set('Asia/Jakarta');

// Ambil waktu sekarang dan 15 menit ke depan
$now = new DateTime();
$reminder_time = new DateTime();
$reminder_time->add(new DateInterval('PT15M'));

// Query untuk mengecek booking
$query = "SELECT b.*, k.plat_nomor, k.merk 
          FROM booking_kendaraan b
          JOIN kendaraan k ON b.id_kendaraan = k.id_kendaraan
          WHERE DATE(b.tanggal_booking) = CURDATE()
          AND TIME(b.jam_booking) BETWEEN CURRENT_TIME() AND TIME(?) 
          AND b.status_booking = 'pending'
          AND (b.status_reminder = 0 OR b.status_reminder IS NULL)";
          
try {
    $stmt = $conn->prepare($query);
    $reminder_time_str = $reminder_time->format('H:i:s');
    $stmt->bind_param('s', $reminder_time_str);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $reminders = [];
    while($row = $result->fetch_assoc()) {
        $reminders[] = $row;
        
        // Update status_reminder
        $update = $conn->prepare("UPDATE booking_kendaraan SET status_reminder = 1 WHERE id_booking = ?");
        $update->bind_param('i', $row['id_booking']);
        $update->execute();
    }
    
    echo json_encode($reminders);
    
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>