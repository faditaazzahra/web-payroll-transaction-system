<?php
session_start();

// Pastikan admin sudah login
if (!isset($_SESSION['id_user'])) {
    header('Location: index.php');
    exit();
}

include 'config/koneksi.php';

// Proses jika form ditambahkan atau diupdate
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add'])) {
        // Menambahkan jenis layanan baru
        $nama_layanan = $_POST['nama_layanan'];
        $harga = $_POST['harga'];

        $sql = "INSERT INTO jenis_layanan (nama_layanan, harga) VALUES (?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('sd', $nama_layanan, $harga);
        $stmt->execute();
        $stmt->close();
    } elseif (isset($_POST['edit'])) {
        // Mengedit jenis layanan
        $id_layanan = $_POST['id_layanan'];
        $nama_layanan = $_POST['nama_layanan'];
        $harga = $_POST['harga'];

        $sql = "UPDATE jenis_layanan SET nama_layanan = ?, harga = ? WHERE id_layanan = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('sdi', $nama_layanan, $harga, $id_layanan);
        $stmt->execute();
        $stmt->close();
    } elseif (isset($_POST['delete'])) {
        // Menghapus jenis layanan
        $id_layanan = $_POST['id_layanan'];

        $sql = "DELETE FROM jenis_layanan WHERE id_layanan = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $id_layanan);
        $stmt->execute();
        $stmt->close();
    }
}

// Pencarian
$search_query = '';
if (isset($_POST['search'])) {
    $search_query = $_POST['search_query'];
}

// Ambil data jenis layanan dari database
$query = "SELECT * FROM jenis_layanan WHERE nama_layanan LIKE ? ORDER BY id_layanan";
$stmt = $conn->prepare($query);
$search_param = "%" . $search_query . "%";
$stmt->bind_param('s', $search_param);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Jenis Layanan - Satria Bima Wash (SBW)</title>
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
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
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
        }

        .table tr {
            transition: all 0.2s ease;
        }

        .table tr:hover {
            background-color: #f8f9fa;
            transform: scale(1.01);
        }

        .action-buttons button {
            padding: 5px 10px;
            border-radius: 20px;
            transition: all 0.3s ease;
        }

        .btn-edit {
            background: linear-gradient(45deg, #4e73df, #224abe);
            color: white;
        }

        .btn-delete {
            background: linear-gradient(45deg, #e74a3b, #be2617);
            color: white;
        }

        .modal-content {
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }

        .modal-header {
            background: linear-gradient(45deg, #4e73df, #224abe);
            color: white;
            border-radius: 15px 15px 0 0;
        }

        .form-control {
            border-radius: 20px;
            padding: 10px 15px;
        }

        .form-control:focus {
            box-shadow: 0 0 0 0.2rem rgba(78,115,223,0.25);
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .fade-in {
            animation: fadeIn 0.5s ease-in;
        }

        .tooltip-action:before {
            content: attr(data-tooltip);
            position: absolute;
            bottom: 80%;
            left: 90%;
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
            bottom: 100%;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card fade-in">
            <div class="card-header">
                <h5 class="card-title mb-0"><i class="fas fa-cogs me-2"></i>Data Layanan</h5>
            </div>

            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <a href="dashboard.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left me-2"></i> Kembali ke Dashboard
                    </a>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addModal">
                        <i class="fas fa-plus me-2"></i> Tambah Jenis Layanan
                    </button>
                </div>
                
                <!-- Form Pencarian -->
                <form method="POST" class="mb-4">
                    <div class="input-group">
                        <input type="text" name="search_query" class="form-control" placeholder="Cari Layanan" value="<?= htmlspecialchars($search_query); ?>">
                        <button class="btn btn-outline-primary" name="search" type="submit">
                            <i class="fas fa-search me-2"></i> Cari
                        </button>
                    </div>
                </form>
                
                <!-- Tabel untuk menampilkan jenis layanan -->
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="text-center">
                            <tr>
                                <th>ID</th>
                                <th>Nama Layanan</th>
                                <th>Harga</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td class="text-center"><?= $row['id_layanan']; ?></td>
                                    <td><?= $row['nama_layanan']; ?></td>
                                    <td>Rp <?= number_format($row['harga'], 0, ',', '.'); ?>,00</td>
                                    <td class="text-center">
                                        <div class="action-buttons">
                                            <button class="btn btn-edit tooltip-action" data-tooltip="Edit Layanan" data-id="<?= $row['id_layanan']; ?>" data-nama="<?= htmlspecialchars($row['nama_layanan']); ?>" data-harga="<?= $row['harga']; ?>" data-bs-toggle="modal" data-bs-target="#editModal">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <form action="" method="POST" class="d-inline">
                                                <input type="hidden" name="id_layanan" value="<?= $row['id_layanan']; ?>">
                                                <button type="submit" name="delete" class="btn btn-delete tooltip-action" data-tooltip="Hapus Layanan" onclick="return confirm('Yakin ingin menghapus?')">
                                                    <i class="fas fa-trash-alt"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Tambah Jenis Layanan -->
    <div class="modal fade" id="addModal" tabindex="-1" aria-labelledby="addModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addModalLabel"><i class="fas fa-plus me-2"></i>Tambah Jenis Layanan</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form action="" method="POST">
                        <div class="mb-3">
                            <label for="nama_layanan" class="form-label"><i class="fas fa-tag me-2"></i>Nama Layanan</label>
                            <input type="text" class="form-control" id="nama_layanan" name="nama_layanan" required>
                        </div>
                        <div class="mb-3">
                            <label for="harga" class="form-label"><i class="fas fa-money-bill-wave me-2"></i>Harga</label>
                            <input type="number" class="form-control" id="harga" name="harga" required>
                        </div>
                        <button type="submit" name="add" class="btn btn-primary"><i class="fas fa-save me-2"></i>Simpan</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Edit Jenis Layanan -->
    <div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editModalLabel"><i class="fas fa-edit me-2"></i>Edit Jenis Layanan</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form action="" method="POST">
                        <input type="hidden" id="edit_id_layanan" name="id_layanan">
                        <div class="mb-3">
                            <label for="edit_nama_layanan" class="form-label"><i class="fas fa-tag me-2"></i>Nama Layanan</label>
                            <input type="text" class="form-control" id="edit_nama_layanan" name="nama_layanan" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_harga" class="form-label"><i class="fas fa-money-bill-wave me-2"></i>Harga</label>
                            <input type="number" class="form-control" id="edit_harga" name="harga" required>
                        </div>
                        <button type="submit" name="edit" class="btn btn-primary"><i class="fas fa-save me-2"></i>Simpan Perubahan</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
    $(document).on('click', '.btn-edit', function() {
        var id = $(this).data('id');
        var nama = $(this).data('nama');
        var harga = $(this).data('harga');

        $('#edit_id_layanan').val(id);
        $('#edit_nama_layanan').val(nama);
        $('#edit_harga').val(harga);
    });
    </script>
</body>
</html>