<?php
session_start();
if (!isset($_SESSION['id_user'])) {
    header('Location: index.php');
    exit();
}

include 'config/koneksi.php';
date_default_timezone_set('Asia/Jakarta');

// Ambil parameter dari URL
$id_karyawan = isset($_GET['id_karyawan']) ? intval($_GET['id_karyawan']) : 0;
$filter_bulan = isset($_GET['bulan']) ? $_GET['bulan'] : date('m');
$filter_tahun = isset($_GET['tahun']) ? $_GET['tahun'] : date('Y');

// Validasi input
if ($id_karyawan <= 0 || !ctype_digit($filter_bulan) || !ctype_digit($filter_tahun)) {
    die("Invalid parameters.");
}

// Query untuk mengambil detail upah harian karyawan
$sql_detail = "SELECT 
    uh.tanggal_upah,
    uh.total_upah
FROM 
    upah_harian uh
WHERE 
    uh.id_karyawan = ? 
    AND MONTH(uh.tanggal_upah) = ? 
    AND YEAR(uh.tanggal_upah) = ?
ORDER BY 
    uh.tanggal_upah";

$stmt = $conn->prepare($sql_detail);
$stmt->bind_param("iii", $id_karyawan, $filter_bulan, $filter_tahun);
$stmt->execute();
$result_detail = $stmt->get_result();

// Ambil informasi karyawan
$sql_karyawan = "SELECT nama_karyawan FROM karyawan WHERE id_karyawan = ?";
$stmt_karyawan = $conn->prepare($sql_karyawan);
$stmt_karyawan->bind_param("i", $id_karyawan);
$stmt_karyawan->execute();
$result_karyawan = $stmt_karyawan->get_result();

if ($result_karyawan->num_rows === 0) {
    die("Karyawan tidak ditemukan.");
}

$karyawan = $result_karyawan->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Upah Harian Karyawan</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="assets/js/reminder.js"></script>
    <style>
        body { 
            font-family: 'Poppins', sans-serif;
            background-color: #f8f9fa;
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

        .card-title {
            margin: 0;
            font-size: 1.25rem;
            font-weight: 600;
        }

        .table-responsive {
            max-height: 500px;
            overflow-y: auto;
        }

        th {
            position: sticky;
            top: 0;
            background: linear-gradient(45deg, #4e73df, #224abe);
            color: white;
            z-index: 10;
        }

        .export-btn {
            display: inline-block;
            padding: 8px 12px;
            background-color: #4e73df;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            font-size: 14px;
            transition: background-color 0.3s;
        }

        .export-btn:hover {
            background-color: #224abe;
        }
    </style>
</head>
<body>
    <div class="card">
        <div class="card-header">
            <h5 class="card-title">
                <i class="fas fa-user me-2"></i>Detail Upah Harian - <?php echo htmlspecialchars($karyawan['nama_karyawan']); ?>
            </h5>
        </div>
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <a href="laporan-gaji-karyawan.php?bulan=<?php echo $filter_bulan; ?>&tahun=<?php echo $filter_tahun; ?>" class="btn btn-secondary">
                    <i class="fas fa-arrow-left me-2"></i>Kembali ke Laporan
                </a>
                <!-- Tombol ekspor -->
                <a href="export/export-laporan-gaji-per-karyawan.php?id_karyawan=<?= $id_karyawan; ?>&bulan=<?= $filter_bulan; ?>&tahun=<?= $filter_tahun; ?>" class="export-btn" target="_blank">
                    <i class="fas fa-file-export me-2"></i>Ekspor Data
                </a>
            </div>

            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Tanggal</th>
                            <th>Total Upah</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $no = 1;
                        $total_upah = 0;
                        while ($row = $result_detail->fetch_assoc()) {
                            $total_upah += $row['total_upah'];
                            echo "<tr>";
                            echo "<td>" . $no++ . "</td>";
                            echo "<td>" . htmlspecialchars($row['tanggal_upah']) . "</td>";
                            echo "<td>Rp " . number_format($row['total_upah'], 2) . "</td>";
                            echo "</tr>";
                        }
                        ?>
                    </tbody>
                    <tfoot>
                        <tr class="table-active">
                            <td colspan="2" class="text-end fw-bold">Total Upah</td>
                            <td class="fw-bold">Rp <?php echo number_format($total_upah, 2); ?></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</body>
</html>
<?php
$conn->close();
?>
