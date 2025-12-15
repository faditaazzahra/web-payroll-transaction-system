<?php
include '../config/koneksi.php';
date_default_timezone_set('Asia/Jakarta');

// Menghitung waktu saat ini dan 15 menit ke depan
$now = new DateTime();
$now_plus_15 = (clone $now)->add(new DateInterval('PT15M'));

$sql_reminder = "
    SELECT plat_nomor, merk, warna, tanggal_booking, jam_booking 
    FROM booking_kendaraan 
    JOIN kendaraan ON booking_kendaraan.id_kendaraan = kendaraan.id_kendaraan
    WHERE status_reminder = 'belum'
    AND tanggal_booking = CURDATE() 
    AND STR_TO_DATE(jam_booking, '%H:%i') BETWEEN ? AND ?";
$stmt_reminder = $conn->prepare($sql_reminder);
$stmt_reminder->bind_param("ss", $now->format('H:i'), $now_plus_15->format('H:i'));
$stmt_reminder->execute();
$result_reminder = $stmt_reminder->get_result();

$reminders = [];
while ($row = $result_reminder->fetch_assoc()) {
    $reminders[] = $row;
}

// Update status_reminder menjadi 'sudah' setelah ditampilkan
if (!empty($reminders)) {
    $conn->query("UPDATE booking_kendaraan SET status_reminder = 'sudah' WHERE status_reminder = 'belum' AND tanggal_booking = CURDATE() AND STR_TO_DATE(jam_booking, '%H:%i') BETWEEN '{$now->format('H:i')}' AND '{$now_plus_15->format('H:i')}'");
}

echo json_encode($reminders); // Mengembalikan data reminder dalam format JSON
