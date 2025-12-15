<?php
// Koneksi ke database
include 'config/koneksi.php';

// Periode laporan
$tanggal_awal = $_GET['tanggal_awal'] ?? date('Y-m-01'); // Default awal bulan
$tanggal_akhir = $_GET['tanggal_akhir'] ?? date('Y-m-d'); // Default hari ini

// Calculate saldo awal (opening balance)
$sql_saldo_awal = "
    SELECT COALESCE(
        (SELECT SUM(CASE 
            WHEN jenis_transaksi = 'Pemasukan' THEN jumlah 
            WHEN jenis_transaksi = 'Pengeluaran' THEN -jumlah 
        END)
        FROM transaksi_kas 
        WHERE tanggal < '$tanggal_awal'), 0
    ) as saldo_awal";
$result_saldo_awal = $conn->query($sql_saldo_awal);
$saldo_awal = $result_saldo_awal->fetch_assoc()['saldo_awal'];

// Calculate total for current period
$sql_total_periode = "
    SELECT 
        COALESCE(SUM(CASE WHEN jenis_transaksi = 'Pemasukan' THEN jumlah ELSE 0 END), 0) as total_pemasukan,
        COALESCE(SUM(CASE WHEN jenis_transaksi = 'Pengeluaran' THEN jumlah ELSE 0 END), 0) as total_pengeluaran
    FROM transaksi_kas 
    WHERE tanggal BETWEEN '$tanggal_awal' AND '$tanggal_akhir'";
$result_total_periode = $conn->query($sql_total_periode);
$total_periode = $result_total_periode->fetch_assoc();

// Calculate saldo akhir (closing balance)
$saldo_akhir = $saldo_awal + $total_periode['total_pemasukan'] - $total_periode['total_pengeluaran'];

// Query untuk total pemasukan dan pengeluaran per kategori
$sql_total_per_kategori = "
    SELECT 
        k.nama_kategori, 
        k.id_kategori,
        SUM(CASE WHEN t.jenis_transaksi = 'Pemasukan' THEN t.jumlah ELSE 0 END) AS total_pemasukan,
        SUM(CASE WHEN t.jenis_transaksi = 'Pengeluaran' THEN t.jumlah ELSE 0 END) AS total_pengeluaran
    FROM kategori k
    LEFT JOIN transaksi_kas t ON k.id_kategori = t.id_kategori
    WHERE t.tanggal BETWEEN '$tanggal_awal' AND '$tanggal_akhir'
    GROUP BY k.id_kategori, k.nama_kategori";
$result_total_per_kategori = $conn->query($sql_total_per_kategori);

