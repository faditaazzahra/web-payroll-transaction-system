<?php
// Koneksi ke database
include '../config/koneksi.php';

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

// Nama bulan
$bulan = [
    '01' => 'Januari', '02' => 'Februari', '03' => 'Maret', 
    '04' => 'April', '05' => 'Mei', '06' => 'Juni', 
    '07' => 'Juli', '08' => 'Agustus', '09' => 'September', 
    '10' => 'Oktober', '11' => 'November', '12' => 'Desember'
];
$nama_bulan = $bulan[$filter_bulan];

// Total upah
$total_upah = 0;
$data_upah = [];
while ($row = $result_detail->fetch_assoc()) {
    $total_upah += $row['total_upah'];
    $data_upah[] = $row;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Export Laporan Gaji Karyawan</title>
    <!-- CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.5/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.3.6/css/buttons.dataTables.min.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #f8f9fa;
            color: #495057;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
            background: linear-gradient(45deg, #4e73df, #224abe);
            color: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.2);
        }
        .header h1 {
            font-size: 28px;
            margin: 0;
        }
        .header p {
            font-size: 16px;
            margin: 5px 0 0;
        }
        .content {
            padding: 20px;
            background-color: #ffffff;
            border-radius: 10px;
            box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.1);
            max-width: 1200px;
            margin: 0 auto;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            padding: 12px;
            text-align: left;
        }
        th {
            background-color: #4e73df;
            color: white;
            text-transform: uppercase;
            font-size: 14px;
        }
        td {
            border-bottom: 1px solid #e9ecef;
        }
        tfoot td {
            font-weight: bold;
        }
        .dataTables_wrapper .dataTables_filter {
            float: right;
            text-align: right;
        }
        .dataTables_wrapper .dataTables_info {
            margin-top: 10px;
            float: left;
        }
        .dataTables_wrapper .dataTables_paginate {
            float: right;
            margin-top: 10px;
        }
        .buttons-container {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-bottom: 10px;
        }
        .btn-export {
            padding: 8px 15px;
            background-color: #4e73df;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        .btn-export:hover {
            background-color: #224abe;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Laporan Gaji Karyawan</h1>
        <p>Karyawan: <?= htmlspecialchars($karyawan['nama_karyawan']) ?></p>
        <p>Periode: <?= $nama_bulan . ' ' . $filter_tahun ?></p>
    </div>
    <div class="content">
        <table id="dataTable" class="display nowrap">
            <thead>
                <tr>
                    <th>No</th>
                    <th>Tanggal</th>
                    <th>Total Upah (Rp)</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $no = 1;
                foreach ($data_upah as $row): ?>
                    <tr>
                        <td><?= $no++ ?></td>
                        <td><?= date('d/m/Y', strtotime($row['tanggal_upah'])) ?></td>
                        <td><?= number_format($row['total_upah'], 2, ',', '.') ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="2" style="text-align: right;">Total Keseluruhan</td>
                    <td>Rp <?= number_format($total_upah, 2, ',', '.') ?></td>
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
    <script>
        $(document).ready(function() {
            const table = $('#dataTable').DataTable({
                dom: 'Bfrtip',
                buttons: [
                    'copy', 'csv', 'excel', 'pdf', 'print'
                ]
            });

            $('#export-copy').click(() => table.button('.buttons-copy').trigger());
            $('#export-csv').click(() => table.button('.buttons-csv').trigger());
            $('#export-excel').click(() => table.button('.buttons-excel').trigger());
            $('#export-pdf').click(() => table.button('.buttons-pdf').trigger());
            $('#export-print').click(() => table.button('.buttons-print').trigger());
        });
    </script>
</body>
</html>