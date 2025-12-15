<?php
session_start();
include '../config/koneksi.php';

// Pastikan admin sudah login
if (!isset($_SESSION['id_user'])) {
    header('Location: index.php');
    exit();
}

// Periksa apakah id_booking dan status baru telah dikirim melalui URL
if (isset($_GET['id']) && isset($_GET['status'])) {
    $id_booking = $_GET['id'];
    $new_status = $_GET['status'];

    // Ambil id_kendaraan dari booking_kendaraan berdasarkan id_booking
    $query_kendaraan = $conn->prepare("SELECT id_kendaraan FROM booking_kendaraan WHERE id_booking = ?");
    $query_kendaraan->bind_param("i", $id_booking);
    $query_kendaraan->execute();
    $result = $query_kendaraan->get_result();
    $kendaraan = $result->fetch_assoc();
    $id_kendaraan = $kendaraan['id_kendaraan'] ?? null;

    // Jika tidak ada id_kendaraan yang ditemukan, kembali ke halaman antrian
    if (!$id_kendaraan) {
        $_SESSION['error'] = "Antrian tidak ditemukan.";
        header('Location: ../antrian.php');
        exit();
    }

    // Update status booking
    $update_status = $conn->prepare("UPDATE booking_kendaraan SET status_booking = ? WHERE id_booking = ?");
    $update_status->bind_param("si", $new_status, $id_booking);
    
    if ($update_status->execute()) {
        // Jika status berubah menjadi "Selesai," arahkan ke halaman transaksi harian
        if ($new_status == 'Selesai') {
            header("Location: ../transaksi-harian.php?id_kendaraan=" . $id_kendaraan);
            exit();
        } else {
            $_SESSION['success'] = "Status antrian berhasil diupdate ke '$new_status'.";
            header('Location: ../antrian.php');
            exit();
        }
    } else {
        $_SESSION['error'] = "Gagal mengupdate status antrian.";
        header('Location: ../antrian.php');
        exit();
    }
} else {
    $_SESSION['error'] = "Data tidak lengkap untuk mengupdate status antrian.";
    header('Location: ../antrian.php');
    exit();
}
?>
