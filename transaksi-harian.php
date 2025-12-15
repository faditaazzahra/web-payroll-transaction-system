<?php
session_start();

// Pastikan admin sudah login
if (!isset($_SESSION['id_user'])) {
    header('Location: index.php');
    exit();
}

include 'config/koneksi.php';

// Set timezone ke Waktu Indonesia Barat
date_default_timezone_set('Asia/Jakarta');

// Set tanggal dan waktu saat ini
$tanggal_transaksi = date('Y-m-d');
$waktu_transaksi = date('H:i');

// Ambil data layanan dari tabel jenis_layanan
$layanan_result = $conn->query("SELECT id_layanan, nama_layanan, harga FROM jenis_layanan");

// Jika ada ID Kendaraan yang dikirim dari halaman antrian
$id_kendaraan = null;
if (isset($_GET['id_kendaraan'])) {
    $id_kendaraan = $_GET['id_kendaraan'];

    // Ambil data kendaraan berdasarkan id_kendaraan jika tersedia
    $kendaraan_query = $conn->prepare("SELECT * FROM kendaraan WHERE id_kendaraan = ?");
    $kendaraan_query->bind_param("i", $id_kendaraan);
    $kendaraan_query->execute();
    $kendaraan_data = $kendaraan_query->get_result()->fetch_assoc();
    $kendaraan_query->close();
}

