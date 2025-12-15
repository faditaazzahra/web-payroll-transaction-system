<?php
session_start();

// Pastikan admin sudah login
if (!isset($_SESSION['id_user'])) {
    header('Location: index.php');
    exit();
}

include 'config/koneksi.php';

// Ambil data karyawan dari database
$result = $conn->query("SELECT id_karyawan, nama_karyawan, nomor_ponsel, alamat, tanggal_masuk FROM karyawan ORDER BY nama_karyawan ASC");

// Proses jika form dihapus
if (isset($_GET['action']) && $_GET['action'] == 'delete') {
    $id_karyawan = $_GET['id'];
    
    // Hapus karyawan dari database
    $sql_delete = "DELETE FROM karyawan WHERE id_karyawan = ?";
    $stmt_delete = $conn->prepare($sql_delete);
    $stmt_delete->bind_param('i', $id_karyawan);
    $stmt_delete->execute();
    $stmt_delete->close();

    header('Location: data-karyawan.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Karyawan - Satria Bima Wash (SBW)</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
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

        .table td {
            vertical-align: middle;
        }

        .table tr {
            transition: all 0.2s ease;
        }

        .table tr:hover {
            background-color: #f8f9fa;
            transform: scale(1.01);
        }

        /* Styling untuk tombol aksi */
        .action-buttons {
            display: flex;
            gap: 5px;
            justify-content: center;
        }

        .btn-edit {
            background: linear-gradient(45deg, #4e73df, #224abe);
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

        .btn-delete {
            background: linear-gradient(45deg, #e74a3b, #be2617);
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

        .btn-delete:hover {
            background-color: #be2617;
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

        .btn-edit:active, .btn-delete:active {
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
    </style>
</head>
<body>
    <div class="card">
        <div class="card-header">
            <h5 class="card-title"><i class="fas fa-users me-2"></i>Data Karyawan Aktif</h5>
        </div>

        <div class="card-body table-responsive">
            <div class="d-flex justify-content-between mb-3">
                <a href="dashboard.php" class="btn btn-secondary mb-3">
                    <i class="fas fa-arrow-left"></i> Kembali ke Dashboard
                </a>
                <a href="process/tambah-karyawan.php" class="btn btn-primary mb-3">
                    <i class="fas fa-plus"></i> Tambah Karyawan
                </a>
            </div>

            <!-- Tabel untuk menampilkan daftar karyawan -->
            <table class="table table-striped table-hover">
                <thead class="text-center">
                    <tr>
                        <th>ID Karyawan</th>
                        <th>Nama Karyawan</th>
                        <th>Nomor HP</th>
                        <th>Alamat Rumah</th>
                        <th>Tanggal Masuk</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody class="text-center">
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?= $row['id_karyawan']; ?></td>
                            <td><?= htmlspecialchars($row['nama_karyawan']); ?></td>
                            <td><?= htmlspecialchars($row['nomor_ponsel']); ?></td>
                            <td><?= htmlspecialchars($row['alamat']); ?></td>
                            <td><?= $row['tanggal_masuk']; ?></td>
                            <td>
                                <div class="action-buttons">
                                    <a href="process/edit-karyawan.php?id=<?= $row['id_karyawan']; ?>" class="btn btn-edit tooltip-action" data-tooltip="Edit Karyawan">
                                        <i class="fas fa-edit"></i><span class="d-none d-md-inline">Edit</span>
                                    </a>
                                    <a href="?action=delete&id=<?= $row['id_karyawan']; ?>" class="btn btn-delete tooltip-action" data-tooltip="Hapus Karyawan" onclick="return confirm('Anda yakin ingin menghapus karyawan ini?');">
                                        <i class="fas fa-trash-alt"></i><span class="d-none d-md-inline">Hapus</span>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
