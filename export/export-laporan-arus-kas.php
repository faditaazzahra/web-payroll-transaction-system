<?php
// Koneksi ke database
include '../config/koneksi.php';

// Periode laporan (contoh default)
$tanggal_awal = $_GET['tanggal_awal'] ?? date('Y-m-01');
$tanggal_akhir = $_GET['tanggal_akhir'] ?? date('Y-m-d');

// Query untuk saldo awal
$sql_saldo_awal = "
    SELECT 
        (SELECT COALESCE(SUM(jumlah), 0) FROM transaksi_kas WHERE jenis_transaksi = 'Pemasukan' AND tanggal < '$tanggal_awal') -
        (SELECT COALESCE(SUM(jumlah), 0) FROM transaksi_kas WHERE jenis_transaksi = 'Pengeluaran' AND tanggal < '$tanggal_awal') AS saldo_awal";
$saldo_awal = $conn->query($sql_saldo_awal)->fetch_assoc()['saldo_awal'];

// Query untuk transaksi kas selama periode
$sql = "
    SELECT t.tanggal, t.deskripsi, t.jumlah, k.nama_kategori, r.nama_rekening
    FROM transaksi_kas t
    LEFT JOIN kategori k ON t.id_kategori = k.id_kategori
    LEFT JOIN transaksi_rekening tr ON t.id_transaksi_kas = tr.id_transaksi_kas
    LEFT JOIN rekening r ON tr.id_rekening = r.id_rekening
    WHERE t.tanggal BETWEEN '$tanggal_awal' AND '$tanggal_akhir'
    ORDER BY t.tanggal ASC";
$result = $conn->query($sql);

// Total pemasukan dan pengeluaran
$sql_total_masuk = "
    SELECT COALESCE(SUM(jumlah), 0) AS total_masuk 
    FROM transaksi_kas 
    WHERE jenis_transaksi = 'Pemasukan' AND tanggal BETWEEN '$tanggal_awal' AND '$tanggal_akhir'";
$total_masuk = $conn->query($sql_total_masuk)->fetch_assoc()['total_masuk'];

$sql_total_keluar = "
    SELECT COALESCE(SUM(jumlah), 0) AS total_keluar 
    FROM transaksi_kas 
    WHERE jenis_transaksi = 'Pengeluaran' AND tanggal BETWEEN '$tanggal_awal' AND '$tanggal_akhir'";
$total_keluar = $conn->query($sql_total_keluar)->fetch_assoc()['total_keluar'];

// Saldo akhir
$saldo_akhir = $saldo_awal + $total_masuk - $total_keluar;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Arus Kas</title>
    <!-- CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.5/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.3.6/css/buttons.dataTables.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
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

        .footer {
            margin-top: 20px;
            padding: 20px;
            background: linear-gradient(45deg, #4e73df, #224abe);
            color: white;
            border-radius: 10px;
            text-align: center;
        }

        .footer p {
            margin: 0;
            font-size: 14px;
        }

        .dataTables_wrapper .dataTables_filter {
            float: right;
            text-align: right;
            margin-bottom: 10px;
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
            margin-bottom: 15px;
            text-align: right;
        }

        .btn-export {
            padding: 8px 12px;
            font-size: 14px;
            border: none;
            background-color: #0d6efd;
            color: white;
            border-radius: 4px;
            margin-left: 5px;
        }

        .btn-export:hover {
            background-color: #0056b3;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Laporan Arus Kas</h1>
        <h3>Satria Bima Wash</h3>
        <p>Periode: <?= date('d F Y', strtotime($tanggal_awal)) ?> s.d <?= date('d F Y', strtotime($tanggal_akhir)) ?></p>
    </div>
    <div class="content">
        <h2>Transaksi Kas</h2>
        <table id="dataTable" class="display nowrap" style="width:100%">
            <thead>
                <tr>
                    <th>Tanggal</th>
                    <th>Kategori</th>
                    <th>Deskripsi</th>
                    <th>Rekening</th>
                    <th>Jumlah (Rp)</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result->num_rows > 0): ?>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?= date('d/m/Y', strtotime($row['tanggal'])) ?></td>
                            <td><?= htmlspecialchars($row['nama_kategori'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($row['deskripsi']) ?></td>
                            <td><?= htmlspecialchars($row['nama_rekening'] ?? 'Tunai') ?></td>
                            <td><?= number_format($row['jumlah'], 2, ',', '.') ?></td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5">Tidak ada data transaksi kas untuk periode ini.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="4" style="text-align: right;">Saldo Awal</td>
                    <td><?= number_format($saldo_awal, 2, ',', '.') ?></td>
                </tr>
                <tr>
                    <td colspan="4" style="text-align: right;">Saldo Akhir</td>
                    <td><?= number_format($saldo_akhir, 2, ',', '.') ?></td>
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
            const saldoAwal = '<?= number_format($saldo_awal, 2, ",", ".") ?>';
            const saldoAkhir = '<?= number_format($saldo_akhir, 2, ",", ".") ?>';

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
                        filename: 'Laporan_Arus_Kas',
                        customize: function(csv) {
                            csv += `\nSaldo Awal,${saldoAwal}\nSaldo Akhir,${saldoAkhir}`;
                            return csv;
                        }
                    },
                    {
                        extend: 'excel',
                        text: '<i class="fa fa-file-excel"></i> Excel',
                        titleAttr: 'Unduh Excel',
                        filename: 'Laporan_Arus_Kas',
                        customize: function(xlsx) {
                            const sheet = xlsx.xl.worksheets['sheet1.xml'];
                            const rows = `
                                <row>
                                    <c t="inlineStr"><is><t>Saldo Awal</t></is></c>
                                    <c t="inlineStr"><is><t>${saldoAwal}</t></is></c>
                                </row>
                                <row>
                                    <c t="inlineStr"><is><t>Saldo Akhir</t></is></c>
                                    <c t="inlineStr"><is><t>${saldoAkhir}</t></is></c>
                                </row>
                            `;
                            const tableEnd = sheet.indexOf('</sheetData>');
                            xlsx.xl.worksheets['sheet1.xml'] = sheet.slice(0, tableEnd) + rows + sheet.slice(tableEnd);
                        }
                    },
                    {
                        extend: 'pdf',
                        text: '<i class="fa fa-file-pdf"></i> PDF',
                        titleAttr: 'Unduh PDF',
                        filename: 'Laporan_Arus_Kas',
                        orientation: 'portrait',
                        pageSize: 'A4',
                        customize: function(doc) {
                            doc.content.push(
                                { text: 'Saldo Awal: Rp ' + saldoAwal, margin: [0, 10, 0, 0], alignment: 'right' },
                                { text: 'Saldo Akhir: Rp ' + saldoAkhir, margin: [0, 0, 0, 10], alignment: 'right' }
                            );
                        }
                    },
                    {
                        extend: 'print',
                        text: '<i class="fa fa-print"></i> Print',
                        titleAttr: 'Cetak Data',
                        customize: function(win) {
                            $(win.document.body).append(
                                '<p style="text-align:right; margin-top: 20px;">Saldo Awal: Rp ' + saldoAwal + '</p>' +
                                '<p style="text-align:right;">Saldo Akhir: Rp ' + saldoAkhir + '</p>'
                            );
                        }
                    }
                ]
            });
        });
    </script>
</body>
</html>
