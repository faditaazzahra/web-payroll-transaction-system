<?php
session_start();

// Pastikan admin sudah login
if (!isset($_SESSION['id_user'])) {
    header('Location: index.php');
    exit();
}

include 'config/koneksi.php';

// Set tanggal hari ini
$tanggal_hari_ini = date('Y-m-d');

// Cek apakah ada catatan absensi untuk hari ini
$query_check = "SELECT COUNT(*) AS count FROM absensi WHERE tanggal = ?";
$stmt_check = $conn->prepare($query_check);
$stmt_check->bind_param('s', $tanggal_hari_ini);
$stmt_check->execute();
$stmt_check->bind_result($count);
$stmt_check->fetch();
$stmt_check->close();

// Jika tidak ada catatan untuk hari ini, hapus semua absensi yang sudah ada
if ($count == 0) {
    // Hapus semua catatan di tabel absensi
    $query_delete = "DELETE FROM absensi";
    $conn->query($query_delete);
}

// Proses jika form dikirim
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] == 'jam_masuk') {
            $id_karyawan = $_POST['id_karyawan'];
            $jam_masuk = $_POST['jam_masuk'];

            // Insert data ke tabel absensi
            $sql_absensi = "INSERT INTO absensi (id_karyawan, tanggal, jam_masuk) VALUES (?, ?, ?)";
            $stmt_absensi = $conn->prepare($sql_absensi);
            $stmt_absensi->bind_param('iss', $id_karyawan, $tanggal_hari_ini, $jam_masuk);
            $stmt_absensi->execute();
            $stmt_absensi->close();
        } elseif ($_POST['action'] == 'jam_keluar') {
            $id_karyawan = $_POST['id_karyawan'];
            $jam_keluar = $_POST['jam_keluar'];

            // Update jam keluar
            $sql_update = "UPDATE absensi SET jam_keluar = ? WHERE id_karyawan = ? AND tanggal = ?";
            $stmt_update = $conn->prepare($sql_update);
            $stmt_update->bind_param('sis', $jam_keluar, $id_karyawan, $tanggal_hari_ini);
            $stmt_update->execute();
            $stmt_update->close();
        }
    }

    header('Location: absensi.php'); // Redirect ke halaman absensi setelah menambahkan
    exit();
}

// Ambil data karyawan dari database untuk dropdown
$karyawan_result = $conn->query("SELECT id_karyawan, nama_karyawan FROM karyawan");

// Ambil data absensi untuk ditampilkan
$absensi_result = $conn->query("SELECT a.id_karyawan, a.tanggal, a.jam_masuk, a.jam_keluar, k.nama_karyawan FROM absensi a JOIN karyawan k ON a.id_karyawan = k.id_karyawan");

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Absensi Karyawan</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="assets/js/reminder.js"></script>
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            padding: 20px;
        }

        .main-container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
            overflow: hidden;
            transition: all 0.3s ease;
            margin-bottom: 20px;
        }

        .card-header {
            background: linear-gradient(45deg, #4e73df, #224abe);
            color: white;
            padding: 20px;
            font-weight: 600;
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
            border-radius: 20px;
            padding: 10px 15px;
            border: 1px solid #ddd;
        }

        .form-control:focus, .form-select:focus {
            border-color: #4e73df;
            box-shadow: 0 0 0 0.2rem rgba(78,115,223,0.25);
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

        .table {
            border-radius: 10px;
            overflow: hidden;
        }

        .table th {
            background: linear-gradient(45deg, #4e73df, #224abe);
            color: white;
            font-weight: 500;
        }

        .table td {
            vertical-align: middle;
        }

        .table tr {
            transition: all 0.2s ease;
        }

        .table tr:hover {
            background-color: #f8f9fa;
            transform: scale(1.01);
        }

        .input-group-text {
            background-color: #4e73df;
            color: white;
            border: none;
        }

        @media (max-width: 768px) {
            .main-container {
                padding: 10px;
            }
        }
    </style>
</head>
<body>
    <div class="main-container">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title"><i class="fas fa-user-check me-2"></i>Absensi Karyawan</h5>
            </div>
    
            <div class="card-body">
                <div class="d-flex justify-content-end mb-3">
                    <a href="dashboard.php" class="btn btn-secondary mb-3">
                        <i class="fas fa-arrow-left me-2"></i> Kembali ke Dashboard
                    </a>
                </div>
        
                <!-- Form untuk menambahkan jam masuk karyawan -->
                <form action="" method="POST" class="mb-4">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="id_karyawan" class="form-label"><i class="fas fa-user-check me-2"></i>Pilih Karyawan</label>
                                <select class="form-control" id="id_karyawan" name="id_karyawan" required>
                                    <option value="">Pilih Karyawan</option>
                                    <?php while ($karyawan = $karyawan_result->fetch_assoc()): ?>
                                        <option value="<?= $karyawan['id_karyawan']; ?>">
                                            <?= htmlspecialchars($karyawan['nama_karyawan']); ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="jam_masuk" class="form-label"><i class="fas fa-clock me-2"></i>Jam Masuk</label>
                                <input type="time" class="form-control" id="jam_masuk" name="jam_masuk" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="action" class="form-label">&nbsp;</label>
                                <button type="submit" class="btn btn-primary form-control" name="action" value="jam_masuk">
                                    <i class="fas fa-plus"></i> Tambah Jam Masuk
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
    
                <!-- Form untuk menambahkan jam keluar karyawan -->
                <form action="" method="POST" class="mb-4">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="id_karyawan_keluar" class="form-label"><i class="fas fa-user-check me-2"></i>Pilih Karyawan</label>
                                <select class="form-control" id="id_karyawan_keluar" name="id_karyawan" required>
                                    <option value="">Pilih Karyawan</option>
                                    <?php 
                                    $karyawan_result->data_seek(0);
                                    while ($karyawan = $karyawan_result->fetch_assoc()): 
                                    ?>
                                        <option value="<?= $karyawan['id_karyawan']; ?>">
                                            <?= htmlspecialchars($karyawan['nama_karyawan']); ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="jam_keluar" class="form-label"><i class="fas fa-clock me-2"></i>Jam Keluar</label>
                                <input type="time" class="form-control" id="jam_keluar" name="jam_keluar" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="action" class="form-label">&nbsp;</label>
                                <button type="submit" class="btn btn-primary form-control" name="action" value="jam_keluar">
                                    <i class="fas fa-plus"></i> Tambah Jam Keluar
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    
        <div class="card">
            <div class ="card-header">
                <h5 class="card-title"><i class="fas fa-table me-2"></i>Data Absensi Harian</h5>
            </div>
    
            <div class="card-body">
                <table class="table table-striped mt-1">
                    <thead class="text-center">
                        <tr>
                            <th>ID Karyawan</th>
                            <th>Nama Karyawan</th>
                            <th>Tanggal</th>
                            <th>Jam Masuk</th>
                            <th>Jam Keluar</th>
                        </tr>
                    </thead>
                    <tbody class="text-center">
                        <?php while ($row = $absensi_result->fetch_assoc()): ?>
                            <tr>
                                <td><?= $row['id_karyawan']; ?></td>
                                <td><?= htmlspecialchars($row['nama_karyawan']); ?></td>
                                <td><?= $row['tanggal']; ?></td>
                                <td><?= $row['jam_masuk']; ?></td>
                                <td><?= $row['jam_keluar']; ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>