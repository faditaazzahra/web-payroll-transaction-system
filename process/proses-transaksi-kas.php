<?php
include '../config/koneksi.php';

// Data yang diterima dari formulir
$tanggal = $_POST['tanggal'];
$jumlah = $_POST['jumlah'];
$id_kategori = $_POST['id_kategori'];
$deskripsi = $_POST['deskripsi'];
$jenis_transaksi = $_POST['jenis_transaksi'];
$id_user = $_POST['id_user'];
$id_rekening = $_POST['id_rekening']; // ID rekening yang dipilih dari form

// Mulai transaksi
$conn->begin_transaction();

try {
    // 1. Menyimpan data transaksi ke dalam tabel transaksi_kas
    $sql_kas = "INSERT INTO transaksi_kas (tanggal, jumlah, id_kategori, deskripsi, jenis_transaksi, id_user) 
                VALUES ('$tanggal', '$jumlah', '$id_kategori', '$deskripsi', '$jenis_transaksi', '$id_user')";

    if ($conn->query($sql_kas) === TRUE) {
        // Mendapatkan id_transaksi_kas yang baru dimasukkan
        $id_transaksi_kas = $conn->insert_id;
        
        // 2. Mengambil saldo terakhir dari rekening yang dipilih
        $result = $conn->query("SELECT saldo FROM rekening WHERE id_rekening = '$id_rekening'");
        $row = $result->fetch_assoc();
        $saldo_sebelumnya = $row['saldo'];
        
        // 3. Menghitung saldo baru berdasarkan jenis transaksi
        if ($jenis_transaksi == 'Pemasukan') {
            $saldo_setelah_transaksi = $saldo_sebelumnya + $jumlah;
        } else {
            $saldo_setelah_transaksi = $saldo_sebelumnya - $jumlah;
        }

        // 4. Menyimpan data ke dalam tabel transaksi_rekening
        $sql_rekening = "INSERT INTO transaksi_rekening (id_transaksi_kas, id_rekening, saldo_setelah_transaksi) 
                        VALUES ('$id_transaksi_kas', '$id_rekening', '$saldo_setelah_transaksi')";

        if ($conn->query($sql_rekening) === TRUE) {
            // 5. Memperbarui saldo di tabel rekening
            $sql_update_saldo = "UPDATE rekening SET saldo = '$saldo_setelah_transaksi' WHERE id_rekening = '$id_rekening'";
            $conn->query($sql_update_saldo);

            // Commit transaksi
            $conn->commit();
            
            // Redirect ke halaman transaksi-kas.php jika sukses
            header("Location: ../transaksi-kas.php?status=success");
            exit; // Pastikan untuk keluar dari skrip setelah redirect
        } else {
            throw new Exception("Gagal menyimpan transaksi ke tabel transaksi_rekening: " . $conn->error);
        }
    } else {
        throw new Exception("Gagal menyimpan transaksi ke tabel transaksi_kas: " . $conn->error);
    }
} catch (Exception $e) {
    // Rollback jika terjadi error
    $conn->rollback();
    echo "Error: " . $e->getMessage();
}

// Tutup koneksi
$conn->close();
?>