// Jika admin memilih kategori tertentu, ambil rincian transaksi
if (isset($_GET['id_kategori'])) {
    $id_kategori = $_GET['id_kategori'];

    // Query untuk rincian transaksi berdasarkan kategori
    $sql_rincian = "
        SELECT t.tanggal, t.deskripsi, t.jumlah, t.jenis_transaksi 
        FROM transaksi_kas t
        WHERE t.id_kategori = '$id_kategori' 
        AND t.tanggal BETWEEN '$tanggal_awal' AND '$tanggal_akhir'
        ORDER BY t.tanggal ASC";
    $result_rincian = $conn->query($sql_rincian);

    // Ambil nama kategori untuk judul
    $sql_kategori = "SELECT nama_kategori FROM kategori WHERE id_kategori = '$id_kategori'";
    $nama_kategori = $conn->query($sql_kategori)->fetch_assoc()['nama_kategori'];
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Arus Kas</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/bootstrap/css/bootstrap.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #2563eb;
            --secondary-color: #1e40af;
            --success-color: #16a34a;
            --danger-color: #dc2626;
            --background-color: #f1f5f9;
            --card-background: #ffffff;
            --border-radius: 12px;
            --shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }

        body {
            font-family: 'Poppins', sans-serif;
            margin: 0;
            padding: 20px;
            background-color: var(--background-color);
            color: #1f2937;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        .header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 30px;
            border-radius: var(--border-radius);
            text-align: center;
            margin-bottom: 30px;
            box-shadow: var(--shadow);
        }

        .header h1 {
            margin: 0;
            font-size: 2.5em;
            font-weight: 700;
        }

        .header h3 {
            margin: 10px 0 0;
            font-weight: 400;
            opacity: 0.9;
        }

        .summary-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .card {
            background: var(--card-background);
            padding: 20px;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
        }

        .card-header {
            font-size: 1.1em;
            color: #4b5563;
            margin-bottom: 10px;
        }

        .card-value {
            font-size: 1.8em;
            font-weight: 600;
            margin: 10px 0;
        }

        .saldo-awal .card-value { color: var(--primary-color); }
        .pemasukan .card-value { color: var(--success-color); }
        .pengeluaran .card-value { color: var(--danger-color); }
        .saldo-akhir .card-value { color: var(--secondary-color); }

        .table-container {
            background: var(--card-background);
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            margin-top: 30px;
            overflow: hidden;
        }

        .table-header {
            background: var(--primary-color);
            color: white;
            padding: 20px;
        }

        .table-header h4 {
            margin: 0;
            font-size: 1.2em;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
        }

        th {
            background: #f8fafc;
            color: #1f2937;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.85em;
            letter-spacing: 0.5px;
        }

        th, td {
            padding: 15px 20px;
            text-align: left;
            border-bottom: 1px solid #e5e7eb;
        }

        tr:hover {
            background-color: #f8fafc;
        }

        .right {
            text-align: right;
        }

        .link {
            display: inline-block;
            padding: 8px 16px;
            background-color: var(--primary-color);
            color: white;
            text-decoration: none;
            border-radius: 6px;
            font-size: 0.9em;
            transition: background-color 0.3s;
        }

        .link:hover {
            background-color: var(--secondary-color);
        }

        .back-link {
            display: inline-block;
            margin-top: 20px;
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 500;
        }

        .back-link:hover {
            text-decoration: underline;
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .summary-cards {
                grid-template-columns: 1fr;
            }

            .container {
                padding: 10px;
            }

            th, td {
                padding: 10px;
            }
        }

        /* Add icons to cards */
        .card-icon {
            float: right;
            font-size: 2em;
            opacity: 0.2;
        }

        .filter-container {
            margin-bottom: 20px;
            padding: 20px;
            background: var(--card-background);
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
        }

        .filter-container form {
            display: flex;
            gap: 10px;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
        }

        .filter-container input[type="date"] {
            padding: 10px;
            font-size: 1rem;
            border: 1px solid #e5e7eb;
            border-radius: var(--border-radius);
        }

        .filter-container button {
            padding: 10px 20px;
            background: var(--primary-color);
            color: white;
            border: none;
            border-radius: var(--border-radius);
            cursor: pointer;
            font-size: 1rem;
        }

        .filter-container button:hover {
            background: var(--secondary-color);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
                <h1>LAPORAN ARUS KAS</h1>
                <h3>Periode: <?= date('d F Y', strtotime($tanggal_awal)) ?> s.d <?= date('d F Y', strtotime($tanggal_akhir)) ?></h3>
            </div>

            <!-- Filter Tanggal -->
            <div class="filter-container">
                <form method="GET" action="">
                    <div>
                        <label for="tanggal_awal">Tanggal Awal:</label>
                        <input type="date" id="tanggal_awal" name="tanggal_awal" value="<?= $tanggal_awal ?>">
                    </div>
                    <div>
                        <label for="tanggal_akhir">Tanggal Akhir:</label>
                        <input type="date" id="tanggal_akhir" name="tanggal_akhir" value="<?= $tanggal_akhir ?>">
                    </div>
                    <button type="submit">
                        <i class="fas fa-filter"></i> Terapkan Filter
                    </button>
                </form>
            </div>

            <div class="summary-cards">
                <div class="card saldo-awal">
                    <i class="fas fa-wallet card-icon"></i>
                    <div class="card-header">Saldo Awal</div>
                    <div class="card-value">Rp <?= number_format($saldo_awal, 2, ',', '.') ?></div>
                </div>
                <div class="card pemasukan">
                    <i class="fas fa-arrow-down card-icon"></i>
                    <div class="card-header">Total Pemasukan</div>
                    <div class="card-value">Rp <?= number_format($total_periode['total_pemasukan'], 2, ',', '.') ?></div>
                </div>
                <div class="card pengeluaran">
                    <i class="fas fa-arrow-up card-icon"></i>
                    <div class="card-header">Total Pengeluaran</div>
                    <div class="card-value">Rp <?= number_format($total_periode['total_pengeluaran'], 2, ',', '.') ?></div>
                </div>
                <div class="card saldo-akhir">
                    <i class="fas fa-balance-scale card-icon"></i>
                    <div class="card-header">Saldo Akhir</div>
                    <div class="card-value">Rp <?= number_format($saldo_akhir, 2, ',', '.') ?></div>
                </div>
            </div>

            <?php if (!isset($_GET['id_kategori'])): ?>
                <div class="table-container">
                    <div class="table-header">
                        <h4>Total Pemasukan dan Pengeluaran per Kategori</h4>
                    </div>
                    <div style="padding: 20px; text-align: right;">
                        <a href="export/export-laporan-arus-kas.php?format=pdf&tanggal_awal=<?= $tanggal_awal ?>&tanggal_akhir=<?= $tanggal_akhir ?>" class="link">
                            <i class="fas fa-file me-2"></i> Ekspor Data
                        </a>
                    </div>
                    <table>
                        <thead>
                            <tr>
                                <th>Kategori</th>
                                <th class="right">Total Pemasukan (Rp)</th>
                                <th class="right">Total Pengeluaran (Rp)</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $result_total_per_kategori->fetch_assoc()): ?>
                                <tr>
                                    <td><?= $row['nama_kategori'] ?></td>
                                    <td class="right"><?= number_format($row['total_pemasukan'], 2, ',', '.') ?></td>
                                    <td class="right"><?= number_format($row['total_pengeluaran'], 2, ',', '.') ?></td>
                                    <td>
                                        <a class="link" href="?id_kategori=<?= $row['id_kategori'] ?>&tanggal_awal=<?= $tanggal_awal ?>&tanggal_akhir=<?= $tanggal_akhir ?>">
                                            <i class="fas fa-eye"></i> Lihat Rincian
                                        </a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="table-container">
                    <div class="table-header">
                        <h4>Rincian Transaksi: <?= $nama_kategori ?></h4>
                    </div>
                    <table>
                        <thead>
                            <tr>
                                <th>Tanggal</th>
                                <th>Jenis Transaksi</th>
                                <th>Deskripsi</th>
                                <th class="right">Jumlah (Rp)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $result_rincian->fetch_assoc()): ?>
                                <tr>
                                    <td><?= date('d/m/Y', strtotime($row['tanggal'])) ?></td>
                                    <td>
                                        <?php if ($row['jenis_transaksi'] == 'Pemasukan'): ?>
                                            <span style="color: var(--success-color);">
                                                <i class="fas fa-arrow-down"></i> <?= $row['jenis_transaksi'] ?>
                                            </span>
                                        <?php else: ?>
                                            <span style="color: var(--danger-color);">
                                                <i class="fas fa-arrow-up"></i> <?= $row['jenis_transaksi'] ?>
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= $row['deskripsi'] ?></td>
                                    <td class="right"><?= number_format($row['jumlah'], 2, ',', '.') ?></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                    <div style="padding: 20px;">
                        <a href="?tanggal_awal=<?= $tanggal_awal ?>&tanggal_akhir=<?= $tanggal_akhir ?>" class="back-link">
                            <i class="fas fa-arrow-left"></i> Kembali ke Total Per Kategori
                        </a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
</body>
</html>