// Proses jika form dikirim
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        // Mulai transaksi database
        $conn->begin_transaction();

        $plat_nomor = $_POST['plat_nomor'];
        $merk = $_POST['merk'];
        $warna = $_POST['warna'];
        $jenis_kendaraan = $_POST['jenis_kendaraan'];
        $ukuran_kendaraan = $_POST['ukuran'];
        $no_hp = $_POST['no_hp'];
        $jenis_penggunaan = $_POST['jenis_penggunaan'];
        $id_layanan = $_POST['id_layanan'];
        $total_pendapatan = $_POST['total_pendapatan'];
        $untuk_karyawan = $_POST['untuk_karyawan'];
        $untuk_perusahaan = $total_pendapatan - $untuk_karyawan;

        // Cek apakah kendaraan sudah ada
        $cek_kendaraan = $conn->prepare("SELECT id_kendaraan, jumlah_kedatangan FROM kendaraan WHERE plat_nomor = ?");
        $cek_kendaraan->bind_param("s", $plat_nomor);
        $cek_kendaraan->execute();
        $cek_kendaraan_result = $cek_kendaraan->get_result();

        if ($cek_kendaraan_result->num_rows > 0) {
            $kendaraan_data = $cek_kendaraan_result->fetch_assoc();
            $id_kendaraan = $kendaraan_data['id_kendaraan'];
            $jumlah_kedatangan = $kendaraan_data['jumlah_kedatangan'] + 1;
            
            // Cek promo berdasarkan jumlah kedatangan
            $cek_promo = $conn->prepare("
                SELECT * FROM promo 
                WHERE jenis_kendaraan = ? 
                AND jumlah_kunjungan <= ? 
                ORDER BY jumlah_kunjungan DESC 
                LIMIT 1
            ");
            $cek_promo->bind_param("si", $jenis_kendaraan, $jumlah_kedatangan);
            $cek_promo->execute();
            $promo_result = $cek_promo->get_result();
            
            if($promo_result->num_rows > 0) {
                $promo_data = $promo_result->fetch_assoc();
                $_SESSION['promo_message'] = "Selamat! Kendaraan ini mendapatkan promo: " . $promo_data['deskripsi'];
            }
            
            $update_kendaraan = $conn->prepare("UPDATE kendaraan SET jumlah_kedatangan = ? WHERE id_kendaraan = ?");
            $update_kendaraan->bind_param("ii", $jumlah_kedatangan, $id_kendaraan);
            $update_kendaraan->execute();
            $update_kendaraan->close();
        } else {
            // Kendaraan baru, insert dengan jumlah_kedatangan = 1
            $sql_kendaraan = "INSERT INTO kendaraan (plat_nomor, merk, warna, no_hp, jenis_penggunaan, jenis_kendaraan, ukuran, jumlah_kedatangan) 
                            VALUES (?, ?, ?, ?, ?, ?, ?, 1)";
            $stmt_kendaraan = $conn->prepare($sql_kendaraan);
            $stmt_kendaraan->bind_param('sssssss', $plat_nomor, $merk, $warna, $no_hp, $jenis_penggunaan, $jenis_kendaraan, $ukuran_kendaraan);
            $stmt_kendaraan->execute();
            $id_kendaraan = $conn->insert_id;
            $stmt_kendaraan->close();
        }
        $cek_kendaraan->close();

        // 2. Insert data ke tabel transaksi_karyawan untuk semua karyawan yang bertugas
        $today = date('Y-m-d');
        $current_time = date('H:i:s');

        // Ambil semua karyawan yang sedang bertugas
        $sql_karyawan = "SELECT id_karyawan 
                        FROM absensi 
                        WHERE tanggal = ? 
                        AND jam_masuk IS NOT NULL 
                        AND (jam_keluar IS NULL OR jam_keluar >= ?)";
        $stmt_karyawan = $conn->prepare($sql_karyawan);
        $stmt_karyawan->bind_param('ss', $today, $current_time);
        $stmt_karyawan->execute();
        $result_karyawan = $stmt_karyawan->get_result();

        $karyawan_bertugas = [];
        while ($karyawan = $result_karyawan->fetch_assoc()) {
            $karyawan_bertugas[] = $karyawan['id_karyawan'];
        }

        if (empty($karyawan_bertugas)) {
            throw new Exception("Tidak ada karyawan yang sedang bertugas");
        }

        // Insert transaksi pertama untuk mendapatkan id_transaksi
        $sql_transaksi = "INSERT INTO transaksi_karyawan (id_karyawan) VALUES (?)";
        $stmt_transaksi = $conn->prepare($sql_transaksi);
        $stmt_transaksi->bind_param('i', $karyawan_bertugas[0]);
        $stmt_transaksi->execute();
        $id_transaksi = $conn->insert_id;

        // Insert transaksi untuk karyawan lain yang bertugas
        if (count($karyawan_bertugas) > 1) {
            $sql_transaksi_tambahan = "INSERT INTO transaksi_karyawan (id_karyawan, id_pendapatan) VALUES (?, NULL)";
            $stmt_transaksi_tambahan = $conn->prepare($sql_transaksi_tambahan);
            
            for ($i = 1; $i < count($karyawan_bertugas); $i++) {
                $stmt_transaksi_tambahan->bind_param('i', $karyawan_bertugas[$i]);
                $stmt_transaksi_tambahan->execute();
            }
        }

        // 3. Insert data ke tabel pendapatan_harian
        $sql_pendapatan = "INSERT INTO pendapatan_harian (
            id_kendaraan, 
            tanggal_transaksi, 
            waktu_transaksi, 
            id_layanan, 
            total_pendapatan, 
            untuk_karyawan, 
            untuk_perusahaan,
            id_transaksi
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt_pendapatan = $conn->prepare($sql_pendapatan);
        $stmt_pendapatan->bind_param('issiiiis', 
            $id_kendaraan, 
            $tanggal_transaksi, 
            $waktu_transaksi, 
            $id_layanan, 
            $total_pendapatan, 
            $untuk_karyawan, 
            $untuk_perusahaan,
            $id_transaksi
        );
        $stmt_pendapatan->execute();

        // 4. Update semua record transaksi_karyawan dengan id_pendapatan
        $id_pendapatan = $conn->insert_id;
        $sql_update_transaksi = "UPDATE transaksi_karyawan 
                                SET id_pendapatan = ? 
                                WHERE id_karyawan IN (" . implode(',', $karyawan_bertugas) . ")
                                AND (id_pendapatan IS NULL OR id_pendapatan = 0)";
        $stmt_update = $conn->prepare($sql_update_transaksi);
        $stmt_update->bind_param('i', $id_pendapatan);
        $stmt_update->execute();

        // Commit transaksi jika semua berhasil
        $conn->commit();

        $_SESSION['success'] = "Transaksi berhasil ditambahkan";
        header('Location: transaksi-harian.php');
        exit();

    } catch (Exception $e) {
        // Rollback jika terjadi error
        $conn->rollback();
        $_SESSION['error'] = "Error: " . $e->getMessage();
        header('Location: transaksi-harian.php');
        exit();
    }
}

