<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Transaksi Kas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="assets/js/reminder.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
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

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 22px;
            background: linear-gradient(45deg, #4e73df, #224abe);
            color: white;
            border-radius: 15px;
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

        .input-group-text {
            background-color: #4e73df;
            color: white;
            border: none;
        }

        .btn-primary, .btn-light {
            border: none;
            padding: 10px 20px;
            border-radius: 30px;
            transition: all 0.3s ease;
        }

        .btn-primary {
            background: linear-gradient(45deg, #4e73df, #224abe);
        }

        .btn-primary:hover, .btn-light:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        .btn-sm {
            padding: 8px 12px;
            font-size: 0.8rem;
        }

        .d-flex.gap-2 > * {
            margin-right: 5px;
        }

        .d-flex.gap-2 > *:last-child {
            margin-right: 0;
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
            font-size: 0.8rem;
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
        <div class="header">
            <h4>
                <i class="fas fa-book me-3"></i>Transaksi Kas
            </h4>
            <a href="dashboard.php" class="btn btn-light">
                <i class="fas fa-arrow-left me-2"></i> Kembali ke Dashboard
            </a>
        </div>

        <div class="row mt-3">
            <!-- Card Form Transaksi -->
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">
                            <i class="fas fa-plus me-2"></i>Tambah Transaksi Kas
                        </h5>
                    </div>
                    <div class="card-body">
                        <form action="process/proses-transaksi-kas.php" method="POST">
                            <div class="mb-3">
                                <label for="tanggal" class="form-label"><i class="fas fa-calendar-alt me-2"></i>Tanggal Transaksi:</label>
                                <input type="date" id="tanggal" name="tanggal" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label for="kategori" class="form-label"><i class="fas fa-university me-2"></i>Kategori:</label>
                                <select id="kategori" name="id_kategori" class="form-control" required>
                                    <?php
                                    include 'config/koneksi.php';
                                    $result = $conn->query("SELECT id_kategori, nama_kategori FROM kategori");
                                    while ($row = $result->fetch_assoc()) {
                                        echo "<option value='{$row['id_kategori']}'>{$row['nama_kategori']}</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="rekening" class="form-label"><i class="fas fa-piggy-bank me-2"></i>Rekening:</label>
                                <select id="rekening" name="id_rekening" class="form-control" required>
                                    <?php
                                    // Mengambil daftar rekening dari database
                                    $result = $conn->query("SELECT id_rekening, nama_rekening FROM rekening");
                                    while ($row = $result->fetch_assoc()) {
                                        echo "<option value='{$row['id_rekening']}'>{$row['nama_rekening']}</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="jenis_transaksi" class="form-label"><i class="fas fa-exchange-alt me-2"></i>Jenis Transaksi:</label>
                                <select id="jenis_transaksi" name="jenis_transaksi" class="form-control" required>
                                    <option value="Pemasukan">Pemasukan</option>
                                    <option value="Pengeluaran">Pengeluaran</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="jumlah" class="form-label"><i class="fas fa-dollar-sign me-2"></i>Jumlah:</label>
                                <input type="number" id="jumlah" name="jumlah" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label for="deskripsi" class="form-label"><i class="fas fa-pencil-alt me-2"></i>Deskripsi:</label>
                                <textarea id="deskripsi" name="deskripsi" class="form-control" rows="1"></textarea>
                            </div>
                
                            <input type="hidden" name="id_user" value="1">
                            <button type="submit" class="btn btn-primary btn-block">
                                <i class="fas fa-save me-2"></i>Simpan Transaksi
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Card Tabel Transaksi Hari Ini -->
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="card-title">
                            <i class="fas fa-list me-2"></i> Transaksi Kas Hari Ini
                        </h5>
                        <a href="export/export-transaksi-kas.php" class="btn btn-light export-button">
                            <i class="fas fa-file-export me-2"></i> Ekspor Data
                        </a>
                    </div>
                    <div class="card-body table-responsive">
                        <table class="table table-bordered table-striped text-center">
                            <thead>
                                <tr>
                                    <th>Tanggal</th>
                                    <th>Jenis Transaksi</th>
                                    <th>Kategori</th>
                                    <th>Jumlah</th>
                                    <th>Deskripsi</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                // Mengambil transaksi kas hari ini dari database
                                $today = date('Y-m-d');
                                $sql = "SELECT t.id_transaksi_kas, t.tanggal, t.jenis_transaksi, k.nama_kategori, t.jumlah, t.deskripsi 
                                        FROM transaksi_kas t
                                        JOIN kategori k ON t.id_kategori = k.id_kategori
                                        WHERE t.tanggal = '$today'
                                        ORDER BY t.tanggal DESC";
                                $result = $conn->query($sql);

                                if ($result->num_rows > 0) {
                                    while ($row = $result->fetch_assoc()) {
                                        echo "<tr>";
                                            echo "<td>{$row['tanggal']}</td>";
                                            echo "<td>{$row['jenis_transaksi']}</td>";
                                            echo "<td>{$row['nama_kategori']}</td>";
                                            echo "<td>Rp" . number_format($row['jumlah'], 2, ',', '.') . "</td>";
                                            echo "<td>{$row['deskripsi']}</td>";
                                            echo "<td>";
                                                echo "<div class='d-flex justify-content-center gap-2'>";
                                                echo "<a href='process/edit-transaksi-kas.php?id={$row['id_transaksi_kas']}' class='btn btn-warning btn-sm w-30'><i class='fas fa-edit'></i> Edit</a>";
                                                echo "<a href='process/hapus-transaksi-kas.php?id={$row['id_transaksi_kas']}' class='btn btn-danger btn-sm w-30' onclick=\"return confirm('Apakah Anda yakin ingin menghapus transaksi ini?');\"><i class='fas fa-trash'></i> Hapus</a>";
                                                echo "</div>";
                                            echo "</td>";
                                        echo "</tr>";
                                    }
                                } else {
                                    echo "<tr><td colspan='6' class='text-center'>Tidak ada transaksi untuk hari ini.</td></tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS and dependencies -->
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // JavaScript untuk mengatur nilai default input tanggal ke tanggal hari ini
        document.addEventListener('DOMContentLoaded', (event) => {
            const today = new Date().toISOString().split('T')[0];
            document.getElementById('tanggal').value = today;
        });
    </script>
</body>
</html>
