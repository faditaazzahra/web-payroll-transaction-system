<?php
session_start();

// Pastikan admin sudah login
if (!isset($_SESSION['id_user'])) {
    header('Location: ../index.php');
    exit();
}

include '../config/koneksi.php';

// Validasi ID pendapatan
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['error'] = "ID transaksi tidak valid!";
    header('Location: transaksi-harian.php');
    exit();
}

$id_pendapatan = $_GET['id'];

// Ambil data pendapatan harian dari database dengan JOIN yang lengkap
$query = "SELECT ph.*, k.*, j.nama_layanan, j.harga,
    (SELECT COUNT(*) FROM kendaraan WHERE plat_nomor = k.plat_nomor) AS jumlah_kedatangan
    FROM pendapatan_harian ph
    JOIN kendaraan k ON ph.id_kendaraan = k.id_kendaraan
    JOIN jenis_layanan j ON ph.id_layanan = j.id_layanan
    WHERE ph.id_pendapatan = ?";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $id_pendapatan);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $_SESSION['error'] = "Data transaksi tidak ditemukan!";
    header('Location: transaksi-harian.php');
    exit();
}

$row = $result->fetch_assoc();

// Proses form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        // Mulai transaksi
        $conn->begin_transaction();

        // Data kendaraan
        $plat_nomor = $_POST['plat_nomor'];
        $merk = $_POST['merk'];
        $warna = $_POST['warna'];
        $no_hp = $_POST['no_hp'];
        $jenis_penggunaan = $_POST['jenis_penggunaan'];
        $jenis_kendaraan = $_POST['jenis_kendaraan'];
        $ukuran_kendaraan = $_POST['ukuran'];

        // Data pendapatan
        $id_layanan = $_POST['id_layanan'];
        $total_pendapatan = $_POST['total_pendapatan'];
        $untuk_karyawan = $_POST['untuk_karyawan'];
        $untuk_perusahaan = $total_pendapatan - $untuk_karyawan;

        // Update tabel kendaraan
        $sql_kendaraan = "UPDATE kendaraan SET 
            plat_nomor = ?,
            merk = ?,
            warna = ?,
            no_hp = ?,
            jenis_penggunaan = ?,
            jenis_kendaraan = ?,
            ukuran = ?
            WHERE id_kendaraan = ?";
        
        $stmt_kendaraan = $conn->prepare($sql_kendaraan);
        $stmt_kendaraan->bind_param("sssssssi", 
            $plat_nomor, 
            $merk, 
            $warna, 
            $no_hp, 
            $jenis_penggunaan, 
            $jenis_kendaraan, 
            $ukuran_kendaraan, 
            $row['id_kendaraan']
        );
        $stmt_kendaraan->execute();

        // Update tabel pendapatan_harian
        $sql_pendapatan = "UPDATE pendapatan_harian SET 
            id_layanan = ?,
            total_pendapatan = ?,
            untuk_karyawan = ?,
            untuk_perusahaan = ?
            WHERE id_pendapatan = ?";
        
        $stmt_pendapatan = $conn->prepare($sql_pendapatan);
        $stmt_pendapatan->bind_param("iiiii", 
            $id_layanan, 
            $total_pendapatan, 
            $untuk_karyawan, 
            $untuk_perusahaan, 
            $id_pendapatan
        );
        $stmt_pendapatan->execute();

        // Commit transaksi
        $conn->commit();

        $_SESSION['success'] = "Data transaksi berhasil diperbarui!";
        header('Location: ../transaksi-harian.php');
        exit();

    } catch (Exception $e) {
        // Rollback jika terjadi error
        $conn->rollback();
        $_SESSION['error'] = "Terjadi kesalahan: " . $e->getMessage();
        header('Location: edit-transaksi.php?id=' . $id_pendapatan);
        exit();
    }
}

// Ambil data layanan untuk dropdown
$layanan_query = "SELECT id_layanan, nama_layanan, harga FROM jenis_layanan";
$layanan_result = $conn->query($layanan_query);
$layanan_options = [];
while ($layanan = $layanan_result->fetch_assoc()) {
    $layanan_options[] = $layanan;
}