// Ambil data pendapatan harian dari database
$result = $conn->query("
    SELECT k.plat_nomor, ph.tanggal_transaksi, ph.waktu_transaksi, k.jumlah_kedatangan, 
        k.merk, k.warna, k.no_hp, k.jenis_penggunaan, j.nama_layanan, 
        ph.total_pendapatan, ph.untuk_karyawan, ph.untuk_perusahaan,
        ph.id_pendapatan
    FROM pendapatan_harian ph
    JOIN kendaraan k ON ph.id_kendaraan = k.id_kendaraan
    JOIN jenis_layanan j ON ph.id_layanan = j.id_layanan
    WHERE ph.tanggal_transaksi = CURDATE()  -- Mengambil hanya transaksi hari ini
    ORDER BY ph.waktu_transaksi DESC
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pendapatan Harian - Satria Bima Wash (SBW)</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.32/dist/sweetalert2.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.32/dist/sweetalert2.all.min.js"></script>
    <script src="assets/js/reminder.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
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
            vertical-align: middle;
        }

        .table tr {
            transition: all 0.2s ease;
        }

        .table tr:hover {
            background-color: #f8f9fa;
            transform: scale(1.01);
        }

        .table td {
            vertical-align: middle;
        }

        .input-group-text {
            background-color: #4e73df;
            color: white;
            border: none;
        }

        /* Styling untuk tombol aksi */
        .action-buttons {
            display: flex;
            gap: 5px;
            justify-content: center;
        }

        .btn-edit {
            background-color: #4e73df;
            color: white;
            border: none;
            padding: 6px 12px;
            border-radius: 5px;
            font-size: 0.8rem;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .btn-edit:hover {
            background-color: #2e59d9;
            transform: translateY(-2px);
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
        }

        /* Efek hover untuk baris tabel */
        .table tbody tr {
            transition: all 0.2s ease;
        }

        .table tbody tr:hover {
            background-color: #f8f9fa;
        }

        /* Animasi untuk tombol */
        @keyframes pulse {
            0% {
                transform: scale(1);
            }
            50% {
                transform: scale(1.05);
            }
            100% {
                transform: scale(1);
            }
        }

        .btn-edit:active {
            animation: pulse 0.3s ease;
        }

        /* Tooltip styling */
        .tooltip-action {
            position: relative;
        }

        .tooltip-action:before {
            content: attr(data-tooltip);
            position: absolute;
            bottom: 100%;
            left: 50%;
            transform: translateX(-50%);
            padding: 5px 10px;
            background-color: #333;
            color: white;
            font-size: 12px;
            border-radius: 4px;
            white-space: nowrap;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
        }

        .tooltip-action:hover:before {
            opacity: 1;
            visibility: visible;
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
                <h5 class="card-title"><i class="fas fa-cash-register me-2"></i>Input Transaksi Baru</h5>
            </div>

            <div class="card-body">
                <div class="d-flex justify-content-end mb-3">
                    <a href="dashboard.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left me-2"></i>Kembali ke Dashboard
                    </a>
                </div>
                <form action="" method="POST">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="plat_nomor" class="form-label"><i class="fas fa-car me-2"></i>Plat Nomor:</label>
                                <input type="text"  class="form-control" id="plat_nomor" name="plat_nomor" required>
                            </div>
                            <div class="mb-3">
                                <label for="tanggal_transaksi" class="form-label"><i class="fas fa-calendar-alt me-2"></i>Tanggal Transaksi:</label>
                                <input type="text" class="form-control" id="tanggal_transaksi" name="tanggal_transaksi" value="<?= $tanggal_transaksi ?>" readonly>
                            </div>
                            <div class="mb-3">
                                <label for="waktu_transaksi" class="form-label"><i class="fas fa-clock me-2"></i>Waktu Transaksi:</label>
                                <input type="text" class="form-control" id="waktu_transaksi" name="waktu_transaksi" value="<?= $waktu_transaksi ?>" readonly>
                            </div>
                            <div class="mb-3">
                                <label for="merk" class="form-label"><i class="fas fa-tag me-2"></i>Merk Kendaraan:</label>
                                <input type="text" class="form-control" id="merk" name="merk" required>
                            </div>
                            <div class="mb-3">
                                <label for="warna" class="form-label"><i class="fas fa-palette me-2"></i>Warna Kendaraan:</label>
                                <input type="text" class="form-control" id="warna" name="warna" required>
                            </div>
                            <div class="mb-3">
                                <label for="jenis_kendaraan" class="form-label"><i class="fas fa-car me-2"></i>Jenis Kendaraan:</label>
                                <select class="form-select" id="jenis_kendaraan" name="jenis_kendaraan" required>
                                    <option value="Motor">Motor</option>
                                    <option value="Mobil">Mobil</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="ukuran_kendaraan" class="form-label"><i class="fas fa-expand-arrows-alt me-2"></i>Ukuran Kendaraan:</label>
                                <select class="form-select" id="ukuran_kendaraan" name="ukuran" required>
                                    <option value="Kecil">Kecil</option>
                                    <option value="Medium">Medium</option>
                                    <option value="Besar">Besar</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="no_hp" class="form-label"><i class="fas fa-phone me-2"></i>Nomor HP:</label>
                                <input type="text" class="form-control" id="no_hp" name="no_hp">
                            </div>
                            <div class="mb-3">
                                <label for="jenis_penggunaan" class="form-label"><i class="fas fa-users me-2"></i>Jenis Penggunaan:</label>
                                <select class="form-select" id="jenis_penggunaan" name="jenis_penggunaan" required>
                                    <option value="Umum">Umum</option>
                                    <option value="Online">Online</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="id_layanan" class="form-label"><i class="fas fa-list me-2"></i>Layanan:</label>
                                <select class="form-select" id="id_layanan" name="id_layanan" required>
                                    <?php while($layanan = $layanan_result->fetch_assoc()): ?>
                                        <option value="<?= $layanan['id_layanan'] ?>"><?= $layanan['nama_layanan'] ?></option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="total_pendapatan" class="form-label"><i class="fas fa-money-bill me-2"></i>Total Tarif:</label>
                                <input type="number" class="form-control" id="total_pendapatan" name="total_pendapatan" required>
                            </div>
                            <div class="mb-3">
                                <label for="untuk_karyawan" class="form-label"><i class="fas fa-user-alt me-2"></i>Upah untuk Karyawan:</label>
                                <input type="number" class="form-control" id="untuk_karyawan" name="untuk_karyawan" required>
                            </div>
                            <div class="mb-3">
                                <label for="untuk_perusahaan" class="form-label"><i class="fas fa-building me-2"></i>Pendapatan untuk Perusahaan:</label>
                                <input type="number" class="form-control" id="untuk_perusahaan" name="untuk_perusahaan" readonly>
                            </div>
                        </div>
                    </div>
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i> Simpan Transaksi
                        </button>
                    </div>
                </form>
            </div>
        </div>
    
        <div class="card">
            <div class="card-header">
                <h5 class="card-title"><i class="fas fa-table me-2"></i>Data Transaksi Hari Ini</h5>
            </div>

            <div class="card-body table-responsive">
                <div class="d-flex justify-content-end mb-3">
                    <a href="export/export-transaksi-harian.php" class="btn btn-primary">
                        <i class="fas fa-file-export me-2"></i>Ekspor Data
                    </a>
                </div>

                <table class="table table-striped mt-1">
                    <thead>
                        <tr class="text-center">
                            <th>Plat Nomor</th>
                            <th>Tanggal Transaksi</th>
                            <th>Waktu Transaksi</th>
                            <th>Jumlah Kedatangan</th>
                            <th>Merk Kendaraan </th>
                            <th>Warna Kendaraan</th>
                            <th>Nomor HP</th>
                            <th>Jenis Penggunaan</th>
                            <th>Nama Layanan</th>
                            <th>Total Pendapatan</th>
                            <th>Untuk Karyawan</th>
                            <th>Untuk Perusahaan</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($row = $result->fetch_assoc()): ?>
                            <tr class="text-center">
                                <td><?= $row['plat_nomor'] ?></td>
                                <td><?= $row['tanggal_transaksi'] ?></td>
                                <td><?= $row['waktu_transaksi'] ?></td>
                                <td><?= $row['jumlah_kedatangan'] ?></td>
                                <td><?= $row['merk'] ?></td>
                                <td><?= $row['warna'] ?></td>
                                <td><?= $row['no_hp'] ?></td>
                                <td><?= $row['jenis_penggunaan'] ?></td>
                                <td><?= $row['nama_layanan'] ?></td>
                                <td><?= $row['total_pendapatan'] ?></td>
                                <td><?= $row['untuk_karyawan'] ?></td>
                                <td><?= $row['untuk_perusahaan'] ?></td>
                                <td>
                                    <a href="process/edit-transaksi-jasa.php?id=<?= $row['id_pendapatan'] ?>" class="btn btn-edit tooltip-action" data-tooltip="Edit Transaksi">
                                        <i class="fas fa-edit"></i><span class="d-none d-md-inline">Edit</span>
                                    </a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        $(document).ready(function() {
            // Hitung total pendapatan dan upah untuk perusahaan secara otomatis
            $('#id_layanan').change(function() {
                var id_layanan = $(this).val();
                $.ajax({
                    type: 'POST',
                    url: 'process/get-harga-layanan.php',
                    data: {id_layanan: id_layanan},
                    success: function(data) {
                        $('#total_pendapatan').val(data);
                        var total_pendapatan = parseFloat(data);
                        var upah_karyawan = parseFloat($('#untuk_karyawan').val());
                        var upah_perusahaan = total_pendapatan - upah_karyawan;
                        $('#untuk_perusahaan').val(upah_perusahaan);
                    }
                });
            });

            // Hitung upah untuk perusahaan secara otomatis
            $('#untuk_karyawan').change(function() {
                var upah_karyawan = parseFloat($(this).val());
                var total_pendapatan = parseFloat($('#total_pendapatan').val());
                var upah_perusahaan = total_pendapatan - upah_karyawan;
                $('#untuk_perusahaan').val(upah_perusahaan);
            });
        });
    </script>

    <?php if (isset($_SESSION['success'])): ?>
        <script>
            alert("<?= $_SESSION['success']; ?>");
        </script>
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['promo_message'])): ?>
        <script>
            Swal.fire({
                title: 'Promo Tersedia!',
                text: "<?= $_SESSION['promo_message']; ?>",
                icon: 'success',
                confirmButtonText: 'OK'
            });
        </script>
        <?php unset($_SESSION['promo_message']); ?>
    <?php endif; ?>
</body>
</html>