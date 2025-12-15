<?php
session_start();
require_once '../config/koneksi.php';

// Cek apakah ID transaksi kas tersedia di URL
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: ../transaksi-kas.php");
    exit();
}

$id_transaksi = $_GET['id'];

// Ambil data transaksi kas yang akan diedit
$query = "SELECT * FROM transaksi_kas WHERE id_transaksi_kas = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $id_transaksi);
$stmt->execute();
$result = $stmt->get_result();
$transaksi = $result->fetch_assoc();

// Jika data tidak ditemukan
if (!$transaksi) {
    header("Location: ../transaksi-kas.php");
    exit();
}

// Proses form ketika disubmit
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $tanggal = $_POST['tanggal'];
    $jumlah = $_POST['jumlah'];
    $id_kategori = $_POST['id_kategori'];
    $deskripsi = $_POST['deskripsi'];
    $jenis_transaksi = $_POST['jenis_transaksi'];
    $id_user = $_SESSION['user_id'];

    try {
        // Mulai transaksi
        $conn->begin_transaction();

        // Update data transaksi kas
        $update_query = "UPDATE transaksi_kas 
                        SET tanggal = ?, 
                            jumlah = ?, 
                            id_kategori = ?, 
                            deskripsi = ?, 
                            jenis_transaksi = ?,
                            id_user = ?
                        WHERE id_transaksi_kas = ?";
                        
        $stmt = $conn->prepare($update_query);
        $stmt->bind_param("sdsssii", 
            $tanggal, 
            $jumlah, 
            $id_kategori, 
            $deskripsi, 
            $jenis_transaksi,
            $id_user,
            $id_transaksi
        );
        
        $stmt->execute();

        // Update saldo di transaksi_rekening terkait
        $update_saldo_query = "SELECT id_rekening FROM transaksi_rekening WHERE id_transaksi_kas = ?";
        $stmt = $conn->prepare($update_saldo_query);
        $stmt->bind_param("i", $id_transaksi);
        $stmt->execute();
        $rekening_result = $stmt->get_result();
        $rekening_data = $rekening_result->fetch_assoc();
        
        if ($rekening_data) {
            // Hitung saldo setelah transaksi
            $saldo_query = "UPDATE transaksi_rekening tr
                           JOIN (
                               SELECT tr2.id_rekening,
                                      @running_total := CASE tk2.jenis_transaksi
                                          WHEN 'Pemasukan' THEN @running_total + tk2.jumlah
                                          WHEN 'Pengeluaran' THEN @running_total - tk2.jumlah
                                      END AS saldo_setelah
                               FROM (SELECT @running_total := 0) vars,
                                    transaksi_rekening tr2
                                    JOIN transaksi_kas tk2 ON tr2.id_transaksi_kas = tk2.id_transaksi_kas
                               WHERE tr2.id_rekening = ?
                               ORDER BY tk2.tanggal, tk2.id_transaksi_kas
                           ) calcs ON tr.id_rekening = calcs.id_rekening
                           SET tr.saldo_setelah_transaksi = calcs.saldo_setelah
                           WHERE tr.id_transaksi_kas = ?";
            
            $stmt = $conn->prepare($saldo_query);
            $stmt->bind_param("ii", $rekening_data['id_rekening'], $id_transaksi);
            $stmt->execute();

            // Update saldo akhir di tabel rekening
            $update_rekening_query = "UPDATE rekening r
                                    SET r.saldo = (
                                        SELECT MAX(tr.saldo_setelah_transaksi)
                                        FROM transaksi_rekening tr
                                        WHERE tr.id_rekening = r.id_rekening
                                    )
                                    WHERE r.id_rekening = ?";
            
            $stmt = $conn->prepare($update_rekening_query);
            $stmt->bind_param("i", $rekening_data['id_rekening']);
            $stmt->execute();
        }

        // Commit transaksi
        $conn->commit();
        
        header("Location: ../transaksi-kas.php?status=success&message=" . urlencode("Transaksi kas berhasil diupdate"));
        exit();

    } catch (Exception $e) {
        // Rollback transaksi jika terjadi error
        $conn->rollback();
        $error_message = "Terjadi kesalahan: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Transaksi Kas</title>
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
            height: 80px;
        }

        .card-title {
            margin: 0;
            font-size: 1.25rem;
            font-weight: 600;
            padding-top: 10px;
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
    </style>
</head>

<body>
<div class="card">
    <div class="card-header">
        <h5 class="card-title"><i class="fas fa-edit me-2"></i>Edit Transaksi Kas</h5>
    </div>

    <div class="card-body">
        <div class="d-flex justify-content-end mb-3">
            <a href="../transaksi-kas.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-2"></i>Kembali ke Transaksi Kas
            </a>
        </div>
        <?php if (isset($error_message)): ?>
            <div class="alert alert-danger"><?php echo $error_message; ?></div>
        <?php endif; ?>
        <form method="POST" action="">
            <input type="hidden" name="id_transaksi_kas" value="<?php echo $transaksi['id_transaksi_kas']; ?>">
            <div class="row">
                <div class="col-md-4">
                    <div class="mb-3">
                        <label for="tanggal" class="form-label"><i class="fas fa-calendar-alt me-2"></i>Tanggal Transaksi:</label>
                        <input type="date" id="tanggal" name="tanggal" class="form-control" value="<?php echo $transaksi['tanggal']; ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="jumlah" class="form-label"><i class="fas fa-money-bill me-2"></i>Jumlah:</label>
                        <input type="number" id="jumlah" name="jumlah" class="form-control" value="<?php echo $transaksi['jumlah']; ?>" required>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="mb-3">
                        <label for="kategori" class="form-label"><i class="fas fa-university me-2"></i>Kategori:</label>
                        <select id="kategori" name="id_kategori" class="form-control" required>
                            <?php
                            $kategori_result = $conn->query("SELECT id_kategori, nama_kategori FROM kategori");
                            while ($kategori = $kategori_result->fetch_assoc()) {
                                $selected = ($kategori['id_kategori'] == $transaksi['id_kategori']) ? 'selected' : '';
                                echo "<option value='{$kategori['id_kategori']}' $selected>{$kategori['nama_kategori']}</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="jenis_transaksi" class="form-label"><i class="fas fa-exchange-alt me-2"></i>Jenis Transaksi:</label>
                        <select id="jenis_transaksi" name="jenis_transaksi" class="form-control" required>
                            <option value="Pemasukan" <?php echo ($transaksi['jenis_transaksi'] == 'Pemasukan') ? 'selected' : ''; ?>>Pemasukan</option>
                            <option value="Pengeluaran" <?php echo ($transaksi['jenis_transaksi'] == 'Pengeluaran') ? 'selected' : ''; ?>>Pengeluaran</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="mb-3">
                        <label for="deskripsi" class="form-label"><i class="fas fa-pencil-alt me-2"></i>Deskripsi:</label>
                        <textarea id="deskripsi" name="deskripsi" class="form-control" rows="5"><?php echo $transaksi['deskripsi']; ?></textarea>
                    </div>
                </div>
            </div>
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save me-2"></i>Simpan Perubahan
            </button>
        </form>
    </div>
</div>
</body>
</html>
