<?php
session_start();
if (!isset($_SESSION['id_user'])) {
    header('Location: index.php');
    exit();
}

include 'config/koneksi.php';
date_default_timezone_set('Asia/Jakarta');

// Inisialisasi variabel
$tanggal = isset($_POST['tanggal']) ? $_POST['tanggal'] : date('Y-m-d');
$pesan = '';
$total_upah = [];

// Jika form disubmit
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Ambil data transaksi
    $sql_transaksi = "SELECT ph.id_pendapatan, ph.untuk_karyawan, 
                    GROUP_CONCAT(tk.id_karyawan) as karyawan_terlibat
                    FROM pendapatan_harian ph 
                    JOIN transaksi_karyawan tk ON ph.id_pendapatan = tk.id_pendapatan
                    WHERE ph.tanggal_transaksi = '$tanggal'
                    GROUP BY ph.id_pendapatan";
    $result_transaksi = $conn->query($sql_transaksi);
    $transaksi = [];
    while ($row = $result_transaksi->fetch_assoc()) {
        $transaksi[] = [
            'upah_karyawan' => $row['untuk_karyawan'],
            'karyawan_terlibat' => explode(',', $row['karyawan_terlibat'])
        ];
    }

    // Hitung total upah per karyawan
    $karyawan_ids = [];
    foreach ($transaksi as $t) {
        $karyawan_terlibat_valid = array_unique(array_map('intval', $t['karyawan_terlibat']));
        $jumlah_karyawan = count($karyawan_terlibat_valid);

        if ($jumlah_karyawan > 0) {
            $upah_per_karyawan = $t['upah_karyawan'] / $jumlah_karyawan;
            foreach ($karyawan_terlibat_valid as $id_karyawan) {
                if (!isset($total_upah[$id_karyawan])) {
                    $total_upah[$id_karyawan] = 0;
                    $karyawan_ids[] = $id_karyawan;
                }
                $total_upah[$id_karyawan] += $upah_per_karyawan;
            }
        }
    }

    // Pastikan id_karyawan tidak kosong sebelum membuat query
    if (!empty($karyawan_ids)) {
        // Ambil data karyawan yang memiliki jam keluar
        $sql_karyawan = "SELECT k.id_karyawan, k.nama_karyawan 
                        FROM karyawan k
                        JOIN absensi a ON k.id_karyawan = a.id_karyawan
                        WHERE k.status_karyawan = 'aktif' 
                        AND a.tanggal = '$tanggal'
                        AND a.jam_keluar IS NOT NULL
                        AND k.id_karyawan IN (" . implode(',', $karyawan_ids) . ")";
        $result_karyawan = $conn->query($sql_karyawan);
        $karyawan = [];
        while ($row = $result_karyawan->fetch_assoc()) {
            $karyawan[] = [
                'id' => $row['id_karyawan'],
                'nama' => $row['nama_karyawan']
            ];
        }
    } else {
        $karyawan = [];
    }

    // Simpan hasil ke database
    foreach ($total_upah as $id_karyawan => $upah) {
        if ($upah > 0) {
            $sql_select = "SELECT total_upah FROM upah_harian WHERE id_karyawan = $id_karyawan AND tanggal_upah = '$tanggal'";
            $result_select = $conn->query($sql_select);
            if ($result_select->num_rows > 0) {
                $sql_update = "UPDATE upah_harian SET total_upah = $upah WHERE id_karyawan = $id_karyawan AND tanggal_upah = '$tanggal'";
                $conn->query($sql_update);
            } else {
                $sql_insert = "INSERT INTO upah_harian (id_karyawan, tanggal_upah, total_upah) 
                            VALUES ($id_karyawan, '$tanggal', $upah)";
                $conn->query($sql_insert);
            }
        }
    }

    $pesan = "success:Perhitungan upah harian untuk tanggal $tanggal telah selesai dan disimpan.";
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Perhitungan Upah Harian</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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

        form {
            padding: 20px;
        }

        .form-control {
            border-radius: 20px;
            padding: 10px 15px;
        }

        .form-control:focus {
            box-shadow: 0 0 0 0.2rem rgba(78,115,223,0.25);
        }

        .fs-3 {
            color: #224abe;
        }
        
        table { 
            border-radius: 10px;
            overflow: hidden;
        }

        th {
            background: linear-gradient(45deg, #4e73df, #224abe);
            color: white;
            font-weight: 500;
        }
        
        tr { 
            transition: all 0.2s ease;
        }

        tr:hover {
            background-color: #f8f9fa;
            transform: scale(1.01);
        }

        .btn-primary {
            background: linear-gradient(45deg, #4e73df, #224abe);
        }

        .btn-primary, .btn-secondary {
            border: none;
            padding: 10px 20px;
            border-radius: 30px;
            transition: all 0.3s ease;
            color: white;
            margin-top: 10px;
        }

        .btn-primary:hover, .btn-secondary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        .message { 
            background: linear-gradient(45deg, #4e73df, #224abe);
            border-color: #4e73df; 
            color: white; 
            padding: 10px; 
            margin-bottom: 20px; 
            border: 1px solid transparent; 
            border-radius: 4px; 
        }

        .table-primary {
            background: linear-gradient(45deg, #4e73df, #224abe);
            color: black;
        }

        .alert-info {
            background: linear-gradient(45deg, #36b9cc, #1a8a9c);
            color: white;
            border: none;
            border-radius: 10px;
        }

        .btn-info {
            background: linear-gradient(45deg, #36b9cc, #1a8a9c);
            color: white;
            border: none;
            border-radius: 20px;
            transition: all 0.3s ease;
        }

        .btn-info:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            background: linear-gradient(45deg, #1a8a9c, #36b9cc);
        }
    </style>
</head>
<body>
    <div class="card">
        <div class="card-header">
            <h5 class="card-title"><i class="fa-solid fa-calculator me-2"></i>Perhitungan Upah Harian</h5>
        </div>

        <div class="card-body">
            <div class="d-flex justify-content-end mb-3">
                <a href="dashboard.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left me-2"></i>Kembali ke Dashboard
                </a>
            </div>
            <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]);?>">
                <label for="tanggal" class="form-label"><i class="fa-solid fa-calendar-alt me-2"></i>Pilih Tanggal:</label>
                <input type="date" class="form-control" id="tanggal" name="tanggal" value="<?php echo $tanggal; ?>" required>
                <input type="submit" class="btn-primary" value="Hitung Upah">
            </form>

            <?php
            if (!empty($pesan)) {
                list($type, $message) = explode(':', $pesan, 2);
                echo "<script>
                    Swal.fire({
                        icon: '$type',
                        title: 'Pemberitahuan',
                        text: '$message'
                    });
                </script>";
            }

            if (!empty($total_upah)) {
                echo "<h2 class='fs-3 mt-2'>Hasil Perhitungan Upah Harian untuk " . date('d F Y', strtotime($tanggal)) . "</h2>";
                echo "<table class='table table-striped'>";
                echo "<thead>
                        <tr>
                            <th class='text-center'>Nama Karyawan</th>
                            <th class='text-center'>Upah Sebenarnya</th>
                            <th class='text-center'>Upah Pembulatan</th>
                            <th class='text-center'>Selisih Pembulatan</th>
                            <th class='text-center'>Aksi</th>
                        </tr>
                    </thead><tbody>";
                
                $total_upah_sebenarnya = 0;
                $total_upah_pembulatan = 0;
                $total_selisih = 0;
                
                foreach ($karyawan as $k) {
                    $upah_sebenarnya = $total_upah[$k['id']];
                    $upah_pembulatan = ceil($upah_sebenarnya/100) * 100;
                    $selisih = $upah_pembulatan - $upah_sebenarnya;
                    
                    $total_upah_sebenarnya += $upah_sebenarnya;
                    $total_upah_pembulatan += $upah_pembulatan;
                    $total_selisih += $selisih;
                    
                    echo "<tr>";
                    echo "<td class='text-center'>" . htmlspecialchars($k['nama']) . "</td>";
                    echo "<td class='text-center'>Rp " . number_format($upah_sebenarnya, 2) . "</td>";
                    echo "<td class='text-center'>Rp " . number_format($upah_pembulatan, 2) . "</td>";
                    echo "<td class='text-center'>Rp " . number_format($selisih, 2) . "</td>";
                    echo "<td class='text-center'>
                            <a href='rincian-upah-harian.php?id_karyawan=" . $k['id'] . "&tanggal=" . $tanggal . "' class='btn btn-sm btn-info'>
                                <i class='fas fa-file-invoice-dollar me-1'></i>Lihat Rincian
                            </a>
                        </td>";
                    echo "</tr>";
                }
                
                echo "<tr class='table-primary fw-bold'>";
                echo "<td class='text-center'>TOTAL</td>";
                echo "<td class='text-center'>Rp " . number_format($total_upah_sebenarnya, 2) . "</td>";
                echo "<td class='text-center'>Rp " . number_format($total_upah_pembulatan, 2) . "</td>";
                echo "<td class='text-center'>Rp " . number_format($total_selisih, 2) . "</td>";
                echo "<td class='text-center'>-</td>";
                echo "</tr>";
                
                echo "</tbody></table>";
                
                // Menampilkan catatan pembulatan
                echo "<div class='alert alert-info mt-3'>";
                echo "<i class='fas fa-info-circle me-2'></i>Catatan: ";
                echo "Total selisih pembulatan sebesar Rp " . number_format($total_selisih, 2) . " ";
                echo "akan dicatat sebagai biaya pembulatan upah karyawan.";
                echo "</div>";
            }
            ?>
        </div>
    </div>
</body>
</html>

<?php
$conn->close();
?>