<?php
include '../config/koneksi.php';

$id = $_GET['id'];

// Mulai transaksi database
$conn->begin_transaction();

try {
    // 1. Ambil informasi transaksi yang akan dihapus
    $stmt = $conn->prepare("SELECT * FROM transaksi_kas WHERE id_transaksi_kas = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $transaksi = $result->fetch_assoc();

    if (!$transaksi) {
        throw new Exception("Transaksi tidak ditemukan");
    }

    // 2. Update saldo rekening
    if ($transaksi['jenis_transaksi'] == 'Pemasukan') {
        $sql_update = "UPDATE rekening SET saldo = saldo - ? WHERE id_rekening = ?";
    } else {
        $sql_update = "UPDATE rekening SET saldo = saldo + ? WHERE id_rekening = ?";
    }

    $stmt = $conn->prepare($sql_update);
    $stmt->bind_param("di", $transaksi['jumlah'], $transaksi['id_rekening']);
    $stmt->execute();

    // 3. Hapus data di tabel transaksi_rekening
    $stmt = $conn->prepare("DELETE FROM transaksi_rekening WHERE id_transaksi_kas = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();

    // 4. Hapus data di tabel transaksi_kas
    $stmt = $conn->prepare("DELETE FROM transaksi_kas WHERE id_transaksi_kas = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();

    // Commit transaksi
    $conn->commit();
    header("Location: ../transaksi-kas.php?status=success");
    exit();

} catch (Exception $e) {
    // Rollback jika terjadi error
    $conn->rollback();
    echo "Error: " . $e->getMessage();
}

$conn->close();
?>