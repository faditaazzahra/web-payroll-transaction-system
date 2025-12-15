<?php
session_start();
if (!isset($_SESSION['id_user'])) {
    header('Location: index.php');
    exit();
}

include 'config/koneksi.php';
date_default_timezone_set('Asia/Jakarta');

// Inisialisasi variabel
$tanggal = isset($_GET['tanggal']) ? $_GET['tanggal'] : date('Y-m-d');
$id_karyawan = isset($_GET['id_karyawan']) ? $_GET['id_karyawan'] : null;

// Ambil data karyawan
$sql_list_karyawan = "SELECT id_karyawan, nama_karyawan FROM karyawan WHERE status_karyawan = 'aktif'";
$result_list_karyawan = $conn->query($sql_list_karyawan);
$list_karyawan = [];
while ($row = $result_list_karyawan->fetch_assoc()) {
    $list_karyawan[] = $row;
}

// Jika tidak ada id_karyawan yang dipilih, gunakan id_karyawan pertama dari list
if ($id_karyawan === null && !empty($list_karyawan)) {
    $id_karyawan = $list_karyawan[0]['id_karyawan'];
}

// Ambil data upah harian karyawan
$upah_data = [];
$nama_karyawan = "";
$total_upah_sebenarnya = 0;
$total_upah_pembulatan = 0;

