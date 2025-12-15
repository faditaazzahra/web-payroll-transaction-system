<?php
session_start();

// Pastikan admin sudah login
if (!isset($_SESSION['id_user'])) {
    header('Location: ../index.php');
    exit();
}

include '../config/koneksi.php';

// Proses jika form dikirim
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nama_karyawan = $_POST['nama_karyawan'];
    $nomor_ponsel = $_POST['nomor_ponsel'];
    $alamat = $_POST['alamat'];
    $tanggal_masuk = $_POST['tanggal_masuk'];

    // Insert data ke tabel karyawan
    $sql_insert = "INSERT INTO karyawan (nama_karyawan, nomor_ponsel, alamat, tanggal_masuk) VALUES (?, ?, ?, ?)";
    $stmt_insert = $conn->prepare($sql_insert);
    $stmt_insert->bind_param('ssss', $nama_karyawan, $nomor_ponsel, $alamat, $tanggal_masuk);
    $stmt_insert->execute();
    $stmt_insert->close();

    header('Location: ../data-karyawan.php'); // Redirect ke halaman daftar karyawan setelah menambahkan
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Karyawan - Satria Bima Wash (SBW)</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            padding: 20px;
        }

        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
            overflow: hidden;
            transition: all 0.3s ease;
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(0,0,0,0.2);
        }

        .card-header {
            background: linear-gradient(45deg, #4e73df, #224abe);
            color: white;
            padding: 20px;
            font-weight: 600;
        }

        .btn-primary, .btn-secondary {
            border: none;
            padding: 10px 20px;
            border-radius: 30px;
            transition: all 0.3s ease;
        }

        .btn-primary {
            background: linear-gradient(45deg, #4e73df, #224abe);
        }

        .btn-primary:hover, .btn-secondary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>
    <div class="card">
        <div class="card-header">
            <h5 class="card-title"><i class="fas fa-plus me-2"></i>Tambah Karyawan</h5>
        </div>

        <div class="card-body">
            <form action="" method="POST">
                <div class="mb-3">
                    <label for="nama_karyawan" class="form-label"><i class="fa-solid fa-user me-2"></i>Nama Karyawan</label>
                    <input type="text" class="form-control" id="nama_karyawan" name="nama_karyawan" required>
                </div>
                <div class="mb-3">
                    <label for="nomor_ponsel" class="form-label"><i class="fa-solid fa-phone me-2"></i>Nomor HP</label>
                    <input type="text" class="form-control" id="nomor_ponsel" name="nomor_ponsel" required>
                </div>
                <div class="mb-3">
                    <label for="alamat" class="form-label"><i class="fa-solid fa-map-marker-alt me-2"></i>Alamat Rumah</label>
                    <input type="text" class="form-control" id="alamat" name="alamat" required>
                </div>
                <div class="mb-3">
                    <label for="tanggal_masuk" class="form-label"><i class="fa-solid fa-calendar-alt me-2"></i>Tanggal Masuk</label>
                    <input type="date" class="form-control" id="tanggal_masuk" name="tanggal_masuk" required>
                </div>
                <button type="submit" class="btn btn-primary"><i class="fas fa-plus me-2"></i>Tambah Karyawan</button>
                <a href="../data-karyawan.php" class="btn btn-secondary"><i class="fa-solid fa-arrow-left me-2"></i>Kembali</a>
            </form>
        </div>
    </div>
</body>
</html>
