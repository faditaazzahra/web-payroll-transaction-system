<?php
include '../config/koneksi.php';

// Query untuk mengambil data dari tabel
$sql = "
    SELECT k.plat_nomor, ph.tanggal_transaksi, ph.waktu_transaksi, 
        k.merk, k.warna, k.no_hp, k.jenis_penggunaan, j.nama_layanan, 
        ph.total_pendapatan, ph.untuk_karyawan, ph.untuk_perusahaan
    FROM pendapatan_harian ph
    JOIN kendaraan k ON ph.id_kendaraan = k.id_kendaraan
    JOIN jenis_layanan j ON ph.id_layanan = j.id_layanan
    WHERE ph.tanggal_transaksi = CURDATE()  -- Mengambil hanya transaksi hari ini
    ORDER BY ph.waktu_transaksi DESC";

$result = $conn->query($sql);

// Tanggal otomatis
$tanggal_hari_ini = date('l, d F Y'); // Format: Hari, Tanggal Bulan Tahun
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ekspor Data Transaksi</title>
    <!-- CDN CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.3.6/css/buttons.dataTables.min.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            padding: 20px;
        }

        .container {
            max-width: 1200px;
            margin: auto;
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
            padding: 20px;
            overflow-x: auto;
        }

        h1, h5 {
            color: #4e73df;
            font-weight: 600;
        }

        table {
            width: 100%;
            max-width: 1200px;
            border-collapse: collapse;
            margin: auto;
        }

        table th, table td {
            text-align: center;
            padding: 10px;
            border: 1px solid #ddd;
            vertical-align: middle;
            white-space: nowrap; /* Hindari teks meluber */
        }

        table th {
            background: linear-gradient(45deg, #4e73df, #224abe);
            color: white;
        }

        table tbody tr:hover {
            background-color: #f8f9fa;
            transform: scale(1.01);
        }

        .dataTables_wrapper .dataTables_paginate {
            margin-top: 15px;
        }

        .dataTables_wrapper .dataTables_info {
            margin-top: 10px;
        }

        .dt-buttons .btn {
            background: linear-gradient(45deg, #4e73df, #224abe);
            color: white !important;
            border: none;
            border-radius: 5px;
            padding: 8px 12px;
            font-size: 0.9rem;
            margin: 5px;
            transition: all 0.3s ease;
        }

        .dt-buttons .btn:hover {
            background: #2e59d9 !important;
            transform: translateY(-2px);
            box-shadow: 0 5px 10px rgba(0, 0, 0, 0.15);
        }

        @media (max-width: 768px) {
            table {
                font-size: 0.8rem;
            }

            h1 {
                font-size: 1.5rem;
            }
        }
    </style>
    <!-- CDN JavaScript -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.3.6/js/dataTables.buttons.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.3.6/js/buttons.html5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.3.6/js/buttons.print.min.js"></script>
</head>
<body>
    <div class="container">
        <div class="text-center mt-2 mb-5">
            <h1>Data Transaksi Harian</h1>
            <h5>Satria Bima Wash per Tanggal: <?= $tanggal_hari_ini ?></h5>
        </div>

        <table id="dataTable" class="display nowrap" style="width:100%">
            <thead>
                <tr>
                    <th>No</th>
                    <th>Plat Nomor</th>
                    <th>Tanggal Transaksi</th>
                    <th>Waktu Transaksi</th>
                    <th>Merk</th>
                    <th>Warna</th>
                    <th>Nomor HP</th>
                    <th>Jenis Penggunaan</th>
                    <th>Nama Layanan</th>
                    <th>Total Pendapatan</th>
                    <th>Untuk Karyawan</th>
                    <th>Untuk Perusahaan</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result->num_rows > 0): ?>
                    <?php $no = 1; ?>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?= $no++ ?></td>
                            <td><?= $row['plat_nomor'] ?></td>
                            <td><?= $row['tanggal_transaksi'] ?></td>
                            <td><?= $row['waktu_transaksi'] ?></td>
                            <td><?= $row['merk'] ?></td>
                            <td><?= $row['warna'] ?></td>
                            <td><?= $row['no_hp'] ?></td>
                            <td><?= $row['jenis_penggunaan'] ?></td>
                            <td><?= $row['nama_layanan'] ?></td>
                            <td>Rp<?= number_format($row['total_pendapatan'], 0, ',', '.') ?></td>
                            <td>Rp<?= number_format($row['untuk_karyawan'], 0, ',', '.') ?></td>
                            <td>Rp<?= number_format($row['untuk_perusahaan'], 0, ',', '.') ?></td>
                        </tr>
                    <?php endwhile; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <script>
        $(document).ready(function() {
            $('#dataTable').DataTable({
                dom: 'Bfrtip',
                buttons: [
                    {
                        extend: 'copy',
                        text: '<i class="fa fa-copy"></i> Copy',
                        titleAttr: 'Salin Data'
                    },
                    {
                        extend: 'csv',
                        text: '<i class="fa fa-file-csv"></i> CSV',
                        titleAttr: 'Unduh CSV'
                    },
                    {
                        extend: 'excel',
                        text: '<i class="fa fa-file-excel"></i> Excel',
                        titleAttr: 'Unduh Excel'
                    },
                    {
                        extend: 'pdf',
                        text: '<i class="fa fa-file-pdf"></i> PDF',
                        titleAttr: 'Unduh PDF'
                    },
                    {
                        extend: 'print',
                        text: '<i class="fa fa-print"></i> Print',
                        titleAttr: 'Cetak Data'
                    }
                ]
            });
        });
    </script>
</body>
</html>
