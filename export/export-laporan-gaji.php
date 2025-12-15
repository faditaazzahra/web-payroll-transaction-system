<?php
// Koneksi ke database
include '../config/koneksi.php';

// Ambil filter bulan dan tahun dari query string
$filter_bulan = $_GET['bulan'] ?? date('m');
$filter_tahun = $_GET['tahun'] ?? date('Y');

// Nama bulan
$bulan = [
    '01' => 'Januari', '02' => 'Februari', '03' => 'Maret', 
    '04' => 'April', '05' => 'Mei', '06' => 'Juni', 
    '07' => 'Juli', '08' => 'Agustus', '09' => 'September', 
    '10' => 'Oktober', '11' => 'November', '12' => 'Desember'
];
$nama_bulan = $bulan[$filter_bulan];

// Query untuk laporan gaji karyawan (berdasarkan bulan dan tahun yang dipilih)
$sql_laporan = "
    SELECT 
        uh.tanggal_upah,
        k.nama_karyawan, 
        uh.total_upah
    FROM 
        upah_harian uh
    JOIN 
        karyawan k ON uh.id_karyawan = k.id_karyawan
    WHERE 
        k.status_karyawan = 'aktif'
        AND MONTH(uh.tanggal_upah) = '$filter_bulan'
        AND YEAR(uh.tanggal_upah) = '$filter_tahun'
    ORDER BY 
        uh.tanggal_upah ASC, k.nama_karyawan ASC";

$result_laporan = $conn->query($sql_laporan);

// Hitung total keseluruhan gaji
$total_gaji = 0;
$data_gaji = [];
while ($row = $result_laporan->fetch_assoc()) {
    $total_gaji += $row['total_upah'];
    $data_gaji[] = $row;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Gaji Karyawan</title>
    <!-- CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.5/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.3.6/css/buttons.dataTables.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
        }

        .header {
            text-align: center;
            background: linear-gradient(45deg, #4e73df, #224abe);
            color: #fff;
            padding: 20px;
        }

        .header h1 {
            font-size: 28px;
            margin: 0;
        }

        .header p {
            margin: 5px 0 0;
            font-size: 14px;
        }

        .content {
            margin: 20px auto;
            padding: 20px;
            background: #fff;
            border-radius: 10px;
            box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.1);
            max-width: 1200px;
        }

        .content h2 {
            font-size: 22px;
            margin-bottom: 20px;
            color: #0d6efd;
            font-weight: bold;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            padding: 12px 15px;
            text-align: left;
        }

        th {
            background-color: #f1f1f1;
            font-size: 14px;
            color: #495057;
            text-transform: uppercase;
            border-bottom: 2px solid #dee2e6;
        }

        td {
            border-bottom: 1px solid #dee2e6;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Laporan Gaji Karyawan</h1>
        <h3>Satria Bima Wash</h3>
        <p>Periode: <?= $nama_bulan . ' ' . $filter_tahun ?></p>
    </div>
    <div class="content">
        <h2>Data Gaji Karyawan</h2>
        <table id="dataTable" class="display nowrap" style="width:100%">
            <thead>
                <tr>
                    <th>No</th>
                    <th>Tanggal</th>
                    <th>Nama Karyawan</th>
                    <th>Total Gaji (Rp)</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $no = 1;
                foreach ($data_gaji as $row): ?>
                    <tr>
                        <td><?= $no++ ?></td>
                        <td><?= date('d/m/Y', strtotime($row['tanggal_upah'])) ?></td>
                        <td><?= htmlspecialchars($row['nama_karyawan']) ?></td>
                        <td><?= number_format($row['total_upah'], 2, ',', '.') ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="3" style="text-align: right; font-weight: bold;">Total Keseluruhan</td>
                    <td style="font-weight: bold;">Rp <?= number_format($total_gaji, 2, ',', '.') ?></td>
                </tr>
            </tfoot>
        </table>
    </div>

    <!-- JS -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.3.6/js/dataTables.buttons.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.3.6/js/buttons.html5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.3.6/js/buttons.print.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>

    <!-- DataTables Initialization -->
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
                        titleAttr: 'Unduh CSV',
                        filename: 'Laporan_Gaji_Karyawan'
                    },
                    {
                        extend: 'excel',
                        text: '<i class="fa fa-file-excel"></i> Excel',
                        titleAttr: 'Unduh Excel',
                        filename: 'Laporan_Gaji_Karyawan'
                    },
                    {
                        extend: 'pdf',
                        text: '<i class="fa fa-file-pdf"></i> PDF',
                        titleAttr: 'Unduh PDF',
                        filename: 'Laporan_Gaji_Karyawan',
                        orientation: 'portrait',
                        pageSize: 'A4'
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