if ($id_karyawan) {
    // Ambil nama karyawan
    $sql_nama = "SELECT nama_karyawan FROM karyawan WHERE id_karyawan = $id_karyawan";
    $result_nama = $conn->query($sql_nama);
    if ($result_nama->num_rows > 0) {
        $nama_karyawan = $result_nama->fetch_assoc()['nama_karyawan'];
    }

    // Ambil data transaksi karyawan pada tanggal tersebut
$sql_transaksi = "SELECT k.plat_nomor, k.jenis_kendaraan, k.ukuran, k.merk, k.warna, 
ph.waktu_transaksi, ph.untuk_karyawan, ph.id_pendapatan,
(SELECT COUNT(DISTINCT id_karyawan) FROM transaksi_karyawan WHERE id_pendapatan = ph.id_pendapatan) as jumlah_karyawan_terlibat,
jl.nama_layanan
FROM pendapatan_harian ph 
JOIN transaksi_karyawan tk ON ph.id_pendapatan = tk.id_pendapatan
JOIN kendaraan k ON ph.id_kendaraan = k.id_kendaraan
JOIN jenis_layanan jl ON ph.id_layanan = jl.id_layanan
WHERE ph.tanggal_transaksi = '$tanggal'
AND tk.id_karyawan = $id_karyawan
GROUP BY ph.id_pendapatan";

$result_transaksi = $conn->query($sql_transaksi);

if ($result_transaksi->num_rows > 0) {
while ($row = $result_transaksi->fetch_assoc()) {
// Hitung upah per karyawan berdasarkan jumlah karyawan yang terlibat
$upah_per_karyawan = $row['untuk_karyawan'] / $row['jumlah_karyawan_terlibat'];
$total_upah_sebenarnya += $upah_per_karyawan;

$upah_data[] = [
'plat_nomor' => $row['plat_nomor'],
'jenis_kendaraan' => $row['jenis_kendaraan'],
'ukuran' => $row['ukuran'],
'merk' => $row['merk'],
'warna' => $row['warna'],
'waktu_transaksi' => $row['waktu_transaksi'],
'untuk_karyawan' => $row['untuk_karyawan'],
'jumlah_karyawan' => $row['jumlah_karyawan_terlibat'],
'upah_per_karyawan' => $upah_per_karyawan,
'nama_layanan' => $row['nama_layanan']
];
}
}
    
    // Hitung upah pembulatan
    $total_upah_pembulatan = ceil($total_upah_sebenarnya/100) * 100;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rincian Perhitungan Upah Harian</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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
            margin-bottom: 20px;
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
            border-bottom: 0;
        }

        .card-header h5 {
            margin: 0;
            font-size: 1.25rem;
            font-weight: 600;
        }

        .card-body {
            padding: 25px;
        }

        .filter-section {
            background-color: #f1f5fe;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
        }

        .form-select, .form-control {
            border-radius: 10px;
            padding: 10px 15px;
            border: 1px solid #dce1eb;
        }

        .form-select:focus, .form-control:focus {
            box-shadow: 0 0 0 0.2rem rgba(78,115,223,0.25);
            border-color: #4e73df;
        }

        table { 
            border-radius: 10px;
            overflow: hidden;
            width: 100%;
            margin-bottom: 20px;
        }

        th {
            background: linear-gradient(45deg, #4e73df, #224abe);
            color: white;
            font-weight: 500;
            text-align: center;
        }
        
        tr { 
            transition: all 0.2s ease;
        }

        tr:hover {
            background-color: #f8f9fa;
        }

        .btn-primary {
            background: linear-gradient(45deg, #4e73df, #224abe);
            border: none;
        }

        .btn-primary, .btn-secondary {
            padding: 10px 20px;
            border-radius: 10px;
            transition: all 0.3s ease;
            color: white;
        }

        .btn-primary:hover, .btn-secondary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        .btn-secondary {
            background: #6c757d;
            border: none;
        }

        .summary-box {
            background: #f1f5fe;
            border-radius: 10px;
            padding: 15px;
            margin-top: 10px;
        }

        .summary-item {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #dce1eb;
        }

        .summary-item:last-child {
            border-bottom: none;
        }

        .summary-item.total {
            font-weight: bold;
            color: #224abe;
            font-size: 1.1em;
        }

        .badge-info {
            background-color: #36b9cc;
            color: white;
            border-radius: 30px;
            padding: 5px 10px;
            font-size: 0.8em;
            font-weight: 500;
        }

        .detail-row {
            margin-bottom: 10px;
        }

        .detail-label {
            font-weight: 500;
            color: #4e73df;
        }

        @media print {
            .no-print {
                display: none !important;
            }
            body {
                background-color: white;
                padding: 0;
            }
            .card {
                box-shadow: none;
                border: 1px solid #ddd;
            }
            .card:hover {
                transform: none;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5><i class="fa-solid fa-file-invoice-dollar me-2"></i>Rincian Perhitungan Upah Harian</h5>
                <div>
                    <button onclick="window.print();" class="btn btn-sm btn-outline-light no-print">
                        <i class="fas fa-print me-1"></i>Cetak
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="filter-section no-print">
                    <form method="get" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]);?>" class="row g-3 align-items-end">
                        <div class="col-md-4">
                            <label for="id_karyawan" class="form-label"><i class="fa-solid fa-user me-1"></i>Karyawan:</label>
                            <select class="form-select" id="id_karyawan" name="id_karyawan" required>
                                <?php foreach ($list_karyawan as $k): ?>
                                <option value="<?php echo $k['id_karyawan']; ?>" <?php echo ($k['id_karyawan'] == $id_karyawan) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($k['nama_karyawan']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="tanggal" class="form-label"><i class="fa-solid fa-calendar-alt me-1"></i>Tanggal:</label>
                            <input type="date" class="form-control" id="tanggal" name="tanggal" value="<?php echo $tanggal; ?>" required>
                        </div>
                        <div class="col-md-4">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-search me-1"></i>Tampilkan
                            </button>
                        </div>
                    </form>
                </div>

                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h4>Rincian Upah Harian - <?php echo date('d F Y', strtotime($tanggal)); ?></h4>
                    <a href="upah-harian.php" class="btn btn-secondary no-print">
                        <i class="fas fa-arrow-left me-1"></i>Kembali
                    </a>
                </div>

                <?php if ($id_karyawan && !empty($nama_karyawan)): ?>
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="detail-row">
                            <span class="detail-label">Nama Karyawan:</span> 
                            <?php echo htmlspecialchars($nama_karyawan); ?>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Tanggal:</span> 
                            <?php echo date('d F Y', strtotime($tanggal)); ?>
                        </div>
                    </div>
                </div>

                <?php if (!empty($upah_data)): ?>
                <div class="table-responsive">
                    <table class="table table-striped table-bordered">
                        <thead>
                            <tr>
                                <th>Plat Nomor</th>
                                <th>Waktu Transaksi</th>
                                <th>Jenis Layanan</th>
                                <th>Untuk Karyawan</th>
                                <th>Jumlah Karyawan Terlibat</th>
                                <th>Upah Bersih</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($upah_data as $data): ?>
                            <tr>
                                <td class="text-center">
                                    <?php echo htmlspecialchars($data['plat_nomor']); ?>
                                    <div>
                                        <small class="badge-info">
                                            <?php echo $data['jenis_kendaraan'] . ' ' . $data['ukuran']; ?>
                                        </small>
                                    </div>
                                    <div>
                                        <small><?php echo $data['merk'] . ' - ' . $data['warna']; ?></small>
                                    </div>
                                </td>
                                <td class="text-center"><?php echo date('H:i', strtotime($data['waktu_transaksi'])); ?></td>
                                <td class="text-center"><?php echo htmlspecialchars($data['nama_layanan']); ?></td>
                                <td class="text-center">Rp <?php echo number_format($data['untuk_karyawan'], 2); ?></td>
                                <td class="text-center"><?php echo $data['jumlah_karyawan']; ?></td>
                                <td class="text-center">Rp <?php echo number_format($data['upah_per_karyawan'], 2); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="summary-box">
                    <div class="summary-item">
                        <span>Total Upah Sebenarnya:</span>
                        <span>Rp <?php echo number_format($total_upah_sebenarnya, 2); ?></span>
                    </div>
                    <div class="summary-item">
                        <span>Pembulatan:</span>
                        <span>Rp <?php echo number_format($total_upah_pembulatan - $total_upah_sebenarnya, 2); ?></span>
                    </div>
                    <div class="summary-item total">
                        <span>Total Upah Setelah Pembulatan:</span>
                        <span>Rp <?php echo number_format($total_upah_pembulatan, 2); ?></span>
                    </div>
                </div>
                
                <div class="mt-3">
                    <small class="text-muted">
                        <i class="fas fa-info-circle me-1"></i>Catatan: Pembulatan dilakukan ke atas hingga kelipatan 100 untuk memudahkan pembayaran.
                    </small>
                </div>
                
                <?php else: ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>Tidak ada data transaksi untuk karyawan ini pada tanggal yang dipilih.
                </div>
                <?php endif; ?>
                
                <?php else: ?>
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle me-2"></i>Harap pilih karyawan dan tanggal untuk melihat rincian upah harian.
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        // Script untuk auto submit form ketika select atau date berubah
        document.getElementById('id_karyawan').addEventListener('change', function() {
            this.form.submit();
        });
        
        document.getElementById('tanggal').addEventListener('change', function() {
            this.form.submit();
        });
    </script>
</body>
</html>

<?php
$conn->close();
?>