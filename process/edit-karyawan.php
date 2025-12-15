<?php
session_start();

// Pastikan admin sudah login
if (!isset($_SESSION['id_user'])) {
    header('Location: ../index.php');
    exit();
}

include '../config/koneksi.php';

// Ambil id_karyawan dari URL
$id_karyawan = $_GET['id'];

// Ambil data karyawan dari database
$sql_select = "SELECT id_karyawan, nama_karyawan, nomor_ponsel, alamat, tanggal_masuk FROM karyawan WHERE id_karyawan = ?";
$stmt_select = $conn->prepare($sql_select);
$stmt_select->bind_param('i', $id_karyawan);
$stmt_select->execute();
$result = $stmt_select->get_result();
$row = $result->fetch_assoc();

if (!$row) {
    die('Karyawan tidak ditemukan.');
}

// Proses jika form dikirim
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nama_karyawan = $_POST['nama_karyawan'];
    $nomor_ponsel = $_POST['nomor_ponsel'];
    $alamat = $_POST['alamat'];
    $tanggal_masuk = $_POST['tanggal_masuk'];

    // Update data karyawan
    $sql_update = "UPDATE karyawan SET nama_karyawan = ?, nomor_ponsel = ?, alamat = ?, tanggal_masuk = ? WHERE id_karyawan = ?";
    $stmt_update = $conn->prepare($sql_update);
    $stmt_update->bind_param('ssssi', $nama_karyawan, $nomor_ponsel, $alamat, $tanggal_masuk, $id_karyawan);
    $stmt_update->execute();
    $stmt_update->close();

    header('Location: ../data-karyawan.php'); // Redirect ke halaman daftar karyawan setelah mengedit
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Karyawan - Satria Bima Wash (SBW)</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f8f9fa;
            padding: 20px;
        }

        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }

        .card-header {
            background: linear-gradient(45deg, #4e73df, #224abe);
            color: white;
            border-radius: 10px 10px 0 0;
            padding: 15px 20px;
        }

        .card-title {
            margin: 0;
            font-size: 1.25rem;
            font-weight: 600;
        }

        .form-label {
            font-weight: 500;
            color: #444;
        }

        .form-control, .form-select {
            border-radius: 8px;
            padding: 10px 15px;
            border: 1px solid #ddd;
        }

        .form-control:focus, .form-select:focus {
            border-color: #4e73df;
            box-shadow: 0 0 0 0.2rem rgba(78,115,223,0.25);
        }

        .btn-primary {
            background-color: #4e73df;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
        }

        .btn-primary:hover {
            background-color: #2e59d9;
        }

        .btn-secondary {
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
        }
    </style>
</head>
<body>
    <div class="card">
        <div class="card-header">
            <h5 class="card-title"><i class="fas fa-edit me-2"></i>Edit Karyawan</h5>
        </div>

        <div class="card-body">
            <form action="" method="POST">
                <input type="hidden" name="id_karyawan" value="<?= $row['id_karyawan']; ?>">
                <div class="mb-3">
                    <label for="nama_karyawan" class="form-label"><i class="fas fa-user me-2"></i>Nama Karyawan</label>
                    <input type="text" class="form-control" id="nama_karyawan" name="nama_karyawan" value="<?= htmlspecialchars($row['nama_karyawan']); ?>" required>
                </div>
                <div class="mb-3">
                    <label for="nomor_ponsel" class="form-label"><i class="fas fa-phone me-2"></i>Nomor HP</label>
                    <input type="text" class="form-control" id="nomor_ponsel" name="nomor_ponsel" value="<?= htmlspecialchars($row['nomor_ponsel']); ?>" required>
                </div>
                <div class="mb-3">
                    <label for="alamat" class="form-label"><i class="fas fa-map-marker-alt me-2"></i>Alamat</label>
                    <input type="text" class="form-control" id="alamat" name="alamat" value="<?= htmlspecialchars($row['alamat']); ?>" required>
                </div>
                <div class="mb-3">
                    <label for="tanggal_masuk" class="form-label"><i class="fas fa-calendar-alt me-2"></i>Tanggal Masuk</label>
                    <input type="date" class="form-control" id="tanggal_masuk" name="tanggal_masuk" value="<?= htmlspecialchars($row['tanggal_masuk']); ?>" required>
                </div>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save me-2"></i>Simpan Perubahan</button>
                <a href="../data-karyawan.php" class="btn btn-secondary"><i class="fas fa-arrow-left me-2"></i>Kembali</a>
            </form>
        </div>
    </div>
</body>
</html>