// Tutup koneksi statement
if (isset($stmt)) {
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Transaksi - Satria Bima Wash (SBW)</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
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
    </style>
</head>
<body>
    <div class="card">
        <div class="card-header">
            <h5 class="card-title"><i class="fas fa-edit edit-icon me-2"></i>Edit Transaksi Harian</h5>
        </div>
        <div class="card-body">
            <div class="d-flex justify-content-end mb-3">
                <a href="../transaksi-harian.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left me-2"></i>Kembali ke Transaksi Harian
                </a>
            </div>
            <form action="" method="POST">
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="plat_nomor" class="form-label"><i class="fas fa-car me-2"></i>Plat Nomor:</label>
                            <input type="text" class="form-control" id="plat_nomor" name="plat_nomor" value="<?= $row['plat_nomor'] ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="tanggal_transaksi" class="form-label"><i class="fas fa-calendar-alt me-2"></i>Tanggal Transaksi:</label>
                            <input type="text" class="form-control" id="tanggal_transaksi" name="tanggal_transaksi" value="<?= $row['tanggal_transaksi'] ?>" readonly>
                        </div>
                        <div class="mb-3">
                            <label for="waktu_transaksi" class="form-label"><i class="fas fa-clock me-2"></i>Waktu Transaksi:</label>
                            <input type="text" class="form-control" id="waktu_transaksi" name="waktu_transaksi" value="<?= $row['waktu_transaksi'] ?>" readonly>
                        </div>
                        <div class="mb-3">
                            <label for="merk" class="form-label"><i class="fas fa-tag me-2"></i>Merk Kendaraan:</label>
                            <input type="text" class="form-control" id="merk" name="merk" value="<?= $row['merk'] ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="warna" class="form-label">Warna Kendaraan:</label>
                            <input type="text" class="form-control" id="warna" name="warna" value="<?= $row['warna'] ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="jenis_kendaraan" class="form-label"><i class="fas fa-car me-2"></i>Jenis Kendaraan:</label>
                            <select class="form-select" id="jenis_kendaraan" name="jenis_kendaraan" required>
                                <option value="Motor" <?= $row['jenis_kendaraan'] == 'Motor' ? 'selected' : '' ?>>Motor</option>
                                <option value="Mobil" <?= $row['jenis_kendaraan'] == 'Mobil' ? 'selected' : '' ?>>Mobil</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="ukuran" class="form-label"><i class="fas fa-expand-arrows-alt me-2"></i>Ukuran Kendaraan:</label>
                            <select class="form-select" id="ukuran" name="ukuran" required>
                                <option value="Kecil" <?= $row['ukuran'] == 'Kecil' ? 'selected' : '' ?>>Kecil</option>
                                <option value="Medium" <?= $row['ukuran'] == 'Medium' ? 'selected' : '' ?>>Medium</option>
                                <option value="Besar" <?= $row['ukuran'] == 'Besar' ? 'selected' : '' ?>>Besar</option>
                            </select>
                        </div>
                    </div>

                    <!--- Kolom Kanan --->
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="no_hp" class="form-label"><i class="fas fa-phone me-2"></i>Nomor HP:</label>
                            <input type="text" class="form-control" id="no_hp" name="no_hp" value="<?= $row['no_hp'] ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="jenis_penggunaan" class="form-label"><i class="fas fa-users me-2"></i>Jenis Penggunaan:</label>
                            <select class="form-select" id="jenis_penggunaan" name="jenis_penggunaan" required>
                                <option value="Umum" <?= $row['jenis_penggunaan'] == 'Umum' ? 'selected' : '' ?>>Umum</option>
                                <option value="Online" <?= $row['jenis_penggunaan'] == 'Online' ? 'selected' : '' ?>>Online</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="id_layanan" class="form-label"><i class="fas fa-list me-2"></i>Layanan:</label>
                            <select class="form-select" id="id_layanan" name="id_layanan" required>
                                <?php
                                $layanan_result = $conn->query("SELECT id_layanan, nama_layanan FROM jenis_layanan");
                                while($layanan = $layanan_result->fetch_assoc()): ?>
                                    <option value="<?= $layanan['id_layanan'] ?>" <?= $row['nama_layanan'] == $layanan['nama_layanan'] ? 'selected' : '' ?>><?= $layanan['nama_layanan'] ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="total_pendapatan" class="form-label"><i class="fas fa-money-bill me-2"></i>Total Pendapatan:</label>
                            <input type="number" class="form-control" id="total_pendapatan" name="total_pendapatan" value="<?= $row['total_pendapatan'] ?>" readonly>
                        </div>
                        <div class="mb-3">
                            <label for="untuk_karyawan" class="form-label"><i class="fas fa-user-alt me-2"></i>Upah untuk Karyawan:</label>
                            <input type="number" class="form-control" id="untuk_karyawan" name="untuk_karyawan" value="<?= $row['untuk_karyawan'] ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="untuk_perusahaan" class="form-label"><i class="fas fa-building me-2"></i>Upah untuk Perusahaan:</label>
                            <input type="number" class="form-control" id="untuk_perusahaan" name="untuk_perusahaan" value="<?= $row['untuk_perusahaan'] ?>" readonly>
                        </div>
                    </div>
                </div>
                <div class="col-12">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
    $(document).ready(function() {
        // Array untuk menyimpan data layanan
        const layananData = <?php echo json_encode($layanan_options); ?>;

        // Handler perubahan layanan
        $('#id_layanan').change(function() {
            const selectedLayanan = layananData.find(l => l.id_layanan == $(this).val());
            if (selectedLayanan) {
                $('#total_pendapatan').val(selectedLayanan.harga);
                // Update upah karyawan (50% dari total pendapatan)
                const upahKaryawan = selectedLayanan.harga * 0.5;
                $('#untuk_karyawan').val(upahKaryawan);
                $('#untuk_perusahaan').val(selectedLayanan.harga - upahKaryawan);
            }
        });

        // Handler perubahan upah karyawan
        $('#untuk_karyawan').change(function() {
            const totalPendapatan = parseFloat($('#total_pendapatan').val());
            const upahKaryawan = parseFloat($(this).val());
            
            if (upahKaryawan > totalPendapatan) {
                alert('Upah karyawan tidak boleh melebihi total pendapatan!');
                $(this).val(totalPendapatan * 0.5);
                $('#untuk_perusahaan').val(totalPendapatan * 0.5);
            } else {
                $('#untuk_perusahaan').val(totalPendapatan - upahKaryawan);
            }
        });
    });
    </script>
</body>
</html>